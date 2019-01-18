<?php

add_action( 'rcl_payments_gateway_init', 'rcl_gateway_robokassa_init', 10);
function rcl_gateway_robokassa_init() {
    rcl_gateway_register('robokassa', 'Rcl_Gateway_Robokassa');
}

class Rcl_Gateway_Robokassa extends Rcl_Gateway_Core{

    function __construct(){
        parent::__construct(array(
            'request'   => 'OutSum',
            'label'     => 'Робокасса',
            'submit'    => __('Оплатить через РОБОКАССУ'),
            'icon'      => rcl_addon_url('assets/robokassa.jpg',__FILE__)
        ));
    }

    function get_options(){
        global $rmag_options;

        $curs = array( 'RUB', 'USD', 'EUR' );

        if(false !== array_search($rmag_options['primary_cur'], $curs)) {

            $options = array(
                array(
                    'type' => 'text',
                    'slug' => 'robologin',
                    'title' => __('Login','rcl-robokassa')
                ),
                array(
                    'type' => 'select',
                    'slug' => 'robotest',
                    'title' => __('The status of the account ROBOKASSA','rcl-robokassa'),
                    'values' => array(
                        __('Work','rcl-robokassa'),
                        __('Test','rcl-robokassa')
                    ),
                    'childrens' => array(
                        0 => array(
                            array(
                                'type' => 'password',
                                'slug' => 'onerobopass',
                                'title' => __('1 Password','rcl-robokassa')
                            ),
                            array(
                                'type' => 'password',
                                'slug' => 'tworobopass',
                                'title' => __('2 Password','rcl-robokassa')
                            )
                        ),
                        1 => array(
                            array(
                                'type' => 'password',
                                'slug' => 'test_onerobopass',
                                'title' => __('1 Password','rcl-robokassa')
                            ),
                            array(
                                'type' => 'password',
                                'slug' => 'test_tworobopass',
                                'title' => __('2 Password','rcl-robokassa')
                            )
                        )
                    )
                ),
                array(
                    'type' => 'select',
                    'slug' => 'robo_fn',
                    'title' => __('Фискализация платежа','rcl-robokassa'),
                    'values' => array(
                        __('Отключено','rcl-robokassa'),
                        __('Включено','rcl-robokassa')
                    ),
                    'childrens' => array(
                        1 => array(
                            array(
                                'title' => __('Ставка НДС','rcl-robokassa'),
                                'type' => 'select',
                                'slug' => 'robo_nds',
                                'values' => array(
                                    'none' => __('без НДС'),
                                    'vat0' => __('НДС по ставке 0%'),
                                    'vat10' => __('НДС по ставке 10%'),
                                    'vat18' => __('НДС по ставке 18%'),
                                    'vat110' => __('НДС по ставке 10/110'),
                                    'vat118' => __('НДС по ставке 18/118'),
                                    'vat20' => __('НДС по ставке 20%'),
                                    'vat120' => __('НДС по ставке 20/120')
                                )
                            ),
                            array(
                                'title' => __('Система налогообложения','rcl-robokassa'),
                                'type' => 'select',
                                'slug' => 'robo_tax',
                                'values' => array(
                                    'osn' => __('ОСН'),
                                    'usn_income' => __('УСН (доходы)'),
                                    'usn_income_outcome' => __('УСН (доходы-расходы)'),
                                    'envd' => __('ЕНДВ'),
                                    'esn' => __('ЕСН'),
                                    'patent' => __('Патент')
                                )
                            )
                        )
                    )
                )
            );

        }else{

            $options = array(
                array(
                    'type' => 'custom',
                    'title' => __('Настройки подключения Робокассы'),
                    'content' => '<span style="color:red">Данное подключение не поддерживает действующую валюту сайта.<br>'
                        . 'Поддерживается работа с RUB, USD, EUR</span>'
                )
            );

        }

        return $options;

    }

