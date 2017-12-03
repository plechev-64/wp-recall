<?php

require_once 'classes/class-rcl-profile-fields.php';

if (is_admin())
    require_once 'admin/index.php';

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_profile_scripts',10);
endif;

function rcl_profile_scripts(){
    global $user_ID;   
    if(rcl_is_office($user_ID)){
        rcl_enqueue_style( 'rcl-profile', rcl_addon_url('style.css', __FILE__) );
        rcl_enqueue_script( 'rcl-profile-scripts', rcl_addon_url('js/scripts.js', __FILE__) );
    }
}

add_filter('rcl_init_js_variables','rcl_init_js_profile_variables',10);
function rcl_init_js_profile_variables($data){
    $data['local']['no_repeat_pass'] = __('Repeated password not correct!','wp-recall');        
    return $data;
}

add_action('init','rcl_tab_profile');
function rcl_tab_profile(){
    
    rcl_tab(
        array(
            'id'=>'profile',
            'name'=>__('Profile','wp-recall'),
            'supports'=>array('ajax'),
            'public'=>0,
            'icon'=>'fa-user',
            'content'=>array(
                array(
                    'callback' => array(
                        'name'=>'rcl_tab_profile_content'
                    )
                )
            )
        )
    );

}

add_action('rcl_bar_setup','rcl_bar_add_profile_link',10);
function rcl_bar_add_profile_link(){
    global $user_ID;
    
    if(!is_user_logged_in()) return false;
    
    rcl_bar_add_menu_item('profile-link',
        array(                
            'url'=> rcl_get_tab_permalink($user_ID,'profile'),
            'icon'=>'fa-user-secret',
            'label'=>__('Profile settings','wp-recall')
        )
    );

}

add_action('init','rcl_add_block_show_profile_fields');
function rcl_add_block_show_profile_fields(){
    rcl_block('details','rcl_show_custom_fields_profile',array('id'=>'pf-block','order'=>20,'public'=>1));
}

function rcl_show_custom_fields_profile($master_id){
    
    $get_fields = rcl_get_profile_fields();

    $show_custom_field = '';

    if($get_fields){

        $get_fields = stripslashes_deep($get_fields);

        $cf = new Rcl_Custom_Fields();

        foreach((array)$get_fields as $custom_field){
            $custom_field = apply_filters('custom_field_profile',$custom_field);
            if(!$custom_field) continue;
            $slug = isset($custom_field['name'])? $custom_field['name']: $custom_field['slug'];
            if(isset($custom_field['req'])&&$custom_field['req']==1){
                $meta = get_the_author_meta($slug,$master_id);
                $show_custom_field .= $cf->get_field_value($custom_field,$meta);
            }
        }
    }

    if(!$show_custom_field) return false;

    return '<div class="show-profile-fields">'.$show_custom_field.'</div>';
}

if(!is_admin()) add_action('wp','rcl_update_profile_notice');
function rcl_update_profile_notice(){
    if (isset($_GET['updated'])) 
        rcl_notice_text(__('Your profile has been updated','wp-recall'),'success');
}

//Обновляем профиль пользователя
add_action('wp', 'rcl_edit_profile', 10);
function rcl_edit_profile(){
    global $user_ID;
    
    if ( !isset( $_POST['submit_user_profile'] ) ) return false;
    
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-profile_' . $user_ID ) ) return false;

    rcl_update_profile_fields($user_ID);

    do_action( 'personal_options_update', $user_ID );
    
    $redirect_url = rcl_get_tab_permalink($user_ID,'profile').'&updated=true';

    wp_redirect( $redirect_url );
    
    exit;
}

add_filter('rcl_profile_fields','rcl_add_office_profile_fields',10);
function rcl_add_office_profile_fields($fields){
    global $userdata;

    $profileFields = array();
    
    if(isset($userdata) && $userdata->user_level >= rcl_get_option('consol_access_rcl',7)){
        $profileFields[] = array(
            'slug' => 'show_admin_bar_front',
            'title' => __('Admin toolbar','wp-recall'),
            'type' => 'select',
            'values' => array(
                'false' => 'Отключено',
                'true' => 'Включено'
            )
        );
    }
    
    $profileFields[] = array(
        'slug' => 'user_email',
        'title' => __('E-mail','wp-recall'),
        'type' => 'email',
        'required' => 1
    );
    
    $profileFields[] = array(
        'slug' => 'primary_pass',
        'title' => __('New password','wp-recall'),
        'type' => 'password',
        'required' => 0,
        'notice' => __('If you want to change your password - enter a new one','wp-recall')
    );
    
    $profileFields[] = array(
        'slug' => 'repeat_pass',
        'title' => __('Repeat password','wp-recall'),
        'type' => 'password',
        'required' => 0,
        'notice' => __('Repeat the new password','wp-recall')
    );

    $fields = ($fields)? array_merge($profileFields, $fields): $profileFields;
    
    return $fields;
    
}

