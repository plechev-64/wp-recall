<?php
function add_tab_rcl($id,$callback,$name='',$args=false){
    _deprecated_function( 'add_tab_rcl', '4.2', 'rcl_tab' );
    return rcl_tab($id,$callback,$name,$args);
}
function add_postlist_rcl($id,$posttype,$name='',$args=false){
    _deprecated_function( 'add_postlist_rcl', '4.2', 'rcl_postlist' );
    return rcl_postlist($id,$posttype,$name,$args);
}
function add_block_rcl($place,$callback,$args=false){
    _deprecated_function( 'add_block_rcl', '4.2', 'rcl_block' );
    return rcl_block($place,$callback,$args);
}
function add_notify_rcl($text,$type='warning'){
    _deprecated_function( 'add_notify_rcl', '4.2', 'rcl_notice_text' );
    return rcl_notice_text($text,$type);
}
function rcl_notify(){
    _deprecated_function( 'rcl_notify', '4.2', 'rcl_notice' );
    return rcl_notice();
}
function get_template_rcl($file_temp,$path=false){
    _deprecated_function( 'get_template_rcl', '4.2', 'rcl_get_template_path' );
    return rcl_get_template_path($file_temp,$path);
}
function include_template_rcl($file_temp,$path=false){
    _deprecated_function( 'include_template_rcl', '4.2', 'rcl_include_template' );
    include rcl_include_template($file_temp,$path);
}
function get_include_template_rcl($file_temp,$path=false){
    _deprecated_function( 'get_include_template_rcl', '4.2', 'rcl_get_include_template' );
    return rcl_get_include_template($file_temp,$path);
}
function get_key_addon_rcl($path){
    _deprecated_function( 'get_key_addon_rcl', '4.2', 'rcl_key_addon' );
    return rcl_key_addon($path);
}
function get_author_block_content_rcl(){
    _deprecated_function( 'get_author_block_content_rcl', '4.2', 'rcl_get_author_block' );
    return rcl_get_author_block();
}
function get_miniaction_user_rcl($action,$user_id=false){
    _deprecated_function( 'get_miniaction_user_rcl', '4.2', 'rcl_get_miniaction' );

    return rcl_get_miniaction($action,$user_id);
}
function get_custom_post_meta_rcl($post_id){
    _deprecated_function( 'get_custom_post_meta_rcl', '4.2', 'rcl_get_postmeta' );
    return rcl_get_postmeta($post_id);
}