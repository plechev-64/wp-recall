<?php
/*************************************************
Добавление товара в миникорзину
*************************************************/
function rcl_add_minicart(){
    global $rmag_options,$CartData;
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

	global $user_ID,$rmag_options,$order;

	if($user_ID){

            include_once 'rcl_order.php';
            $ord = new Rcl_Order();

            $get_fields = get_option( 'custom_orders_field' );
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

                    if(!$order->order_price){ //Если заказ бесплатный
                            $notify = "Ваш заказ был создан!<br />"
                                . "Заказ содержал только бесплатные товары<br>"
                                . "Заказу присвоен статус - \"Оплачено\"<br>"
                                . "Заказ поступил в обработку. Ссылки на заказанные файлы будут высланы письмом на вашу почту.";

                    }else{
                        if(function_exists('rcl_payform')){
                            $type_order_payment = $rmag_options['type_order_payment'];
                            if($type_order_payment==1||$type_order_payment==2){
                                    $notify = "Ваш заказ был создан!<br />"
                                            . "Заказу присвоен статус - \"Неоплачено\"<br />"
                                            . "Вы можете оплатить его сейчас или из своего личного кабинета. "
                                            . "Там же вы можете узнать статус вашего заказа.<br />";
                                    $payform = rcl_payform(array('id_pay'=>$order_id,'summ'=>$order->order_price,'user_id'=>$user_ID,'type'=>2));

                            }else{
                                $notify = "Ваш заказ был создан!<br />"
                                        . "Заказу присвоен статус - \"Неоплачено\"<br />"
                                        . "Вы можете оплатить его в любое время в своем личном кабинете. "
                                        . "Там же вы можете узнать статус вашего заказа.";
                                //$log['redirectform'] = apply_filters('notify_new_order',$notify);
                            }
                        }else{
                            $notify = "Ваш заказ был создан!<br />"
                                    . "Заказу присвоен статус - \"Неоплачено\"<br />"
                                    . "Вы можете следить за статусом своего заказа в своем личном кабинете.";
                        }
                    }

                    $notify = apply_filters('notify_new_order',$notify,$order_data);

                    if($payform) $notify .= $payform;
                    $log['redirectform'] = "<p class='res_confirm' style='border:1px solid #ccc;font-weight:bold;padding:10px;'>".$notify."</p>";
                    $log['otvet']=100;

                } else { //если товар не весь в наличии, формируем сообщение об ошибке и отправляем пользователю

                    foreach($false_amount as $prod_id => $cnt){
                            $error_amount .= 'Наименование товара: <b>'.get_the_title($prod_id).' доступно '.get_post_meta($prod_id, 'amount_product', 1).' шт.</b>';
                    }

                    $log['otvet']=10;
                    $log['amount'] = "<p class='res_confirm' style='margin-top:20px;color:red;border:1px solid #ccc;font-weight:bold;padding:10px;'>"
                            . "Заказ не был создан!<br />"
                            . "Возможно вы пытаетесь зарезервировать большее количество товара, чем есть в наличии.</p>"
                            . "".$error_amount.""
                            . "<p>Пожалуйста уменьшите количество товара в заказе и попробуйте оформить заказ снова.</p>";
                    echo json_encode($log);
                    exit;
                }
            }else{
                $log['otvet']=5;
                $log['recall'] = '<p style="text-align:center;color:red;">'
                        . 'Пожалуйста, заполните все обязательные поля!'
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

			switch($status){
				case 1: $status = 'Не оплачен'; break;
				case 2: $status = 'Оплачен'; break;
				case 3: $status = 'В обработке'; break;
				case 4: $status = 'Отправлен'; break;
				case 5: $status = 'Закрыт'; break;
				case 6: $status = 'Корзина'; break;
			}



			$log['otvet']=100;
			$log['order']=$order;
			$log['status']=$status;
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
function rcl_delete_trash_order(){
    global $user_ID;
    global $wpdb;
    global $rmag_options;
    $idorder = intval($_POST['idorder']);

    if($idorder&&$user_ID){

        rcl_remove_reserve($idorder,1);

        //убираем заказ в корзину
        $res = rcl_update_status_order($idorder,6,$user_ID);

        if($res){
            $log['otvet']=100;
            $log['idorder']=$idorder;
            $log['content']='<h3>Заказ №'.$idorder.' был удален.</h3>';
        }

    } else {
            $log['otvet']=1;
    }
    echo json_encode($log);
    exit;
}
add_action('wp_ajax_rcl_delete_trash_order', 'rcl_delete_trash_order');
/*************************************************
Полное удаление заказа
*************************************************/
function rcl_all_delete_order(){
	global $user_ID;
	global $wpdb;
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

    $reg_user = ($rmag_options['noreg_order'])? false: true;

    $fio_new_user = sanitize_text_field($_POST['fio_new_user']);
    $email_new_user = sanitize_email($_POST['email_new_user']);

    include_once 'rcl_order.php';
    $ord = new Rcl_Order();

    $get_fields = get_option( 'custom_profile_field' );
    $get_order_fields = get_option( 'custom_orders_field' );

    $req_prof = $ord->chek_requared_fields($get_fields,'profile');
    $req_order = $ord->chek_requared_fields($get_order_fields);

	if($email_new_user&&$req_prof&&$req_order){

            $res_email = email_exists( $email_new_user );
            $res_login = username_exists($email_new_user);
            $correctemail = is_email($email_new_user);
            $valid = validate_username($email_new_user);

            if(!$reg_user&&(!$correctemail||!$valid)){
		if(!$valid||!$correctemail){
                    $log['int']=1;
                    $log['recall'] = '<p style="text-align:center;color:red;">Вы ввели некорректный email!</p>';
                    echo json_encode($res);
                    exit;
                }
            }

            //var_dump($reg_user);exit;
            if($reg_user&&($res_login||$res_email||!$correctemail||!$valid)){

                if(!$valid||!$correctemail){
                    $log['int']=1;
                    $log['recall'] .= '<p style="text-align:center;color:red;">Вы ввели некорректный email!</p>';
                }
                if($res_login||$res_email){
                    $log['int']=1;
                    $log['recall'] .= '<p style="text-align:center;color:red;">Этот email уже используется!<br>'
                            . 'Если это ваш email, то авторизуйтесь и продолжите оформление заказа.</p>';
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
                            'user_pass' => $random_password //обязательно
                            ,'user_login' => $email_new_user //обязательно
                            ,'user_nicename' => ''
                            ,'user_email' => $email_new_user
                            ,'display_name' => $fio_new_user
                            ,'nickname' => $email_new_user
                            ,'first_name' => $fio_new_user
                            ,'rich_editing' => 'true'  // false - выключить визуальный редактор для пользователя.
                        );

                        $user_id = wp_insert_user( $userdata );

                        $wpdb->insert( $wpdb->prefix .'user_action', array( 'user' => $user_id, 'time_action' => '' ));
                }

		if($user_id){

                    if($get_fields&&$user_id){
                        $cf = new Rcl_Custom_Fields();
                        $cf->register_user_metas($user_id);
                    }

                    $creds = array();
                    $creds['user_login'] = $email_new_user;
                    $creds['user_password'] = $random_password;

                    //Сразу авторизуем пользователя
                    if($reg_user&&!$rcl_options['confirm_register_recall']){

                        $creds['remember'] = true;
                        $user = wp_signon( $creds, false );

                        $redirect_url = rcl_format_url(get_author_posts_url($user_id),'orders');

                    }else{

                        if($rcl_options['confirm_register_recall']==1) wp_update_user( array ('ID' => $user_id, 'role' => 'need-confirm') ) ;
                        $redirect_url = false;

                    }

                    $order_id = $ord->get_order_id();

                    $results = $ord->insert_order($order_id,$user_id);

                    if(!$results){
                        $log['int']=1;
                        $log['recall'] = '<p style="text-align:center;color:red;">Возникла ошибка, заказ не был создан!</p>';
                        echo json_encode($log);
                        exit;
                    }

                    $order_custom_field = $ord->insert_detail_order($get_order_fields);
                    $order = rcl_get_order($order_id);
                    $table_order = rcl_get_include_template('order.php',__FILE__);
                    $ord->send_mail($order_custom_field,$table_order,$user_id,$creds);

                    $notice = ($rcl_options['confirm_register_recall']==1)? '<p class=res_confirm style="color:orange;">Для отслеживания статуса заказа подтвердите указанный email!<br>'
                                . 'Перейдите по ссылке в высланном письме.</p>': '';

                    if(!$order->order_price){ //Если заказ бесплатный
                        $notice .= "<p class='res_confirm'>Ваш заказ был создан!<br />"
                                . "Заказ содержал только бесплатные товары<br>"
                                . "Заказу присвоен статус - \"Оплачено\"<br>"
                                . "Заказ поступил в обработку. В своем личном кабинете вы можете узнать статус вашего заказа.</p>";
                        $log['recall'] = $notice;
                        $log['redirect']= $redirect_url;
                        $log['int']=100;
                        echo json_encode($log);
                        exit;
                    }

                    if(function_exists('rcl_payform')){
                        $type_order_payment = $rmag_options['type_order_payment'];
                        if($type_order_payment==1||$type_order_payment==2){

                            $notice .= "<p class='res_confirm'>Ваш заказ был создан!<br />Заказу присвоен статус - \"Неоплачено\"<br />Вы можете оплатить его сейчас или из своего ЛК. Там же вы можете узнать статус вашего заказа.</p>";
                            if($type_order_payment==2) $notice .= "<p class='res_confirm'>Вы можете пополнить свой личный счет на сайте из своего личного кабинета и в будущем оплачивать свои заказы через него</p>";

                            if(!$rcl_options['confirm_register_recall']){
                                $notice .= "<p align='center'><a href='".$redirect_url."'>Перейти в свой личный кабинет</a></p>";
                                $notice .= rcl_payform(array('id_pay'=>$order_id,'summ'=>$order->order_price,'user_id'=>$user_id,'type'=>2));
                            }
                            $log['recall'] = $notice;
                            $log['redirect']=0;
                            $log['int']=100;

                        }else{
                            $log['int']=100;
                            $log['redirect']= $redirect_url;
                            $notice .= "<p class=res_confirm>Ваш заказ был создан!<br />Проверьте свою почту.</p>";
                            $log['recall'] = $notice;
                        }
                    }else{
                        $log['int']=100;
                        $log['redirect'] = $redirect_url;
                        $notice .= '<p class=res_confirm>Ваш заказ был создан!<br />Проверьте свою почту.</p>';
                        $log['recall'] = $notice;
                    }
                }
            }
	}else{
            $log['int']=1;
            $log['recall'] = '<p style="text-align:center;color:red;">Пожалуйста, заполните все обязательные поля!</p>';
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
	$order_id = intval($_POST['idorder']);

	if(!$order_id||!$user_ID){
		$log['otvet']=1;
		echo json_encode($log);
		exit;
	}

	$order = rcl_get_order($order_id);
	//rcl_setup_orderdata($order);

	$oldusercount = rcl_get_user_money();

	if(!$oldusercount){
		$log['otvet']=1;
		$log['recall'] = $order->order_price;
		echo json_encode($log);
		exit;
	}

	//print_r($order);

	$newusercount = $oldusercount - $order->order_price;

	if($newusercount<0){
		$log['otvet']=1;
		$log['recall'] = $order->order_price;
		echo json_encode($log);
		exit;
	}

	rcl_update_user_money($newusercount);

	$result = rcl_update_status_order($order_id,2);

	if(!$result){
		$log['otvet']=1;
		$log['recall'] = 'Ошибка запроса!';
		echo json_encode($log);
		exit;
	}

	rcl_payment_order($order_id,$user_ID);

    do_action('payment_rcl',$user_ID,$order->order_price,$order_id,2);

	$text = "<p>Ваш заказ успешно оплачен! Соответствующее уведомление было выслано администрации сервиса.</p>";

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
    $price = intval($_POST['price']);
    if(isset($price)){
            update_post_meta($id_post,'price-products',$price);
            $log['otvet']=100;
    }else {
            $log['otvet']=1;
    }
    echo json_encode($log);
    exit;
}
if(is_admin()) add_action('wp_ajax_rcl_edit_price_product', 'rcl_edit_price_product');
?>