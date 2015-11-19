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
          UNIQUE KEY id (id)
        ) DEFAULT CHARSET=utf8;");
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
          UNIQUE KEY id (id)
        ) DEFAULT CHARSET=utf8;");

        $wpdb->query("ALTER TABLE ".RCL_PREF ."groups_options ADD option_key VARCHAR( 255 ) after group_id");
        require_once 'migration.php';
        rcl_group_migrate_old_data();
}

/*$wpdb->query("ALTER TABLE ".RCL_PREF ."groups_options ADD option_key VARCHAR( 255 ) after group_id");
        require_once 'migration.php';
        rcl_group_migrate_old_data();*/
//$rcl_options['user_groups_recall']='';
//update_option('primary-rcl-options',$rcl_options);
?>