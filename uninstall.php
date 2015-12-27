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

include_once( 'class-rcl-install.php' );

RCL_Install::remove_roles();

wp_trash_post( get_option( $rcl_options['lk_page_rcl'] ) );
wp_trash_post( get_option( $rcl_options['feed_page_rcl'] ) );
wp_trash_post( get_option( $rcl_options['users_page_rcl'] ) );

$user_action_table = RCL_PREF . 'user_action';
$wpdb->query( "DROP TABLE IF EXISTS " . $user_action_table );

wp_clear_scheduled_hook('rcl_daily_addon_update');
wp_clear_scheduled_hook('days_garbage_file_rcl');
wp_clear_scheduled_hook('hourly_notify_new_message');

wp_clear_scheduled_hook('rcl_cron_hourly_schedule');
wp_clear_scheduled_hook('rcl_cron_twicedaily_schedule');
wp_clear_scheduled_hook('rcl_cron_daily_schedule');

//TODO: Добавить функцию удаления всех опций связанных с WP Recall
//ap: ниже код удаления опций плагина и кастомных полей формы публикации и профиля
//закомменчен из-за постоянных жалоб об удалении данных плагина при его удалении
/*delete_option('rcl_cart_fields');
delete_option('rcl_profile_fields');
delete_option('rcl_profile_search_fields');
delete_option('rcl_fields_post_1');
delete_option('rcl_fields_products');
delete_option('rcl_global_options');
delete_option('rcl_active_addons');*/