<?php
global $wpdb;
$table_name = RCL_PREF."profile_otziv";
if($wpdb->get_var("show tables like '". $table_name . "'") != $table_name) {
	$wpdb->query("CREATE TABLE IF NOT EXISTS `". $table_name . "` (
      ID bigint (20) NOT NULL AUTO_INCREMENT,
	  author_id INT(20) NOT NULL,
	  content_otziv longtext NOT NULL,
	  user_id INT(20) NOT NULL,
	  status VARCHAR(5) NOT NULL,
	  UNIQUE KEY id (id),
            INDEX author_id (author_id),
            INDEX user_id (user_id)
	  ) DEFAULT CHARSET=utf8;");
}else{
            /*14.0.0*/
            $wpdb->query("ALTER TABLE $table_name "
                    . "ADD INDEX author_id (author_id), "
                    . "ADD INDEX user_id (user_id)");
        }

global $rcl_options;
if(!isset($rcl_options['rating_rcl-review'])) $rcl_options['rating_rcl-review'] = 1;
if(!isset($rcl_options['rating_user_rcl-review'])) $rcl_options['rating_user_rcl-review'] = 10;
if(!isset($rcl_options['rating_point_rcl-review'])) $rcl_options['rating_point_rcl-review'] = 10;
update_option('rcl_global_options',$rcl_options);