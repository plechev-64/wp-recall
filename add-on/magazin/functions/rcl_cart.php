<?php

class Rcl_Cart {
    
    public $summ;
    public $price;
    public $cnt_products;
    public $values;
    public $request;
    
    function __construct() {
        $this->summ = $_SESSION['cartdata']['summ'];
        //$this->price = $_SESSION[$prod_id];
        
        $all = 0;
        if(isset($_SESSION['cart'])){
            foreach($_SESSION['cart'] as $prod_id=>$val){
                $all += $val['number'];
            }
        }
        $this->cnt_products = $all;
        $this->values = array();
        $this->request = '';
    }
    
    function loop(){
        //print_r($_SESSION['cart']);
        $n=0;
        $basket = '';
        //unset($_SESSION['cart']);
        if(isset($_SESSION['cart'])){
            foreach($_SESSION['cart'] as $id_prod=>$val){
                $ids[] = $id_prod;
            }
            $ids = implode(',',$ids);

            $products = get_posts(array('numberposts' => -1,'order' => 'ASC','post_type' => 'products','include' => $ids));

        }else{
            return $basket;
        }
        
        foreach((array)$products as $product){
            if(isset($_SESSION['cart'][$product->ID])){
            $n++;
            
            $price = $_SESSION['cart'][$product->ID]['price'];
            $numprod = $_SESSION['cart'][$product->ID]['number'];
            $product_price = $price * $numprod;

                $basket .= '<tr class="prodrow-'.$product->ID.'">'
                        . '<td class="number">'.$n.'</td>'
                        . '<td>'.get_the_post_thumbnail( $product->ID, array(50,50) ).'</td>'
                        . '<td><a href="'.get_permalink($product->ID).'">'.get_the_title($product->ID).'</a></td>'
                        . '<td>'.$price.'</td>'
                        . '<td class="numprod-'.$product->ID.'">'.$numprod.'</td>'
                        . '<td class="sumprod-'.$product->ID.'">'.$product_price.'</td>'
                        . '<td class="add_remove">';
                if($price) {
                        $basket .= '<input type="text" size="2" name="number_product" class="number_product" id="number-product-'.$product->ID.'" value="1">'
                        . '<a class="add-product" id="'.$product->ID.'" href="#"><i class="fa fa-plus-square-o"></i></a>'
                        . '<a class="remove-product" id="'.$product->ID.'" href="#"><i class="fa fa-minus-square-o"></i></a>';
                }else{
                    $basket .= '<a class="remove-product" id="'.$product->ID.'" href="#"><i class="fa fa-minus-square-o"></i></a>';
                }
                $basket .= '<input class="idhidden" name="idhidden" type="hidden" value="'.$product->ID.'">
                <input class="numhidden-'.$product->ID.'" name="productnum" type="hidden" value="'.$numprod.'">
                </td>
                </tr>';
            }
        }

        return $basket;
    }
    
