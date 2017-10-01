<?php

add_action('wp','rcl_deleted_post_notice');
function rcl_deleted_post_notice(){
    if (isset($_GET['public'])&&$_GET['public']=='deleted') 
        rcl_notice_text(__('The publication has been successfully removed!','wp-recall'),'warning');
}

add_action('init', 'rcl_delete_post_activate');
function rcl_delete_post_activate ( ) {
    if ( isset( $_POST['delete-post-rcl'] )&&wp_verify_nonce( $_POST['_wpnonce'], 'delete-post-rcl' ) ) {
        add_action( 'wp', 'rcl_delete_post' );
    }
}

add_filter('rcl_init_js_variables','rcl_init_js_public_variables',10);
function rcl_init_js_public_variables($data){

    $data['local']['preview'] = __('Preview','wp-recall');
    $data['local']['publish'] = __('Publish','wp-recall');
    $data['local']['save_draft'] = __('Save as Draft','wp-recall');
    $data['local']['edit'] = __('Edit','wp-recall');
    $data['local']['edit_box_title'] = __('Quick edit','wp-recall');   
    $data['local']['allowed_downloads'] = __('You have exceeded the allowed number of downloads! Max:','wp-recall');
    $data['local']['upload_size_public'] = __('Exceeds the maximum file size! Max:','wp-recall');
    
    return $data;
}

add_action('wp', 'rcl_edit_post_activate');
function rcl_edit_post_activate ( ) {
    if( defined( 'DOING_AJAX' ) && DOING_AJAX) return false;
    if ( isset( $_POST['rcl-edit-post'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'rcl-edit-post' ) ) {
          rcl_edit_post();
    }
}

add_action('init', 'rcl_setup_author_role', 10);
function rcl_setup_author_role() {
    global $current_user;
    
    if(!defined( 'DOING_AJAX' ) || !DOING_AJAX) return;
    
    if ( isset( $_REQUEST['post_id'] ) ){
        $current_user->allcaps['edit_published_pages'] = 1;
        $current_user->allcaps['edit_others_pages'] = 1;
        $current_user->allcaps['edit_others_posts'] = 1;
    }

} 

add_action('init','rcl_add_postlist_posts',10);
function rcl_add_postlist_posts(){
    rcl_postlist('posts','post',__('Records','wp-recall'),array('order'=>30));
}

add_action('init','rcl_init_publics_block');
function rcl_init_publics_block(){
    
    if(rcl_get_option('publics_block_rcl') == 1){

        $tab_data = array(
            'id'=>'publics', 
            'name'=>__('Posts','wp-recall'),
            'supports'=>array('ajax','cache'),
            'public'=>rcl_get_option('view_publics_block_rcl'),
            'icon'=>'fa-list',
            'output'=>'menu',
            'content' => array(
                array(
                    'callback' => array(
                        'id' => 'type-post',
                        'name'=>'rcl_get_postslist',
                        'args'=>array('post',__('Posts','wp-recall'))
                    )
                )
            )
        );

        rcl_tab($tab_data);
    }
    
    if(rcl_get_option('output_public_form_rcl') == 1){
        
        rcl_tab(
            array(
                'id'=>'postform',
                'name'=>__('Publication','wp-recall'),
                'public'=>0,
                'icon'=>'fa-pencil',
                'content'=>array(
                    array(
                        'callback' => array(
                            'name'=>'rcl_tab_postform'
                        )
                    )
                )
            )
        );

    }
}

