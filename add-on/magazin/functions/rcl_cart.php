<?php

class Rcl_Cart {
    
    public $summ;
    public $price;
    public $cnt_products;
    public $values;
    public $request;
    
    function __construct() {
		global $CartData,$rmag_options;
		
        $this->summ = $_SESSION['cartdata']['summ'];

        $all = 0;
        if(isset($_SESSION['cart'])){
            foreach($_SESSION['cart'] as $prod_id=>$val){
                $all += $val['number'];
            }
        }
        $this->cnt_products = $all;
        $this->values = array();
        $this->request = '';
		
		$CartData = (object)array(
			'numberproducts'=>$all,
			'cart_price'=>$this->summ,
			'cart_url'=>$rmag_options['basket_page_rmag'],
			'cart'=> $_SESSION['cart']
		);
    }

    function cart_fields($get_fields_order,$key){
        
        $order_field = '';
		
        $cf = new Rcl_Custom_Fields();
		
        foreach((array)$get_fields_order as $custom_field){
            
            $custom_field = apply_filters('custom_field_cart_form',$custom_field);
            
            if($key=='profile'&&$custom_field['order']!=1) continue;
			
			$slug = $custom_field['slug'];
			
			$this->values[$key][$number_field]['other'] = $slug;
			if($custom_field['type']=='checkbox'){
				$chek = explode('#',$custom_field['field_select']);
				$count_field = count($chek);
				for($a=0;$a<$count_field;$a++){
                                    $number_field++;
                                    $slug_chek = $slug.'_'.$a;
                                    $this->values[$key][$number_field]['chek'] = $slug_chek;
				}
			}
			if($custom_field['type']=='radio'){
				$radio = explode('#',$custom_field['field_select']);
				$count_field = count($radio);
				for($a=0;$a<$count_field;$a++){
					$number_field++;
					$slug_chek = $slug.'_'.$a;
					$this->values[$key][$number_field]['radio']['name'] .= $slug;
					$this->values[$key][$number_field]['radio']['id'] .= $slug_chek;
				}
			}

			$requared = ($custom_field['requared']==1)? '<span class="required">*</span>': '';
			$val = (isset($custom_field['value']))? $custom_field['value']: '';
			
			$order_field .= '<tr>'
			.'<td><label>'.$custom_field['title'].$requared.':</label></td>'
			.'<td>'.$cf->get_input($custom_field,$val).'</td>'
			.'</tr>';
			
			$number_field++;

        }
        
        return $order_field;
        
    }
    
    function script_request($key){
        
        $basket = '';
        
        foreach((array)$this->values[$key] as $value){
            if($value['chek']){
                    $basket .=  "if(jQuery('#".$value['chek']."').attr('checked')=='checked') var ".$value['chek']." = jQuery('#".$value['chek']."').attr('value');";
                    $reg_request .= "+'&".$value['chek']."='+".$value['chek'];
            }
            if($value['radio']){
                    $basket .=  "if(jQuery('#".$value['radio']['id']."').attr('checked')=='checked') var ".$value['radio']['name']." = jQuery('#".$value['radio']['id']."').attr('value');";
                    $reg_radio .= "+'&".$value['radio']['name']."='+".$value['radio']['name'];
            }
            if($value['other']){
                    $basket .=  "var ".$value['other']." = jQuery('#".$value['other']."').attr('value');";
                    $reg_request .= "+'&".$value['other']."='+".$value['other'];
            }
        }
                    
        $this->request .=  $reg_request.$reg_radio;      
        return $basket;
    }
	
	function get_products(){
		global $post;
        //print_r($_SESSION);
        $basket = '';
        //unset($_SESSION['cart']);
        if(isset($_SESSION['cart'])&&$_SESSION['cart']){
            foreach($_SESSION['cart'] as $id_prod=>$val){
                $ids[] = $id_prod;
            }
            $ids = implode(',',$ids);

            $products = get_posts(array('numberposts' => -1,'order' => 'ASC','post_type' => 'products','include' => $ids));

        }else{
            return $basket;
        }
		
		if(!$products) return false;

        return $products;
    }
    
