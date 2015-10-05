<?php

add_action('init','rcl_add_yandexkassa_payment');
function rcl_add_yandexkassa_payment(){
    $pm = new Rcl_Yandexkassa_Payment();
    $pm->register_payment('yandexkassa');
}

class Rcl_Yandexkassa_Payment extends Rcl_Payment{

    public $form_pay_id;

    function register_payment($form_pay_id){
        $this->form_pay_id = $form_pay_id;
        parent::add_payment($this->form_pay_id, array('class'=>get_class($this),'request'=>'shopId'));
        if(is_admin()) $this->add_options();
    }

    function add_options(){
        add_filter('rcl_pay_option',(array($this,'options')));
        add_filter('rcl_pay_child_option',(array($this,'child_options')));
    }

    function options($options){
        $options[$this->form_pay_id] = __('Yandex.Kassa','rcl').' ('.__('not tested','rcl').')';
        return $options;
    }

    function child_options($child){

        $opt = new Rcl_Options();

        $child .= $opt->child(
            array(
                'name'=>'connect_sale',
                'value'=>$this->form_pay_id
            ),
            array(
                $opt->title(__('Connection settings Yandex.Kassa','rcl')),
                $opt->label(__('ID cash','rcl')),
                $opt->option('text',array('name'=>'shopid')),
                $opt->label(__('The room showcases','rcl')),
                $opt->option('text',array('name'=>'scid')),
                $opt->label(__('The secret word','rcl')),
                $opt->option('password',array('name'=>'secret_word')),
            )
        );

        return $child;
    }

    function pay_form($data){
        global $rmag_options;

        $shopid = $rmag_options['shopid'];
        $scid = $rmag_options['scid'];

        $formaction = 'https://money.yandex.ru/eshop.xml';

        $submit = ($this->pay_type==1)? __('Confirm the operation','rcl'): __('Pay through payment system','rcl');

        $hidden = $this->hidden(
                array(
                    'shopId'=>$shopid,
                    'scid'=>$scid,
                    'sum'=>$this->pay_summ,
                    'orderNumber'=>$this->pay_id,
                    'customerNumber'=>$this->user_id,
                    'typePay'=>$this->pay_type,
                )
            );

        $form = "<form id='form-payment-".$this->pay_id."' style='display: inline;' action='".$formaction."' method=POST>
            ".$hidden."
            <input class='recall-button' pay_type=submit value='$submit'>
        </form>";

        return $form;
    }

    function result($data){
        $data->pay_summ = $_REQUEST["orderSumAmount"];
        $data->pay_id = $_REQUEST["orderNumber"];
        $data->user_id = $_REQUEST["customerNumber"];
        $data->pay_type = $_REQUEST["typePay"];

        if($_REQUEST['checkOrder']) $this->check_pay($data);

        $code = $this->check_hash();
        if(!$code) parent::insert_pay($data);

        $this->ya_response($code);

    }

    function check_pay($data){
        $code = $this->check_hash($data);
        $this->ya_response($code);
    }

    function ya_response($code){
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <'.$_POST['action'].'Response performedDatetime="'.date('c').'" code="'.$code.'" invoiceId="'.$_POST['invoiceId'].'" shopId="'.$_POST['shopId'].'" />';
        die();
    }

    function check_hash($data){
        global $rmag_options;

        $hash = md5(
                $_POST['action']
                .';'.$data->pay_summ
                .';'.$_POST['orderSumCurrencyPaycash']
                .';'.$_POST['orderSumBankPaycash']
                .';'.$_POST['shopId']
                .';'.$_POST['invoiceId']
                .';'.$data->user_id
                .';'.$rmag_options['secret_word']
        );

        if (strtolower($hash) != strtolower($_POST['md5'])) {
                $code = 1;
        } else {
            //if (!$this->get_pay()) $code = 200; //Если данного заказа нет
            if ($data->pay_summ != $_POST['orderSumAmount']) {
                $code = 100;
            } else {
                $code = 0;
            }
        }

        if($code) rcl_mail_payment_error($hash);

        return $code;
    }

    function success(){
        global $rmag_options;

        $data['pay_id'] = $_REQUEST["orderNumber"];
        $data['user_id'] = $_REQUEST["customerNumber"];

        if(parent::get_pay((object)$data)){
            wp_redirect(get_permalink($rmag_options['page_successfully_pay'])); exit;
        } else {
            wp_die(__('A record of the payment in the database was not found','rcl'));
        }

    }
}

