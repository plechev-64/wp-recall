<?php
function rcl_order_statuses(){
    $sts = array(
          1    => __( 'Not paid', 'wp-recall' ),
          2    => __( 'Paid', 'wp-recall' ),
          3   => __( 'Sent', 'wp-recall' ),
          4    => __( 'Received', 'wp-recall' ),
          5    => __( 'Closed', 'wp-recall' ),
          6    => __( 'Trash', 'wp-recall' )
      );
    return apply_filters('order_statuses',$sts);
}

//Устанавливаем перечень статусов
function rcl_get_status_name_order($status_id){   
    $sts = rcl_order_statuses();
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
        $title = ($product->post_title)? $product->post_title: get_the_title($product->product_id);
        echo apply_filters('rcl_product_title',$title);
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
add_filter('not_null_price','rcl_add_primary_currency_price',10,2);
function rcl_add_primary_currency_price($price,$product_id=false){
    if($product_id){
        return $price .= ' '.sprintf('<span itemprop="priceCurrency" content="%s">%s</span>',rcl_get_current_type_currency($product_id),rcl_get_primary_currency(1));
    }
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
    
    if(isset($args['count'])) $sql = "SELECT COUNT(DISTINCT order_id) FROM ".RMAG_PREF ."orders_history";
    else $sql = "SELECT DISTINCT(order_id) FROM ".RMAG_PREF ."orders_history";

    if(isset($args['order_id'])) $wheres[] = "order_id IN ('".$args['order_id']."')";
    if(isset($args['user_id'])) $wheres[] = "user_id='".$args['user_id']."'";
    if(isset($args['order_status'])) $wheres[] = "order_status='".$args['order_status']."'";
    if(isset($args['status_not_in'])) $wheres[] = "order_status NOT IN ('".$args['status_not_in']."')";
    if(isset($args['product_id'])) $wheres[] = "product_id IN ('".$args['product_id']."')";
    if(isset($args['year'])) $date[] = $args['year'];
    if(isset($args['month'])) $date[] = $args['month'];

    if($date){
        $date = implode('-',$date);
        $wheres[] = "order_date LIKE '%$date%'";
    }

    if($wheres){
        /*if(isset($args['search'])&&$args['search']) $where = implode(' OR ',$wheres);
        else */
            $where = implode(' AND ',$wheres);
    }

    if($where) $sql .= " WHERE ".$where;
    
    if(!isset($args['count'])){
        $orderby = (isset($args['orderby']))? "ORDER BY ".$args['orderby']:"ORDER BY ID";
        $order = (isset($args['order']))? $args['order']:"DESC";

        $sql .= " $orderby $order";

        if(isset($args['per_page'])){

            $per_page = (isset($args['per_page']))? $args['per_page']: 30;
            $offset = (isset($args['offset']))? $args['offset']: 0;
            $sql .= " LIMIT $offset,$per_page";

        }
    }else{
        //если считаем
        return $wpdb->get_var($sql);
    }

    $ids = $wpdb->get_col($sql);
    
    if(!$ids) return false;
    
    $rdrs = $wpdb->get_results("SELECT * FROM ".RMAG_PREF ."orders_history WHERE order_id IN (".implode(',',$ids).") $orderby $order");

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
    global $post,$product;
    if(!$desc) return false;
    
    if($product){
        if($product->post_excerpt) $excerpt = strip_tags($product->post_excerpt);
        else $excerpt = strip_tags($product->post_content);
    }else{
        if($post->post_excerpt) $excerpt = strip_tags($post->post_excerpt);
        else $excerpt = strip_tags($post->post_content);
    }

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
    global $post,$productlist,$product;
    
    $desc = (isset($productlist['desc']))? $productlist['desc']: 200;
    
    $excerpt = rcl_get_product_excerpt($desc);
    
    if(!$excerpt) return false;
    
    $excerpt = '<div class="meta">
        <i class="fa fa-info rcl-icon"></i>
        <span class="meta-content-box">
            <span class="meta-content" itemprop="description">'.$excerpt.'</span>
        </span>
    </div>';
    
    echo $excerpt;
}

function rcl_get_product_category($prod_id){
    
    $start = '<div class="product-meta meta"><i class="fa fa-%s rcl-icon"></i><span class="meta-content-box"><span class="meta-content">%s: ';
    $end = '</span></span></div>';
    
    $cats = get_the_term_list( $prod_id, 'prodcat', sprintf($start,'folder-open',__('Categories','wp-recall')), ', ', $end );
    $cats .= get_the_term_list( $prod_id, 'product_tag', sprintf($start,'tags',__('Tags','wp-recall')), ', ', $end );
    
    if(!$cats) return false;
    
    return $cats;
}

function rcl_product_category_excerpt($excerpt){
    global $post;
    $excerpt .= '<div class="product-meta">'.rcl_get_product_category($post->ID).'</div>';
    return $excerpt;
}

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
    $price = apply_filters('rcl_get_number_price',$price,$prod_id);
    $price = (!$price) ? 0 : $price;
    return $price;
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
    if($price){
        $price = '<span itemprop="price" content="'.$price.'">'.$price.'</span>';
        return apply_filters('not_null_price',$price,$prod_id);
    }else{
        return apply_filters('null_price',$price,$prod_id);
    }
}

