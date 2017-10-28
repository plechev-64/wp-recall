<?php

require_once "admin-menu.php";
require_once "add-on-manager.php";
require_once "templates-manager.php";
require_once "metaboxes.php";

add_action('admin_init','rcl_admin_scripts', 10);
function rcl_admin_scripts(){
    wp_enqueue_style( 'animate-css', RCL_URL.'assets/css/animate-css/animate.min.css' );   
}

add_filter('rcl_custom_field_options','rcl_edit_field_options',10,3);
function rcl_edit_field_options($options, $field, $type){

    $types = array( 'range', 'runner' );

    if(in_array($field['type'],$types)){

        foreach($options as $k => $option){

            if($option['slug'] == 'required'){
                unset($options[$k]);
            }

        }

    }
    
    return $options;
    
}

function rmag_global_options(){
    
    $content = ' <div id="recall" class="left-sidebar wrap">
        <form method="post" action="">
        '.wp_nonce_field('update-options-rmag','_wpnonce',true,false);

        $content = apply_filters('admin_options_rmag',$content);

        $content .= '<div class="submit-block">
                <input type="submit" class="rcl-save-button" name="primary-rmag-options" value="'.__('Save settings','wp-recall').'" />
            </div>
        </form>
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

rcl_ajax_action('rcl_update_options', false);
function rcl_update_options(){
    global $rcl_options;
    
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-options-rcl' ) ){
        wp_send_json(array(
            'error' => __('Error','wp-recall')
        ));
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

    wp_send_json(array(
        'success' => __('Settings saved!','wp-recall')
    ));

}

function wp_enqueue_theme_rcl($url){
    wp_enqueue_style( 'theme_rcl', $url );
}

/*16.0.0*/
add_action( 'admin_init', 'rcl_update_custom_fields', 10);
function rcl_update_custom_fields(){
    global $wpdb;

    if( !isset($_POST['rcl_save_custom_fields']) ) return false;
    
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'rcl-update-custom-fields' ) ) return false;

    $fields = array();
    
    $table = 'postmeta';
    
    if($_POST['rcl-fields-options']['name-option'] == 'rcl_profile_fields')
        $table = 'usermeta';

    $POSTDATA = apply_filters('rcl_pre_update_custom_fields_options',$_POST);
    
    if(!$POSTDATA) return false;
    
    if(isset($POSTDATA['rcl_deleted_custom_fields'])){
        
        $deleted = explode(',',$POSTDATA['rcl_deleted_custom_fields']);
        
        if($deleted){
            
            foreach($deleted as $slug){
                $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->$table." WHERE meta_key = '%s'",$slug));
            }
            
        }
        
    }
    
    $newFields = array();
    
    if(isset($POSTDATA['new-field'])){
        
        $nKey = 0;

        foreach($POSTDATA['new-field'] as $optionSlug => $vals){
            $newFields[$nKey] = $vals;
            $nKey++;
        }

    }

    $fields = array();
    $nKey = 0;

    foreach($POSTDATA['fields'] as $k => $slug){
        
        if(!$slug){
            
            if(!isset($newFields[$nKey]) || !$newFields[$nKey]['title']) continue;
            
            if(isset($newFields[$nKey]['slug']) && $newFields[$nKey]['slug'])
                $slug = $newFields[$nKey]['slug'];
            else
                $slug = str_replace(array('-',' '),'_',rcl_sanitize_string($newFields[$nKey]['title']).'-'.rand(10,100));
            
            $field = $newFields[$nKey];
            
            $nKey++;
            
        }else{
            
            if(!isset($POSTDATA['field'][$slug])) continue;
        
            $field = $POSTDATA['field'][$slug];
            
        }

        $field['slug'] = $slug;
        
        $fields[] = $field;
        
    }
    
    foreach($fields as $k => $field){
        
        if(isset($field['values']) && $field['values'] && is_array($field['values'])){
            
            $values = array();
            foreach($field['values'] as $val){
                if($val == '') continue;
                $values[] = $val;
            }
            
            $fields[$k]['values'] = $values;
            
        }
        
    }
    
    if(isset($POSTDATA['options'])){
        $fields['options'] = $POSTDATA['options'];
    }

    update_option( $_POST['rcl-fields-options']['name-option'], $fields );
    
    do_action('rcl_update_custom_fields', $fields, $POSTDATA);

    wp_redirect( $_POST['_wp_http_referer'] ); exit;
    
}

rcl_ajax_action('rcl_get_new_custom_field', false);
function rcl_get_new_custom_field(){
    
    $post_type = $_POST['post_type'];
    $primary = (array)json_decode(wp_unslash($_POST['primary_options']));
    $default = (array)json_decode(wp_unslash($_POST['default_options']));
    
    $manageFields = new Rcl_Custom_Fields_Manager($post_type,$primary);
    
    if($default){
        
        $manageFields->defaultOptions = array();
        
        foreach($default as $option){
            $manageFields->defaultOptions[] = (array)$option;
        }
        
    }
    
    $content = $manageFields->empty_field();

    wp_send_json(array(
        'content' => $content
    ));
    
}

rcl_ajax_action('rcl_get_custom_field_options', false);
function rcl_get_custom_field_options(){
    
    $type_field = $_POST['type_field'];
    $old_type = $_POST['old_type'];
    $post_type = $_POST['post_type'];
    $slug_field = $_POST['slug'];   
    
    $primary = (array)json_decode(wp_unslash($_POST['primary_options']));
    $default = (array)json_decode(wp_unslash($_POST['default_options']));
    
    $manageFields = new Rcl_Custom_Fields_Manager($post_type,$primary);
    
    if($default){
        
        $manageFields->defaultOptions = array();
        
        foreach($default as $option){
            $manageFields->defaultOptions[] = (array)$option;
        }
        
    }
    
    $manageFields->field = array( 'type' => $type_field );
    
    if(strpos($slug_field,'CreateNewField') === false){

        $manageFields->field['slug'] = $slug_field;

    }else{
        
        $manageFields->field['slug'] = '';
        $manageFields->new_slug = $slug_field;
        
    }

    $content = $manageFields->get_options();
    
    $multiVars = array(
        'select',
        'radio',
        'checkbox',
        'multiselect'
    );
    
    if(in_array($type_field,$multiVars)){
        
       $content .= '<script>'
        . "jQuery('#field-".$slug_field." .rcl-field-input .dynamic-values').sortable({
             containment: 'parent',
             placeholder: 'ui-sortable-placeholder',
             distance: 15,
             stop: function( event, ui ) {
                 var items = ui.item.parents('.dynamic-values').find('.dynamic-value');
                 items.each(function(f){
                     if(items.length == (f+1)){
                         jQuery(this).children('a').attr('onclick','rcl_add_dynamic_field(this);return false;').children('i').attr('class','fa-plus');
                     }else{
                         jQuery(this).children('a').attr('onclick','rcl_remove_dynamic_field(this);return false;').children('i').attr('class','fa-minus');
                     }
                 });

             }
         });"
        . '</script>'; 
        
    }

    wp_send_json(array(
        'content' => $content
    ));
    
}