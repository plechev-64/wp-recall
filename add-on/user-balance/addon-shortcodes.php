<?php

add_shortcode('rcl-paybutton','rcl_get_pay_form');
add_shortcode('rcl-pay-form','rcl_get_pay_form');
function rcl_get_pay_form($args){

    $args = wp_parse_args($args, array(
        'pay_summ' => 0,
        'ids' => '',
        'ids__not_in' => '',
        'submit_value' => '',
        'description' => '',
        'pay_type' => 'any',
        'amount_type' => 'number',
        'amount_min' => 1,
        'amount_max' => false,
        'amount_step' => 1,
        'default' => 1
    ));

    $gateWays = new Rcl_Payment_Form($args);

    if($args['pay_summ']){
        return $gateWays->get_form();
    }else{
        return $gateWays->get_custom_amount_form();
    }

}

add_shortcode('rcl-usercount','rcl_shortcode_usercount');
function rcl_shortcode_usercount(){
    return rcl_get_html_usercount();
}

add_shortcode('rcl-form-balance','rcl_form_user_balance');
function rcl_form_user_balance($args = array()){

    $args['pay_type'] = 1;
    $args['ids__not_in'][] = 'user_balance';
    $args['description'] = __('Пополнение личного счета', 'wp-recall');

    return rcl_get_pay_form($args);
}

