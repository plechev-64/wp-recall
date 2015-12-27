<?php
global $wpdb;
if(!defined('RMAG_PREF')) define('RMAG_PREF', $wpdb->prefix."rmag_");
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$table10 = RMAG_PREF ."details_orders";
   if($wpdb->get_var("show tables like '". $table10 . "'") != $table10) {
	   $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table10 . "` (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  order_id INT(20) NOT NULL,
	  details_order LONGTEXT NOT NULL,
	  UNIQUE KEY id (id),
            INDEX order_id (order_id),
	) DEFAULT CHARSET=utf8;");
	}else{
            /*14.0.0*/
            $wpdb->query("ALTER TABLE $table "
                    . "ADD INDEX order_id (order_id)");
        }
        
	$table8 = RMAG_PREF ."orders_history";
   if($wpdb->get_var("show tables like '". $table8 . "'") != $table8) {
	   $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table8 . "` (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  order_id INT(20) NOT NULL,
	  user_id INT(20) NOT NULL,
	  product_id INT(20) NOT NULL,
	  product_price INT(20) NOT NULL,
	  numberproduct INT(20) NOT NULL,
	  order_date DATETIME NOT NULL,
	  order_status INT(10) NOT NULL,
	  UNIQUE KEY id (id),
            INDEX order_id (order_id),
            INDEX user_id (user_id),
            INDEX product_id (product_id),
            INDEX order_status (order_status)
	) DEFAULT CHARSET=utf8;");
        }else{

            $inv_id = $wpdb->get_var("SELECT inv_id FROM ".RMAG_PREF ."orders_history WHERE status!='0'");

            if($inv_id){
                $sql="ALTER TABLE $table8
                    CHANGE inv_id order_id INT(20) NOT NULL,
                    CHANGE user user_id INT(20) NOT NULL,
                    CHANGE product product_id INT(20) NOT NULL,
                    CHANGE price product_price INT(20) NOT NULL,
                    CHANGE count numberproduct INT(20) NOT NULL,
                    CHANGE time_action order_date DATETIME NOT NULL,
                    CHANGE status order_status INT(10) NOT NULL";
                $wpdb->query($sql);
            }
            
            /*14.0.0*/
            $wpdb->query("ALTER TABLE $table8 "
                    . "ADD INDEX order_id (order_id), "
                    . "ADD INDEX product_id (product_id), "
                    . "ADD INDEX order_status (order_status), "
                    . "ADD INDEX user_id (user_id)");

        }

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