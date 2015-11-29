<?php

function rcl_migration_feed_data(){
    global $wpdb;
    
    $feeds = $wpdb->get_results("SELECT user_id,meta_value FROM $wpdb->usermeta WHERE meta_key='rcl_feed'");

    if(!$feeds) return false;

    $sql = array();
    foreach($feeds as $feed){
        $sql[] = "($feed->user_id, $feed->meta_value, 'author', 1)";
    }

    $wpdb->query("INSERT INTO ".RCL_PREF."feeds (user_id, object_id, feed_type, feed_status) VALUES ".implode(',',$sql));

    $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key='rcl_feed'");
}

