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

$table = RCL_PREF ."rating_values";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
	  ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	  user_id BIGINT(20) UNSIGNED NOT NULL,
	  object_id BIGINT(20) UNSIGNED NOT NULL,
	  object_author BIGINT(20) UNSIGNED NOT NULL,
	  rating_value VARCHAR(5) NOT NULL,
          rating_date DATETIME NOT NULL,
          rating_type VARCHAR(20) NOT NULL,
	  PRIMARY KEY  id (id),
            KEY user_id (user_id),
            KEY object_id (object_id),
            KEY rating_value (rating_value),
            KEY rating_type (rating_type)
	) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."rating_totals";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
	  ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	  object_id BIGINT(20) UNSIGNED NOT NULL,
          object_author BIGINT(20) UNSIGNED NOT NULL,
	  rating_total VARCHAR(10) NOT NULL,
          rating_type VARCHAR(20) NOT NULL,
	  PRIMARY KEY  id (id),
            KEY object_id (object_id),
            KEY object_author (object_author),
            KEY rating_type (rating_type),
            KEY rating_total (rating_total)
	) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."rating_users";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
	  user_id BIGINT(20) UNSIGNED NOT NULL,
	  rating_total VARCHAR(10) NOT NULL,
	  PRIMARY KEY  id (user_id),
            KEY rating_total (rating_total)
	) $collate;";

dbDelta( $sql );

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
