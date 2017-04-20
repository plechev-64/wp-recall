<?php

global $rclOrder;

$order_id = $_GET['order-id'];

$rclOrder = rcl_get_order($order_id);

$content = '<h2>'.__('Order data','wp-recall').'</h2>';

$content .= '<div id="rcl-order">';

$content .= '<div class="order-before-box">';

    $content .= '<span class="title-before-box">'.__('Buyer data','wp-recall').'</span>';
    
    $content .= '<div class="content-before-box">';
    
    $content .= '<p><b>'.__('Name','wp-recall').'</b>: '.get_the_author_meta('display_name',$rclOrder->user_id).'</p>';
    $content .= '<p><b>'.__('E-mail','wp-recall').'</b>: '.get_the_author_meta('email',$rclOrder->user_id).'</p>';

    $content .= '</div>';

$content .= '</div>';

$content .= rcl_get_include_template('order.php',__FILE__);

$content .= '<form><input type="button" class="button-primary" value="'.__('Back to all orders','wp-recall').'" onClick="history.back()"></form>';

$content .= '</div>';

echo $content;