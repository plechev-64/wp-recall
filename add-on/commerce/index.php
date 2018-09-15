<?php

require_once 'core.php';
require_once 'deprecated.php';
require_once 'shortcodes.php';

require_once 'classes/class-rcl-cart.php';
require_once 'classes/class-rcl-create-order.php';
require_once 'classes/class-rcl-orders-query.php';
require_once 'classes/class-rcl-cart-button-form.php';
require_once 'classes/class-rcl-cart-constructor.php';
require_once 'classes/class-rcl-product-variations.php';
require_once 'classes/class-rcl-product-price.php';

require_once 'content/order-content.php';
require_once 'content/product-gallery.php';
require_once 'content/product-related.php';
require_once 'content/product-terms.php';
require_once 'content/product-box.php';

require_once "functions-ajax.php";

add_image_size( 'rcl-product-thumb', 350, 350, true );

if(is_admin()):
    require_once "admin/index.php";
else:   
    require_once "functions-frontend.php";
endif;

add_action('rcl_enqueue_scripts','rcl_commerce_scripts',10);
function rcl_commerce_scripts(){
    rcl_enqueue_style('rcl-commerce',rcl_addon_url('style.css', __FILE__));
    rcl_enqueue_script( 'rcl-commerce', rcl_addon_url('js/scripts.js', __FILE__) );
}

