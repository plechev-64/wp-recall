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
	  UNIQUE KEY id (id)
	  ) DEFAULT CHARSET=utf8;");
}

global $rcl_options;
$rcl_options['rating_rcl-review'] = 1;
$rcl_options['rating_user_rcl-review'] = 10;
$rcl_options['rating_point_rcl-review'] = 10;
update_option('primary-rcl-options',$rcl_options);