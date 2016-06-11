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
                            .__('Go to basket','wp-recall')
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
            $log['error'] = __('You are trying to remove from the basket more goods than there!','wp-recall');
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
                . __('The order was not created!','wp-recall').'<br>'
                . __('You may be trying to book a larger quantity than is available.','wp-recall').'<br>'
                . $error_amount
                . __('Please reduce the quantity of goods in order and try to place your order again','wp-recall')
                . '</div>';
        }

        echo json_encode($result);
        exit;
    }
    
    $status = ($order->order_price)? 1: 2;

    $notice = __('Your order has been created!','wp-recall').'<br>';
    $notice .= sprintf(__('Order granted the status - "%s"','wp-recall'),rcl_get_status_name_order($status)).'. ';
    $notice .= __('The order is processing.','wp-recall').'<br>';

    if(!$order->order_price){ //Если заказ бесплатный
        $notice .= __('The order contained only free items','wp-recall').'<br>';
    }
    
    //Если регистрировали пользователя
    if($rcl_order->userdata){
        
        //Если отправляем данные о регистрации
        if($rcl_order->buyer_register){
            
            $confirm = (isset($rcl_options['confirm_register_recall']))? $rcl_options['confirm_register_recall']: 0;
            
            $notice .= __('All necessary data for authorization on the site have been sent to the specified e-mail','wp-recall')."<br />";
            $notice .= __('In your personal account you can find out the status of your order.','wp-recall').'<br>';
            $notice .= __('You can fill up your personal account on the site of his private office in the future to pay for their orders through it','wp-recall')."<br />";

            if($confirm){
                
                $notice .= __('To monitor the status of the order to confirm the specified email!','wp-recall').'<br>';
                $notice .= __('Follow the link in the email sent to','wp-recall').'<br>';
                
            }else{
                
                if($order->order_price){
                    if(function_exists('rcl_payform')){

                        $type_pay = $rmag_options['type_order_payment'];
                        if($type_pay==1||$type_pay==2){

                            $notice .= rcl_payform(array(
                                    'id_pay'=>$order->order_id,
                                    'summ'=>$order->order_price,
                                    'user_id'=>$order->order_author,
                                    'type'=>2,
                                    'description'=>sprintf(__('Payment order №%s from %s','wp-recall'),$order->order_id,get_the_author_meta('user_email',$order->order_author))
                                ));
                        }
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
                $type_pay = $rmag_options['type_order_payment'];
                if($type_pay==1||$type_pay==2){

                    $notice .= __('You can pay it now or from your personal account. There you can find out the status of your order.','wp-recall');

                    $payform = rcl_payform(array(
                        'id_pay'=>$order->order_id,
                        'summ'=>$order->order_price,
                        'user_id'=>$order->order_author,
                        'type'=>2,
                        'description'=>sprintf(__('Payment order №%d from %s','wp-recall'),$order->order_id,get_the_author_meta('user_email',$order->order_author))
                    ));

                }else{

                    $notice .= __('You can pay at any time in your personal account. There you can find out the status of your order.','wp-recall');

                }
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
add_action('wp_ajax_nopriv_rcl_all_delete_order', 'rcl_all_delete_order');

/*************************************************
Оплата заказа средствами с личного счета
*************************************************/
function rcl_pay_order_private_account(){
    global $user_ID,$wpdb,$rmag_options,$order;

    rcl_verify_ajax_nonce();

    $order_id = intval($_POST['idorder']);

    if(!$order_id||!$user_ID){
        $log['otvet']=1;
        echo json_encode($log);
        exit;
    }

    $order = rcl_get_order($order_id);

    $oldusercount = rcl_get_user_balance();
    
    $newusercount = $oldusercount - $order->order_price;

    if(!$oldusercount||$newusercount<0){
        $log['error'] = sprintf(__('Insufficient funds in the account!<br>Order price: %d %s','wp-recall'),$order->order_price,rcl_get_primary_currency(1));
        echo json_encode($log);
        exit;
    }

    rcl_update_user_balance($newusercount,$user_ID,sprintf(__('Payment order №%d','wp-recall'),$order_id));

    $result = rcl_update_status_order($order_id,2);

    if(!$result){
        $log['error'] = __('Error','wp-recall');
        echo json_encode($log);
        exit;
    }

    rcl_payment_order($order_id,$user_ID);

    do_action('payment_rcl',$user_ID,$order->order_price,$order_id,2);

    $text = "<p>".__('Your order is successfully paid! The notification has been sent to the administration.','wp-recall')."</p>";

    $text = apply_filters('payment_order_text',$text);

    $log['recall'] = "<div style='clear: both;color:green;font-weight:bold;padding:10px; border:2px solid green;'>".$text."</div>";
    $log['count'] = $newusercount;
    $log['idorder']=$order_id;
    $log['otvet']=100;
    echo json_encode($log);
    exit;
}
add_action('wp_ajax_rcl_pay_order_private_account', 'rcl_pay_order_private_account');

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