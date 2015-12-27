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

/*14.0.0*/
function rcl_rename_plugin_options(){
    global $wpdb;
    
    $oldfield = $wpdb->get_var("SELECT option_name FROM $wpdb->options WHERE option_name = 'primary-rcl-options'");

    if(!$oldfield) return false;
    
    $active_addons = get_option('active_addons_recall');
    
    if($active_addons){
        $new_actives = array();
        foreach($active_addons as $addon=>$data){
            $new_actives[$addon]['path'] = $data['src'];
        }
        update_option('active_addons_recall',$new_actives);
    }
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_global_options'),
        array('option_name'=>'primary-rcl-options')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_active_addons'),
        array('option_name'=>'active_addons_recall')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_profile_fields'),
        array('option_name'=>'custom_profile_field')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_profile_default'),
        array('option_name'=>'show_defolt_field')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_cart_fields'),
        array('option_name'=>'custom_orders_field')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_fields_products'),
        array('option_name'=>'custom_saleform_fields')
    );
    
    $wpdb->update(
        $wpdb->options,
        array('option_name'=>'rcl_profile_search_fields'),
        array('option_name'=>'custom_profile_search_form')
    );
    
    $formfields = $wpdb->get_col("SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'custom_fields_%'");
    
    if($formfields){
        foreach($formfields as $name){
            $newname = str_replace('custom_fields_','rcl_fields_',$name);
            $wpdb->query("UPDATE $wpdb->options SET option_name='$newname' WHERE option_name='$name'");
        }
    }
    
    $formfields = $wpdb->get_col("SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'custom_public_fields_%'");
    
    if($formfields){
        foreach($formfields as $name){
            $newname = str_replace('custom_public_fields_','rcl_fields_post_',$name);
            $wpdb->query("UPDATE $wpdb->options SET option_name='$newname' WHERE option_name='$name'");
        }
    }
}