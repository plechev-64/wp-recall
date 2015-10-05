<?php
//Устанавливаем перечень статусов
function rcl_get_status_name_order($status_id){
    $sts = array('','Не оплачен','Оплачен','Отправлен','Получен','Закрыт','Корзина');
	$sts = apply_filters('order_statuses',$sts);
    return $sts[$status_id];
}

function rcl_order_ID(){
	global $order;
	echo $order->order_id;
}
function rcl_order_date(){
	global $order;
	echo $order->order_date;
}
function rcl_number_products(){
	global $order;
	echo $order->numberproducts;
}
function rcl_order_price(){
	global $order;
	$price = apply_filters('order_price',$order->order_price,$order);
	echo $price;
}
function rcl_order_status(){
	global $order;
	echo rcl_get_status_name_order($order->order_status);
}
function rcl_product_ID(){
	global $product;
	echo $product->product_id;
}
function rcl_product_permalink(){
	global $product;
	echo get_permalink($product->product_id);
}
function rcl_product_title(){
	global $product;
        echo apply_filters('rcl_product_title',get_the_title($product->product_id));
}
function rcl_product_price(){
	global $product;
	$price = apply_filters('product_price',$product->product_price,$product);
	echo $price;
}
function rcl_product_number(){
	global $product;
	echo $product->numberproduct;
}
function rcl_get_product_summ($product_id=false){
	global $product;
	if($product_id) $product = rcl_get_product($product_id);
	$price = apply_filters('product_summ',$product->summ_price,$product);
	return $price;
}
function rcl_product_summ(){
	global $product;
	echo rcl_get_product_summ();
}
function rcl_get_product($product_id){
	return get_post($product_id);
}
add_filter('product_price','rcl_add_primary_currency_price',10);
add_filter('order_price','rcl_add_primary_currency_price',10);
add_filter('not_null_price','rcl_add_primary_currency_price',10);
function rcl_add_primary_currency_price($price){
	return $price .= ' '.rcl_get_primary_currency(1);
}
//Получаем данные заказа
function rcl_get_order($order_id){
    global $wpdb,$order,$product;
    $orderdata = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."rmag_orders_history WHERE order_id='%d'",$order_id));
    if(!$orderdata) return false;
    return rcl_setup_orderdata($orderdata);
}

//Получаем детали заказа
function rcl_get_order_details($order_id){
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT details_order FROM ".RMAG_PREF."details_orders WHERE order_id='%d'",$order_id));
}
//Получаем все заказы по указанным параметрам
function rcl_get_orders($args){
	global $wpdb;
	$date = array();

	$sql = "SELECT * FROM ".RMAG_PREF ."orders_history";

	$orderby = (isset($args['orderby']))? "ORDER BY ".$args['orderby']:"ORDER BY ID";
	$order = (isset($args['order']))? $args['order']:"DESC";

	if(isset($args['order_id'])) $wheres[] = "order_id IN ('".$args['order_id']."')";
	if(isset($args['user_id'])) $wheres[] = "user_id='".$args['user_id']."'";
	if(isset($args['order_status'])) $wheres[] = "order_status='".$args['order_status']."'";
	if(isset($args['status_not_in'])) $wheres[] = "order_status NOT IN ('".$args['status_not_in']."')";
	if(isset($args['product_id'])) $wheres[] = "product_id IN ('".$args['product_id']."')";
	if(isset($args['year'])) $date[] = $args['year'];
	if(isset($args['month'])) $date[] = $args['month'];

	if($date){
		$date = implode('-',$date);
		$wheres[] = "order_date  LIKE '%$date%'";
	}

        if($wheres){
            if(isset($args['search'])&&$args['search']) $where = implode(' OR ',$wheres);
            else $where = implode(' AND ',$wheres);
        }
	if($where) $sql .= " WHERE ".$where;
	$sql .= " $orderby $order";

	$rdrs = $wpdb->get_results($sql);
	//print_r($rdrs);
	if(!$rdrs) return false;

	foreach($rdrs as $rd){
		$orders[$rd->order_id][] = $rd;
	}

	return $orders;
}

//Удаляем заказ
function rcl_delete_order($order_id){
    global $wpdb;
    do_action('rcl_delete_order',$order_id);
    return $wpdb->query($wpdb->prepare("DELETE FROM ". RMAG_PREF ."orders_history WHERE order_id = '%d'",$order_id));
}

