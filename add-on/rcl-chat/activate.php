<?php
global $wpdb;

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

$table = RCL_PREF ."chats";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
    chat_id bigint (20) NOT NULL AUTO_INCREMENT,
    chat_room VARCHAR(100) NOT NULL,
    chat_status VARCHAR(20) NOT NULL,
    PRIMARY KEY chat_id (chat_id)
) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."chat_users";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
    room_place VARCHAR(20) NOT NULL,
    chat_id INT (20) NOT NULL,
    user_id INT(20) NOT NULL,
    user_activity DATETIME NOT NULL,
    user_write INT(10) NOT NULL,
    user_status INT(10) NOT NULL,
    UNIQUE KEY room_place (room_place),
    KEY chat_id (chat_id),
    KEY user_id (user_id)
) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."chat_messages";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
    message_id bigint (20) NOT NULL AUTO_INCREMENT,
    chat_id INT(20) NOT NULL,
    user_id INT(20) NOT NULL,
    message_content LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    message_time DATETIME NOT NULL,
    private_key INT(20) NOT NULL,
    message_status INT(20) NOT NULL,
    PRIMARY KEY message_id (message_id),
    KEY chat_id (chat_id),
    KEY user_id (user_id),
    KEY message_status (message_status)
) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."chat_messagemeta";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
    meta_id bigint (20) NOT NULL AUTO_INCREMENT,
    message_id INT (20) NOT NULL,
    meta_key VARCHAR(255) NOT NULL,
    meta_value LONGTEXT NOT NULL,
    PRIMARY KEY meta_id (meta_id),
    KEY message_id (message_id),
    KEY meta_key (meta_key)
) $collate;";

dbDelta( $sql );

global $rcl_options;

if(!isset($rcl_options['chat']['contact_panel'])) 
    $rcl_options['chat']['contact_panel'] = 1;

if(!isset($rcl_options['chat']['place_contact_panel'])) 
    $rcl_options['chat']['place_contact_panel'] = 0;

update_option('rcl_global_options',$rcl_options);