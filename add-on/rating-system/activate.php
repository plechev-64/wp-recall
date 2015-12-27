<?php
global $wpdb;
$table = RCL_PREF."rating_values";
if($wpdb->get_var("show tables like '". $table . "'") != $table) {
	   $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table . "` (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  user_id INT(20) NOT NULL,
	  object_id INT(20) NOT NULL,
	  object_author INT(20) NOT NULL,
	  rating_value VARCHAR(5) NOT NULL,
          rating_date DATETIME NOT NULL,
          rating_type VARCHAR(20) NOT NULL,
	  UNIQUE KEY id (id),
            INDEX user_id (user_id),
            INDEX object_id (object_id),
            INDEX rating_value (rating_value),
            INDEX rating_type (rating_type)
	) DEFAULT CHARSET=utf8;");
}else{
            /*14.0.0*/
            $wpdb->query("ALTER TABLE $table "
                    . "ADD INDEX user_id (user_id), "
                    . "ADD INDEX object_id (object_id), "
                    . "ADD INDEX rating_type (rating_type), "
                    . "ADD INDEX rating_value (rating_value)");
        }

$table = RCL_PREF."rating_totals";
if($wpdb->get_var("show tables like '". $table . "'") != $table) {
	   $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table . "` (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  object_id INT(20) NOT NULL,
          object_author INT(20) NOT NULL,
	  rating_total VARCHAR(10) NOT NULL,
          rating_type VARCHAR(20) NOT NULL,
	  UNIQUE KEY id (id),
            INDEX object_id (object_id),
            INDEX object_author (object_author),
            INDEX rating_type (rating_type),
            INDEX rating_total (rating_total)
	) DEFAULT CHARSET=utf8;");
}else{
            /*14.0.0*/
            $wpdb->query("ALTER TABLE $table "
                    . "ADD INDEX object_id (object_id), "
                    . "ADD INDEX object_author (object_author), "
                    . "ADD INDEX rating_type (rating_type), "
                    . "ADD INDEX rating_total (rating_total)");
        }

$table = RCL_PREF."rating_users";
if($wpdb->get_var("show tables like '". $table . "'") != $table) {
	   $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table . "` (
	  user_id INT(20) NOT NULL,
	  rating_total VARCHAR(10) NOT NULL,
	  UNIQUE KEY id (user_id),
            INDEX rating_total (rating_total)
	) DEFAULT CHARSET=utf8;");

}else{
            /*14.0.0*/
            $wpdb->query("ALTER TABLE $table "
                    . "ADD INDEX rating_total (rating_total)");
        }

$table = RCL_PREF."rayting_post";
if($wpdb->get_var("show tables like '". $table . "'") == $table) {
    include_once 'migration.php';
    rcl_update_rating_data();
}

global $rcl_options;
if(!isset($rcl_options['rating_post'])){
    $rcl_options['rating_post'] = 1;
    $rcl_options['rating_comment'] = 1;
    $rcl_options['rating_type_post'] = 0;
    $rcl_options['rating_type_comment'] = 0;
    $rcl_options['rating_point_post'] = 1;
    $rcl_options['rating_point_comment'] = 1;
    $rcl_options['rating_user_post'] = 1;
    $rcl_options['rating_user_comment'] = 1;
    update_option('rcl_global_options',$rcl_options);
}
