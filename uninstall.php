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

//TODO: Добавить функцию удаления всех опций связанных с WP Recall
//ap: ниже код удаления опций плагина и кастомных полей формы публикации и профиля
//закомменчен из-за постоянных жалоб об удалении данных плагина при его удалении
/*delete_option('custom_orders_field');
delete_option('custom_profile_field');
delete_option('custom_profile_search_form');
delete_option('custom_public_fields_1');
delete_option('custom_saleform_fields');
delete_option('primary-rcl-options');
delete_option('active_addons_recall');*/