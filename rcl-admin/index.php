<?php

require_once("admin-menu.php");
require_once("tabs_options.php");
require_once("add-on-manager.php");
require_once("templates-manager.php");

function rmag_global_options(){
    $content = ' <div id="recall" class="left-sidebar wrap">
    <form method="post" action="">
            '.wp_nonce_field('update-options-rmag','_wpnonce',true,false);

    $content = apply_filters('admin_options_rmag',$content);

    $content .= '<div class="submit-block">
    <p><input type="submit" class="button button-primary button-large right" name="primary-rmag-options" value="'.__('Save settings','wp-recall').'" /></p>
    </form></div>
    </div>';
    echo $content;
}

function rmag_update_options ( ) {
  if ( isset( $_POST['primary-rmag-options'] ) ) {
	if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-options-rmag' ) ) return false;
	$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

    foreach($_POST['global'] as $key => $value){
        if($key=='primary-rmag-options') continue;
        $options[$key]=$value;
    }

    update_option('primary-rmag-options',$options);    
    
    if(isset($_POST['local'])){
        foreach((array)$_POST['local'] as $key => $value){
            update_option($key,$value);
        }
    }
    
    wp_redirect(admin_url('admin.php?page=manage-wpm-options'));
    exit;
  }
}
add_action('init', 'rmag_update_options');

function rcl_wp_list_current_action() {
    if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
            return false;

    if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
            return $_REQUEST['action'];

    if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
            return $_REQUEST['action2'];

    return false;
}

if (is_admin()) add_action('admin_init', 'rcl_postmeta_post');
function rcl_postmeta_post() {
    add_meta_box( 'recall_meta', __('Wp-Recall settings','wp-recall'), 'rcl_options_box', 'post', 'normal', 'high'  );
    add_meta_box( 'recall_meta', __('Wp-Recall settings','wp-recall'), 'rcl_options_box', 'page', 'normal', 'high'  );
}

add_filter('rcl_post_options','rcl_gallery_options',10,2);
function rcl_gallery_options($options,$post){
    $mark_v = get_post_meta($post->ID, 'recall_slider', 1);
    $options .= '<p>'.__('Output images via Wp-Recall gallery?','wp-recall').':
        <label><input type="radio" name="wprecall[recall_slider]" value="" '.checked( $mark_v, '',false ).' />'.__('No','wp-recall').'</label>
        <label><input type="radio" name="wprecall[recall_slider]" value="1" '.checked( $mark_v, '1',false ).' />'.__('Yes','wp-recall').'</label>
    </p>';
    return $options;
}

function rcl_options_box( $post ){
    $content = '';
	echo apply_filters('rcl_post_options',$content,$post); ?>
	<input type="hidden" name="rcl_fields_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>" />
	<?php
}

function rcl_postmeta_update( $post_id ){
    if(!isset($_POST['rcl_fields_nonce'])) return false;
    if ( !wp_verify_nonce($_POST['rcl_fields_nonce'], __FILE__) ) return false;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return false;
    if ( !current_user_can('edit_post', $post_id) ) return false;

    if( !isset($_POST['wprecall']) ) return false;

    $POST = $_POST['wprecall'];
    
    foreach($POST as $key=>$value ){
        if(!is_array($value)) $value = trim($value);
        if($value=='') delete_post_meta($post_id, $key);
        else update_post_meta($post_id, $key, $value);
    }
    return $post_id;
}

add_action('wp_ajax_rcl_update_options', 'rcl_update_options');
function rcl_update_options(){
    global $rcl_options;
    
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-options-rcl' ) ){
        $result['result'] = 0;
        $result['notice'] = __('Error','wp-recall');
        echo json_encode($result);
        exit;
    }

    $POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    
    array_walk_recursive(
    $POST, function(&$v, $k) {
      $v = trim($v);
    });

    if($POST['global']['login_form_recall']==1&&!isset($POST['global']['page_login_form_recall'])){
            $POST['global']['page_login_form_recall'] = wp_insert_post(array('post_title'=>__('Login and register','wp-recall'),'post_content'=>'[loginform]','post_status'=>'publish','post_author'=>1,'post_type'=>'page','post_name'=>'login-form'));
    }

    foreach((array)$POST['global'] as $key => $value){
        $value = apply_filters('rcl_global_option_value',$value,$key);
        $options[$key] = $value;
    }

    if(isset($rcl_options['users_page_rcl'])) 
        $options['users_page_rcl'] = $rcl_options['users_page_rcl'];

    update_option('rcl_global_options',$options);

    if(isset($POST['local'])){
        foreach((array)$POST['local'] as $key => $value){
            $value = apply_filters('rcl_local_option_value',$value,$key);
            if($value=='') delete_option($key);
            else update_option($key,$value);
        }
    }

    $rcl_options = $options;

    $result['result'] = 1;
    $result['notice'] = __('Settings saved!','wp-recall');

    echo json_encode($result);
    exit;

}

