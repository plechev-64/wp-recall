<?php

/*************************************************
Пополнение личного счета пользователя
*************************************************/
rcl_ajax_action('rcl_add_count_user', false);
function rcl_add_count_user(){
    global $user_ID;

    rcl_verify_ajax_nonce();

    if(!intval($_POST['pay_summ'])){
        wp_send_json(array('error'=>__('Enter the amount','wp-recall')));
    }

    if($user_ID){

        $pay_summ = intval($_POST['pay_summ']);
        $pay_type = (isset($_POST['pay_type']))? $_POST['pay_type']: 1;
        $description = (isset($_POST['description']))? $_POST['description']: '';
        $merchant_icon = (isset($_POST['merchant_icon']))? $_POST['merchant_icon']: 1;
        $submit_value = (isset($_POST['submit_value']))? $_POST['submit_value']: __('Make payment','wp-recall');

        $args = array(
            'pay_summ' => $pay_summ,
            'pay_type' => $pay_type,
            'description' => $description,
            'merchant_icon'=> $merchant_icon,
            'submit_value'=> $submit_value,
            'pay_systems_not_in'=> array('user_balance'),
        );

        $args = apply_filters('rcl_ajax_pay_form_args',$args);

        $log['redirectform'] = rcl_get_pay_form($args);
        $log['otvet']=100;

    } else {

        $log['error'] = __('Error','wp-recall');

    }

    wp_send_json($log);

}

rcl_ajax_action('rcl_pay_order_user_balance', false);
function rcl_pay_order_user_balance(){
    global $user_ID,$rmag_options;

    rcl_verify_ajax_nonce();

    $POST = wp_unslash($_POST);

    $pay_id = intval($POST['pay_id']);
    $pay_type = $POST['pay_type'];
    $pay_summ = $POST['pay_summ'];
    $description = $POST['description'];
    $baggage_data = json_decode(base64_decode($POST['baggage_data']));

    if(!$pay_id){
        wp_send_json(array('error'=>__('Order not found!','wp-recall')));
    }

    $data = array(
        'user_id' => $user_ID,
        'pay_type' => $pay_type,
        'pay_id' => $pay_id,
        'pay_summ' => $pay_summ,
        'current_connect' => 'user_balance',
        'baggage_data' => $baggage_data
    );

    do_action('rcl_pre_pay_balance',(object)$data);

    $userBalance = rcl_get_user_balance();

    $newBalance = $userBalance - $pay_summ;

    if(!$userBalance || $newBalance < 0){
        wp_send_json(array('error'=>sprintf(__('Insufficient funds in your personal account!<br>Order price: %d %s','wp-recall'),$pay_summ,rcl_get_primary_currency(1))));
    }

    rcl_update_user_balance($newBalance,$user_ID,$description);

    do_action('rcl_success_pay_balance',(object)$data);

    wp_send_json(array(
        'redirect' => rcl_format_url(get_permalink($rmag_options['page_successfully_pay'])).'payment-type='.$pay_type
    ));

}

rcl_ajax_action('rcl_load_payment_form');
function rcl_load_payment_form(){

    $form = rcl_get_pay_form(array(
        'pay_summ' => $_POST['pay_summ'],
        'ids' => array($_POST['gateway_id']),
        'pay_type' => $_POST['pay_type'],
        'description' => $_POST['description'],
    ));

    $content = '<div class="rcl-pre-payment-data">';

    $content .= '<div class="payment-meta"><b>'.__('Сумма платежа').'</b>: '.$_POST['pay_summ'].'</div>';

    $content .= '<div class="payment-meta"><b>'.__('Описание платежа').'</b>: '.$_POST['description'].'</div>';

    $content .= $form;

    $content .= '</div>';

    wp_send_json(array(
        'dialog' => array(
            'content' => $content,
            'size' => 'auto',
            'title' => __('Ссылка на оплату')
        ),
        'pay_type' => $_POST['pay_type']
    ));

}

