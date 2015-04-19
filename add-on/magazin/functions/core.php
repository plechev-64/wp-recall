<?php
//Устанавливаем перечень статусов
function get_status_name($status_id){
    $sts = array('','Не оплачен','Оплачен','Отправлен','Получен','Закрыт','Корзина');
	$sts = apply_filters('order_statuses',$sts);
    return $sts[$status_id];
}

//Перечень действующих валют
function get_currency_list(){
	return array(
		'RUB' => array('рублей','руб.','р.'),
		'UAH' => array('гривен','грн.','грн.'),
		'USD' => array('долларов','долл.','долл.'),
		'EUR' => array('евро','евр.','евр.'),
	);
}

function the_order_ID(){
	global $order;
	echo $order->order_id;
}
function the_order_date(){
	global $order;
	echo $order->order_date;
}
function the_number_products(){
	global $order;
	echo $order->numberproducts;
}
function the_order_price(){
	global $order;
	$price = apply_filters('order_price',$order->order_price,$order);
	echo $price;
}
function the_order_status(){
	global $order;
	echo get_status_name($order->order_status);
}
function the_product_ID(){
	global $product;
	echo $product->product_id;
}
function the_product_permalink(){
	global $product;
	echo get_permalink($product->product_id);
}
function the_product_title(){
	global $product;
	echo get_the_title($product->product_id);
}
function the_product_price(){
	global $product;
	$price = apply_filters('product_price',$product->product_price,$product);
	echo $price;
}
function the_product_number(){
	global $product;
	echo $product->numberproduct;
}
function get_product_summ($product_id=false){
	global $product;
	if($product_id) $product = get_product($product_id);
	$price = apply_filters('product_summ',$product->summ_price,$product);
	return $price;
}
function the_product_summ(){
	global $product;
	echo get_product_summ();
}
function get_product($product_id){
	return get_post($product_id);
}
add_filter('product_price','add_primary_currency_price',10);
add_filter('order_price','add_primary_currency_price',10);
add_filter('not_null_price','add_primary_currency_price',10);
function add_primary_currency_price($price){
	return $price .= ' '.get_primary_currency(1);
}
//Получаем данные заказа
function get_order($order_id){
    global $wpdb,$order,$product;
	$orderdata = $wpdb->get_results("SELECT * FROM ".RMAG_PREF."orders_history WHERE order_id='$order_id'");
	if(!$orderdata) return false;	
	return setup_orderdata($orderdata);
}

