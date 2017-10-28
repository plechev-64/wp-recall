<?php

require_once 'addon-settings.php';

add_action('admin_init','rcl_public_admin_scripts');
function rcl_public_admin_scripts(){
    wp_enqueue_style( 'rcl_public_admin_style', rcl_addon_url('admin/assets/style.css', __FILE__) );
}

add_action('admin_menu', 'rcl_admin_page_publicform',30);
function rcl_admin_page_publicform(){
    add_submenu_page( 'manage-wprecall', __('Form of publication','wp-recall'), __('Form of publication','wp-recall'), 'manage_options', 'manage-public-form', 'rcl_public_form_manager');
}

function rcl_public_form_manager(){
    global $wpdb;
    
    $post_type = (isset($_GET['post-type']))? $_GET['post-type']: 'post';
    $form_id = (isset($_GET['form-id']))? $_GET['form-id']: 1;
    
    $shortCode = 'public-form post_type="'.$post_type.'"';
    
    if($post_type == 'post' && $form_id > 1){
        $shortCode .= ' form_id="'.$form_id.'"';
    }

    rcl_sortable_scripts();

    $formManager = new Rcl_Public_Form_Manager(array(
        'post_type' => $post_type, 
        'form_id' => $form_id
    ));
    
    $content = '<h2>'.__('Manage publication forms','wp-recall').'</h2>';
    
    $content .= '<p>'.__('On this page you can manage the creation of '
            . 'publications for registered record types. Create custom '
            . 'fields for the form of publication of various types and manage').'</p>';
    
    $content .= $formManager->form_navi();
    
    $content .= '<div class="rcl-custom-fields-navi">';
    $content .= '<p>'.__('Use shortcode for publication form','wp-recall').' ['.$shortCode.']</p>';
    $content .= '</div>';
    
    $content .= $formManager->active_fields_box();

    $content .= $formManager->inactive_fields_box();

    echo $content;
}

add_action('dbx_post_advanced', 'custom_fields_editor_post_rcl', 1);
function custom_fields_editor_post_rcl() {
    global $post;   
    add_meta_box( 'custom_fields_editor_post', __('Arbitrary fields of  publication','wp-recall'), 'custom_fields_list_posteditor_rcl', $post->post_type, 'normal', 'high'  );
}

function custom_fields_list_posteditor_rcl($post){ 
    $form_id = 1;
    
    if($post->ID && $post->post_type == 'post')
        $form_id = get_post_meta($post->ID, 'publicform-id', 1);
    
    $content = rcl_get_custom_fields_edit_box($post->ID,$post->post_type,$form_id); 
    
    if(!$content) return false;
    
    echo $content; 
    
    echo '<input type="hidden" name="custom_fields_nonce_rcl" value="'.wp_create_nonce(__FILE__).'" />';
}

add_action('save_post', 'rcl_custom_fields_update', 0);
function rcl_custom_fields_update( $post_id ){
    if(!isset($_POST['custom_fields_nonce_rcl'])) return false;
    if ( !wp_verify_nonce($_POST['custom_fields_nonce_rcl'], __FILE__) ) return false;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return false;
	if ( !current_user_can('edit_post', $post_id) ) return false;

	rcl_update_post_custom_fields($post_id);

	return $post_id;
}

add_action('admin_init','rcl_public_form_admin_actions', 10);
function rcl_public_form_admin_actions(){
    
    if(!isset($_GET['page']) || $_GET['page'] != 'manage-public-form') return false;
    
    if(!isset($_GET['form-action']) || !wp_verify_nonce( $_GET['_wpnonce'], 'rcl-form-action')) return false;
    
    switch($_GET['form-action']){
        
        case 'new-form':
            
            $newFormId = $_GET['form-id'];
            
            add_option('rcl_fields_post_'.$newFormId, array());
            
            wp_redirect(admin_url('admin.php?page=manage-public-form&post-type=post&form-id='.$newFormId)); exit;
            
        break;
    
        case 'delete-form':
            
            $delFormId = $_GET['form-id'];
            
            delete_option('rcl_fields_post_'.$delFormId);
            
            wp_redirect(admin_url('admin.php?page=manage-public-form&post-type=post')); exit;
            
        break;
        
    }
    
}

add_action('rcl_add_dashboard_metabox', 'rcl_add_publicpost_metabox');
function rcl_add_publicpost_metabox($screen){
    add_meta_box( 'rcl-publicpost-metabox', __('Posts awaiting approval','wp-recall'), 'rcl_publicpost_metabox', $screen->id, 'column3' );
}

function rcl_publicpost_metabox(){
    
    $posts = get_posts(array('numberposts'=>-1,'post_type'=>'any','post_status'=>'pending'));
    
    if(!$posts){
        echo '<p>'.__('No posts under moderation','wp-recall').'</p>'; return;
    }
    
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<tr>'
        . '<th>'.__('Header','wp-recall').'</th>'
        . '<th>'.__('Author','wp-recall').'</th>'
        . '<th>'.__('Type','wp-recall').'</th>'
        . '</tr>';
    foreach($posts as $post){
        echo '<tr>'
        . '<td><a href="'.get_edit_post_link($post->ID).'" target="_blank">'.$post->post_title.'</a></td>'
        . '<td>'.$post->post_author.': '.get_the_author_meta('user_login',$post->post_author).'</td>'
        . '<td>'.$post->post_type.'</td>'
        . '</tr>';
    }
    echo '</table>';

}