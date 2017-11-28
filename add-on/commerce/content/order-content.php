<?php

add_action('rcl_order_before','rcl_add_order_manager',10);
function rcl_add_order_manager(){
    global $user_ID;
    
    if(!rcl_is_office($user_ID)) return false;
    
    echo rcl_get_order_manager();

}

add_action('rcl_order_before','rcl_add_order_notices',10);
function rcl_add_order_notices(){
    global $rclOrder,$rmag_options,$user_ID,$rcl_user_URL;
    
    if(!isset($_GET['order-status'])) return false;
        
    $buyer_register = (isset($rmag_options['buyer_register']))? $rmag_options['buyer_register']: 1;

    $status = intval($_GET['order-status']);

    $notice = '<div class="rcl-order-notices order-before-box">';

    $notice .= '<div class="content-before-box">'; 

    switch($status){
        case 'new':

            $notice .= __('Your order has been created!','wp-recall').'<br>';
            $notice .= sprintf(__('Status granted to order - "%s"','wp-recall'), rcl_get_status_name_order($rclOrder->order_status)).'. ';
            $notice .= __('The order is being processed.','wp-recall').'<br>';

            if(!$rclOrder->order_price){ //Если заказ бесплатный

                $notice .= __('The order contained only free items','wp-recall').'<br>';

            }else{

                if($rclOrder->order_status == 1 && function_exists('rcl_get_pay_form')){
                    
                    if($user_ID){
                        $notice .= __('You can pay for it now or from your personal account.','wp-recall');
                    }else{
                        $notice .= __('You can pay for it from your personal account.','wp-recall');
                    }
                    
                    $notice .= ' '.__('There you can find the status of your order.','wp-recall');

                }else{

                    $notice .= __('You can monitor the status of your order in your personal account.','wp-recall');

                }

            }

            if(!$user_ID && $buyer_register){

                $notice .= __('All necessary data for authorization on the site have been sent to the specified e-mail','wp-recall')."<br />";
                $notice .= __('In your personal account you can find out the status of your order.','wp-recall').'<br>';
                $notice .= __('You can top up your personal account on the site in your back office and in the future pay for orders with it','wp-recall')."<br />";

                if(rcl_get_option('confirm_register_recall')){

                    $notice .= __('To monitor the order status please confirm the specified email!','wp-recall').'<br>';
                    $notice .= __('Follow the link in the letter sent to your email','wp-recall').'<br>';

                }

            }

        break;
    }

    $notice .= '</div>';

    $notice .= '</div>';

    echo $notice;

}

if(!is_admin())
    add_action('rcl_order_before','rcl_add_order_pay_form',30);
function rcl_add_order_pay_form(){
    global $user_ID, $rclOrder,$rmag_options;
    
    if(!isset($_GET['order-status']) && !rcl_is_office()) return false;
    
    if(!$user_ID || !$rclOrder->order_price || $rclOrder->order_status != 1) return false;
    
    if(function_exists('rcl_get_pay_form')){
        
        $type_pay = $rmag_options['type_order_payment'];
        
        $dataPay = array(
            'baggage_data' => array(
                'order_id' => $rclOrder->order_id
            ),
            'pay_id' => $rclOrder->order_id,
            'user_id' => $rclOrder->user_id,
            'pay_summ' => $rclOrder->order_price,
            'description' => sprintf(__('Payment order №%s dated %s','wp-recall'),$rclOrder->order_id,get_the_author_meta('user_email',$rclOrder->user_id)),
            'merchant_icon' => 1
        );
        
        if(!$type_pay){
            $dataPay['pay_systems'] = 'user_balance';
        }
        
        if($type_pay == 1){
            $dataPay['pay_systems_not_in'] = 'user_balance';
        }

        $content = '<div class="rcl-order-pay-form order-before-box">';
        $content .= '<span class="title-before-box">'.__('Pay for the created order by one of the proposed methods','wp-recall').'</span>';
            $content .= '<div class="content-before-box">'; 
                $content .= rcl_get_pay_form($dataPay);
            $content .= '</div>';
        $content .= '</div>';
        
        echo $content;
        
    }
    
}

add_action('rcl_order_before','rcl_add_order_details',20);
function rcl_add_order_details(){
    global $rclOrder;
    
    if(!$rclOrder->order_details) return false;
    
    $CF = new Rcl_Custom_Fields();
    
    $content = '<div class="rcl-order-details order-before-box">'; 
    
        $content .= '<span class="title-before-box">'.__('Order data','wp-recall').'</span>';
    
        $content .= '<div class="content-before-box">';

        $content .= '<p>'.__('Order','wp-recall').' №: '.$rclOrder->order_id.'</p>';
        $content .= '<p>'.__('Order status','wp-recall').': '.rcl_get_status_name_order($rclOrder->order_status).'</p>';
        $content .= '<p>'.__('Created date','wp-recall').': '.$rclOrder->order_date.'</p>';

        $content .= '</div>';
    
        $content .= '<span class="title-before-box">'.__('Data specified when placing the order','wp-recall').'</span>';
    
        $content .= '<div class="content-before-box">'; 

        if(is_array($rclOrder->order_details)){
            
            foreach($rclOrder->order_details as $data){

                $content .= $CF->get_field_value($data,$data['value'],true);

            }
            
        }else{
            //поддержка заказов созданных ранее версии 16.0
            
            $content .= $rclOrder->order_details;
        }

        $content .= '</div>';
    
    $content .= '</div>';
    
    echo $content;
    
}

