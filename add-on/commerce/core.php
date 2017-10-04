<?php

function rcl_create_order(){
    
    $Order = new Rcl_Create_Order();

    $order_id = $Order->insert_order();
    
    return $order_id;
    
}

function rcl_insert_order($args,$products){
    global $wpdb;
    
    if(!isset($args['order_date']))
        $args['order_date'] = current_time('mysql');
    
    foreach($products as $k=>$product){
        
        if(!isset($product['product_amount']))
            $products[$k]['product_amount'] = 1;
        
        if(!isset($product['product_price']))
            $products[$k]['product_price'] = get_post_meta($product['product_id'],'price-products',1);
        
        if(!isset($product['variations']))
            $products[$k]['variations'] = '';
        
    }
    
    if(!isset($args['order_price'])){
        
        $args['order_price'] = 0;
        foreach($products as $product){
            $args['order_price'] += $product['product_amount'] * $product['product_price'];
        }
        
    }
    
    if(!isset($args['products_amount'])){
        
        $args['products_amount'] = 0;
        foreach($products as $product){
            $args['products_amount'] += $product['product_amount'];
        }
        
    }
    
    $args = apply_filters('rcl_pre_insert_order', $args, $products);
    
    $args['order_details'] = maybe_serialize($args['order_details']);
    
    $result = $wpdb->insert(
        RCL_PREF."orders",
        $args
    );
    
    if(!$result){
        wp_die(__('Error creating order'));
    }
    
    $order_id = $wpdb->insert_id;
    
    //на случай миграции данных из старой таблицы
    if(!$order_id && isset($args['order_id']))
        $order_id = $args['order_id'];
    
    //прикрепляем к заказу товары
    foreach($products as $product){
        rcl_insert_order_item($order_id,$product);
    }
    
    do_action('rcl_insert_order',$order_id,$products);
    
    return $order_id;
}

function rcl_insert_order_item($order_id,$product){ 
    global $wpdb;
    
    if(!isset($product['variations']))
            $product['variations'] = '';
    
    $args = array(
        'order_id' => $order_id,
        'product_id' => $product['product_id'],
        'product_price' => $product['product_price'],
        'product_amount' => $product['product_amount'],
        'variations' => maybe_serialize($product['variations'])
    );
    
    $result = $wpdb->insert(
        RCL_PREF."order_items",
        $args
    );
    
    if(!$result)
        return false;
    
    do_action('rcl_insert_order_item',$order_id,$product);
    
    return $wpdb->insert_id;
    
}

//Удаляем заказ
function rcl_delete_order($order_id){
    global $wpdb;
    
    do_action('rcl_delete_order',$order_id);
    
    return $wpdb->query($wpdb->prepare("DELETE FROM ". RCL_PREF ."orders WHERE order_id = '%d'",$order_id));
    
}

add_action('rcl_delete_order','rcl_delete_order_items',10);
function rcl_delete_order_items($order_id){
    global $wpdb;
    
    do_action('rcl_delete_order_items',$order_id);
    
    return $wpdb->query($wpdb->prepare("DELETE FROM ". RCL_PREF ."order_items WHERE order_id = '%d'",$order_id));
    
}

//Обновляем статус заказа
function rcl_update_status_order($order_id, $new_status){
    global $wpdb;

    do_action('rcl_update_status_order',$order_id, $new_status);
    
    return $wpdb->update( 
        RCL_PREF ."orders", 
        array(
            'order_status' => $new_status
        ), 
        array(
            'order_id' => $order_id
        ) 
    );
    
}

