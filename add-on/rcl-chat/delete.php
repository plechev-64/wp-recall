<?php
global $wpdb;
$wpdb->query("DROP TABLE ".RCL_PREF."chats");
$wpdb->query("DROP TABLE ".RCL_PREF."chat_users");
$wpdb->query("DROP TABLE ".RCL_PREF."chat_messages");
$wpdb->query("DROP TABLE ".RCL_PREF."chat_messagemeta");
