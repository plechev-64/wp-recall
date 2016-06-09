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

        $log['data_sumprice'] =  $summ;
        $log['allprod'] = $all;
        $log['empty-content'] = rcl_get_include_template('cart-mini-content.php',__FILE__);

        $log['recall'] = 100;
    }else{
        $log['recall'] = 200; //Отрицательное значение
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
        $price = (!$price) ? 0 : $price;
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
        $log['recall'] = 200; //Отрицательное значение
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
            $log['recall'] = 300;
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
        $log['recall'] = 200; //Отрицательное значение
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

	global $user_ID,$rmag_options,$order;

	if($user_ID){

            include_once 'rcl_order.php';
            $ord = new Rcl_Order();

            $get_fields = get_option( 'rcl_cart_fields' );
            $requared = $ord->chek_requared_fields($get_fields);

            if($requared){

                $false_amount = $ord->chek_amount();

                if(!$false_amount){ //если весь товар в наличии, оформляем заказ

                    $order_id = $ord->get_order_id();

                    $res = $ord->insert_order($order_id);
                    if(!$res){
                        $log['otvet']=1;
                        echo json_encode($log);
                        exit;
                    }

                    $order_custom_field = $ord->insert_detail_order($get_fields);
                    $order = rcl_get_order($order_id);
                    $table_order = rcl_get_include_template('order.php',__FILE__);
                    $ord->send_mail($order_custom_field,$table_order);
                    
                    $notify = __('Your order has been created!','wp-recall').'<br>';
                    

                    if(!$order->order_price){ //Если заказ бесплатный
                        
                        $notify .= sprintf(__('Order granted the status - "%s"','wp-recall'),rcl_get_status_name_order(2)).'<br>';
                        $notify .= __('The order contained only free items','wp-recall').'<br>';
                        $notify .= __('The order is processing','wp-recall');

                    }else{
                        if(function_exists('rcl_payform')){
                            $type_order_payment = $rmag_options['type_order_payment'];
                            if($type_order_payment==1||$type_order_payment==2){
                                
                                $notify .= sprintf(__('Order granted the status - "%s"','wp-recall'),rcl_get_status_name_order(1)).'<br>';
                                $notify .= __('You can pay it now or from your personal account. There you can find out the status of your order.','wp-recall');
                                    
                                $payform = rcl_payform(array(
                                    'id_pay'=>$order_id,
                                    'summ'=>$order->order_price,
                                    'user_id'=>$user_ID,
                                    'type'=>2,
                                    'description'=>sprintf(__('Payment order №%d from %s','wp-recall'),$order_id,get_the_author_meta('user_email',$user_ID))
                                ));

                            }else{
                                
                                $notify .= sprintf(__('Order granted the status - "%s"','wp-recall'),rcl_get_status_name_order(1)).'<br>';
                                $notify .= __('You can pay at any time in your personal account. There you can find out the status of your order.','wp-recall');

                            }
                        }else{
                            
                            $notify .= sprintf(__('Order granted the status - "%s"','wp-recall'),rcl_get_status_name_order(1)).'<br>';
                            $notify .= __('You can monitor the status of your order in your personal account.','wp-recall');

                        }
                    }

                    $notify = apply_filters('notify_new_order',$notify,$order_data);

                    if($payform) 
                        $notify .= $payform;
                    
                    $log['redirectform'] = "<p class='res_confirm' style='border:1px solid #ccc;font-weight:bold;padding:10px;'>".$notify."</p>";
                    $log['otvet']=100;

                } else { //если товар не весь в наличии, формируем сообщение об ошибке и отправляем пользователю

                    foreach($false_amount as $prod_id => $cnt){
                        $error_amount .= sprintf(__('Product Name :%s available %d items .','wp-recall'),'<b>'.get_the_title($prod_id).'</b>',get_post_meta($prod_id, 'amount_product', 1)).'<br>';
                    }

                    $log['otvet']=10;
                    $log['amount'] = "<p class='res_confirm' style='margin-top:20px;color:red;border:1px solid #ccc;font-weight:bold;padding:10px;'>"
                            . __('The order was not created!','wp-recall').'<br>'
                            . __('You may be trying to book a larger quantity than is available.','wp-recall')
                            . '</p>'
                            . $error_amount
                            . '<p>'
                            . __('Please reduce the quantity of goods in order and try to place your order again','wp-recall')
                            . '</p>';
                    echo json_encode($log);
                    exit;
                }
            }else{
                $log['otvet']=5;
                $log['recall'] = '<p style="text-align:center;color:red;">'
                        . __('Please fill in all mandatory fields','wp-recall')
                        . '</p>';
            }
	} else {
		$log['otvet']=1;
	}
        echo json_encode($log);
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
Регистрация пользователя после оформления заказа
*************************************************/
function rcl_register_user_order(){
    global $rmag_options,$wpdb,$order,$rcl_options;
    
    rcl_verify_ajax_nonce();

    $reg_user = ($rmag_options['noreg_order'])? false: true;

    $fio_new_user = sanitize_text_field($_POST['fio_new_user']);
    $email_new_user = sanitize_text_field($_POST['email_new_user']);

    include_once 'rcl_order.php';
    $ord = new Rcl_Order();

    $get_fields = get_option( 'rcl_profile_fields' );
    $get_order_fields = get_option( 'rcl_cart_fields' );

    $req_prof = $ord->chek_requared_fields($get_fields,'profile');
    $req_order = $ord->chek_requared_fields($get_order_fields);
    
    if($email_new_user&&$req_prof&&$req_order){

        $res_email = email_exists( $email_new_user );
        $res_login = username_exists($email_new_user);
        $correctemail = is_email($email_new_user);
        $valid = validate_username($email_new_user);

        if(!$reg_user&&(!$correctemail||!$valid)){
            if(!$valid||!$correctemail){
                $log['error'] = __('You have entered an invalid email!','wp-recall');
                echo json_encode($res);
                exit;
            }
        }

        if($reg_user&&($res_login||$res_email||!$correctemail||!$valid)){

            if(!$valid||!$correctemail){
                $log['error'] .= __('You have entered an invalid email!','wp-recall').'<br>';
            }
            
            if($res_login||$res_email){
                $log['error'] .= __('This email is already used! If this is your email, then log in and proceed with the order.','wp-recall');
            }

        }else{

            $user_id = false;

            if(!$reg_user){
                $user = get_user_by('email', $email_new_user);
                if($user) $user_id = $user->ID;
            }

            if(!$user_id){

                $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );

                $userdata = array(
                    'user_pass'=>$random_password,
                    'user_login'=>$email_new_user,
                    'user_email'=>$email_new_user,
                    'display_name'=>$fio_new_user
                );

                $user_id = rcl_insert_user($userdata);

            }

            if($user_id){

                if($get_fields&&$user_id){
                    $cf = new Rcl_Custom_Fields();
                    $cf->register_user_metas($user_id);
                }

                //Сразу авторизуем пользователя
                if($reg_user&&!$rcl_options['confirm_register_recall']){

                    $creds = array();
                    $creds['user_login'] = $email_new_user;
                    $creds['user_password'] = $random_password;
                    $creds['remember'] = true;
                    $user = wp_signon( $creds, false );
                    $redirect_url = rcl_format_url(get_author_posts_url($user_id),'orders');

                }else{

                    $redirect_url = false;

                }

                $order_id = $ord->get_order_id();

                $results = $ord->insert_order($order_id,$user_id);

                if(!$results){
                    $log['error'] = __('An error has occurred, the order was not created!','wp-recall');
                    echo json_encode($log);
                    exit;
                }

                $order_custom_field = $ord->insert_detail_order($get_order_fields);
                $order = rcl_get_order($order_id);
                $table_order = rcl_get_include_template('order.php',__FILE__);
                $ord->send_mail($order_custom_field,$table_order,$user_id,$creds);

                $notice = ($rcl_options['confirm_register_recall']==1)? '<p class=res_confirm style="color:orange;">'
                        .__('To monitor the status of the order to confirm the specified email!','wp-recall').'<br>'
                        .__('Follow the link in the email sent to','wp-recall').'</p>': '';

                if(!$order->order_price){ //Если заказ бесплатный
                    
                    $notice .= "<p class='res_confirm'>"
                            . __('Your order has been created!','wp-recall')."<br />"
                            . __('The order contained only free items','wp-recall')."<br />"
                            . sprintf(__('Order granted the status - "%s"','wp-recall'),rcl_get_status_name_order(2)).'<br>'
                            . __('The order is processing. In your personal account you can find out the status of your order.','wp-recall')
                            . "</p>";
                    
                    $log['recall'] = $notice;
                    $log['redirect']= $redirect_url;
                    $log['int']=100;
                    echo json_encode($log);
                    exit;
                }

                if(function_exists('rcl_payform')){
                    $type_order_payment = $rmag_options['type_order_payment'];
                    if($type_order_payment==1||$type_order_payment==2){

                        $notice .= "<p class='res_confirm'>"
                                    .__('Your order has been created!','wp-recall')."<br />"
                                    .sprintf(__('Order granted the status - "%s"','wp-recall'),rcl_get_status_name_order(1))."<br />"
                                    .__('You can pay it from his personal account. There you can find out the status of your order.','wp-recall')
                                . "</p>";
                        
                        $notice .= "<p class='res_confirm'>"
                                    .__('All necessary data for authorization on the site have been sent to the specified e-mail','wp-recall')
                                . "</p>";
                        
                        if($type_order_payment==2) $notice .= "<p class='res_confirm'>"
                                    .__('You can fill up your personal account on the site of his private office in the future to pay for their orders through it','wp-recall')
                                . "</p>";

                        if(!$rcl_options['confirm_register_recall']){
                            $notice .= "<p align='center'><a href='".$redirect_url."'>".__('Go to your personal cabinet','wp-recall')."</a></p>";
                            $notice .= rcl_payform(array(
                                    'id_pay'=>$order_id,
                                    'summ'=>$order->order_price,
                                    'user_id'=>$user_id,
                                    'type'=>2,
                                    'description'=>sprintf(__('Payment order №%s from %s','wp-recall'),$order_id,get_the_author_meta('user_email',$user_id))
                                ));
                        }
                        $log['recall'] = $notice;
                        $log['redirect']=0;
                        $log['int']=100;

                    }else{
                        $log['int']=100;
                        $log['redirect']= $redirect_url;
                        $notice .= "<p class=res_confirm>"
                                .__('Your order has been created!','wp-recall')."<br />"
                                .__('Check your email','wp-recall')
                                . "</p>";
                        $log['recall'] = $notice;
                    }
                }else{
                    $log['int']=100;
                    $log['redirect'] = $redirect_url;
                    $notice .= "<p class=res_confirm>"
                                .__('Your order has been created!','wp-recall')."<br />"
                                .__('Check your email','wp-recall')
                                . "</p>";
                    $log['recall'] = $notice;
                }
            }
        }
    }else{
        $log['error'] = __('Please fill in all mandatory fields!','wp-recall');
    }
    echo json_encode($log);
    exit;
}
add_action('wp_ajax_rcl_register_user_order', 'rcl_register_user_order');
add_action('wp_ajax_nopriv_rcl_register_user_order', 'rcl_register_user_order');


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

    if(!$oldusercount){
        $log['otvet']=1;
        $log['recall'] = $order->order_price;
        echo json_encode($log);
        exit;
    }

    $newusercount = $oldusercount - $order->order_price;

    if($newusercount<0){
        $log['otvet']=1;
        $log['recall'] = $order->order_price;
        echo json_encode($log);
        exit;
    }

    rcl_update_user_balance($newusercount,$user_ID,sprintf(__('Payment order №%d','wp-recall'),$order_id));

    $result = rcl_update_status_order($order_id,2);

    if(!$result){
        $log['otvet']=1;
        $log['recall'] = __('Error','wp-recall');
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