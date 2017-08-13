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
        user_id BIGINT(20) UNSIGNED NOT NULL,
        user_balance VARCHAR (20) NOT NULL,
        PRIMARY KEY  user_id (user_id)
      ) $collate;";

dbDelta( $sql );   
   
$table = RMAG_PREF ."pay_results";

if($wpdb->get_var("show tables like '". $table . "'") != $table) {
    
    $sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
            ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            payment_id INT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            pay_amount VARCHAR(20) NOT NULL,
            time_action DATETIME NOT NULL,
            pay_system VARCHAR(100) NOT NULL,
            pay_type VARCHAR(100) NOT NULL,
            PRIMARY KEY  id (id),
            KEY payment_id (payment_id),
            KEY user_id (user_id)
          ) $collate;";

    dbDelta( $sql );
    
}else{
    
    $wpdb->query("ALTER TABLE `$table` CHANGE `inv_id` `payment_id` INT( 20 ) NOT NULL");
    $wpdb->query("ALTER TABLE `$table` CHANGE `user` `user_id` INT( 20 ) NOT NULL");
    $wpdb->query("ALTER TABLE `$table` CHANGE `count` `pay_amount` VARCHAR( 20 ) NOT NULL");
    $wpdb->query("ALTER TABLE ". $table . " ADD pay_system VARCHAR( 100 ) AFTER time_action");
    $wpdb->query("ALTER TABLE ". $table . " ADD pay_type VARCHAR( 100 ) AFTER time_action");
    
}