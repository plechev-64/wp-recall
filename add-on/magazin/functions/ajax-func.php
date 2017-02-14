<?php
/*************************************************
Добавление товара в миникорзину
*************************************************/
function rcl_add_minicart(){
    global $rmag_options,$CartData;
    
    rcl_verify_ajax_nonce();
    
    $id_post = intval($_POST['id_post']);
    $number = intval($_POST['number']);

    if(get_post_type($id_post)!='products') return false;
    
    $check_rights = apply_filters('rcl_check_rights_edit_cart',true,$id_post);
    
    if(!$check_rights) return false;

    if(!$number||$number==''||$number==0) $number=1;
    if($number>=0){
        $cnt = (!isset($_SESSION['cart'][$id_post]))? $number : $_SESSION['cart'][$id_post]['number'] + $number;
        $_SESSION['cart'][$id_post]['number'] = $cnt;

        $price = rcl_get_number_price($id_post);
        $price = (!$price) ? 0 : $price;

        $_SESSION['cart'][$id_post]['price'] = $price;

        $allprice = $price * $number;

        $summ = (!isset($_SESSION['cartdata']['summ']))? $allprice : $_SESSION['cartdata']['summ'] + $allprice;
        $_SESSION['cartdata']['summ'] = $summ;

        $all = 0;
        foreach($_SESSION['cart'] as $val){
            $all += $val['number'];
        }

        $CartData = (object)array(
                'numberproducts'=>$all,
                'cart_price'=>$summ,
                'cart_url'=>$rmag_options['basket_page_rmag'],
                'cart'=> $_SESSION['cart']
        );
        
        $cart_url = (isset($rmag_options['basket_page_rmag']))? get_permalink($rmag_options['basket_page_rmag']): '#';

        $log['data_sumprice'] =  $summ;
        $log['allprod'] = $all;
        $log['empty-content'] = rcl_get_include_template('cart-mini-content.php',__FILE__);

        $log['recall'] = 100;
        $log['success'] =   __('Added to cart!','wp-recall').'<br>'
                            .sprintf(__('In your shopping cart: %d items','wp-recall'),$all).'<br>'
                            .'<a style="text-decoration:underline;" href="'.$cart_url.'">'
                            .__('Go to cart','wp-recall')
                            .'</a>';
    }else{
        $log['error'] = __('Negative meaning!','wp-recall');
    }

    echo json_encode($log);
    exit;
}
add_action('wp_ajax_rcl_add_minicart', 'rcl_add_minicart');
add_action('wp_ajax_nopriv_rcl_add_minicart', 'rcl_add_minicart');
/*************************************************
Добавление товара в корзину
*************************************************/
function rcl_add_cart(){

    $id_post = intval($_POST['id_post']);
    $number = intval($_POST['number']);

    if(get_post_type($id_post)!='products') return false;
    
    $check_rights = apply_filters('rcl_check_rights_edit_cart',true,$id_post);
    
    if(!$check_rights) return false;

    if(!$number||$number==''||$number==0) $number=1;
    if($number>=0){
        $cnt = (!isset($_SESSION['cart'][$id_post]))? $number : $_SESSION['cart'][$id_post]['number'] + $number;
        $_SESSION['cart'][$id_post]['number'] = $cnt;

        $price = rcl_get_number_price($id_post);
        
        $_SESSION['cart'][$id_post]['price'] = $price;

        $allprice = $price * $number;

        $summ = (!isset($_SESSION['cartdata']['summ']))? $allprice : $_SESSION['cartdata']['summ'] + $allprice;
        $_SESSION['cartdata']['summ'] = $summ;

        $all = 0;
        foreach($_SESSION['cart'] as $val){
            $all += $val['number'];
        }

        $log['data_sumprice'] = $summ;
        $log['allprod'] = $all;
        $log['id_prod'] = $id_post;

        $log['num_product'] = $cnt;
        $log['sumproduct'] = $cnt * $price;

        $log['recall'] = 100;
    }else{
        $log['error'] = __('Negative meaning!','wp-recall');
    }

    echo json_encode($log);
    exit;
}
add_action('wp_ajax_rcl_add_cart', 'rcl_add_cart');
add_action('wp_ajax_nopriv_rcl_add_cart', 'rcl_add_cart');
/*************************************************
Уменьшаем товар в корзине
*************************************************/
function rcl_remove_product_cart(){
    
    rcl_verify_ajax_nonce();

    $id_post = intval($_POST['id_post']);
    $number = intval($_POST['number']);

    if(get_post_type($id_post)!='products') return false;

    if(!$number||$number==''||$number==0) $number=1;
    if($number>=0){
        $price = $_SESSION['cart'][$id_post]['price'];
        $cnt = $_SESSION['cart'][$id_post]['number'] - $number;

        if($cnt<0){
            $log['error'] = __('You are trying to remove more goods than there are in the cart!','wp-recall');
            echo json_encode($log);
            exit;
        }

        if(!$cnt) unset($_SESSION['cart'][$id_post]);
        else $_SESSION['cart'][$id_post]['number'] = $cnt;

        $allprice = $price * $number;

        $summ = $_SESSION['cartdata']['summ'] - $allprice;
        $_SESSION['cartdata']['summ'] = $summ;

        $all = 0;
        foreach($_SESSION['cart'] as $val){
            $all += $val['number'];
        }

        $log['data_sumprice'] = $summ;
        $log['sumproduct'] = $cnt * $price;
        $log['id_prod'] = $id_post;
        $log['allprod'] = $all;
        $log['num_product'] = $cnt;
        $log['recall'] = 100;


    }else{
        $log['error'] = __('Negative meaning!','wp-recall');
    }

    echo json_encode($log);
    exit;
}
add_action('wp_ajax_rcl_remove_product_cart', 'rcl_remove_product_cart');
add_action('wp_ajax_nopriv_rcl_remove_product_cart', 'rcl_remove_product_cart');
/*************************************************
Подтверждение заказа
*************************************************/
function rcl_confirm_order(){
    
    rcl_verify_ajax_nonce();

    global $rcl_options,$rmag_options,$order;
 
    $result = array();

    include_once 'class-rcl-order.php';
    $rcl_order = new Rcl_Order();

    $order = $rcl_order->insert_order();
    //print_r($orderdata);exit;
    if($rcl_order->is_error){
        foreach($order->errors as $code=>$error){
            $result['errors'][$code] = $error[0];
        }

        if(isset($order->errors['amount_false'])){
            $error_amount = '';
            foreach($rcl_order->amount['error'] as $product_id => $amount){
                $error_amount .= sprintf(__('Product Name :%s available %d items .','wp-recall'),'<b>'.get_the_title($product_id).'</b>',$amount).'<br>';
            }

            $result['code'] = 10;
            $result['html'] = "<div class='order-notice-box'>"
                . __('The order has not been created!','wp-recall').'<br>'
                . __('You may be trying to order a larger quantity of products than is available.','wp-recall').'<br>'
                . $error_amount
                . __('Please reduce the quantity of goods in yout order and try to place your order','wp-recall')
                . '</div>';
        }

        echo json_encode($result);
        exit;
    }
    
    $status = ($order->order_price)? 1: 2;

    $notice = __('Your order has been created!','wp-recall').'<br>';
    $notice .= sprintf(__('Status granted to order - "%s"','wp-recall'),rcl_get_status_name_order($status)).'. ';
    $notice .= __('The order is being processed.','wp-recall').'<br>';

    if(!$order->order_price){ //Если заказ бесплатный
        $notice .= __('The order contained only free items','wp-recall').'<br>';
    }
    
    if(function_exists('rcl_payform')){
        
        $type_pay = $rmag_options['type_order_payment'];
        
        $dataPay = array(
            'baggage_data' => array(
                'order_id' => $order->order_id
            ),
            'pay_id' => $order->order_id,
            'pay_summ' => $order->order_price,
            'description' => sprintf(__('Payment order №%s dated %s','wp-recall'),$order->order_id,get_the_author_meta('user_email',$order->order_author)),
            'merchant_icon' => 1
        );
        
        if(!$type_pay){
            $dataPay['pay_systems'] = 'user_balance';
        }
        
        if($type_pay == 1){
            $dataPay['pay_systems_not_in'] = 'user_balance';
        }

        $payment_form = '<div class="rcl-types-paeers">';        
        $payment_form .= rcl_get_pay_form($dataPay);
        $payment_form .= '</div>';
    }
    
    //Если регистрировали пользователя
    if($rcl_order->userdata){
        
        //Если отправляем данные о регистрации
        if($rcl_order->buyer_register){
            
            $confirm = (isset($rcl_options['confirm_register_recall']))? $rcl_options['confirm_register_recall']: 0;
            
            $notice .= __('All necessary data for authorization on the site have been sent to the specified e-mail','wp-recall')."<br />";
            $notice .= __('In your personal account you can find out the status of your order.','wp-recall').'<br>';
            $notice .= __('You can top up your personal account on the site in your back office and in the future pay for orders with it','wp-recall')."<br />";

            if($confirm){
                
                $notice .= __('To monitor the order status please confirm the specified email!','wp-recall').'<br>';
                $notice .= __('Follow the link in the letter sent to your email','wp-recall').'<br>';
                
            }else{
                
                if($order->order_price){
                    if(function_exists('rcl_payform')){
                        $notice .= $payment_form;
                    }
                }
                
                $notice .= "<p align='center'>"
                        . "<a class='recall-button' href='".$rcl_order->orders_page."'>".__('Go to your personal cabinet','wp-recall')."</a>"
                        . "</p>";
                
            }
            
            $result['redirect'] = $rcl_order->orders_page;
            
        }

    }else{
        
        if($order->order_price){
            if(function_exists('rcl_payform')){              

                    $notice .= __('You can pay for it now or from your personal account. There you can find the status of your order.','wp-recall');

                    $payform = $payment_form;

            }else{

                $notice .= __('You can monitor the status of your order in your personal account.','wp-recall');

            }
        }
    }

    $notice = apply_filters('notify_new_order',$notice,'');

    if($payform) 
        $notice .= $payform;

    $result['success'] = "<div class='order-notice-box'>".$notice."</div>";
    $result['code']=100;

    echo json_encode($result);
    exit;
}
add_action('wp_ajax_rcl_confirm_order', 'rcl_confirm_order');
add_action('wp_ajax_nopriv_rcl_confirm_order', 'rcl_confirm_order');
/*************************************************
Смена статуса заказа
*************************************************/
function rcl_edit_order_status(){
    global $user_ID,$rmag_options,$wpdb;

    rcl_verify_ajax_nonce();

    $order = intval($_POST['order']);
    $status = intval($_POST['status']);

    if($order){

        $oldstatus = $wpdb->get_var($wpdb->prepare("SELECT order_status FROM ".RMAG_PREF."orders_history WHERE order_id='%d'",$order));

        $res = rcl_update_status_order($order,$status);

        if($res){

            if($oldstatus==1&&$status==6){
                    rcl_remove_reserve($order,1);
            }else{
                    rcl_remove_reserve($order);
            }

            $log['otvet'] = 100;
            $log['order'] = $order;
            $log['status'] = rcl_get_status_name_order($status_id);

        }else {
                $log['otvet']=1;
        }

    } else {
            $log['otvet']=1;
    }
        
    echo json_encode($log);
    exit;
}
add_action('wp_ajax_rcl_edit_order_status', 'rcl_edit_order_status');

