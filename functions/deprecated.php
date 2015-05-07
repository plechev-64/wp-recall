<?php
_deprecated_function( 'add_tab_rcl', '4.2', 'rcl_tab' );
_deprecated_function( 'add_postlist_rcl', '4.2', 'rcl_postlist' );
_deprecated_function( 'add_block_rcl', '4.2', 'rcl_block' );
_deprecated_function( 'add_notify_rcl', '4.2', 'rcl_notice_text' );
_deprecated_function( 'rcl_notify', '4.2', 'rcl_notice' );
_deprecated_function( 'get_template_rcl', '4.2', 'rcl_get_template_path' );
_deprecated_function( 'include_template_rcl', '4.2', 'rcl_include_template' );
_deprecated_function( 'get_include_template_rcl', '4.2', 'rcl_get_include_template' );
_deprecated_function( 'get_key_addon_rcl', '4.2', 'rcl_key_addon' );
_deprecated_function( 'get_author_block_content_rcl', '4.2', 'rcl_get_author_block' );
_deprecated_function( 'get_miniaction_user_rcl', '4.2', 'rcl_get_miniaction' );
_deprecated_function( 'get_custom_post_meta_rcl', '4.2', 'rcl_get_postmeta' );

function add_tab_rcl($id,$callback,$name='',$args=false){
    return rcl_tab($id,$callback,$name,$args);
}
function add_postlist_rcl($id,$posttype,$name='',$args=false){
    return rcl_postlist($id,$posttype,$name,$args);
}
function add_block_rcl($place,$callback,$args=false){
    return rcl_block($place,$callback,$args);
}
function add_notify_rcl($text,$type='warning'){
    return rcl_notice_text($text,$type);
}
function rcl_notify(){
    return rcl_notice();
}
function get_template_rcl($file_temp,$path=false){
    return rcl_get_template_path($file_temp,$path);
}
function include_template_rcl($file_temp,$path=false){
    include rcl_include_template($file_temp,$path);
}
function get_include_template_rcl($file_temp,$path=false){
    return rcl_get_include_template($file_temp,$path);
}
function get_key_addon_rcl($path){
    return rcl_key_addon($path);
}
function get_author_block_content_rcl(){
    return rcl_get_author_block();
}
function get_miniaction_user_rcl($action,$user_id=false){
    return rcl_get_miniaction($action,$user_id);
}
function get_custom_post_meta_rcl($post_id){
	return rcl_get_postmeta($post_id);
}