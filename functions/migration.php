<?php
/*12.2.0*/
function rcl_update_avatar_data(){
    global $wpdb;

    $avatars = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE option_name LIKE 'avatar_user_%'");

    if(!$avatars) return false;

    foreach($avatars as $avatar){
        $user_id = str_replace('avatar_user_', '', $avatar->option_name);
        update_user_meta($user_id,'rcl_avatar',$avatar->option_value);
    }
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'avatar_user_%'");
}

/*13.1.1*/
function rcl_rename_media_dir(){
    global $wpdb;
    //Правим пути до аватарок
    $urls = $wpdb->get_results("SELECT meta_value,user_id FROM $wpdb->usermeta WHERE meta_key='rcl_avatar' AND meta_value LIKE '%temp-rcl%'");
    foreach($urls as $url){
        update_user_meta($url->user_id,'rcl_avatar',str_replace('temp-rcl','rcl-uploads',$url->meta_value));
    }
    //Правим пути до изображений публикаций
    $contents = $wpdb->get_results("SELECT post_content,ID FROM $wpdb->posts WHERE post_content LIKE '%temp-rcl%'");
    foreach($contents as $content){
        $wpdb->update(
            $wpdb->posts,
            array('post_content'=>str_replace('temp-rcl','rcl-uploads',$content->post_content)),
            array('ID'=>$content->ID)
        );
    }
}