function rcl_tab_profile_content($master_id){
    global $userdata, $user_ID;

    $profileFields = rcl_get_profile_fields(array('user_id'=>$master_id));

    $content = '<h3>'.__('User profile','wp-recall').' '.$userdata->display_name.'</h3>
    <form name="profile" id="your-profile" action="" method="post"  enctype="multipart/form-data">
    <table class="rcl-form">';

    $CF = new Rcl_Custom_Fields();

    $profileFields = stripslashes_deep($profileFields);
    
    $hiddens = array();
    foreach($profileFields as $field){

        $field = apply_filters('custom_field_profile',$field);

        $slug = isset($field['name'])? $field['name']: $field['slug'];

        if(!$field || !$slug) continue;
        
        if($field['type'] == 'hidden'){
            $hiddens[] = $field; continue;
        }

        $value = (isset($userdata->$slug))? $userdata->$slug: false;
        
        if($slug == 'email')
            $value = get_the_author_meta('email',$user_ID);

        $trAttrs = array();
        $attrs = array();
        
        $trAttrs['class'] = array(
            'field-'.$slug,
            'form-block-rcl'
        );
        
        if(isset($field['id'])){
            $trAttrs['id'] = $field['id'];
        }
        
        if(isset($field['class'])){
            $trAttrs['class'][] = $field['class'];
        }
        
        foreach($trAttrs as $k => $attr){
            if(is_array($attr)){
                $attrs[] = $k.'="'.implode(' ',$attr).'"';
            }else{
                $attrs[] = $k.'="'.$attr.'"';
            }
        }
        
        if(isset($field['attr'])){
            $attrs[] = $field['attr'];
        }
        
        if($field['slug'] != 'show_admin_bar_front' && !isset($field['value_in_key']) )
            $field['value_in_key'] = true;

        $content .= '<tr '.implode(' ',$attrs).'>';

        $star = (isset($field['required'])&&$field['required']==1)? ' <span class="required">*</span> ': '';
        
        $label = sprintf('<label>%s%s:</label>',$CF->get_title($field),$star);

        $content .= '<td><label>'.$label.'</label></td>';
        $content .= '<td>'.$CF->get_input($field, $value).'</td>';
        
        $content .= '</tr>';
    }

    $content .= '</table>';
    
    foreach($hiddens as $field){
        $content .= $CF->get_input($field, $value = (isset($userdata->$slug))? $userdata->$slug: false);
    }

    $content .= "<script>
                jQuery(function(){
                    jQuery('#your-profile').find('.required-checkbox').each(function(){
                        var name = jQuery(this).attr('name');
                        var chekval = jQuery('#your-profile input[name=\"'+name+'\"]:checked').val();
                        if(chekval) jQuery('#your-profile input[name=\"'+name+'\"]').attr('required',false);
                        else jQuery('#your-profile input[name=\"'+name+'\"]').attr('required',true);
                    });"
                . "});"
            . "</script>";

    $content = apply_filters('profile_options_rcl',$content,$userdata);

    $content .= wp_nonce_field( 'update-profile_' . $user_ID,'_wpnonce',true,false ).'
        <div style="text-align:right;">'
            . '<input type="submit" id="cpsubmit" class="recall-button" value="'.__('Update profile','wp-recall').'" onclick="return rcl_check_profile_form();" name="submit_user_profile" />
        </div>
    </form>';

    if(rcl_get_option('delete_user_account')){
        $content .= '
        <form method="post" action="" name="delete_account" onsubmit="return confirm(\''.__('Are you sure? It can’t be restaured!','wp-recall').'\');">
        '.wp_nonce_field('delete-user-'.$user_ID,'_wpnonce',true,false).'
        <input type="submit" id="delete_acc" class="recall-button"  value="'.__('Delete your profile','wp-recall').'" name="rcl_delete_user_account"/>
        </form>';
    }

    return $content;
}

//Выводим возможность синхронизации соц.аккаунтов в его личном кабинете
//при активированном плагине Ulogin
if(function_exists('ulogin_profile_personal_options')){
    function get_ulogin_profile_options($profile_block,$userdata){
        ob_start();
        ulogin_profile_personal_options($userdata);
	$profile_block .= ob_get_contents();
	ob_end_clean();
	return $profile_block;
    }
    add_filter('profile_options_rcl','get_ulogin_profile_options',10,2);
}

add_action('init', 'rcl_delete_user_account_activate');
function rcl_delete_user_account_activate ( ) {
  if ( isset( $_POST['rcl_delete_user_account'] ) ) {
    add_action( 'wp', 'rcl_delete_user_account' );
  }
}

//Удаляем аккаунт пользователя
function rcl_delete_user_account(){
    global $user_ID,$wpdb;
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'delete-user-' . $user_ID ) ) return false;

    require_once(ABSPATH.'wp-admin/includes/user.php' );

    $wpdb->query($wpdb->prepare("DELETE FROM ".RCL_PREF."user_action WHERE user ='%d'",$user_ID));
    
    $delete = wp_delete_user( $user_ID );
    
    if($delete){
        wp_die(__('We are very sorry but your account has been deleted!','wp-recall'));
        echo '<a href="/">'.__('Back to main page','wp-recall').'</a>';
    }else{
        wp_die(__('Account deletion failed! Go back and try again.','wp-recall'));
    }
}
