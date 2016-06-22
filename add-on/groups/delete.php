<?php
global $wpdb;

$table = RCL_PREF ."groups";
$wpdb->query( "DROP TABLE IF EXISTS " . $table );

$table = RCL_PREF ."groups_users";
$wpdb->query( "DROP TABLE IF EXISTS " . $table );

$table = RCL_PREF ."groups_options";
$wpdb->query( "DROP TABLE IF EXISTS " . $table );