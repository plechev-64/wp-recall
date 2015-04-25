<?php
global $wpdb;
$wpdb->query("DROP TABLE ".RCL_PREF."rayting_post");
$wpdb->query("DROP TABLE ".RCL_PREF."rayting_comments");
$wpdb->query("DROP TABLE ".RCL_PREF."total_rayting_posts");
$wpdb->query("DROP TABLE ".RCL_PREF."total_rayting_comments");
$wpdb->query("DROP TABLE ".RCL_PREF."total_rayting_users");
global $rcl_options;
unset($rcl_options['rayt_post_recall']);
unset($rcl_options['rayt_comment_recall']);
unset($rcl_options['rayt_post_user_rayt']);
unset($rcl_options['rayt_comment_user_rayt']);
unset($rcl_options['count_rayt_post']);
unset($rcl_options['count_rayt_comment']);
update_option('primary-rcl-options',$rcl_options);
?>