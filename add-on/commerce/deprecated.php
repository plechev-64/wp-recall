<?php

add_action('init','rmag_global_unit',10);
function rmag_global_unit(){
    if(defined('RMAG_PREF')) return false;
    global $wpdb,$rmag_options,$user_ID;

    if(!isset($_SESSION['return_'.$user_ID]))
            $_SESSION['return_'.$user_ID] = (isset($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER']: '/';
    
    $rmag_options = get_option('primary-rmag-options');
    define('RMAG_PREF', $wpdb->prefix."rmag_");
}

add_action('rcl_insert_order','init_old_action_insert_order_rcl');
function init_old_action_insert_order_rcl($order_id){  
    $order = rcl_get_order($order_id);   
    do_action('insert_order_rcl',$order->user_id,$order->order_id);
}