    function cart_fields($get_fields_order,$key){
        
        $order_field = '';
        
        foreach((array)$get_fields_order as $custom_field){
            
            $custom_field = apply_filters('custom_field_cart_form',$custom_field);
            
            if($key=='profile'&&$custom_field['order']!=1) continue;

                $slug = $custom_field['slug'];
                
                $req = ($custom_field['requared']==1)? 'required': '';
                $requared = ($custom_field['requared']==1)? '<span class="required">*</span>': '';
                $val = (isset($custom_field['value']))? $custom_field['value']: '';
                
                $order_field .= '<tr><td><label>'.$custom_field['title'].$requared.':</label></th>';
                if($custom_field['type']=='text')
                        $order_field .= '<td><input '.$req.' type="text" name="'.$slug.'" id="'.$slug.'" maxlength="50" value="'.$val.'" /><br/></td>';
                if($custom_field['type']=='number')
                        $order_field .= '<td><input '.$req.' type="number" name="'.$slug.'" id="'.$slug.'" maxlength="50" value="'.$val.'" /><br/></td>';
                if($custom_field['type']=='date')
                        $order_field .= '<td><input '.$req.' type="text" name="'.$slug.'" class="datepicker" id="'.$slug.'" maxlength="50" value="'.$val.'" /><br/></td>';
                if($custom_field['type']=='time')
                        $order_field .= '<td><input '.$req.' type="time" name="'.$slug.'" id="'.$slug.'" maxlength="50" value="'.$val.'" /><br/></td>';
                if($custom_field['type']=='textarea')
                        $order_field .= '<td><textarea '.$req.' name="'.$slug.'" id="'.$slug.'" rows="5" cols="50">'.$val.'</textarea></td>';
                if($custom_field['type']=='select'){
                        $field_select = '';
                        $fields = explode('#',$custom_field['field_select']);
                        $count_field = count($fields);
                        for($a=0;$a<$count_field;$a++){
                                $field_select .='<option value="'.$fields[$a].'">'.$fields[$a].'</option>';
                        }
                        $order_field .= '<td><select '.$req.' name="'.$slug.'" class="regular-text" id="'.$slug.'">
                        '.$field_select.'
                        </select></td>';
                }
                if($custom_field['type']=='file') 
                        $order_field .='<td><input type="file" name="'.$slug.'" id="'.$slug.'"></td>';
                $this->values[$key][$number_field]['other'] .= $slug;
                if($custom_field['type']=='checkbox'){
                        $chek = explode('#',$custom_field['field_select']);
                        $count_field = count($chek);
                        $order_field .='<td>';
                        for($a=0;$a<$count_field;$a++){
                                $number_field++;
                                $slug_chek = $slug.'_'.$a;
                                $order_field .='<input type="checkbox" id="'.$slug_chek.'" name="'.$slug_chek.'" value="'.$chek[$a].'"> '.$chek[$a].'<br />';
                                $this->values[$key][$number_field]['chek'] .= $slug_chek;
                        }
                        $order_field .='</td>';
                }
                if($custom_field['type']=='radio'){
                        $radio = explode('#',$custom_field['field_select']);
                        $count_field = count($radio);
                        $order_field .='<td>';
                        for($a=0;$a<$count_field;$a++){
                                $number_field++;
                                $slug_chek = $slug.'_'.$a;
                                $order_field .='<input type="radio" '.checked($a,0,false).' name="'.$slug.'" id="'.$slug_chek.'" value="'.$radio[$a].'"> '.$radio[$a].'<br />';
                                $this->values[$key][$number_field]['radio']['name'] .= $slug;
                                $this->values[$key][$number_field]['radio']['id'] .= $slug_chek;
                        }

                        $order_field .='</td>';
                }

                $order_field .= '</tr>';
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
    
    function cart() {

        global $user_ID;

        $basket .= '<table class="basket-table">'
            . '<tr class="head-table">'
                . '<td>№п/п</td>'
                . '<td></td>'
                . '<td>Наименование товара</td>'
                . '<td>Цена</td>'
                . '<td>Кол-во</td>'
                . '<td>Сумма</td>'
                . '<td></td>'
            . '</tr>';

        $basket .= $this->loop();

        $basket .= '<tr>'
                . '<td colspan="4">Итого:</td>'
                . '<td class="allprod">'.$this->cnt_products.'</td>'
                . '<td class="sumprice" colspan="2">'.$this->summ.'</td>'
            . '</tr>'
        . '</table>';
        
        $basket = apply_filters('cart_rcl',$basket);

            if($this->cnt_products){
                
                    $basket .= '<div class="confirm" style="text-align:left;">
                            <h3 align="center">Для оформления заказа заполните форму ниже:</h3>';

                    $get_fields_order = get_option( 'custom_orders_field' );

                    if($get_fields_order) $order_field = $this->cart_fields($get_fields_order,'order');					

                    if($user_ID){
                            
                            if($order_field) $basket .= '<div id="regnewuser"  style="display:none;"></div>
                            <table class="form-table">'.$order_field.'</table>';
                            
                            $basket .= '<div align="center">'.get_button_rcl('Подтвердить заказ','#',array('icon'=>false,'class'=>'confirm_order','id'=>false)).'</div>
                            </div>
                            <div class="redirectform" style="text-align:center;"></div>';
                            
                            $basket .= "<script>
                    jQuery(function(){
                    jQuery('.confirm_order').live('click',function(){";
                            
                    $basket .= $this->script_request('order');
                    
                    $basket .= "
                            
                            var dataString_count = 'action=confirm_order_recall'".$this->request.";
                            jQuery.ajax({
                            type: 'POST',
                            data: dataString_count,
                            dataType: 'json',
                            url: '/wp-admin/admin-ajax.php',
                            success: function(data){
                                    if(data['otvet']==100){
                                            jQuery('.redirectform').html(data['redirectform']);
                                            jQuery('.confirm').remove();
                                            jQuery('.add_remove').empty();
                                    } else if(data['otvet']==10){
                                       jQuery('.redirectform').html(data['amount']);
                                    } else if(data['otvet']==5){
                                            jQuery('#regnewuser').html(data['recall']);
                                            jQuery('#regnewuser').slideDown(1500).delay(5000).slideUp(1500);
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

                        $basket .= '<div id="regnewuser"  style="display:none;"></div>
                        <table class="form-table">
                            <tr>
                                <td><label>Укажите ваш E-mail <span class="required">*</span>:</label></td>
                                <td><input required type="text" class="email_new_user" name="email_new_user" value=""></td>
                            </tr>
                             <tr>
                                <td><label>Отображаемое Имя</label></td>
                                <td><input type="text" class="fio_new_user" name="fio_new_user" value=""></td>
                            </tr>
                            '.$order_field.'
                        </table>
                        <p align="center">'.get_button_rcl('Оформить заказ','#',array('icon'=>false,'class'=>'add_new_user_in_order','id'=>false)).'</p>

                        </div>';
                        $basket .= "<script>
                        jQuery(function(){
                                jQuery('.add_new_user_in_order').live('click',function(){";

                                    $basket .= $this->script_request('order');

                                    $basket .= $this->script_request('profile');

                                    $basket .= "
                                    var fio = jQuery('.confirm .fio_new_user').attr('value');
                                    var email = jQuery('.confirm .email_new_user').attr('value');

                                    var dataString = 'action=confirm_order_recall&action=add_new_user_in_order&fio_new_user='+fio+'&email_new_user='+email".$this->request.";

                                    jQuery.ajax({
                                            type: 'POST',
                                            data: dataString,
                                            dataType: 'json',
                                            url: '".get_bloginfo('wpurl')."/wp-admin/admin-ajax.php',
                                            success: function(data){
                                                    if(data['int']==100){				
                                                            jQuery('#regnewuser').html(data['recall']);
                                                            jQuery('#regnewuser').slideDown(1500);
                                                            if(data['redirect']!=0){
                                                                    location.replace(data['redirect']);
                                                            }else{
                                                                    jQuery('.form-table').remove();
                                                                    jQuery('.add_new_user_in_order').remove();
                                                            }
                                                    } else {
                                                            jQuery('#regnewuser').html(data['recall']);
                                                            jQuery('#regnewuser').slideDown(1500).delay(5000).slideUp(1500);
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