    function cart() {

        global $user_ID,$products;
		
		$products = $this->get_products();
		
		if(!$products) return '<p>В вашей корзине пусто.</p>';
		
		if(!$user_ID) $basket .= '<h3 class="title-data">Корзина <span class="weight-normal">(цены указаны в рублях)</span></h3>';
		
        $basket .= rcl_get_include_template('cart.php',__FILE__);
        
        $basket = apply_filters('cart_rcl',$basket);

            if($this->cnt_products){

                    $basket .= '<div class="confirm">';

                    $get_fields_order = get_option( 'custom_orders_field' );

                    if($get_fields_order) $order_field = $this->cart_fields($get_fields_order,'order');					

                    if($user_ID){
                            
                            if($order_field) $basket .= '<h3 align="center">Для оформления заказа заполните форму ниже:</h3>
							<div id="regnewuser"  style="display:none;"></div>
                            <table class="form-table">'.$order_field.'</table>';
                            
                            $basket .= rcl_get_button('Оформить заказ','#',array('icon'=>false,'class'=>'confirm_order'))
							.'</div>
                            <div class="redirectform" style="text-align:center;"></div>';
                            
                            $basket .= "<script>
                    jQuery(function(){
                    jQuery('.confirm_order').live('click',function(){";
                            
                    $basket .= $this->script_request('order');
                    
                    $basket .= "
                            
                            var dataString_count = 'action=rcl_confirm_order'".$this->request.";
                            jQuery.ajax({
                            type: 'POST',
                            data: dataString_count,
                            dataType: 'json',
                            url: wpurl+'wp-admin/admin-ajax.php',
                            success: function(data){
                                    if(data['otvet']==100){
                                            jQuery('.redirectform').html(data['redirectform']);
                                            jQuery('.confirm').remove();
                                            jQuery('.add_remove').empty();
                                    } else if(data['otvet']==10){
                                       jQuery('.redirectform').html(data['amount']);
                                    } else if(data['otvet']==5){
                                            jQuery('#regnewuser').html(data['recall']);
                                            jQuery('#regnewuser').slideDown(500).delay(5000).slideUp(500);
                                    }else {
                                       alert('Ошибка проверки данных.');
                                    }
                            } 
                            });	

                            return false;
                    });
                });
                </script>";
                    
                }else{
                        $get_fields = get_option( 'custom_profile_field' );

                        if($get_fields) $order_field .= $this->cart_fields($get_fields,'profile');

                        $basket .= '<h3 align="center">Для оформления заказа заполните форму ниже:</h3>
						<div id="regnewuser"  style="display:none;"></div>
                        <table class="form-table">
                            <tr>
                                <td><label>Укажите ваш E-mail <span class="required">*</span>:</label></td>
                                <td><input required type="text" class="email_new_user" name="email_new_user" value=""></td>
                            </tr>
                             <tr>
                                <td><label>Ваше Имя</label></td>
                                <td><input type="text" class="fio_new_user" name="fio_new_user" value=""></td>
                            </tr>
                            '.$order_field.'
                        </table>
                        <p align="right">'.rcl_get_button('Оформить заказ','#',array('icon'=>false,'class'=>'rcl_register_user_order','id'=>false)).'</p>

                        </div>';
                        $basket .= "<script>
                        jQuery(function(){
                                jQuery('.rcl_register_user_order').live('click',function(){";

                                    $basket .= $this->script_request('order');

                                    $basket .= $this->script_request('profile');

                                    $basket .= "
                                    var fio = jQuery('.confirm .fio_new_user').attr('value');
                                    var email = jQuery('.confirm .email_new_user').attr('value');

                                    var dataString = 'action=rcl_confirm_order&action=rcl_register_user_order&fio_new_user='+fio+'&email_new_user='+email".$this->request.";

                                    jQuery.ajax({
                                            type: 'POST',
                                            data: dataString,
                                            dataType: 'json',
                                            url: wpurl+'wp-admin/admin-ajax.php',
                                            success: function(data){
                                                    if(data['int']==100){				
                                                            jQuery('#regnewuser').html(data['recall']);
                                                            jQuery('#regnewuser').slideDown(500);
                                                            if(data['redirect']!=0){
                                                                    location.replace(data['redirect']);
                                                            }else{
                                                                    jQuery('.form-table').remove();
                                                                    jQuery('.rcl_register_user_order').remove();
                                                            }
                                                    } else {
                                                            jQuery('#regnewuser').html(data['recall']);
                                                            jQuery('#regnewuser').slideDown(500).delay(5000).slideUp(500);
                                                    }
                                            } 
                                    });	  	
                                    return false;
                            });
                    });
                    </script>";
                }
            } 

            return $basket;
    }
}
