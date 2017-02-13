<?php

if (!session_id()) { session_start(); }

require_once("functions.php");
require_once("functions/ajax-func.php");

if(is_admin()):
    require_once("admin-pages.php");
else:
    require_once("functions/shortcodes.php");
    add_action('rcl_enqueue_scripts','rcl_rmag_scripts',10);
endif;

function rcl_rmag_scripts(){
    rcl_enqueue_style('rcl-magazine',rcl_addon_url('style.css', __FILE__));
    rcl_enqueue_script( 'rcl-magazine', rcl_addon_url('js/scripts.js', __FILE__) );
}

function rmag_global_unit(){
    if(defined('RMAG_PREF')) return false;
    global $wpdb,$rmag_options,$user_ID;

    if(!isset($_SESSION['return_'.$user_ID]))
            $_SESSION['return_'.$user_ID] = (isset($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER']: '/';
    
    $rmag_options = get_option('primary-rmag-options');
    define('RMAG_PREF', $wpdb->prefix."rmag_");
}
add_action('init','rmag_global_unit',10);

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
            'id' => 'status-'.$k,
            'name' => $name,
            'callback' => array(
                'name' => 'rcl_single_order_tab',
                'args' => array($_GET['order-id'])
            )
        );

        
    }else{
        
        $statuses = rcl_order_statuses();
    
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
    global $wpdb,$user_ID,$rmag_options,$rcl_options,$order,$user_LK;

    $block = apply_filters('content_order_tab','');

    global $orders;

    $orders = rcl_get_orders(array('user_id'=>$user_LK,'order_status'=>$status_id));

    if(!$orders) $block .= '<p>'.sprintf(__('No orders with status "%s" yet','wp-recall'),rcl_get_status_name_order($status_id)).'.</p>';
    else $block .= rcl_get_include_template('orders-history.php',__FILE__);

    return $block;
}

function rcl_single_order_tab($order_id){
    global $user_LK,$rmag_options;
    
    $order = rcl_get_order($order_id);

    if($order->order_author!=$user_LK) return false;

    $status = $order->order_status;
    $order_id = $order->order_id;
    $price = $order->order_price;

    $block .= '<a class="recall-button view-orders" href="'.rcl_format_url(get_author_posts_url($user_LK),'orders').'">Смотреть все заказы</a>';

    $block .= '<h3>'.__('Order','wp-recall').' №'.$order_id.'</h3>';

    $postdata = rcl_encode_post(array(
        'callback'=>'rcl_trash_order',
        'order_id'=>$order_id
    ));

    $block .= '<div id="manage-order">';
    
    if($status == 1||$status == 5) 
        $block .= '<div class="remove-order">'
                . '<a href="#" class="remove_order recall-button rcl-ajax" data-post="'.$postdata.'"><i class="fa fa-trash" aria-hidden="true"></i> '.__('Delete','wp-recall').'</a>'
                . '</div>';
    
    if($status==1 && function_exists('rcl_payform')){

        $type_pay = $rmag_options['type_order_payment'];
        
        $dataPay = array(
            'baggage_data' => array(
                'order_id' => $order_id
            ),
            'pay_id' => $order_id,
            'pay_summ' => $price,
            'pay_type' => 2,
            'description' => sprintf(__('Payment order №%s dated %s','wp-recall'),$order->order_id,get_the_author_meta('user_email',$order->order_author)),
            'merchant_icon' => 1
        );

        if(!$type_pay){
            $dataPay['pay_systems'] = 'user_balance';
        }
        
        if($type_pay == 1){
            $dataPay['pay_systems_not_in'] = 'user_balance';
        }

        $block .= '<div class="rcl-types-paeers">';
        $block .= rcl_get_pay_form($dataPay);
        $block .= '</div>';

    }
    
    $block .= '</div>';

    $block .= '<div id="rcl-cart-notice" class="redirectform"></div>';

    $block .= rcl_get_include_template('order.php',__FILE__);
    
    return $block;
}