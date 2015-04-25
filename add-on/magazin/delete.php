<?php
global $wpdb;
define('RMAG_PREF', $wpdb->prefix."rmag_");
	delete_option( 'primary-rmag-options' );
	$wpdb->query("DROP TABLE ".RMAG_PREF ."orders_history");
?>