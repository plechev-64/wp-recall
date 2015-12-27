<?php
global $wpdb;

$table = RCL_PREF ."feeds";
if($wpdb->get_var("show tables like '". $table . "'") != $table) {
    $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table . "` (
      feed_id INT(20) NOT NULL AUTO_INCREMENT,
      user_id INT(20) NOT NULL,
      object_id INT(20) NOT NULL,
      feed_type VARCHAR(20) NOT NULL,
      feed_status INT(10) NOT NULL,
      UNIQUE KEY feed_id (feed_id),
      INDEX user_id (user_id),
      INDEX object_id (object_id),
      INDEX feed_type (feed_type)
    ) DEFAULT CHARSET=utf8;");

    require_once 'migration.php';
    rcl_migration_feed_data();
}else{
    /*14.0.0*/
    $wpdb->query("ALTER TABLE $table ADD INDEX user_id (user_id), ADD INDEX object_id (object_id), ADD INDEX feed_type (feed_type)");
}