//Обновляем статус заказа
function rcl_update_status_order($order_id,$status,$user_id=false){
    global $wpdb;
    $args = array('order_id' => $order_id);
    if($user_id) $args['user_id'] = $user_id;
    do_action('rcl_update_status_order',$order_id,$status);
    return $wpdb->update( RMAG_PREF ."orders_history", array( 'order_status' => $status), $args );
}
//Вывод краткого описания товара
function rcl_get_product_excerpt($desc){
    global $post;
    if(!$desc) return false;

    $excerpt = strip_tags($post->post_content);

    if($excerpt){
        if(strlen($excerpt) > $desc){
            $excerpt = substr($excerpt, 0, $desc);
            $excerpt = preg_replace('@(.*)\s[^\s]*$@s', '\\1 ...', $excerpt);
        }
    }

    $excerpt = apply_filters('rcl_get_product_excerpt',$excerpt);

    return $excerpt;
}

function rcl_product_excerpt(){
    global $post,$desc;
    echo rcl_get_product_excerpt($desc);
}

function rcl_get_product_category($prod_id){
	$product_cat = get_the_term_list( $prod_id, 'prodcat', '<p class="fa fa-tag product-cat"><b>Категория товара:</b> ', ', ', '</p>' );
	return $product_cat;
}

function rcl_product_category_excerpt($excerpt){
    global $post;
    $excerpt .= rcl_get_product_category($post->ID);
    return $excerpt;
}
//add_filter('rcl_get_product_excerpt','rcl_product_category_excerpt',10);

//Вывод дополнительной валюты сайта
function rcl_get_secondary_currency($type=0){
	global $rmag_options;
	$cur = (isset($rmag_options['secondary_cur']))? $rmag_options['secondary_cur']:'RUB';
	return rcl_get_currency($cur,$type);
}
function rcl_secondary_currency($type=0){
	echo rcl_get_secondary_currency($type);
}

//Цена товара
function rcl_get_number_price($prod_id){
	$price = get_post_meta($prod_id,'price-products',1);
    return apply_filters('rcl_get_number_price',$price,$prod_id);
}

add_filter('rcl_get_number_price','rcl_get_currency_price',10,2);
function rcl_get_currency_price($price,$prod_id){
	global $rmag_options;
	if(!$rmag_options['multi_cur']) return $price;

	$currency = (get_post_meta($prod_id,'type_currency',1))?get_post_meta($prod_id,'type_currency',1):$rmag_options['primary_cur'];
	if($currency==$rmag_options['primary_cur']) return $price;
	$curse = (get_post_meta($prod_id,'curse_currency',1))?get_post_meta($prod_id,'curse_currency',1):$rmag_options['curse_currency'];
	$price = ($curse)? $curse*$price: $price;

	return round($price);
}

add_filter('rcl_get_number_price','rcl_get_margin_product',20,2);
function rcl_get_margin_product($price,$prod_id){
	global $rmag_options;
	$margin = (get_post_meta($prod_id,'margin_product',1))?get_post_meta($prod_id,'margin_product',1):$rmag_options['margin_product'];
	if(!$margin) return $price;
	$price = $price + ($price*$margin/100);
	return round($price);
}

function rcl_get_price($prod_id){
    $price = rcl_get_number_price($prod_id);
	return apply_filters('rcl_get_price',$price,$prod_id);
}

add_filter('rcl_get_price','rcl_filters_price',10,2);
function rcl_filters_price($price,$prod_id){
	if($price) return apply_filters('not_null_price',$price,$prod_id);
    else return apply_filters('null_price',$price,$prod_id);
}

add_filter('null_price','rcl_get_null_price_content',10);
function rcl_get_null_price_content($price){
    return '<span class="price-prod no-price">Бесплатно!</span>';
}

add_filter('not_null_price','rcl_get_not_null_price_content',20);
function rcl_get_not_null_price_content($price){
    return '<span class="price-prod">'.$price.'</span>';
}

function rcl_get_chart_orders($orders){
    global $order,$chartData,$chartArgs;

    if(!$orders) return false;

    $chartArgs = array();
    $chartData = array(
        'title' => 'Динамика доходов',
        'title-x' => 'Период времени',
        'data'=>array(
            array('"Дни/Месяцы"', '"Платежи (шт.)"', '"Доход (тыс.)"')
        )
    );

    foreach($orders as $order){
        rcl_setup_orderdata($order);
        rcl_setup_chartdata($order->order_date,$order->order_price);
    }

    return rcl_get_chart($chartArgs);
}

