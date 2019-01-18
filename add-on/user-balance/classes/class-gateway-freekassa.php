<?php

add_action( 'rcl_payments_gateway_init', 'rcl_gateway_freekassa_init', 10);
function rcl_gateway_freekassa_init() {
    rcl_gateway_register('freekassa', 'Rcl_Gateway_Freekassa');
}

class Rcl_Gateway_Freekassa extends Rcl_Gateway_Core{

    function __construct(){
        parent::__construct(array(
            'request'   => 'us_pay_type',
            'label'     => 'Free-Kassa',
            'submit'    => __('Оплатить через Free-Kassa'),
            'icon'      => rcl_addon_url('assets/freekassa.jpg',__FILE__)
        ));
    }

    function get_options(){

        return array(
            array(
                'type' => 'text',
                'slug' => 'fk_shop',
                'title' => __('Идентификатор магазина')
            ),
            array(
                'type' => 'password',
                'slug' => 'fk_secret_1',
                'title' => __('Секретное слово 1')
            ),
            array(
                'type' => 'password',
                'slug' => 'fk_secret_2',
                'title' => __('Секретное слово 2')
            )
        );

    }

    function get_form($data){
        global $rmag_options;

        $shop_id = $rmag_options['fk_shop'];
        $secret_1 = $rmag_options['fk_secret_1'];

        $signature = md5(implode(':',array(
            $shop_id,
            $data->pay_summ,
            $secret_1,
            $data->pay_id
        )));

        return parent::construct_form(array(
            'action' => 'https://www.free-kassa.ru/merchant/cash.php',
            'method' => 'get',
            'fields' => array(
                array(
                    'type' => 'hidden',
                    'slug' => 'm',
                    'value' => $shop_id
                ),
                array(
                    'type' => 'hidden',
                    'slug' => 'oa',
                    'value' => $data->pay_summ
                ),
                array(
                    'type' => 'hidden',
                    'slug' => 's',
                    'value' => md5(implode(':',array(
                        $shop_id,
                        $data->pay_summ,
                        $secret_1,
                        $data->pay_id
                    )))
                ),
                array(
                    'type' => 'hidden',
                    'slug' => 'o',
                    'value' => $data->pay_id
                ),
                array(
                    'type' => 'hidden',
                    'slug' => 'us_user_id',
                    'value' => $data->user_id
                ),
                array(
                    'type' => 'hidden',
                    'slug' => 'us_pay_type',
                    'value' => $data->pay_type
                ),
                array(
                    'type' => 'hidden',
                    'slug' => 'us_baggage_data',
                    'value' => $data->baggage_data
                )
            )
        ));

    }

    function result($process){
        global $rmag_options;

        $my_signature = md5(implode(':',array(
            $rmag_options['fk_shop'],
            $_REQUEST["AMOUNT"],
            $rmag_options['fk_secret_2'],
            $_REQUEST["MERCHANT_ORDER_ID"]
        )));

        if ($my_signature != $_REQUEST['SIGN']){ rcl_mail_payment_error($my_signature); die;}

        if(!parent::get_payment($_REQUEST["MERCHANT_ORDER_ID"])){
            parent::insert_payment(array(
                'pay_id' => $_REQUEST["MERCHANT_ORDER_ID"],
                'pay_summ' => $_REQUEST["AMOUNT"],
                'user_id' => $_REQUEST["us_user_id"],
                'pay_type' => $_REQUEST["us_pay_type"],
                'baggage_data' => $_REQUEST["us_baggage_data"],
            ));
        }

        die('YES');

    }

    function success($process){

        if(parent::get_payment($_REQUEST["MERCHANT_ORDER_ID"])){
            wp_redirect(get_permalink($process->page_successfully)); exit;
        } else {
            wp_die(__('Данные платежа не были найдены в базе данных.'));
        }

    }
}