function rcl_get_orders($args = array()){
    
    if(isset($args['fields'])){
        if(!in_array('order_id',$args['fields']))
            $args['fields'][] = 'order_id';
    }
    
    $ordersQuery = new Rcl_Orders_Query();
    
    $args['unserialise'] = 'order_details';
    
    $orders = $ordersQuery->get_results($args);
    
    if(!$orders) return array();

    //указываем для получения товары только из полученных заказов
    foreach($orders as $k=>$order){

        $args['order_id__in'][] = $order->order_id;
        
    }

    $args['order_id__in'] = array_unique($args['order_id__in']);
    $args['number'] = -1; //снимаем ограничение выборки товаров
    
    $productsQuery = new Rcl_Order_Items_Query();
    
    $products = $productsQuery->get_results($args);
    
    $Orders = array();
    foreach($orders as $order){
        
        if(!isset($order->order_id) || !$order->order_id) continue;
        
        $Products = array();
        foreach($products as $product){
            
            if(!isset($product->order_id) || $order->order_id != $product->order_id) continue;
            
            unset($product->order_id);
            
            $product->variations = maybe_unserialize($product->variations);
            
            $Products[] = $product;
            
        }
        
        $order->products = ($Products)? $Products: array();
        
        $Orders[] = $order;
        
    }
    
    return $Orders;

}

function rcl_count_orders($args = false){
    $ordersQuery = new Rcl_Orders_Query();   
    return $ordersQuery->count($args);
}

function rcl_get_order($order_id){
    
    $orders = rcl_get_orders(array(
        'order_id' => $order_id
    ));
    
    if(!$orders)
        return array();
    
    return $orders[0];
    
}

function rcl_order_statuses(){
    
    $sts = array(
        1 => __( 'Not paid', 'wp-recall' ),
        2 => __( 'Paid', 'wp-recall' ),
        3 => __( 'Sent', 'wp-recall' ),
        4 => __( 'Received', 'wp-recall' ),
        5 => __( 'Closed', 'wp-recall' ),
        6 => __( 'Trash', 'wp-recall' )
    );
    
    return apply_filters('order_statuses',$sts);
}

function rcl_get_status_name_order($status_id){  
    
    $sts = rcl_order_statuses();
    
    if(!isset($sts[$status_id])) return false;
    
    return $sts[$status_id];
    
}

function rcl_get_price($product_id){
    global $post;
    
    if($post && is_object($post) && isset($post->product_price)){
        
        return $post->product_price;
        
    }
    
    return get_post_meta($product_id,'price-products',1);
    
}

//оплата заказа
function rcl_payment_order($order_id){
    
    rcl_update_status_order($order_id,2);
    
    do_action('rcl_payment_order',$order_id);
    
}

add_action('rcl_create_order', 'rcl_create_order_send_mail',10,2);
function rcl_create_order_send_mail($order_id, $register_data){
    global $rclOrder,$rmag_options;
    
    $rclOrder = rcl_get_order($order_id);
    
    remove_action('rcl_order_before','rcl_add_order_pay_form',30);
    
    $orderData = rcl_get_include_template('order.php',__FILE__);
    
    $subject = __('Order data','wp-recall').' №'.$rclOrder->order_id;

    $textmail = '
    <p>'.__('This user has formed an order','wp-recall').' "'.get_bloginfo('name').'".</p>
    <h3>'.__('Information about the customer','wp-recall').':</h3>
    <p><b>'.__('Name','wp-recall').'</b>: '.get_the_author_meta('display_name',$rclOrder->user_id).'</p>
    <p><b>'.__('Email','wp-recall').'</b>: '.get_the_author_meta('user_email',$rclOrder->user_id).'</p>
    <p>'.sprintf(__('Order №%d received status "%s"','wp-recall'),$rclOrder->order_id,rcl_get_status_name_order(1)).'.</p>
    <h3>'.__('Order details','wp-recall').':</h3>
    '.$orderData.'
    <p>'.__('Link to managing your order','wp-recall').':</p>
    <p>'.admin_url('admin.php?page=manage-rmag&order-id='.$rclOrder->order_id).'</p>';

    $admin_email = (isset($rmag_options['admin_email_magazin_recall']) && $rmag_options['admin_email_magazin_recall'])? $rmag_options['admin_email_magazin_recall']: get_option('admin_email');

    rcl_mail($admin_email, $subject, $textmail);
    

    $email = get_the_author_meta('user_email',$rclOrder->user_id);
    
    $link = rcl_get_tab_permalink($rclOrder->user_id,'orders');

    $textmail = '';

    if($register_data){
        
        $subject = __('Your account and order information','wp-recall').' №'.$rclOrder->order_id;
        
        $textmail .= '<h3>'.__('You have been registered','wp-recall').'</h3>
            <p>'.__('Personal account has been created, you can monitor the status of your orders , create new orders and pay for them by available means through it','wp-recall').'</p>
            <p>'.__('Required data for authorization in your personal account','wp-recall').':</p>
            <p>'.__('Login','wp-recall').': '.$register_data['user_login'].'</p>
            <p>'.__('Password','wp-recall').': '.$register_data['user_pass'].'</p>
            <p>'.__('Next time use your personal cabinet for placing new orders on our website','wp-recall').'.</p>';

        if(rcl_get_option('confirm_register_recall')){
            
            $confirmstr = base64_encode(
                        json_encode(
                            array(
                                $register_data['user_login'],
                                md5($rclOrder->user_id)
                            )
                        )
                    );

            $url = get_bloginfo('wpurl').'/?rcl-confirmdata='.urlencode($confirmstr);
            
            $textmail .= '<p>'.__('Confirm your email on the site by clicking on the link below','wp-recall').':</p>
            <p><a href="'.$url.'">'.$url.'</a></p>
            <p>'.__('Can’t activate your account?','wp-recall').'</p>
            <p>'.__('Copy the text of the link below , paste it into the address bar of your browser and press Enter','wp-recall').'</p>';
        }

    }

    $textmail .= '
    <p>'.__('You have formed an order','wp-recall').' "'.get_bloginfo('name').'".</p>
    <h3>'.__('Order details','wp-recall').'</h3>
    <p>'.sprintf(__('Order №%d received status "%s"','wp-recall'),$rclOrder->order_id,rcl_get_status_name_order(1)).'.</p>
    '.$orderData;
    $textmail .= '<p>'.__('Link to managing your order','wp-recall').': <a href="'.$link.'">'.$link.'</a></p>';
    
    rcl_mail($email, $subject, $textmail);
    
}