//Формирование массива данных заказа
function rcl_setup_orderdata($orderdata){
	global $order,$product;

	$order = (object)array(
		'order_id'=>0,
		'order_price'=>0,
		'order_author'=>0,
		'order_status'=>6,
		'numberproducts'=>0,
		'order_date'=>false,
		'products'=>array()
	);

	foreach($orderdata as $data){ rcl_setup_productdata($data);
		//print_r($product);
		if(!$order->order_id) $order->order_id = $product->order_id;
		if(!$order->order_author) $order->order_author = $product->user_id;
		if(!$order->order_date) $order->order_date = $product->order_date;
		$order->order_price += $product->summ_price;
		$order->numberproducts += $product->numberproduct;
		if($product->order_status<$order->order_status) $order->order_status = $product->order_status;
		$order->products[] = $product;
	}

	return $order;
}
function rcl_setup_productdata($productdata){
	global $product;

	$product = (object)array(
		'product_id'=>$productdata->product_id,
		'product_price'=>$productdata->product_price,
		'summ_price'=>$productdata->product_price*$productdata->numberproduct,
		'numberproduct'=>$productdata->numberproduct,
		'user_id'=>$productdata->user_id,
		'order_id'=>$productdata->order_id,
		'order_date'=>$productdata->order_date,
		'order_status'=>$productdata->order_status
	);

	return $product;
}
function rcl_setup_cartdata($productdata){
	global $product,$CartData;

	$price = $CartData->cart[$productdata->ID]['price'];
	$numprod = $CartData->cart[$productdata->ID]['number'];
	$product_price = $price * $numprod;
	$price = apply_filters('cart_price_product',$price,$productdata->ID);

	$product = (object)array(
		'product_id'=>$productdata->ID,
		'product_price'=>$CartData->cart[$productdata->ID]['price'],
		'summ_price'=>$price,
		'numberproduct'=>$CartData->cart[$productdata->ID]['number']
	);

	return $product;
}

add_action('insert_pay_rcl','rcl_add_payment_order');
function rcl_add_payment_order($pay){
    if($pay->pay_type!=2) return false;
    rcl_payment_order($pay->pay_id);
}

function rcl_payment_order($order_id,$user_id=false){
    global $wpdb,$order,$rmag_options;

    $order = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."rmag_orders_history WHERE order_id='%d'",$order_id));
    rcl_setup_orderdata($order);

    if(!$user_id) $user_id = $order->order_author;

    rcl_remove_reserve($order_id);

    rcl_update_status_order($order_id,2);

    //Если работает реферальная система и партнеру начисляются проценты с покупок его реферала
    if(function_exists('add_referall_incentive_order'))
            add_referall_incentive_order($user_id,$order->order_price);

    $get_fields = get_option( 'custom_profile_field' );

    if($get_fields){
        $cf = new Rcl_Custom_Fields();

        foreach((array)$get_fields as $custom_field){
            $slug = $custom_field['slug'];
            $meta = get_the_author_meta($slug,$user_id);
            $show_custom_field .= $cf->get_field_value($custom_field,$meta);
        }
    }

    $table_order = rcl_get_include_template('order.php',__FILE__);

    $args = array(
            'role' => 'administrator'
    );
    $users = get_users( $args );

    $subject = 'Заказ №'.$order->order_id.' оплачен!';

    $admin_email = $rmag_options['admin_email_magazin_recall'];

	$text = '';

	$text = apply_filters('payment_mail_text',$text);

	//print_r($text);exit;

    $textmail = '
    <p>Пользователь оплатил заказ в магазине "'.get_bloginfo('name').'".</p>
    <h3>Информация о пользователе:</h3>
    <p><b>Имя</b>: '.get_the_author_meta('display_name',$user_id).'</p>
    <p><b>Email</b>: '.get_the_author_meta('user_email',$user_id).'</p>
    '.$show_custom_field.'
    <p>Заказ №'.$order_id.' получил статус "Оплачено".</p>
    <h3>Детали заказа:</h3>
    '.$table_order.'
	'.$text.'
    <p>Ссылка для управления заказом в админке:</p>
    <p>'.admin_url('admin.php?page=manage-rmag&order-id='.$order_id).'</p>';

    if($admin_email){
        rcl_mail($admin_email, $subject, $textmail);
    }else{
        foreach((array)$users as $userdata){
                $email = $userdata->user_email;
                rcl_mail($email, $subject, $textmail);
        }
    }

    $email = get_the_author_meta('user_email',$user_id);
    $textmail = '
    <p>Вы оплатили заказ в магазине "'.get_bloginfo('name').'" средствами со своего личного счета.</p>
    <h3>Информация о покупателе:</h3>
    <p><b>Имя</b>: '.get_the_author_meta('display_name',$user_id).'</p>
    <p><b>Email</b>: '.get_the_author_meta('user_email',$user_id).'</p>
    '.$show_custom_field.'
    <p>Заказ №'.$order_id.' получил статус "Оплачено".</p>
    <h3>Детали заказа:</h3>
    '.$table_order.'
	'.$text.'
    <p>Ваш заказ оплачен и поступил в обработку. Вы можете следить за сменой его статуса из своего личного кабинета</p>';
    rcl_mail($email, $subject, $textmail);

    do_action('payorder_user_count_rcl',$user_id,$order->order_price,'Оплата заказа №'.$order_id,1);
}