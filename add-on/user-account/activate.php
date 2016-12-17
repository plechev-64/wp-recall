<?php
global $wpdb;

if(!defined('RMAG_PREF')) 
    define('RMAG_PREF', $wpdb->prefix."rmag_");

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

$table = RMAG_PREF ."users_balance";

$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        user_id INT(20) NOT NULL,
        user_balance VARCHAR (20) NOT NULL,
        PRIMARY KEY user_id (user_id)
      ) $collate;";

dbDelta( $sql );   
   
$table = RMAG_PREF ."pay_results";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        ID bigint (20) NOT NULL AUTO_INCREMENT,
        inv_id INT(20) NOT NULL,
        user INT(20) NOT NULL,
        count INT(20) NOT NULL,
        time_action DATETIME NOT NULL,
        PRIMARY KEY id (id),
        KEY inv_id (inv_id),
        KEY user (user)
      ) $collate;";

dbDelta( $sql );