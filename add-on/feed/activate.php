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
      UNIQUE KEY id (feed_id)
    ) DEFAULT CHARSET=utf8;");

    require_once 'migration.php';
    rcl_migration_feed_data();
}