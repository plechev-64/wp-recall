<?php

global $wpdb;

$table = RCL_PREF ."rating_values";
$wpdb->query( "DROP TABLE IF EXISTS " . $table );

$table = RCL_PREF ."rating_totals";
$wpdb->query( "DROP TABLE IF EXISTS " . $table );

$table = RCL_PREF ."rating_users";
$wpdb->query( "DROP TABLE IF EXISTS " . $table );