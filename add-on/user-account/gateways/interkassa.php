<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of rcl_interkassa_form
 *
 * @author Андрей
 */
add_action('init','rcl_add_interkassa_payment');
function rcl_add_interkassa_payment(){
    $pm = new Rcl_Interkassa_Payment();
    $pm->register_payment('interkassa');
}

class Rcl_Interkassa_Payment extends Rcl_Payment{

    public $form_pay_id;

    function register_payment($form_pay_id){
        $this->form_pay_id = $form_pay_id;
        parent::add_payment($this->form_pay_id, array('class'=>get_class($this),'request'=>'ik_co_id' ));
        if(is_admin()) $this->add_options();
    }

    function add_options(){
        add_filter('rcl_pay_option',(array($this,'options')));
        add_filter('rcl_pay_child_option',(array($this,'child_options')));
    }

    function options($options){
        $options[$this->form_pay_id] = __('Interkassa','wp-recall');
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
                $opt->title(__('Connection settings Interkassa','wp-recall')),
                $opt->label(__('Secret Key','wp-recall')),
                $opt->option('password',array('name'=>'intersecretkey')),
                $opt->label(__('Test Key','wp-recall')),
                $opt->option('password',array('name'=>'intertestkey')),
                $opt->label(__('The ID of the store','wp-recall')),
                $opt->option('text',array('name'=>'interidshop')),
                $opt->label(__('The status of the account Interkassa','wp-recall')),
                $opt->option('select',array(
                    'name'=>'interkassatest',
                    'options'=>array(
                        __('Work','wp-recall'),
                        __('Test','wp-recall')
                    )
                )),
            )
        );

        return $child;
    }

    function pay_form($data){
        global $rmag_options;

        $shop_id = $rmag_options['interidshop'];
        $test = $rmag_options['interkassatest'];
        $key = $rmag_options['intersecretkey'];

        if($this->pay_type==1) $arr['ik_desc'] = __('Top up personal account','wp-recall');
        else if($this->pay_type==2) $arr['ik_desc'] = __('Payment for the order on the website','wp-recall');
        else $arr['ik_desc'] = __('Other payments','wp-recall');

        $submit = ($data->pay_type==1)? __('Confirm the operation','wp-recall'): __('Pay through payment system','wp-recall');

        if($test==1){
            $ik_pw_via = 'test_interkassa_test_xts';
            $arr['ik_pw_via'] = $ik_pw_via;
            $test_input = "<input pay_type='hidden' name='ik_pw_via' value='$ik_pw_via'>";
        }

        $arr['ik_am'] = $data->pay_summ;
        $arr['ik_co_id'] = $shop_id;
        $arr['ik_pm_no'] = $data->pay_id;
        $arr['ik_x_user_id'] = $data->user_id;
        $arr['ik_x_type'] = $data->pay_type;

        ksort ($arr, SORT_STRING);
        array_push($arr, $key);
        $signStr = implode(':', $arr);
        $ik_sign = base64_encode(md5($signStr, true));

        $fields = array(
                'ik_co_id'=>$shop_id,
                'ik_am'=>$data->pay_summ,
                'ik_pm_no'=>$data->pay_id,
                'ik_desc'=>$arr['ik_desc'],
                'ik_x_user_id'=>$data->user_id,
                'ik_sign'=>$ik_sign,
                'ik_x_type'=>$data->pay_type
            );

        $form = parent::form($fields,$data,'https://sci.interkassa.com/');

        return $form;
    }

    function result($data){
        global $rmag_options;

        $data->pay_summ = $_REQUEST["ik_am"];
        $data->pay_id = $_REQUEST["ik_pm_no"];
        $data->user_id = $_REQUEST["ik_x_user_id"];
        $data->pay_type = $_REQUEST["ik_x_type"];

        foreach ($_POST as $key => $value) {
            if (!preg_match('/ik_/', $key)) continue;
            $arr[$key] = $value;
        }

        $ikSign = $arr['ik_sign'];
        unset($arr['ik_sign']);

        if ($arr['ik_pw_via'] == 'test_interkassa_test_xts') {
            $secret_key = $rmag_options['intertestkey'];
        } else {
            $secret_key = $rmag_options['intersecretkey'];
        }

        ksort ($arr, SORT_STRING);
        array_push($arr, $secret_key);
        $signStr = implode(':', $arr);
        $sign = base64_encode(md5($signStr, true));

        if ($sign !=$ikSign){ rcl_mail_payment_error($sign); die;}

        if(!parent::get_pay($data)) parent::insert_pay($data);

        exit;
    }

    function success(){
        global $rmag_options;

        $data['pay_id'] = $_REQUEST["ik_pm_no"];
        $data['user_id'] = $_REQUEST["ik_x_user_id"];

        if(parent::get_pay((object)$data)){
            wp_redirect(get_permalink($rmag_options['page_successfully_pay'])); exit;
        } else {
            wp_die(__('A record of the payment in the database was not found','wp-recall'));
        }

    }
}