add_filter('null_price','rcl_get_null_price_content',10);
function rcl_get_null_price_content($price){
    return '<span class="price-prod no-price" itemprop="price" content="0">'.__('Free','wp-recall').'!</span>';
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
        'title' => __('Finance','wp-recall'),
        'title-x' => __('Period of time','wp-recall'),
        'data'=>array(
            array('"'.__('Days/Months','wp-recall').'"', '"'.__('Payments (pcs.)','wp-recall').'"', '"'.__('Income (tsd.)','wp-recall').'"')
        )
    );

    foreach($orders as $order){
        //rcl_setup_orderdata($order);
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
        
        $product = get_post($productdata->product_id);
        
        if(!$product){
            $product = array(
                'ID'=>$productdata->product_id,
                'post_title'=>'Товар не существует'
            );
            $product = (object)$product;
        }
        
        $product->product_id = $productdata->product_id;
	$product->product_price = $productdata->product_price;
        $product->summ_price = $productdata->product_price*$productdata->numberproduct;
        $product->numberproduct = $productdata->numberproduct;
        $product->user_id = $productdata->user_id;
        $product->order_id = $productdata->order_id;
        $product->order_date = $productdata->order_date;
        $product->order_status = $productdata->order_status;

	return $product;
}
function rcl_setup_cartdata($productdata){
	global $product,$CartData;

	$price = $CartData->cart[$productdata->ID]['price'];
	$numprod = $CartData->cart[$productdata->ID]['number'];
	$product_price = $price * $numprod;
	$price = apply_filters('cart_price_product',$price,$productdata->ID);

	$productdata = (object)array(
		'product_id'=>$productdata->ID,
		'product_price'=>$CartData->cart[$productdata->ID]['price'],
		'summ_price'=>$price,
		'numberproduct'=>$CartData->cart[$productdata->ID]['number']
	);
        
        $product = rcl_setup_productdata($productdata);

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

    $get_fields = get_option( 'rcl_profile_fields' );

    if($get_fields){
        $cf = new Rcl_Custom_Fields();

        foreach((array)$get_fields as $custom_field){
            $slug = $custom_field['slug'];
            $meta = get_the_author_meta($slug,$user_id);
            $show_custom_field .= $cf->get_field_value($custom_field,$meta);
        }
    }

    $table_order = rcl_get_include_template('order.php',__FILE__);


    $subject = 'Заказ №'.$order->order_id.' оплачен!';

    $admin_email = $rmag_options['admin_email_magazin_recall'];

    $text = '';

    $text = apply_filters('payment_mail_text',$text);

    $textmail = '
    <p>'.__('User pay a purchase','wp-recall').' "'.get_bloginfo('name').'".</p>
    <h3>'.__('Information about the customer','wp-recall').':</h3>
    <p><b>'.__('Name','wp-recall').'</b>: '.get_the_author_meta('display_name',$user_id).'</p>
    <p><b>'.__('Email','wp-recall').'</b>: '.get_the_author_meta('user_email',$user_id).'</p>
    '.$show_custom_field.'
    <p>'.sprintf(__('Order №%d received the status of "%s"','wp-recall'),$order_id,rcl_get_status_name_order(2)).'.</p>
    <h3>'.__('Order details','wp-recall').':</h3>
    '.$table_order.'
	'.$text.'
    <p>'.__('Link to control the order in admin','wp-recall').':</p>
    <p>'.admin_url('admin.php?page=manage-rmag&order-id='.$order_id).'</p>';

    if($admin_email){
        rcl_mail($admin_email, $subject, $textmail);
    }else{
        $admin_email = get_option('admin_email');
        rcl_mail($admin_email, $subject, $textmail);
    }

    $email = get_the_author_meta('user_email',$user_id);
    $textmail = '
    <p>'.sprintf(__('You paid for a purchase "%s" funds from his personal account.','wp-recall'),get_bloginfo('name')).'</p>
    <h3>'.__('Information about the customer','wp-recall').':</h3>
    <p><b>'.__('Name','wp-recall').'</b>: '.get_the_author_meta('display_name',$user_id).'</p>
    <p><b>'.__('Email','wp-recall').'</b>: '.get_the_author_meta('user_email',$user_id).'</p>
    <p>'.sprintf(__('Order №%d received the status of "%s"','wp-recall'),$order_id,rcl_get_status_name_order(2)).'.</p>
    <h3>'.__('Order details','wp-recall').':</h3>
    '.$table_order.'
	'.$text.'
    <p>'.__('Your order has been paid and is processing. You can follow the change of his status from his personal account','wp-recall').'</p>';
    rcl_mail($email, $subject, $textmail);

    do_action('rcl_payment_order',$order_id,$order);
}