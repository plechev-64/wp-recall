<?php

//вывод корзины в recallbar
add_action('rcl_bar_setup','rcl_bar_add_cart',10);
function rcl_bar_add_cart(){
    
    $Cart = new Rcl_Cart();
    
    $args = array(
        'icon' => 'fa-shopping-cart',
        'url' => $Cart->cart_url,
        'label' => __('Cart','wp-recall'),
        'counter' => '<span class="cart-numbers rcl-order-amount">'.$Cart->products_amount.'</span>'
    );
    
    if($Cart->products_amount){
        $args['class'] = 'animated shake';
    }
    
    rcl_bar_add_icon('rcl-cart',
        $args
    );
    
}

function rcl_single_order_tab($order_id){
    global $user_LK,$rmag_options,$rclOrder;
    
    $rclOrder = rcl_get_order($order_id);

    if($rclOrder->user_id!=$user_LK) return false;
    
    $block = '<div id="rcl-order">';

    $block .= rcl_get_include_template('order.php',__FILE__);
    
    $block .= '</div>';
    
    return $block;
}

//Выводим кнопку корзины
add_filter('the_excerpt', 'rcl_add_cart_button',15);
add_filter('the_content', 'rcl_add_cart_button',15);
function rcl_add_cart_button($content){
    global $post, $rmag_options;
    
    if($post->post_type != 'products') return $content;
    
    if(doing_filter('the_excerpt')){
        
        $archiveCart = (isset($rmag_options['cart_button_archive_page']))? $rmag_options['cart_button_archive_page']: 1;
        
        if(!$archiveCart) return $content;
        
    }
    
    if(doing_filter('the_content')){
        
        $productCart = (isset($rmag_options['cart_button_single_page']))? $rmag_options['cart_button_single_page']: array('top','bottom');
        
        if(!in_array('bottom',$productCart)) return $content;
        
    }
    
    $button = new Rcl_Cart_Button_Form(array(
        'product_id' => $post->ID
    ));
    
    $content .= $button->cart_form(array(
        'variations' => false
    ));

    return $content;
}

//дополняем данные товаров выводимых на странице архива метаданными
add_action( 'wp', 'rcl_add_products_meta');
function rcl_add_products_meta(){
    global $wp_query,$wpdb;
    
    if(!$wp_query->is_tax && !$wp_query->is_archive) return false;
    
    if($wp_query->query_vars['post_type'] == 'products'){
        
        if(!$wp_query->posts) return false;

        $posts = array();

        foreach($wp_query->posts as $post){
            $posts[] = $post->ID;
        }
        
        if($posts){
            
            $metaPrices = $wpdb->get_results("SELECT post_id,meta_value FROM $wpdb->postmeta WHERE meta_key='price-products' AND post_id IN (".implode(',',$posts).")");

            $prices = array();
            foreach($metaPrices as $meta){
                $prices[$meta->post_id] = $meta->meta_value;
            }
            
        }

        foreach($wp_query->posts as $post){
            $post->product_price = (isset($prices[$post->ID]))? $prices[$post->ID]: 0;
        }

    }
    
}

add_action('wp','rcl_commerce_actions',10);
function rcl_commerce_actions(){
    
    if(!isset($_POST['rcl-commerce-action'])) return false;
    
    $action = $_POST['rcl-commerce-action'];
    
    switch($action){
        
        case 'new-order':
            global $rmag_options;
        
            if(isset($rmag_options['basket_page_rmag']))
                $cart_url = get_permalink($rmag_options['basket_page_rmag']);
            
            $order_id = rcl_create_order();
            
            if(is_wp_error($order_id)){
                wp_die(print_r($order_id));
            }
            
            wp_redirect(rcl_format_url($cart_url).'order-id='.$order_id.'&order-status=new'); exit;
            
        break;
        
    }
    
}