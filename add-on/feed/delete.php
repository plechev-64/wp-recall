<?php
global $wpdb;

$table = RCL_PREF ."feeds";
$wpdb->query( "DROP TABLE IF EXISTS " . $table );