//отправка писем при оплате заказа
add_action('rcl_payment_order','rcl_payment_order_send_mail',10);
function rcl_payment_order_send_mail($order_id){
    global $rclOrder,$rmag_options;
    
    $rclOrder = rcl_get_order($order_id);
    
    remove_action('rcl_order_before','rcl_add_order_pay_form',30);

    $orderData = rcl_get_include_template('order.php',__FILE__);
    
    $userName = get_the_author_meta('display_name',$rclOrder->user_id);
    $userEmail = get_the_author_meta('user_email',$rclOrder->user_id);

    $subject = sprintf(__('Order №%d has been paid','wp-recall'),$rclOrder->order_id);

    $textmail = '
    <p>'.__('User has paid for the order','wp-recall').' "'.get_bloginfo('name').'".</p>
    <h3>'.__('Information about the customer','wp-recall').':</h3>
    <p><b>'.__('Name','wp-recall').'</b>: '.$userName.'</p>
    <p><b>'.__('Email','wp-recall').'</b>: '.$userEmail.'</p>
    <p>'.sprintf(__('Order №%d received status "%s"','wp-recall'),$order_id,rcl_get_status_name_order(2)).'.</p>
    <h3>'.__('Order details','wp-recall').':</h3>
    '.$orderData.'
    <p>'.__('Link for managing the order','wp-recall').':</p>
    <p>'.admin_url('admin.php?page=manage-rmag&order-id='.$order_id).'</p>';

    $admin_email = (isset($rmag_options['admin_email_magazin_recall']) && $rmag_options['admin_email_magazin_recall'])? $rmag_options['admin_email_magazin_recall']: get_option('admin_email');
        
    rcl_mail($admin_email, $subject, $textmail);

    $email = get_the_author_meta('user_email',$rclOrder->user_id);
    $textmail = '
    <p>'.sprintf(__('You paid for the order %s with funds from your personal account.','wp-recall'),get_bloginfo('name')).'</p>
    <h3>'.__('Information about the customer','wp-recall').':</h3>
    <p><b>'.__('Name','wp-recall').'</b>: '.$userName.'</p>
    <p><b>'.__('Email','wp-recall').'</b>: '.$userEmail.'</p>
    <p>'.sprintf(__('Order №%d received status "%s"','wp-recall'),$order_id,rcl_get_status_name_order(2)).'.</p>
    <h3>'.__('Order details','wp-recall').':</h3>
    '.$orderData.'
    <p>'.__('Your order has been paid and is being processied. You can monitor its status in your personal cabinet','wp-recall').'</p>';
    rcl_mail($email, $subject, $textmail);
    
}

