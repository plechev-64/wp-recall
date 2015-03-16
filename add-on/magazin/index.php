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

require_once("admin-pages.php");
require_once("functions.php");
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
    global $wpdb,$user_ID,$rmag_options,$rcl_options;

	if($user_ID!=$author_lk) return false;
			
	$orders = $wpdb->get_results("SELECT * FROM ".RMAG_PREF ."orders_history WHERE user='$user_ID' ORDER BY ID DESC");
                
	if($orders){
            
            foreach($orders as $rd){
                $rdrs[$rd->inv_id][] = $rd;
            } 

            $magazine_block='';

            foreach($rdrs as $order_id=>$products){
                $sumprise = 0;
                $status = 6;
                foreach($products as $product){                
                    $sumprise += $product->price*$product->count;
                    if($product->status<$status) $status = $product->status;
                }

                $magazine_block .= '<div class="order-'.$order_id.'">';
                $magazine_block .= '<h3 style="margin-bottom:5px;">Заказ ID: '.$order_id.'</h3>';      
                $magazine_block .= '<h4 style="margin-bottom:5px;">Статус заказа: '.get_status_name($status).'</h4>';
                
                $magazine_block = apply_filters('content_order',$magazine_block,$order_id);

                if($status == 1||$status == 5) $magazine_block .= '<input  style="margin:0 10px 10px 0;" class="remove_order recall-button" type="button" name="'.$order_id.'" value="Удалить">';
                if($status==1&&function_exists('rcl_payform')){
                    $type_order_payment = $rmag_options['type_order_payment'];
                    if($type_order_payment==1||$type_order_payment==2){
                        $magazine_block .= rcl_payform(array('id_pay'=>$order_id,'summ'=>$sumprise,'type'=>2));
                    }else{
                        $magazine_block .= '<input class="pay_order  recall-button" type="button" name="'.$order_id.'" value="Оплатить">';
                    }
                }
                
                $magazine_block .= '<div class="redirectform-'.$order_id.'"></div>';
                
                $magazine_block .= '<table><tr>'
                        . '<td>№ п/п</td>'
                        . '<td>Наименование</td>'
                        . '<td>Цена</td>'
                        . '<td>Количество</td>'
                        . '<td>Сумма</td>'
                        . '</tr>';
                
                $tr = 1;
                foreach($products as $product){
                    $magazine_block .= '<tr>'
                        . '<td>'.$tr++.'</td>'
                        . '<td>'.get_the_title($product->product).'</td>'
                        . '<td>'.$product->price.'</td>'
                        . '<td>'.$product->count.'</td>'
                        . '<td>'.$product->price*$product->count.'</td>'
                        . '</tr>';               
                }
                
                $magazine_block .= '<tr style="font-weight:bold;">'
                        . '<td colspan="4">Сумма заказа</td>'
                        . '<td>'.$sumprise.'</td>'
                        . '</tr>';
                
                $magazine_block .= '</table>';
                $magazine_block .= '</div>';
            }

            $magazine_block .= "<script type='text/javascript'>
                jQuery(function(){
                        jQuery('.value_count_user').click(function(){
                                jQuery('.redirectform').empty();
                        });
                });
            </script>";
            
	}else{
		$magazine_block = 'У вас пока не оформлено ни одного заказа.';
	}
	
	return $magazine_block;
}

add_filter('file_scripts_rcl','get_scripts_magazine_rcl');
function get_scripts_magazine_rcl($script){

	$ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',";				

	$script .= "
                
		jQuery('.slider-products').bxSlider({
			auto:true,
			pause:10000
		});
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
		/* Увеличиваем количество товара в корзине */
			jQuery('.add-product').live('click',function(){
				var id_post = jQuery(this).data('product');		
				var number = jQuery('#number-product-'+id_post).val();
				var dataString = 'action=add_in_basket_recall&id_post='+ id_post+'&number='+ number;
				jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['recall']==100){
						jQuery('.sumprice').empty().html(data['data_sumprice']);
						jQuery('.value_count_user').attr('value', data['data_sumprice']);
						jQuery('.sumprod-'+data['id_prod']).empty().html(data['sumproduct']);
						jQuery('.numprod-'+data['id_prod']).empty().html(data['num_product']);
						jQuery('.numhidden-'+data['id_prod']).attr('value', data['num_product']);
						jQuery('.allprod').empty().html(data['allprod']);
						
					}
					if(data['recall']==200){
						alert('Отрицательное значение!');
					}
				} 
				});	  	
				return false;
			});
		/* Уменьшаем товар количество товара в корзине */
			jQuery('.remove-product').live('click',function(){
				var id_post = jQuery(this).attr('id');
				var number = jQuery('#number-product-'+id_post).val();
				var num = parseInt(jQuery('.numprod-'+id_post).html());
				if(num>0){
					var dataString = 'action=remove_out_basket_recall&id_post='+ id_post+'&number='+ number;
					jQuery.ajax({
					".$ajaxdata."
					success: function(data){
						if(data['recall']==100){
							jQuery('.sumprice').empty().html(data['data_sumprice']);
							jQuery('.sumprod-'+data['id_prod']).empty().html(data['sumproduct']);
							var numprod = data['num_product'];
							if(numprod>0){
								jQuery('.numprod-'+data['id_prod']).empty().html(numprod);
								jQuery('.numhidden-'+data['id_prod']).attr('value', numprod);
							}else{
								var numberproduct = 0;
								jQuery('.prodrow-'+data['id_prod']).remove();
								jQuery('.basket-table').find('.number').each(function() {	
									numberproduct ++;
									jQuery(this).html(numberproduct);
								});
							}					
							jQuery('.allprod').empty().html(data['allprod']);
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
		/* Кладем товар в корзину */	
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
						jQuery('.sumprice').html(data['data_sumprice']);
						jQuery('.allprod').html(data['allprod']);
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