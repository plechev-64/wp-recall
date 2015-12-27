<?php
global $rcl_options;

if(!isset($rcl_options['info_author_recall']))
    $rcl_options['info_author_recall']=1;
if(!isset($rcl_options['moderation_public_post']))
    $rcl_options['moderation_public_post']=1;
if(!isset($rcl_options['media_downloader_recall']))
    $rcl_options['media_downloader_recall']='';
if(!isset($rcl_options['id_parent_category']))
    $rcl_options['id_parent_category']='';
if(!isset($rcl_options['user_public_access_recall']))
    $rcl_options['user_public_access_recall']=0;

if(!isset($rcl_options['public_form_page_rcl']))
    $rcl_options['public_form_page_rcl'] = wp_insert_post(
            array(
                'post_title'=>'Форма публикации',
                'post_content'=>'[public-form]',
                'post_status'=>'publish',
                'post_author'=>1,
                'post_type'=>'page',
                'post_name'=>'rcl-postedit'
            ));

if(!isset($rcl_options['publics_block_rcl']))
    $rcl_options['publics_block_rcl'] = 1;
if(!isset($rcl_options['view_publics_block_rcl']))
    $rcl_options['view_publics_block_rcl'] = 1;
if(!isset($rcl_options['type_text_editor']))
    $rcl_options['type_text_editor'] = 0;
if(!isset($rcl_options['output_public_form_rcl']))
    $rcl_options['output_public_form_rcl'] = 1;
if(!isset($rcl_options['user_public_access_recall']))
    $rcl_options['user_public_access_recall'] = 2;
if(!isset($rcl_options['rcl_editor_buttons']))
    $rcl_options['rcl_editor_buttons'] = array('header','text','image','html');
if(!isset($rcl_options['front_editing']))
    $rcl_options['front_editing'] = array(2);

update_option('rcl_global_options',$rcl_options);
?>