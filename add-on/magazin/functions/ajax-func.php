<?php
/*************************************************
Добавление товара в миникорзину
*************************************************/
function add_in_minibasket_recall(){
    global $rmag_options,$CartData;
    $id_post = $_POST['id_post'];
    $number = $_POST['number'];
    if(!$number||$number==''||$number==0) $number=1;
    if($number>=0){
        $cnt = (!isset($_SESSION['cart'][$id_post]))? $number : $_SESSION['cart'][$id_post]['number'] + $number;            
        $_SESSION['cart'][$id_post]['number'] = $cnt;

        $price = get_number_price($id_post);
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
        $log['empty-content'] = get_include_template_rcl('cart-mini-content.php',__FILE__);

        $log['recall'] = 100;
    }else{
        $log['recall'] = 200; //Отрицательное значение
    }
	
    echo json_encode($log);	
    exit;
}
add_action('wp_ajax_add_in_minibasket_recall', 'add_in_minibasket_recall');
add_action('wp_ajax_nopriv_add_in_minibasket_recall', 'add_in_minibasket_recall');
/*************************************************
Добавление товара в корзину
*************************************************/
function add_in_basket_recall(){

    $id_post = $_POST['id_post'];
    $number = $_POST['number'];
    if(!$number||$number==''||$number==0) $number=1;
    if($number>=0){
        $cnt = (!isset($_SESSION['cart'][$id_post]))? $number : $_SESSION['cart'][$id_post]['number'] + $number;            
        $_SESSION['cart'][$id_post]['number'] = $cnt;
        
        $price = get_number_price($id_post);
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
add_action('wp_ajax_add_in_basket_recall', 'add_in_basket_recall');
add_action('wp_ajax_nopriv_add_in_basket_recall', 'add_in_basket_recall');	
/*************************************************
Уменьшаем товар в корзине
*************************************************/
function remove_out_basket_recall(){

    $id_post = $_POST['id_post'];
    $number = $_POST['number'];
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
add_action('wp_ajax_remove_out_basket_recall', 'remove_out_basket_recall');
add_action('wp_ajax_nopriv_remove_out_basket_recall', 'remove_out_basket_recall');
/*************************************************
Подтверждение заказа
*************************************************/
function confirm_order_recall(){
	 
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
                    $ord->insert_order($order_id);					
                    $order_custom_field = $ord->insert_detail_order($get_fields);			
                    $order = get_order($order_id);     
                    $table_order = get_include_template_rcl('order.php',__FILE__);
                    $ord->send_mail($order_custom_field,$table_order);	
                    
                    if(!$order->order_price){ //Если заказ бесплатный
                        $log['redirectform'] = "<p class='res_confirm' style='border:1px solid #ccc;font-weight:bold;padding:10px;'>Ваш заказ был создан!<br />"
                                . "Заказ содержал только бесплатные товары<br>"
                                . "Заказу присвоен статус - \"Оплачено\"<br>"
                                . "Заказ поступил в обработку. В своем личном кабинете вы можете узнать статус вашего заказа.</p>";
                        
                    }else{
                        if(function_exists('rcl_payform')){
                            $type_order_payment = $rmag_options['type_order_payment'];
                            if($type_order_payment==1||$type_order_payment==2){
                                    $notify = "Ваш заказ был создан!<br />"
                                            . "Заказу присвоен статус - \"Неоплачено\"<br />"
                                            . "Вы можете оплатить его сейчас или из своего личного кабинета. "
                                            . "Там же вы можете узнать статус вашего заказа.";
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

                    for($i=1;$i<=$_POST['count'];$i++){
                        if($false_amount[$i]){
                                $error_amount .= '<p>Наименование товара: <b>'.get_the_title($_POST['idprod_'.$i]).' доступно '.get_post_meta($_POST['idprod_'.$i], 'amount_product', 1).' шт.</b></p>';
                        } 

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
add_action('wp_ajax_confirm_order_recall', 'confirm_order_recall');
add_action('wp_ajax_nopriv_confirm_order_recall', 'confirm_order_recall');
/*************************************************
Смена статуса заказа
*************************************************/
function select_status_order_recall(){
	global $user_ID,$rmag_options,$wpdb;
	
	$order = $_POST['order'];
	$status = $_POST['status'];

	if($_POST['order']){
            
		$oldstatus = $wpdb->get_var("SELECT status FROM ".RMAG_PREF."orders_history WHERE inv_id='$order'");
		
		$res = update_status_order($order,$status);
                
		if($res){
		
			if($oldstatus==1&&$status==6){
				remove_reserve_product($order,1);
			}else{
				remove_reserve_product($order);
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
add_action('wp_ajax_select_status_order_recall', 'select_status_order_recall');

/*************************************************
Удаление заказа в корзину
*************************************************/
function delete_order_in_trash_recall(){
	global $user_ID;
	global $wpdb;
	global $rmag_options;
	$idorder = $_POST['idorder'];

	if($idorder){
	
            remove_reserve_product($idorder,1);

            //убираем заказ в корзину
            $res = update_status_order($idorder,6,$user_ID);

            if($res){
                    $log['otvet']=100;
                    $log['idorder']=$idorder;
                    //$log['otvet']=100;
            }
            
	} else {
		$log['otvet']=1;
	}
	echo json_encode($log);		
	exit;
}
add_action('wp_ajax_delete_order_in_trash_recall', 'delete_order_in_trash_recall');
add_action('wp_ajax_nopriv_delete_order_in_trash_recall', 'delete_order_in_trash_recall');	
/*************************************************
Полное удаление заказа
*************************************************/
function all_delete_order_recall(){
	global $user_ID;
	global $wpdb;
	$idorder = $_POST['idorder'];

	if($idorder){
            $res = delete_order($idorder);

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
add_action('wp_ajax_all_delete_order_recall', 'all_delete_order_recall');
add_action('wp_ajax_nopriv_all_delete_order_recall', 'all_delete_order_recall');

/*************************************************
Регистрация пользователя после оформления заказа
*************************************************/
function add_new_user_in_order(){
	global $rmag_options,$wpdb,$order;
	
	$reg_user = ($rmag_options['noreg_order'])? false: true;
        
	$fio_new_user = $_POST['fio_new_user'];	
	$email_new_user = $_POST['email_new_user'];
        
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
                    $res['int']=1;
                    $res['recall'] = '<p style="text-align:center;color:red;">Вы ввели некорректный email!</p>';
					echo json_encode($res);
					exit;
                }
			}
			
            //var_dump($reg_user);exit;
            if($reg_user&&($res_login||$res_email||!$correctemail||!$valid)){
		
                if(!$valid||!$correctemail){
                    $res['int']=1;
                    $res['recall'] .= '<p style="text-align:center;color:red;">Вы ввели некорректный email!</p>';
                }
                if($res_login||$res_email){
                    $res['int']=1;
                    $res['recall'] .= '<p style="text-align:center;color:red;">Этот email уже используется!</p>';
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

                    //Сразу авторизуем пользователя
					if($reg_user){
						$creds = array();
						$creds['user_login'] = $email_new_user;
						$creds['user_password'] = $random_password;
						$creds['remember'] = true;
						$user = wp_signon( $creds, false );
						
						$redirect_url = get_redirect_url_rcl(get_author_posts_url($user_id),'order');
					}else{
						$redirect_url = false;
					}
                    //Начинаем обработку его заказа

                    //if($_POST['count']){

                    $order_id = $ord->get_order_id();
                    $ord->insert_order($order_id,$user_id);
                    $order_custom_field = $ord->insert_detail_order($get_order_fields);
                    //$show_custom_field = $ord->detail_order($get_fields,$user_id);
                    $order = get_order($order_id);
                    $table_order = get_include_template_rcl('order.php',__FILE__);
                    $ord->send_mail($order_custom_field,$table_order,$user_id,$creds);

                    //}
                    
                    if(!$order->order_price){ //Если заказ бесплатный
                        $log['recall'] = "<p class='res_confirm' style='border:1px solid #ccc;font-weight:bold;padding:10px;'>Ваш заказ был создан!<br />"
                                . "Заказ содержал только бесплатные товары<br>"
                                . "Заказу присвоен статус - \"Оплачено\"<br>"
                                . "Заказ поступил в обработку. В своем личном кабинете вы можете узнать статус вашего заказа.</p>"; 
                        $res['redirect']= $redirect_url;
                        $res['int']=100;
                        echo json_encode($res);
                        exit;
                    }

                    if(function_exists('rcl_payform')){
                        $type_order_payment = $rmag_options['type_order_payment'];
                        if($type_order_payment==1||$type_order_payment==2){

                            $res['recall'] = "
                            <p class='res_confirm' style='border:1px solid #ccc;font-weight:bold;padding:10px;'>Ваш заказ был создан!<br />Заказу присвоен статус - \"Неоплачено\"<br />Вы можете оплатить его сейчас или из своего ЛК. Там же вы можете узнать статус вашего заказа.</p>";
                            if($type_order_payment==2) $res['recall'] .= "
                            <p class='res_confirm' style='border:1px solid #ccc;font-weight:bold;padding:10px;'>Вы можете пополнить свой личный счет на сайте из своего личного кабинета и в будущем оплачивать свои заказы через него</p>
                            <p align='center'><a href='".$redirect_url."'>Перейти в свой личный кабинет</a></p>";

                            $res['recall'] .= rcl_payform(array('id_pay'=>$order_id,'summ'=>$order->order_price,'user_id'=>$user_id,'type'=>2));
                            $res['redirect']=0;
                            $res['int']=100;

                        }else{						
                            $res['int']=100;
                            $res['redirect']= $redirect_url;		
                            $res['recall']='<p style="text-align:center;color:green;">Ваш заказ был создан!<br />Проверьте свою почту.</p>';
                        }
                    }else{
                        $res['int']=100;
                        $res['redirect']= $redirect_url;
                        $res['recall']='<p style="text-align:center;color:green;">Ваш заказ был создан!<br />Проверьте свою почту.</p>';
                    }
                }						
            }
	}else{
            $res['int']=1;
            $res['recall'] = '<p style="text-align:center;color:red;">Пожалуйста, заполните все обязательные поля!</p>';		
        } 
	echo json_encode($res);
	exit;
}
add_action('wp_ajax_add_new_user_in_order', 'add_new_user_in_order');
add_action('wp_ajax_nopriv_add_new_user_in_order', 'add_new_user_in_order');


/*************************************************
Оплата заказа средствами с личного счета
*************************************************/
function pay_order_in_count_recall(){
	global $user_ID;
	global $wpdb;
	global $rmag_options;
	$inv_id = $_POST['idorder'];

	if(!$inv_id||!$user_ID){
		$log['otvet']=1;
		echo json_encode($log);
		exit;	
	}
	
	$order_data = get_order($inv_id);

	$summa_order = 0;
	foreach((array)$order_data as $sumproduct){
		$summa_product = "$sumproduct->count"*$sumproduct->price;
		$summa_order = $summa_order + $summa_product; 
	}
	
	$oldusercount = get_user_money();

	if(!$oldusercount){
		$log['otvet']=1;
		$log['recall'] = $summa_order;
		echo json_encode($log);
		exit;		
	}
			
	$newusercount = $oldusercount - $summa_order;
				
	if($newusercount<0){
		$log['otvet']=1;
		$log['recall'] = $summa_order;
		echo json_encode($log);
		exit;				
	}
		
	update_user_money($newusercount);
		
	$result = update_status_order($inv_id,2);
								
	if(!$result){
		$log['otvet']=1;
		$log['recall'] = 'Ошибка запроса!';
		echo json_encode($log);
		exit;
	}
        
    $order = get_order($inv_id);
	
	remove_reserve_product($inv_id);							
		
	//Если работает реферальная система и партнеру начисляются проценты с покупок его реферала
	if(function_exists('add_referall_incentive_order')) 
		add_referall_incentive_order($user_ID,$summa_order);
					
	$get_fields = get_option( 'custom_profile_field' );
        
	$cf = new Rcl_Custom_Fields();
        
	foreach((array)$get_fields as $custom_field){				
		$slug = $custom_field['slug'];
		$meta = get_the_author_meta($slug,$user_ID);
		$show_custom_field .= $cf->get_field_value($custom_field,$meta);
	}	
	
	$table_order = get_include_template_rcl('order.php',__FILE__);	
						
	$args = array(
		'role' => 'administrator'
	);
	$users = get_users( $args );
        
	$subject = 'Заказ оплачен!';
        
        $admin_email = $rmag_options['admin_email_magazin_recall'];
        
        $textmail = '
        <p>Пользователь оплатил заказ в магазине "'.get_bloginfo('name').'" средствами со своего личного счета.</p>
        <h3>Информация о пользователе:</h3>
        <p><b>Имя</b>: '.get_the_author_meta('display_name',$user_ID).'</p>
        <p><b>Email</b>: '.get_the_author_meta('user_email',$user_ID).'</p>
        '.$show_custom_field.'
        <p>Заказ №'.$inv_id.' получил статус "Оплачено".</p>
        <h3>Детали заказа:</h3>
        '.$table_order.'
        <p>Ссылка для управления заказом в админке:</p>  
        <p>'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=manage-rmag&order='.$inv_id.'</p>';
            
        if($admin_email){
            rcl_mail($admin_email, $subject, $textmail);
	}else{
            foreach((array)$users as $userdata){
                    $email = $userdata->user_email;									
                    rcl_mail($email, $subject, $textmail);
            }
	}
	
	$email = get_the_author_meta('user_email',$user_ID);				
	$textmail = '
	<p>Вы оплатили заказ в магазине "'.get_bloginfo('name').'" средствами со своего личного счета.</p>
	<h3>Информация о покупателе:</h3>
	<p><b>Имя</b>: '.get_the_author_meta('display_name',$user_ID).'</p>
	<p><b>Email</b>: '.get_the_author_meta('user_email',$user_ID).'</p>
	'.$show_custom_field.'
	<p>Заказ №'.$inv_id.' получил статус "Оплачено".</p>
	<h3>Детали заказа:</h3>
	'.$table_order.'
	<p>Ваш заказ оплачен и поступил в обработку. Вы можете следить за сменой его статуса из своего личного кабинета</p>';				
	rcl_mail($email, $subject, $textmail);

	do_action('payorder_user_count_rcl',$user_ID,$order->order_price,'Оплата заказа №'.$inv_id.' средствами с личного счета',1);
        
    do_action('payment_rcl',$user_ID,$order->order_price,$inv_id,2);
		
	$log['recall'] = "<p style='color:green;font-weight:bold;padding:10px; border:2px solid green;'>Ваш заказ успешно оплачен! Соответствующее уведомление было выслано администрации сервиса.</p>";
	$log['count'] = $newusercount;
	$log['idorder']=$inv_id;
	$log['otvet']=100;
	echo json_encode($log);
	exit;	
}
add_action('wp_ajax_pay_order_in_count_recall', 'pay_order_in_count_recall');

function edit_price_product_rcl(){
    $id_post = $_POST['id_post'];
    $price = $_POST['price'];
    if(isset($price)){		
            update_post_meta($id_post,'price-products',$price);
            $log['otvet']=100;	
    }else {
            $log['otvet']=1;
    }
    echo json_encode($log);	
    exit;
}
if(is_admin()) add_action('wp_ajax_edit_price_product_rcl', 'edit_price_product_rcl');
?>