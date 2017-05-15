<?php
/**
 * Created by PhpStorm.
 * Author: Maksim Martirosov
 * Date: 05.10.2015
 * Time: 20:39
 * Project: wp-recall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb, $rcl_options;

include_once( 'classes/class-rcl-install.php' );
include_once( 'rcl-functions.php' );
include_once( 'functions/files.php' );

$upload_dir = rcl_get_wp_upload_dir();
define('RCL_UPLOAD_PATH', $upload_dir['basedir'] . '/rcl-uploads/' );
define('RCL_TAKEPATH', WP_CONTENT_DIR . '/wp-recall/' );

//Удаляем созданные роли
RCL_Install::remove_roles();

//Удаляем расписания крона
wp_clear_scheduled_hook('rcl_cron_hourly_schedule');
wp_clear_scheduled_hook('rcl_cron_twicedaily_schedule');
wp_clear_scheduled_hook('rcl_cron_daily_schedule');

//Подчищаем на сервере
rcl_remove_dir(RCL_TAKEPATH);
rcl_remove_dir(RCL_UPLOAD_PATH);

//Удаляем созданные страницы
wp_trash_post( get_option( $rcl_options['lk_page_rcl'] ) );
wp_trash_post( get_option( $rcl_options['feed_page_rcl'] ) );
wp_trash_post( get_option( $rcl_options['users_page_rcl'] ) );

//Удаляем таблицы и настройки плагина
$tables = $wpdb->get_results("SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_name like '%rcl_%'");
if($tables){
    foreach($tables as $tables){
        $wpdb->query( "DROP TABLE IF EXISTS " . $tables->table_name );
    }
}

$tables = $wpdb->get_results("SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_name like '%rmag_%'");
if($tables){
    foreach($tables as $tables){
        $wpdb->query( "DROP TABLE IF EXISTS " . $tables->table_name );
    }
}

$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%rcl%'" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%rmag%'" );
$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '%rcl%'" );

//включаем всем пользователям сайта показ админ панели
$wpdb->update(
    $wpdb->prefix.'usermeta',
    array('meta_value'=>'true'),
    array('meta_key'=>'show_admin_bar_front')
);