//Получаем детали заказа
function get_order_details($order_id){
    global $wpdb;
    return $wpdb->get_var("SELECT details_order FROM ".RMAG_PREF."details_orders WHERE order_id='$order_id'");
}
//Получаем все заказы по указанным параметрам
function get_orders($args){
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
	
	if($wheres) $where = implode(' AND ',$wheres);
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
function delete_order($order_id){
    global $wpdb;
    do_action('delete_order',$order_id);
    return $wpdb->query("DELETE FROM ". RMAG_PREF ."orders_history WHERE order_id = '$order_id'");
}

//Обновляем статус заказа
function update_status_order($order_id,$status,$user_id=false){
    global $wpdb;
    $args = array('order_id' => $order_id);
    if($user_id) $args['user_id'] = $user_id;
    do_action('update_status_order',$order_id,$status);
    return $wpdb->update( RMAG_PREF ."orders_history", array( 'order_status' => $status), $args );
}
//Вывод краткого описания товара
function get_product_excerpt($desc){
    global $post;
    if(!$desc) return false;

    $excerpt = strip_tags($post->post_content);

    if(!$excerpt){       
        if(strlen($excerpt) > $desc){
            $excerpt = substr($excerpt, 0, $desc);
            $excerpt = preg_replace('@(.*)\s[^\s]*$@s', '\\1 ...', $excerpt);
        }
    }
    
    $excerpt = apply_filters('get_product_excerpt',$excerpt);
    
    return $excerpt;
}

function the_product_excerpt(){
    global $post,$desc;
    echo get_product_excerpt($desc);
}

function get_product_category($prod_id){
	$product_cat = get_the_term_list( $prod_id, 'prodcat', '<p class="fa fa-tag product-cat"><b>Категория товара:</b> ', ', ', '</p>' );
	return $product_cat;
}

function add_product_category_excerpt($excerpt){
    global $post;
    $excerpt .= get_product_category($post->ID);
    return $excerpt;
}
//add_filter('get_product_excerpt','add_product_category_excerpt',10);

function get_currency($cur=false,$type=0){
	$curs = get_currency_list();
	$curs = apply_filters('currency_list',$curs);
	if(!$cur){
		foreach($curs as $cur => $nms){
			$crs[$cur] = $cur;
		}
		return $crs;
	} 
	if(!isset($curs[$cur][$type])) return false;
	return $curs[$cur][$type];
}

function the_type_currency_list($post_id){
	global $rmag_options;	
	if($rmag_options['multi_cur']){
		$type = get_post_meta($post_id,'type_currency',1);
		$curs = array($rmag_options['primary_cur'],$rmag_options['secondary_cur']);
		$conts = '<select name="wprecall[type_currency]">';
		foreach($curs as $cur){
			$conts .= '<option '.selected($type,$cur,false).' value="'.$cur.'">'.$cur.'</option>';
		}
		$conts .= '</select>';
	}else{
		$conts = $rmag_options['primary_cur'];
	}
	echo $conts;
}
function get_current_type_currency($post_id){
	global $rmag_options;	
	if($rmag_options['multi_cur']){
		$type = get_post_meta($post_id,'type_currency',1);
		$curs = array($rmag_options['primary_cur'],$rmag_options['secondary_cur']);		
		if($type==$curs[0]||$type==$curs[1]) $current = $type;
		else $current = $curs[0];
	}else{
		$current = $rmag_options['primary_cur'];
	}
	return $current;
}
function get_current_currency($post_id){
	$current = get_current_type_currency($post_id);
	return get_currency($current,1);
}
//Вывод основной валюты сайта
function get_primary_currency($type=0){
	global $rmag_options;
	$cur = (isset($rmag_options['primary_cur']))? $rmag_options['primary_cur']:'RUB';
	return get_currency($cur,$type);
}
function primary_currency($type=0){
	echo get_primary_currency($type);
}
//Вывод дополнительной валюты сайта
function get_secondary_currency($type=0){
	global $rmag_options;
	$cur = (isset($rmag_options['secondary_cur']))? $rmag_options['secondary_cur']:'RUB';
	return get_currency($cur,$type);
}
function secondary_currency($type=0){
	echo get_secondary_currency($type);
}

//Цена товара
function get_number_price($prod_id){
	$price = get_post_meta($prod_id,'price-products',1);
    return apply_filters('get_number_price',$price,$prod_id); 
}

add_filter('get_number_price','get_currency_price',10,2);
function get_currency_price($price,$prod_id){
	global $rmag_options;	
	if(!$rmag_options['multi_cur']) return $price;
	
	$currency = (get_post_meta($prod_id,'type_currency',1))?get_post_meta($prod_id,'type_currency',1):$rmag_options['primary_cur'];
	if($currency==$rmag_options['primary_cur']) return $price;
	$curse = (get_post_meta($prod_id,'curse_currency',1))?get_post_meta($prod_id,'curse_currency',1):$rmag_options['curse_currency'];
	$price = ($curse)? $curse*$price: $price;		
		
	return $price;
}

add_filter('get_number_price','get_margin_product',20,2);
function get_margin_product($price,$prod_id){
	global $rmag_options;
	$margin = (get_post_meta($prod_id,'margin_product',1))?get_post_meta($prod_id,'margin_product',1):$rmag_options['margin_product'];
	if(!$margin) return $price;
	$price = $price + ($price*$margin/100);
	return $price;
}

function get_price($prod_id){
    $price = get_number_price($prod_id);
	return apply_filters('get_price',$price,$prod_id); 
}

add_filter('get_price','add_filters_price',10,2);
function add_filters_price($price,$prod_id){
	if($price) return apply_filters('not_null_price',$price,$prod_id);
    else return apply_filters('null_price',$price,$prod_id);
}

add_filter('null_price','get_null_price_block',10);
function get_null_price_block($price){
    return '<span class="price-prod no-price">Бесплатно!</span>';
}

add_filter('not_null_price','get_not_null_price_block',20);
function get_not_null_price_block($price){     
    return '<span class="price-prod">'.$price.'</span>';
}

function get_chart_orders($orders){
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
        setup_orderdata($order);
        setup_chartdata($order->order_date,$order->order_price);
    }
    
    return get_chart_rcl($chartArgs);
}

//Формирование массива данных заказа
function setup_orderdata($orderdata){
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

	foreach($orderdata as $data){ setup_productdata($data);
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
function setup_productdata($productdata){
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
function setup_cartdata($productdata){
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