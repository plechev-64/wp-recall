<?php
global $wpdb;
$wpdb->query("DROP TABLE ".RCL_PREF."private_contacts");
$wpdb->query("DROP TABLE ".RCL_PREF."private_message");
$wpdb->query("DROP TABLE ".RCL_PREF."black_list_user");

global $rcl_options;
unset($rcl_options['max_private_message']);
unset($rcl_options['update_private_message']);
unset($rcl_options['global_update_private_message']);
update_option('primary-rcl-options',$rcl_options);
?>