function wp_enqueue_theme_rcl($url){
    wp_enqueue_style( 'theme_rcl', $url );
}

add_action('admin_notices', 'my_plugin_admin_notices');
function my_plugin_admin_notices() {
    
    if(isset($_GET['page'])&&(
            $_GET['page']=='manage-wprecall'||
            $_GET['page']=='rcl-repository'||
            $_GET['page']=='manage-doc-recall'||
            $_GET['page']=='manage-addon-recall'
    ))
        
    echo "<div class='updated is-dismissible notice'><p>Понравился плагин WP-Recall? Поддержите развитие плагина, оставив положительный отзыв на его странице в <a target='_blank' href='https://wordpress.org/plugins/wp-recall/'>репозитории</a>!</p></div>";
}

/*16.0.0*/
add_action( 'admin_init', 'rcl_update_custom_fields', 10);
function rcl_update_custom_fields(){
    global $wpdb;

    if( !isset($_POST['rcl_save_custom_fields']) ) return false;
    
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'rcl-update-custom-fields' ) ) return false;

    $fields = array();
    
    $table = 'postmeta';
    
    $fs = 0;
    $placeholder_id = 0;
    $tps = array('select'=>1,'multiselect'=>1,'radio'=>1,'checkbox'=>1,'agree'=>1,'file'=>1);
    
    if($_POST['rcl-fields-options']['name-option'] == 'rcl_profile_fields')
        $table = 'usermeta';

    $POST = apply_filters('rcl_pre_update_custom_fields_options',$_POST);

    if(isset($POST['options'])){
        foreach($POST['options'] as $key=>$val){
            $fields['options'][$key] = $val;
        }
    }

    foreach($POST['field'] as $key=>$data){

        if($key=='placeholder'||$key=='field_select'||$key=='sizefile') continue;

        foreach($data as $a=>$value){

            if(!$POST['field']['title'][$a]) break;

            if($table&&!$POST['field']['title'][$a]){
                
                if($POST['field']['slug'][$a]){
                    
                    $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->$table." WHERE meta_key = '%s'",$POST['field']['slug'][$a]));

                }
                
                continue;
            }

            if($key=='slug'){

                if(!$value)
                    $value = str_replace('-','_',rcl_sanitize_string($POST['field']['title'][$a]).'-'.rand(10,100));

                $value = str_replace(' ','_',$value);

            }

            if($key=='type'){

                if($POST['field']['type'][$a]=='file'){
                    $fields[$a]['sizefile'] = $POST['field']['sizefile'][$POST['field']['slug'][$a]];
                }
                if($POST['field']['type'][$a]=='agree'){
                    $fields[$a]['url-agreement'] = $POST['field']['url-agreement'][$POST['field']['slug'][$a]];
                }

                if(isset($tps[$POST['field']['type'][$a]])){
                    $fields[$a]['field_select'] = $POST['field']['field_select'][$fs++];
                }else{
                    if($POST['rcl-fields-options']['placeholder'])
                        $fields[$a]['placeholder'] = $_POST['field']['placeholder'][$placeholder_id++];
                }

            }
            
            $fields[$a][$key] = $value;
            
        }
    }

    if($table && $_POST['deleted']){
        
        $dels = explode(',',$_POST['deleted']);
        
        foreach($dels as $del){
            
            $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->$table." WHERE meta_key = '%s'",$slug));
            
        }
    }

    update_option( $_POST['rcl-fields-options']['name-option'], $fields );
    
    do_action('rcl_update_custom_fields',$fields,$POST);

    wp_redirect( $_POST['_wp_http_referer'] ); exit;
    
}

