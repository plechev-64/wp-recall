<?php

global $rclOrder;

$order_id = $_GET['order-id'];

$rclOrder = rcl_get_order($order_id);

$content = '<div id="rcl-order">';

$content .= rcl_get_include_template('order.php',__FILE__);

$content .= '</div>';

echo $content;