/*************************************************
Удаление заказа в корзину
*************************************************/
function rcl_trash_order($post){
    global $user_ID;
    
    $order_id = intval($post->order_id);

    if($order_id&&$user_ID){

        rcl_remove_reserve($order_id,1);

        //убираем заказ в корзину
        $res = rcl_update_status_order($order_id,6,$user_ID);

        if($res){
            return '<h3>'.sprintf(__('Order №%s was deleted','wp-recall'),$order_id).'</h3>';
        }

    } else {
        return array('error'=>__('Error','wp-recall'));
    }
}

/*************************************************
Полное удаление заказа
*************************************************/
function rcl_all_delete_order(){
    global $user_ID,$wpdb;

    rcl_verify_ajax_nonce();

    $idorder = intval($_POST['idorder']);

    if($idorder&&$user_ID){
        $res = rcl_delete_order($idorder);

        if($res){
                $log['otvet']=100;
                $log['idorder']=$idorder;
        }
    } else {
            $log['otvet']=1;
    }
    echo json_encode($log);
    exit;
}
add_action('wp_ajax_rcl_all_delete_order', 'rcl_all_delete_order');

function rcl_edit_price_product(){

    $id_post = intval($_POST['id_post']);
    $price = floatval($_POST['price']);
    if(isset($price)){
        update_post_meta($id_post,'price-products',$price);
        $log['otvet']=100;
    }else {
        $log['otvet']=1;
    }
    echo json_encode($log);
    exit;
}
if(is_admin()) 
    add_action('wp_ajax_rcl_edit_price_product', 'rcl_edit_price_product');