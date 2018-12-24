<?php

require_once "admin-menu.php";
require_once "add-on-manager.php";
require_once "templates-manager.php";
require_once "metaboxes.php";

add_action('admin_init','rcl_admin_scripts', 10);
function rcl_admin_scripts(){
    wp_enqueue_style( 'animate-css', RCL_URL.'assets/css/animate-css/animate.min.css' );
    wp_enqueue_script( 'rcl-fields-manager', RCL_URL.'admin/assets/fields-manager.js' );
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
    add_meta_box( 'recall_meta', __('WP-Recall settings','wp-recall'), 'rcl_options_box', 'post', 'normal', 'high'  );
    add_meta_box( 'recall_meta', __('WP-Recall settings','wp-recall'), 'rcl_options_box', 'page', 'normal', 'high'  );
}

add_filter('rcl_post_options','rcl_gallery_options',10,2);
function rcl_gallery_options($options,$post){
    $mark_v = get_post_meta($post->ID, 'recall_slider', 1);
    $options .= '<p>'.__('Output images via WP-Recall gallery?','wp-recall').':
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

    $options = apply_filters('rcl_pre_update_options', $options);

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

add_filter('admin_footer_text', 'rcl_admin_footer_text', 10);
function rcl_admin_footer_text( $footer_text ) {
    $current_screen = get_current_screen();

    $dlm_page_ids = array(
        'toplevel_page_manage-wprecall',
        'wp-recall_page_rcl-options',
        'wp-recall_page_rcl-repository',
        'wp-recall_page_manage-addon-recall',
        'wp-recall_page_manage-templates-recall',
        'wp-recall_page_rcl-tabs-manager',
        'wp-recall_page_manage-userfield',
        'wp-recall_page_manage-public-form'
    );

    if ( isset( $current_screen->id ) && in_array( $current_screen->id, $dlm_page_ids ) ) {
        $footer_text = sprintf( __( 'If you liked plugin %sWP-Recall%s, please vote for it in repository %s★★★★★%s. Thank you so much!', 'wp-recall' ), '<strong>', '</strong>', '<a href="https://wordpress.org/support/view/plugin-reviews/wp-recall?filter=5#new-post" target="_blank">', '</a>' );
    }

    return $footer_text;
}

function rcl_send_addon_activation_notice($addon_id, $addon_headers){
    wp_remote_post( RCL_SERVICE_HOST.'/products-files/api/add-ons.php?rcl-addon-info=add-notice',
        array('body' =>  array(
                'rcl-key' => get_option('rcl-key'),
                'addon-id' => $addon_id,
                'headers' => array(
                    'version' => $addon_headers['version'],
                    'item-id' => $addon_headers['item-id'],
                    'key-id' => $addon_headers['key-id'],
                ),
                'host' => $_SERVER['SERVER_NAME']
            )
        )
    );
}

rcl_ajax_action('rcl_manager_update_fields', false);
function rcl_manager_update_fields(){

    $manager_id = $_POST['manager_id'];
    $option_name = $_POST['option_name'];

    $fieldsData = $_POST['fields'];
    $structure = isset($_POST['structure'])? $_POST['structure']: false;

    $fields = array();
    $keyFields = array();
    $changeIds = array();
    $isset_new = false;
    foreach($fieldsData as $field_id => $field){

        if(!$field['title']) continue;

        if(isset($field['values'])){
            //удаляем из массива values пустые значения
            $values = array();
            foreach($field['values'] as $k => $v){
                if($v == '') continue;
                $values[$k] = $v;
            }
            $field['values'] = $values;
        }

        if(stristr($field_id, 'newField') !== FALSE){

            $isset_new = true;

            $old_id = $field_id;

            if(!$field['id']){

                $field_id = str_replace(array('-',' '),'_',rcl_sanitize_string($field['title']).'-'.rand(1,100));

            }else{
                $field_id = $field['id'];
            }

            $changeIds[$old_id] = $field_id;

        }

        $field['slug'] = $field_id;

        $keyFields[$field_id] = 1;

        unset($field['id']);

        $fields[] = $field;

    }

    if($structure){

        $strArray = array(); $area_id = -1;

        foreach($structure as $value){

            if(is_array($value)){

                if(isset($value['group_id'])){
                    $group_id = $value['group_id'];

                    $strArray[$group_id] = isset($_POST['structure-groups'][$group_id])? $_POST['structure-groups'][$group_id]: array();

                }else if(isset($value['field_id'])){
                    $strArray[$group_id]['areas'][$area_id]['fields'][] = $value['field_id'];
                }

            }else{
                $area_id++;
                $strArray[$group_id]['areas'][$area_id]['width'] = isset($_POST['structure-areas'][$area_id]['width'])? $_POST['structure-areas'][$area_id]['width']: 0;
            }

        }

        $endStructure = array();

        foreach($strArray as $group_id => $group){

            if($group['id'] && $group_id != $group['id']){
                $group_id = $group['id'];
            }

            $endStructure[$group_id] = $group;
            $endStructure[$group_id]['areas'] = array();

            foreach($group['areas'] as $area){

                $fieldsArea = array();

                foreach($area['fields'] as $k => $field_id){

                    if(isset($changeIds[$field_id])){
                        $field_id = $changeIds[$field_id];
                    }

                    if(!isset($keyFields[$field_id])){
                        unset($area['fields'][$k]); continue;
                    }

                    $fieldsArea[] = $field_id;

                }

                $endStructure[$group_id]['areas'][] = array(
                    'width' => round($area['width'], 0),
                    'fields' => $fieldsArea
                );

            }

        }

        $structure = $endStructure;

    }

    update_option($option_name, $fields);

    if($structure)
        update_site_option( 'rcl_fields_'.$manager_id.'_structure', $structure);
    else
        delete_site_option( 'rcl_fields_'.$manager_id.'_structure');

    do_action('rcl_fields_update', $fields, $manager_id);

    $args = array(
        'success' => __('Settings saved!', 'usp')
    );

    if($isset_new){
        $args['reload'] = true;
    }

    wp_send_json($args);

}

rcl_ajax_action('rcl_manager_get_new_field', false, false);
function rcl_manager_get_new_field(){

    //$managerProps = (array)json_decode(wp_unslash($_POST['props']));
    $managerProps = $_POST['props'];

    $Manager = new Rcl_Fields_Manager($managerProps['manager_id'], $managerProps);

    $field_id = 'newField-'.rand(1,10000);

    $Manager->add_field(array(
        'slug' => $field_id,
        'type' => $Manager->types[0],
        '_new' => true
    ));

    wp_send_json(array(
        'content' => $Manager->get_field_manager($field_id)
    ));

}

rcl_ajax_action('rcl_manager_get_custom_field_options', false, false);
function rcl_manager_get_custom_field_options(){

    $new_type = $_POST['newType'];
    $old_type = $_POST['oldType'];
    $field_id = $_POST['fieldId'];

    //$managerProps = (array)json_decode(wp_unslash($_POST['manager']));
    $managerProps = $_POST['manager'];

    $Manager = new Rcl_Fields_Manager($managerProps['manager_id'],$managerProps);

    //$Manager->setup_fields();

    if(stristr($field_id, 'newField') !== FALSE){

        $Manager->add_field(array(
            'slug' => $field_id,
            'type' => $new_type,
            '_new' => true
        ));

    }else{

        $Manager->set_field_prop($field_id, 'type', $new_type);

        $Manager->fields[$field_id] = $Manager::setup((array)$Manager->fields[$field_id]);

    }

    $content = $Manager->get_field_options_content($field_id);

    $multiVars = array(
        'select',
        'radio',
        'checkbox',
        'multiselect'
    );

    if(in_array($new_type,$multiVars)){

       $content .= $Manager->sortable_dynamic_values_script($field_id);

    }

    wp_send_json(array(
        'content' => $content
    ));

}

rcl_ajax_action('rcl_manager_get_new_area', false);
function rcl_manager_get_new_area(){

    //$managerProps = (array)json_decode(wp_unslash($_POST['props']));
    $managerProps = $_POST['props'];

    $Manager = new Rcl_Fields_Manager('any', $managerProps);

    wp_send_json(array(
        'content' => $Manager->get_active_area()
    ));

}

rcl_ajax_action('rcl_manager_get_new_group', false);
function rcl_manager_get_new_group(){

    //$managerProps = (array)json_decode(wp_unslash($_POST['props']));
    $managerProps = $_POST['props'];

    $Manager = new Rcl_Fields_Manager('any', $managerProps);

    wp_send_json(array(
        'content' => $Manager->get_group_areas()
    ));

}