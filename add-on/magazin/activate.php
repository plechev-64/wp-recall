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
	  UNIQUE KEY id (id)
	) DEFAULT CHARSET=utf8;");
	}
	$table7 = RMAG_PREF ."user_count";
   if($wpdb->get_var("show tables like '". $table7 . "'") != $table7) {
	   $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table7 ."` (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  user INT(20) NOT NULL,
	  count INT(20) NOT NULL,
	  UNIQUE KEY id (id)
	) DEFAULT CHARSET=utf8;");
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
	  UNIQUE KEY id (id)
	) DEFAULT CHARSET=utf8;");
        }else{

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
		$table9 = RMAG_PREF ."pay_results";
   if($wpdb->get_var("show tables like '". $table9 . "'") != $table9) {
	   $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table9 . "` (
	  ID bigint (20) NOT NULL AUTO_INCREMENT,
	  inv_id INT(20) NOT NULL,
	  user INT(20) NOT NULL,
	  count INT(20) NOT NULL,
	  time_action DATETIME NOT NULL,
	  UNIQUE KEY id (id)
	) DEFAULT CHARSET=utf8;");
	}

$rmag_options = get_option('primary-rmag-options');
//$rmag_options['admin_email_magazin_recall']='';
$rmag_options['products_warehouse_recall']=0;
//$rmag_options['add_basket_button_recall']='';
$rmag_options['sistem_related_products']=1;
$rmag_options['title_related_products_recall']='Рекомендуем';
$rmag_options['size_related_products']=3;
//$rmag_options['connect_sale']='';
//$rmag_options['type_order_payment']='';
//$rmag_options['page_result_pay']='';
//$rmag_options['page_success_pay']='';
//$rmag_options['page_successfully_pay']='';
if(!isset($rmag_options['basket_page_rmag'])){
		$rmag_options['basket_page_rmag'] = wp_insert_post(array('post_title'=>'Корзина','post_content'=>'[basket]','post_status'=>'publish','post_author'=>1,'post_type'=>'page','post_name'=>'rcl-cart'));
	}
?>