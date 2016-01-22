<?php
global $wpdb;

if(!defined('RMAG_PREF')) define('RMAG_PREF', $wpdb->prefix."rmag_");

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
$collate = '';

if ( $wpdb->has_cap( 'collation' ) ) {
    if ( ! empty( $wpdb->charset ) ) {
        $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
    }
    if ( ! empty( $wpdb->collate ) ) {
        $collate .= " COLLATE $wpdb->collate";
    }
}

$table = RMAG_PREF ."details_orders";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        ID bigint (20) NOT NULL AUTO_INCREMENT,
        order_id INT(20) NOT NULL,
        details_order LONGTEXT NOT NULL,
        PRIMARY KEY id (id),
          KEY order_id (order_id)
    ) $collate;";

dbDelta( $sql );

$table = RMAG_PREF ."orders_history";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        ID bigint (20) NOT NULL AUTO_INCREMENT,
        order_id INT(20) NOT NULL,
        user_id INT(20) NOT NULL,
        product_id INT(20) NOT NULL,
        product_price INT(20) NOT NULL,
        numberproduct INT(20) NOT NULL,
        order_date DATETIME NOT NULL,
        order_status INT(10) NOT NULL,
        PRIMARY KEY id (id),
          KEY order_id (order_id),
          KEY user_id (user_id),
          KEY product_id (product_id),
          KEY order_status (order_status)
      ) $collate;";

dbDelta( $sql );

$rmag_options = get_option('primary-rmag-options');

if(!isset($rmag_options['products_warehouse_recall'])) $rmag_options['products_warehouse_recall']=0;
if(!isset($rmag_options['sistem_related_products'])) $rmag_options['sistem_related_products']=1;
if(!isset($rmag_options['title_related_products_recall'])) $rmag_options['title_related_products_recall']='Рекомендуем';
if(!isset($rmag_options['size_related_products'])) $rmag_options['size_related_products']=3;
if(!isset($rmag_options['basket_page_rmag'])){
    $rmag_options['basket_page_rmag'] = wp_insert_post(array(
        'post_title'=>'Корзина',
        'post_content'=>'[basket]',
        'post_status'=>'publish',
        'post_author'=>1,
        'post_type'=>'page',
        'post_name'=>'rcl-cart'
    ));
}

update_option('primary-rmag-options',$rmag_options);