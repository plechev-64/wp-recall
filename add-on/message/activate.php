<?php
global $wpdb;
$table7 = RCL_PREF."private_contacts";
if($wpdb->get_var("show tables like '". $table7 . "'") != $table7) {
	   $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table7 . "` (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  user INT(20) NOT NULL,
	  contact INT(20) NOT NULL,
	  status INT(20) NOT NULL,
	  UNIQUE KEY id (id)
	) DEFAULT CHARSET=utf8;");
}

$table2 = RCL_PREF."private_message";
if($wpdb->get_var("show tables like '". $table2 . "'") != $table2) {
	   $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table2 . "` (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  author_mess INT(20) NOT NULL,
	  content_mess longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	  adressat_mess INT(20) NOT NULL,
	  time_mess DATETIME NOT NULL,
	  status_mess INT(10) NOT NULL,
	  UNIQUE KEY id (id)
	  ) DEFAULT CHARSET=utf8;");
}else{
	$wpdb->query("ALTER TABLE `$table2` CHANGE `content_mess` `content_mess` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");
}

$table3 = RCL_PREF."black_list_user";
if($wpdb->get_var("show tables like '". $table3 . "'") != $table3) {
	   $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table3 . "` (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  user INT(20) NOT NULL,
	  ban INT(20) NOT NULL,
	  UNIQUE KEY id (id)
	 ) DEFAULT CHARSET=utf8;");
}

update_option('use_smilies',1);
global $rcl_options;
$rcl_options['max_private_message']=100;
$rcl_options['update_private_message']=20;
$rcl_options['global_update_private_message']=0;
update_option('primary-rcl-options',$rcl_options);
?>