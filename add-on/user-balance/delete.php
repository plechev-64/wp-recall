<?php

global $wpdb;

remove_role('reg-nopay');

if(!defined('RMAG_PREF')) 
    define('RMAG_PREF', $wpdb->prefix."rmag_");

$table = RMAG_PREF ."users_balance";
$wpdb->query( "DROP TABLE IF EXISTS " . $table );

$table = RMAG_PREF ."pay_results";
$wpdb->query( "DROP TABLE IF EXISTS " . $table );