    function get_form($data){
        global $rmag_options;

        $formaction = 'https://merchant.roboxchange.com/Index.aspx';

        if($rmag_options['robotest']==1){
            //$formaction = 'http://test.robokassa.ru/Index.aspx';
            $pass1 = $rmag_options['test_onerobopass'];
        }else{

            $pass1 = $rmag_options['onerobopass'];
        }

        $login = $rmag_options['robologin'];

        $baggage_data = ($data->baggage_data)? $data->baggage_data: 'false';

        $md_array = array(
            $login,
            $data->pay_summ,
            $data->pay_id,
            $data->currency,
            $pass1,
            'shp_a='.$data->user_id,
            'shp_b='.$data->pay_type,
            'shp_c='.$baggage_data
        );

        if($receipt = $this->get_receipt($data)){
            array_splice($md_array, 3, 0, $receipt);
        }

        $crc = md5(implode(':',$md_array));

        $fields = array(
            array(
                'slug' => 'MrchLogin',
                'type' => 'hidden',
                'value' => $login
            ),
            array(
                'slug' => 'OutSum',
                'type' => 'hidden',
                'value' => $data->pay_summ
            ),
            array(
                'slug' => 'InvId',
                'type' => 'hidden',
                'value' => $data->pay_id
            ),
            array(
                'slug' => 'SignatureValue',
                'type' => 'hidden',
                'value' => $crc
            ),
            array(
                'slug' => 'InvDesc',
                'type' => 'hidden',
                'value' => $data->description
            ),
            array(
                'slug' => 'shp_a',
                'type' => 'hidden',
                'value' => $data->user_id
            ),
            array(
                'slug' => 'shp_b',
                'type' => 'hidden',
                'value' => $data->pay_type
            ),
            array(
                'slug' => 'shp_c',
                'type' => 'hidden',
                'value' => $baggage_data
            ),
            array(
                'slug' => 'OutSumCurrency',
                'type' => 'hidden',
                'value' => $data->currency
            )
        );

        if($receipt){
            $fields[] = array(
                'slug' => 'Receipt',
                'type' => 'hidden',
                'value' => $receipt
            );
        }

        if($rmag_options['robotest']==1){
            $fields[] = array(
                'slug' => 'isTest',
                'type' => 'hidden',
                'value' => 1
            );
        }

        return parent::construct_form(array(
            'action' => $formaction,
            'method' => 'post',
            'fields' => $fields
        ));

    }

    function result($data){
        global $rmag_options;

        if(isset($_REQUEST["shp_d"])) return false;

        if($rmag_options['robotest']==1){
            $pass2 = $rmag_options['test_tworobopass'];
        }else{
            $pass2 = $rmag_options['tworobopass'];
        }

        $crc = strtoupper($_REQUEST["SignatureValue"]);

        $md_array = array(
            $_REQUEST["OutSum"],
            $_REQUEST["InvId"],
            $pass2,
            'shp_a='.$_REQUEST["shp_a"],
            'shp_b='.$_REQUEST["shp_b"],
            'shp_c='.$_REQUEST["shp_c"]
        );

        if($_REQUEST["OutSumCurrency"]){
            array_splice($md_array, 2, 0, $_REQUEST["OutSumCurrency"]);
        }

        $my_crc = strtoupper(md5(implode(':',$md_array)));

        if ($my_crc != strtoupper($_REQUEST["SignatureValue"])){
            rcl_mail_payment_error($my_crc);
            echo "bad sign\n"; exit();
        }

        if(!$this->get_payment($_REQUEST["InvId"]))
            $this->insert_payment(array(
                'pay_id' => $_REQUEST["InvId"],
                'pay_type' => $_REQUEST["shp_b"],
                'pay_summ' => $_REQUEST["OutSum"],
                'user_id' => $_REQUEST["shp_a"],
                'baggage_data' => $_REQUEST["shp_c"]
            ));

        echo "OK".$_REQUEST["InvId"]."\n"; exit();

    }

    function success($data){

        if(parent::get_payment($_REQUEST["InvId"])){
            wp_redirect(get_permalink($data->page_successfully)); exit;
        } else {
            wp_die(__('A record of the payment in the database was not found','rcl-robokassa'));
        }

    }

    function get_receipt($data){
        global $rmag_options;

        if(!$rmag_options['robo_fn']) return false;

        $items = array();

        if($data->pay_type == 1){

            $items[] = array(
                "name" => __('Пополнение личного счета'),
                "quantity" => 1,
                "sum" => $data->pay_summ,
                "tax" => $rmag_options['robo_nds']
            );

        }else if($data->pay_type == 2){

            $order = rcl_get_order($data->pay_id);

            if($order){

                foreach($order->products as $k => $product){

                    $items[] = array(
                        "name" => get_the_title($product->product_id),
                        "quantity" => $product->product_amount,
                        "sum" => $product->product_amount * $product->product_price,
                        "tax" => $rmag_options['robo_nds']
                    );

                }

            }

        }else{

            $items[] = array(
                "name" => $data->description,
                "quantity" => 1,
                "sum" => $data->pay_summ,
                "tax" => $rmag_options['robo_nds']
            );

        }

        return json_encode(array(
            'sno' => $rmag_options['robo_tax'],
            'items' => $items
        ));

    }
}