function rcl_product_variation_list($variations){
    echo rcl_get_product_variation_list($variations);
}

function rcl_get_product_variation_list($variations){
    
    if(!$variations) return false;
    
    $content = '<div class="product-variations">';
    
    $content .= '<div class="variations-title"><strong>'.__('Product variation','wp-recall').'</strong></div>';
    
    foreach($variations as $variation){
        
        $value = (is_array($variation[1]))? implode(', ',$variation[1]): $variation[1];
        
        $content .= '<div class="variation-box">';
        $content .= '<span class="variation-title">'.$variation[0].'</span> ';
        $content .= '<span class="variation-value">'.$value.'</span>';
        $content .= '</div>';
        
    }
    
    $content .= '</div>';
    
    return $content;
    
}

function rcl_product_excerpt($post_id){
    echo rcl_get_product_excerpt($post_id);
}

function rcl_get_product_excerpt($post_id){
    
    $post = get_post($post_id);
    
    $excerpt = ($post->post_excerpt)? $post->post_excerpt: wp_trim_words( $post->post_content, 20, '...' );
    
    return '<span class="product-excerpt">'.$excerpt.'</span>';
    
}

function rcl_get_cart_box($product_id,$args = false){
    
    $cartBox = new Rcl_Cart_Button_Form(array('product_id' => $product_id));
    
    return $cartBox->cart_form($args);
    
}

function rmag_migration_table_data(){
    global $wpdb;
    
    $old_orders = $wpdb->get_results("SELECT "
            . "orders.*, "
            . "details.details_order AS order_details "
            . "FROM ".RMAG_PREF."orders_history AS orders "
            . "INNER JOIN ".RMAG_PREF."details_orders AS details ON orders.order_id = details.order_id "
            . "ORDER BY orders.order_id ASC");
    
    if(!$old_orders) return false;
    
    $orders = array();
    foreach($old_orders as $product){
        
        if(!isset($orders[$product->order_id]['order_id']))
            $orders[$product->order_id]['order_id'] = $product->order_id;
        
        if(!isset($orders[$product->order_id]['order_date']))
            $orders[$product->order_id]['order_date'] = $product->order_date;
        
        if(!isset($orders[$product->order_id]['user_id']))
            $orders[$product->order_id]['user_id'] = $product->user_id;
        
        if(!isset($orders[$product->order_id]['order_status']))
            $orders[$product->order_id]['order_status'] = $product->order_status;
        
        if(!isset($orders[$product->order_id]['details']))
            $orders[$product->order_id]['order_details'] = $product->order_details;
        
        if(isset($orders[$product->order_id]['products_amount']))
            $orders[$product->order_id]['products_amount'] += $product->numberproduct;
        else
            $orders[$product->order_id]['products_amount'] = $product->numberproduct;
        
        if(isset($orders[$product->order_id]['order_price']))
            $orders[$product->order_id]['order_price'] += $product->numberproduct * $product->product_price;
        else
            $orders[$product->order_id]['order_price'] = $product->numberproduct * $product->product_price;
        
        $orders[$product->order_id]['products'][] = array(
            'product_id' => $product->product_id,
            'product_price' => $product->product_price,
            'product_amount' => $product->numberproduct,
        );
        
    }
    
    if(!$orders) return false;
    
    $wpdb->query("ALTER TABLE `".RCL_PREF."orders` CHANGE `order_id` `order_id` BIGINT (20) NOT NULL");
    
    foreach($orders as $order){
        
        $products = $order['products'];
        
        unset($order['products']);
        
        rcl_insert_order($order,$products);
        
    }
    
    $wpdb->query("ALTER TABLE `".RCL_PREF."orders` CHANGE `order_id` `order_id` BIGINT (20) NOT NULL AUTO_INCREMENT");
    
}