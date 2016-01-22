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

$table = RCL_PREF ."private_contacts";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  user INT(20) NOT NULL,
	  contact INT(20) NOT NULL,
	  status INT(20) NOT NULL,
	  PRIMARY KEY id (id),
          KEY user (user),
          KEY contact (contact)
	) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."private_message";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  author_mess INT(20) NOT NULL,
	  content_mess longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	  adressat_mess INT(20) NOT NULL,
	  time_mess DATETIME NOT NULL,
	  status_mess INT(10) NOT NULL,
	  PRIMARY KEY id (id),
          KEY author_mess (author_mess),
          KEY adressat_mess (adressat_mess),
          KEY status_mess (status_mess)
	) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."black_list_user";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  user INT(20) NOT NULL,
	  ban INT(20) NOT NULL,
	  PRIMARY KEY id (id),
          KEY user (user),
          KEY ban (ban)
	 ) $collate;";

dbDelta( $sql );

update_option('use_smilies',1);
global $rcl_options;
if(!isset($rcl_options['max_private_message'])) $rcl_options['max_private_message']=100;
if(!isset($rcl_options['sort_mess'])) $rcl_options['sort_mess']=0;
if(!isset($rcl_options['update_private_message'])) $rcl_options['update_private_message']=20;
if(!isset($rcl_options['global_update_private_message'])) $rcl_options['global_update_private_message']=0;
update_option('rcl_global_options',$rcl_options);