<?php
function get_rmag_global_unit(){
    if(defined('RMAG_PREF')) return false;
    global $wpdb,$rmag_options,$user_ID;
    if(!$_SESSION['return_'.$user_ID]) $_SESSION['return_'.$user_ID] = $_SERVER['HTTP_REFERER'];
    $rmag_options = get_option('primary-rmag-options');
    define('RMAG_PREF', $wpdb->prefix."rmag_");
}
add_action('init','get_rmag_global_unit',10);

if (!session_id()) { session_start(); }

require_once("functions.php");
require_once("admin-pages.php");
require_once("functions/shortcodes.php");
require_once("functions/ajax-func.php");


function add_tab_order_rcl($array_tabs){
    $array_tabs['order']='wp_recall_magazin';
    return $array_tabs;
}
add_filter('ajax_tabs_rcl','add_tab_order_rcl');

add_action('init','add_tab_magazin');
function add_tab_magazin(){
    add_tab_rcl('order','wp_recall_magazin','Заказы',array('class'=>'fa-shopping-cart','order'=>30,'path'=>__FILE__));
}

function wp_recall_magazin($author_lk){
    global $wpdb,$user_ID,$rmag_options,$rcl_options,$order;

	if($user_ID!=$author_lk) return false;
	
	if(isset($_GET['order-id'])){
            
                $block = '<a class="recall-button view-orders" href="'.get_redirect_url_rcl(get_author_posts_url($author_lk),'order').'">Смотреть все заказы</a>';
				
		$order = get_order($_GET['order-id']);

		$block .= get_include_template_rcl('order.php',__FILE__);
		
	}else{
		
		global $orders;
		
		$orders = get_orders(array('user_id'=>$user_ID,'status_not_in'=>6));
					
		if(!$orders) return '<p>У вас пока не оформлено ни одного заказа.</p>';
		
		$block = get_include_template_rcl('history.php',__FILE__);
				
	}
	
	return $block;
}

add_filter('file_scripts_rcl','get_scripts_magazine_rcl');
function get_scripts_magazine_rcl($script){

	$ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',";				

	$script .= "

		/* Удаляем заказ пользователя в корзину */
			jQuery('.remove_order').live('click',function(){
				var idorder = jQuery(this).attr('name');
				var dataString = 'action=delete_order_in_trash_recall&idorder='+ idorder;

				jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['otvet']==100){
						jQuery('.order-'+data['idorder']).remove();
					}
				} 
				});	  	
				return false;
			});
		/* Увеличиваем количество товара в большой корзине */
			jQuery('.add-product').live('click',function(){
				var id_post = jQuery(this).parent().data('product');		
				var number = 1;
				var dataString = 'action=add_in_basket_recall&id_post='+ id_post+'&number='+ number;
				jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['recall']==100){
						jQuery('.cart-summa').text(data['data_sumprice']);						
						jQuery('#product-'+data['id_prod']+' .sumprice-product').text(data['sumproduct']);
						jQuery('#product-'+data['id_prod']+' .number-product').text(data['num_product']);
						jQuery('.cart-numbers').text(data['allprod']);
					}
					if(data['recall']==200){
						alert('Отрицательное значение!');
					}
				} 
				});	  	
				return false;
			});
		/* Уменьшаем товар количество товара в большой корзине */
			jQuery('.remove-product').live('click',function(){
				var id_post = jQuery(this).parent().data('product');
				var number = 1;
				if(number>0){
					var dataString = 'action=remove_out_basket_recall&id_post='+ id_post+'&number='+ number;
					jQuery.ajax({
					".$ajaxdata."
					success: function(data){
						if(data['recall']==100){
							jQuery('.cart-summa').text(data['data_sumprice']);						
							jQuery('#product-'+data['id_prod']+' .sumprice-product').text(data['sumproduct']);
							
							var numprod = data['num_product'];
							if(numprod>0){
								jQuery('#product-'+data['id_prod']+' .number-product').text(data['num_product']);								
							}else{
								var numberproduct = 0;
								jQuery('#product-'+data['id_prod']).remove();
							}
							if(data['allprod']==0) jQuery('.confirm').remove();
							
							jQuery('.cart-numbers').text(data['allprod']);
						}
						if(data['recall']==200){
							alert('Отрицательное значение!');
						}
						if(data['recall']==300){
							alert('Вы пытаетесь удалить из корзины больше товара чем там есть!');
						}
					} 
					});	
				}
				return false;
			});			
		/* Кладем товар в малую корзину */	
			jQuery('.add_basket').live('click',function(){
				var id_post = jQuery(this).data('product');
				var id_custom_prod = jQuery(this).attr('name');
				if(id_custom_prod){
					var number = jQuery('#number-custom-product-'+id_custom_prod).val();
				}else{
					var number = jQuery('#number_product').val();
				}
				var dataString = 'action=add_in_minibasket_recall&id_post='+ id_post+'&number='+number+'&custom='+id_custom_prod;
				jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['recall']==100){
						jQuery('.empty-basket').replaceWith(data['empty-content']);
						jQuery('.cart-summa').html(data['data_sumprice']);
						jQuery('.cart-numbers').html(data['allprod']);
						alert('Добавлено в корзину!');
					}
					if(data['recall']==200){
						alert('Отрицательное значение!');
					}
				} 
				});	  	
				return false;
			});
	";
	return $script;
}
?>