<?php

class Rcl_Cart {

    public $summ;
    public $price;
    public $cnt_products;
    public $values;
    public $request;

    function __construct() {
        global $CartData,$rmag_options;

        $this->summ = (isset($_SESSION['cartdata']['summ']))? $_SESSION['cartdata']['summ']: 0;

        $all = 0;
        if(isset($_SESSION['cart'])){
            foreach($_SESSION['cart'] as $prod_id=>$val){
                $all += $val['number'];
            }
        }
        $this->cnt_products = $all;
        $this->values = array();
        $this->request = '';
        
        $cart = (isset($_SESSION['cart']))? $_SESSION['cart']: false;

        $CartData = (object)array(
            'numberproducts'=>$all,
            'cart_price'=>$this->summ,
            'cart_url'=>$rmag_options['basket_page_rmag'],
            'cart'=> $cart
        );
    }

    function cart_fields($get_fields_order,$key){

        $order_field = '';

        $cf = new Rcl_Custom_Fields();

        foreach((array)$get_fields_order as $custom_field){

            $custom_field = apply_filters('rcl_cart_field',$custom_field);

            if($key=='profile'&&$custom_field['order']!=1) continue;

            $slug = $custom_field['slug'];

            if($custom_field['type']=='checkbox'){

                $chek = explode('#',$custom_field['field_select']);
                $count_field = count($chek);
                for($a=0;$a<$count_field;$a++){
                    $number_field++;
                    $slug_chek = $slug.'_'.$a;
                    $this->values[$key][$number_field]['chek'] = $slug_chek;
                }

            }else if($custom_field['type']=='agree'){

                $this->values[$key][$number_field]['chek'] = $slug;

            }else if($custom_field['type']=='radio'){

                $radio = explode('#',$custom_field['field_select']);
                $count_field = count($radio);
                for($a=0;$a<$count_field;$a++){
                    $number_field++;
                    $slug_chek = $slug.'_'.$a;
                    $this->values[$key][$number_field]['radio']['name'] .= $slug;
                    $this->values[$key][$number_field]['radio']['id'] .= $slug_chek;
                }

            }else{

                $this->values[$key][$number_field]['other'] = $slug;

            }

            $requared = ($custom_field['requared']==1)? '<span class="required">*</span>': '';
            $val = (isset($custom_field['value']))? $custom_field['value']: '';

            $order_field .= '<tr>'
            .'<td><label>'.$cf->get_title($custom_field).$requared.':</label></td>'
            .'<td>'.$cf->get_input($custom_field,$val).'</td>'
            .'</tr>';

            $number_field++;

        }

        return $order_field;

    }

    function get_products(){
                global $post;

        $basket = '';

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

        if(!$products) return '<p>'.__('Your shopping cart is empty','wp-recall').'.</p>';

        $cart_content = rcl_get_include_template('cart.php',__FILE__);

        $cart_content = apply_filters('rcl_cart_content',$cart_content);

        if($this->cnt_products){

            $cart_content .= '<div class="rcl-cart-fields">';

            $cart_content .= '<h3 align="center">'.__('To place an order fill out the form below','wp-recall').':</h3>';

            $cart_fields = ($get_fields_order = get_option( 'rcl_cart_fields' ))? $this->cart_fields($get_fields_order,'order'): '';

            $cart_content .= '<table class="form-table">';

            if(!$user_ID){

                $cart_fields .= ($get_fields = get_option( 'rcl_profile_fields' ))? $this->cart_fields($get_fields,'profile'): '';

                $cart_content .= '
                    <tr>
                        <td><label>'.__('Enter your E-mail','wp-recall').' <span class="required">*</span>:</label></td>
                        <td><input required type="text" class="email_new_user" name="email_new_user" value=""></td>
                    </tr>
                    <tr>
                        <td><label>'.__('Your name','wp-recall').'</label></td>
                        <td><input type="text" class="fio_new_user" name="fio_new_user" value=""></td>
                    </tr>';

            }

            $cart_content .= ($cart_fields)? $cart_fields: '';

            $cart_content .= '</table>';

            $cart_content .= '<p align="right">'.rcl_get_button(__('Checkout','wp-recall'),'#',array('icon'=>false,'class'=>'confirm_order','id'=>false)).'</p>';

            $cart_content .= '</div>';

        }

        return    '<form id="rcl-cart" method="post" enctype="multipart/form-data">'
                . $cart_content
                . '<input type="hidden" name="rcl-cart[insert-order]" value="1">'
                . '</form>'
                . '<div id="rcl-cart-notice" style="text-align:center;"></div>';
    }
}
