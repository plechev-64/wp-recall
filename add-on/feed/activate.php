<?php
global $wpdb;
//$meta = $wpdb->get_var("SELECT umeta_id $wpdb->usermeta WHERE meta_key LIKE 'feed_user_%'");
//if($meta) 
	$wpdb->query("UPDATE $wpdb->usermeta SET meta_key='rcl_feed' WHERE meta_key LIKE 'feed_user_%'");