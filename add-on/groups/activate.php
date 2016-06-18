<?php
global $rcl_options,$wpdb;

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
$collate = '';

if ( $wpdb->has_cap( 'collation' ) ) {
    if ( ! empty( $wpdb->charset ) ) {
        $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
    }
    if ( ! empty( $wpdb->collate ) ) {
        $collate .= " COLLATE $wpdb->collate";
    }
}

$table = RCL_PREF ."groups";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        ID INT(20) NOT NULL,
        admin_id INT(20) NOT NULL,
        group_users INT(20) NOT NULL,
        group_status VARCHAR(20) NOT NULL,
        group_date DATETIME NOT NULL,
        PRIMARY KEY id (id),
        KEY admin_id (admin_id)
      ) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."groups_users";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        ID bigint (20) NOT NULL AUTO_INCREMENT,
        group_id INT(20) NOT NULL,
        user_id INT(20) NOT NULL,
        user_role VARCHAR(20) NOT NULL,
        status_time INT(20) NOT NULL,
        user_date DATETIME NOT NULL,
        PRIMARY KEY id (id),
        KEY group_id (group_id),
        KEY user_id (user_id)
      ) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."groups_options";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        ID bigint (20) NOT NULL AUTO_INCREMENT,
        group_id INT(20) NOT NULL,
        option_key VARCHAR( 255 ) NOT NULL,
        option_value LONGTEXT NOT NULL,
        PRIMARY KEY id (id),
        KEY group_id (group_id),
        KEY option_key (option_key)
      ) $collate;";

dbDelta( $sql );