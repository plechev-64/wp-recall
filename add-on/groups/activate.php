<?php
global $rcl_options;
global $wpdb;

$table = RCL_PREF ."groups";
if($wpdb->get_var("show tables like '". $table . "'") != $table) {
    $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table . "` (
      ID INT(20) NOT NULL,
      admin_id INT(20) NOT NULL,
      group_users INT(20) NOT NULL,
      group_status VARCHAR(20) NOT NULL,
      group_date DATETIME NOT NULL,
      UNIQUE KEY id (id),
      INDEX admin_id (admin_id)
    ) DEFAULT CHARSET=utf8;");
}else{
    /*14.0.0*/
    $wpdb->query("ALTER TABLE $table "
            . "ADD INDEX admin_id (admin_id)");
}

$table = RCL_PREF ."groups_users";
if($wpdb->get_var("show tables like '". $table . "'") != $table) {
    $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table . "` (
      ID bigint (20) NOT NULL AUTO_INCREMENT,
      group_id INT(20) NOT NULL,
      user_id INT(20) NOT NULL,
      user_role VARCHAR(20) NOT NULL,
      status_time INT(20) NOT NULL,
      user_date DATETIME NOT NULL,
      UNIQUE KEY id (id),
      INDEX group_id (group_id),
      INDEX user_id (user_id)
    ) DEFAULT CHARSET=utf8;");

}else{
    /*14.0.0*/
    $wpdb->query("ALTER TABLE $table "
            . "ADD INDEX group_id (group_id), "
            . "ADD INDEX user_id (user_id)");
}

$table = RCL_PREF ."groups_options";
if($wpdb->get_var("show tables like '". $table . "'") != $table) {
    $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table . "` (
      ID bigint (20) NOT NULL AUTO_INCREMENT,
      group_id INT(20) NOT NULL,
      option_key VARCHAR( 255 ) NOT NULL,
      option_value LONGTEXT NOT NULL,
      UNIQUE KEY id (id),
      INDEX group_id (group_id),
      INDEX option_key (option_key)
    ) DEFAULT CHARSET=utf8;");
}else{
    /*переход с версии ниже 13.7.0*/
    $row = $wpdb->get_row("SELECT * FROM $table ORDER BY ID DESC");
    $row = (array)$row;   
    if(!isset($row['option_key'])){
        $wpdb->query("ALTER TABLE $table ADD option_key VARCHAR( 255 ) after group_id");
        require_once 'migration.php';
        rcl_group_migrate_old_data();
    }
    
    $wpdb->query("ALTER TABLE $table "
            . "ADD INDEX group_id (group_id), "
            . "ADD INDEX option_key (option_key)");
}