add_action( 'init', 'rcl_register_post_type_products' );
function rcl_register_post_type_products(){

    $labels = array(
        'name' => __('Products catalog','wp-recall'),
        'singular_name' => __('Product','wp-recall'),
        'add_new' => __('Add this item','wp-recall'),
        'add_new_item' => __('Add new item','wp-recall'),
        'edit_item' => __('Edit','wp-recall'),
        'new_item' => __('New','wp-recall'),
        'view_item' => __('View','wp-recall'),
        'search_items' => __('Search','wp-recall'),
        'not_found' => __('Not found','wp-recall'),
        'not_found_in_trash' => __('Cart is empty','wp-recall'),
        'parent_item_colon' => __('Parental goods','wp-recall'),
        'menu_name' => __('Products','wp-recall')
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => false,
        'supports' => array( 'title', 'editor','custom-fields','thumbnail','comments','excerpt','author'),
        'taxonomies' => array( 'prodcat','product_tag' ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 10,
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post'
    );

    $args = apply_filters('register_data_products',$args);

    register_post_type( 'products', $args );
}

add_action( 'init', 'rcl_register_taxonomy_prodcat' );
function rcl_register_taxonomy_prodcat() {

    $labels = array(
        'name' => __('Product categories','wp-recall'),
        'singular_name' => __('Category','wp-recall'),
        'search_items' => __('Search','wp-recall'),
        'popular_items' => __('Popular categories','wp-recall'),
        'all_items' => __('All categories','wp-recall'),
        'parent_item' => __('Parent category','wp-recall'),
        'parent_item_colon' => __('Parent category','wp-recall'),
        'edit_item' => __('Edit category','wp-recall'),
        'update_item' => __('Update category','wp-recall'),
        'add_new_item' => __('Add a new category','wp-recall'),
        'new_item_name' => __('New category','wp-recall'),
        'separate_items_with_commas' => __('Categories separated by commas','wp-recall'),
        'add_or_remove_items' => __('Add or delete a category','wp-recall'),
        'choose_from_most_used' => __('Select to use','wp-recall'),
        'menu_name' => __('Product categories','wp-recall')
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_in_nav_menus' => true,
        'show_ui' => true,
        'show_tagcloud' => true,
        'hierarchical' => true,
        'rewrite' => true,
        'query_var' => true
    );

    register_taxonomy( 'prodcat', array('products'), $args );
}

add_action( 'init', 'rcl_register_taxonomy_product_tag' );
function rcl_register_taxonomy_product_tag() {

    $labels = array(
        'name' => __('Product Tags','wp-recall'),
        'singular_name' => __('Product Tag','wp-recall'),
        'search_items' => __('Search','wp-recall'),
        'popular_items' => __('Popular tags','wp-recall'),
        'all_items' => __('All tags','wp-recall'),
        'parent_item' => __('Parental tag','wp-recall'),
        'parent_item_colon' => __('Parental tag','wp-recall'),
        'edit_item' => __('Edit','wp-recall'),
        'update_item' => __('Update','wp-recall'),
        'add_new_item' => __('Add a new tag','wp-recall'),
        'new_item_name' => __('New tag','wp-recall'),
        'separate_items_with_commas' => __('Tags separated by commas','wp-recall'),
        'add_or_remove_items' => __('Add or delete','wp-recall'),
        'choose_from_most_used' => __('Select to use','wp-recall'),
        'menu_name' => __('Product Tags','wp-recall')
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_in_nav_menus' => true,
        'show_ui' => true,
        'show_tagcloud' => true,
        'hierarchical' => false,
        'rewrite' => true,
        'query_var' => true
    );

    register_taxonomy( 'product_tag', array('products'), $args );
}

//поддержка функционала рейтинга
add_action('init','rcl_register_rating_product_type');
function rcl_register_rating_product_type(){
    
    if(!function_exists('rcl_register_rating_type')) 
        return false;
    
    rcl_register_rating_type(array(
        'post_type'=>'products',
        'type_name'=>__('Products','wp-recall'),
        'style'=>true
        )
    );
}

//инициализация вкладки личного кабинета
add_action('init','rcl_tab_orders');
function rcl_tab_orders(){
    
    $tab_data = array(
        'id'=>'orders',
        'name'=>__('Orders','wp-recall'),
        'supports'=>array('ajax'),
        'public'=>0,
        'icon'=>'fa-shopping-cart'
    );
    
    if(isset($_GET['order-id'])){

        $tab_data['content'][] = array(
            'id' => 'all-orders',
            'name' => __('Orders','wp-recall'),
            'callback' => array(
                'name' => 'rcl_single_order_tab',
                'args' => array(intval($_GET['order-id']))
            )
        );

        
    }else{
        
        $statuses = rcl_order_statuses();
        
        $tab_data['content'][] = array(
            'id' => 'all-orders',
            'name' => __('All orders','wp-recall'),
            'icon' => 'fa-shopping-bag',
            'callback' => array(
                'name' => 'rcl_orders_tab',
                'args' => array(false)
            )
        );
    
        foreach($statuses as $k=>$name){
            $tab_data['content'][] = array(
                'id' => 'status-'.$k,
                'name' => $name,
                'icon' => 'fa-folder-o',
                'callback' => array(
                    'name' => 'rcl_orders_tab',
                    'args' => array($k)
                )
            );
        }
        
    }

    rcl_tab($tab_data);
    
}

function rcl_orders_tab($status_id){
    global $user_LK,$rcl_orders;
    
    $args = array(
        'user_id' => $user_LK,
        'fields' => array(
            'order_id',
            'user_id',
            'order_price',
            'products_amount',
            'order_status',
            'order_date'
        )
    );
    
    if($status_id){
        $args['order_status'] = $status_id;
    }
    
    $count = rcl_count_orders($args);

    if(!$count) 
        return '<p>'.sprintf(__('No orders with status "%s" yet','wp-recall'),rcl_get_status_name_order($status_id)).'.</p>';

    $pagenavi = new Rcl_PageNavi('rcl-orders',$count,array('in_page'=>30));

    $args['offset'] = $pagenavi->offset;
    
    $rcl_orders = rcl_get_orders($args);
    
    $content = $pagenavi->pagenavi();

    $content .= rcl_get_include_template('orders-history.php',__FILE__);
    
    $content .= $pagenavi->pagenavi();

    return $content;
}

/*ниже рассортировать*/

//Оплата заказа
//add_action('rcl_success_pay','rcl_add_payment_order',50);
add_action('rcl_success_pay_system','rcl_add_payment_order',10);
add_action('rcl_success_pay_balance','rcl_add_payment_order',10);
function rcl_add_payment_order($pay){
    
    if($pay->pay_type != 2) return false;
    
    $order = rcl_get_order($pay->pay_id);
    
    if($order && $order->order_price == $pay->pay_summ && $order->order_status == 1){
        
        rcl_payment_order($order->order_id);
        
        if($pay->current_connect == 'user_balance'){
            //если оплата с баланса пользователя
            
            $result = array(
                'success' => __('Your order has been successfully paid! A notification has been sent to the administration.','wp-recall'),
                'user_balance' => rcl_get_user_balance($order->user_id),
                'order_id' => $order->order_id,
                'pay_balance' => 1
            );

            wp_send_json($result);
            
        }
    }
    
}

function rcl_get_order_manager(){
    global $user_ID,$rclOrder;
    
    $args = array(
        array(
            'href' => rcl_get_tab_permalink($user_ID,'orders'),
            'title' => __('See all orders','wp-recall')
        )
    );
    
    if($rclOrder->order_status == 1){
        
        $args[] = array(
            'href' => wp_nonce_url(rcl_get_tab_permalink($user_ID,'orders').'&order-action=trash&order-id='.$rclOrder->order_id, 'order-action' ),
            'title' => __('Delete order','wp-recall')
        );
        
    }
    
    $args = apply_filters('rcl_order_manager_args',$args);
    
    if(!$args) return false;
    
    $content = '<div class="order-manage-box">';
    
    foreach($args as $data){
        $content .= '<span class="manager-item">';
            $content .= '<a href="'.$data['href'].'" class="recall-button">';
                $content .= $data['title'];
            $content .='</a>';
        $content .='</span>';
    }
    
    $content .= '</div>';
    
    return $content;
    
}

add_action('wp','rcl_commerce_setup_order_actions');
function rcl_commerce_setup_order_actions(){
    global $user_ID;
    
    if(!isset($_GET['order-action']) || !isset($_GET['order-id'])) return false;
    
    if(!wp_verify_nonce( $_GET['_wpnonce'], 'order-action' )) return false;
    
    $order_id = intval($_GET['order-id']);
    $order_action = $_GET['order-action'];

    switch($order_action){
        
        case 'trash':
            rcl_update_status_order($order_id, 6);
        break;
    
    }
    
    wp_redirect(rcl_get_tab_permalink($user_ID,'orders')); exit;
    
}