<?php
class Rcl_Payment{

    public $id_pay; //идентификатор платежа
    public $summ; //сумма платежа
    public $type; //тип платежа. 1 - пополнение личного счета, 2 - оплата заказа
    public $time; //время платежа
    public $user; //идентификатор пользователя
    public $pay; //статус платежа

    function __construct(){
        global $post,$rmag_options;

        add_action('insert_pay_rcl',array($this,'pay_account'));

        $this->time = current_time('mysql');
        if($post->ID==$rmag_options['page_result_pay']) $this->result();
        if($post->ID==$rmag_options['page_success_pay']) $this->success();
    }

    function result(){
        global $rmag_options;
        if($rmag_options['connect_sale']==1) $this->robokassa();
	if($rmag_options['connect_sale']==2) $this->interkassa();
        if($rmag_options['connect_sale']==3) $this->yandexkassa();

        if($this->pay) do_action('payment_rcl',$this->user,$this->summ,$this->id_pay,$this->type);
        echo 'OK';
        exit;
    }

    function success(){
        global $rmag_options;

        if($rmag_options['connect_sale']==1){ //если используется робокасса
                $this->id_pay = $_REQUEST["InvId"];
                $this->user = $_REQUEST["shpa"];
        }

        if($rmag_options['connect_sale']==2){ //если используется Интеркасса
                $this->id_pay = $_REQUEST["ik_pm_no"];
                $this->user = $_REQUEST["ik_x_user_id"];
        }

        if($this->get_pay()){
                wp_redirect(get_permalink($rmag_options['page_successfully_pay'])); exit;
        } else {
                wp_die(__('A record of the payment in the database was not found','rcl'));
        }

    }

    function get_pay(){
        global $wpdb;
         return $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RMAG_PREF ."pay_results WHERE inv_id = '%s' AND user = '%d'",$this->id_pay,$this->user));
    }

    function insert_pay(){
        global $wpdb;

        $this->pay = $wpdb->insert( RMAG_PREF .'pay_results',
            array(
                'inv_id' => $this->id_pay,
                'user' => $this->user,
                'count' => $this->summ,
                'time_action' => $this->time
            )
        );

        if(!$this->pay) exit;

        do_action('insert_pay_rcl',$this);

    }

    function pay_account($data){
        global $wpdb;

        if($data->type!=1) return false;

        $oldcount = rcl_get_user_money($this->user);

        if($oldcount) $newcount = $oldcount + $this->summ;
        else $newcount = $this->summ;

        rcl_update_user_money($newcount,$this->user);

        do_action('payment_payservice_rcl',$this->user,$this->summ,__('Top up personal account','rcl'),2);
    }

    function yandexkassa(){

        $this->summ = $_REQUEST["orderSumAmount"];
        $this->id_pay = $_REQUEST["orderNumber"];
        $this->user = $_REQUEST["customerNumber"];
        $this->type = $_REQUEST["typePay"];

        if($_REQUEST['checkOrder']) $this->check_pay();

        $code = $this->check_hash();
        if(!$code) $this->insert_pay();

        $this->ya_response($code);
    }

    function check_hash(){
        global $rmag_options;

        $hash = md5(
                $_POST['action']
                .';'.$this->summ
                .';'.$_POST['orderSumCurrencyPaycash']
                .';'.$_POST['orderSumBankPaycash']
                .';'.$_POST['shopId']
                .';'.$_POST['invoiceId']
                .';'.$this->user
                .';'.$rmag_options['secret_word']
        );

        if (strtolower($hash) != strtolower($_POST['md5'])) {
                $code = 1;
        } else {
            //if (!$this->get_pay()) $code = 200; //Если данного заказа нет
            if ($this->summ != $_POST['orderSumAmount']) {
                $code = 100;
            } else {
                $code = 0;
            }
        }

        if($code) $this->mail_error($hash);

        return $code;
    }

    function check_pay(){
        $code = $this->check_hash();
        $this->ya_response($code);
    }

    function ya_response($code){
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <'.$_POST['action'].'Response performedDatetime="'.date('c').'" code="'.$code.'" invoiceId="'.$_POST['invoiceId'].'" shopId="'.$_POST['shopId'].'" />';
        die();
    }

    function robokassa(){
        global $rmag_options;

        $this->summ = $_REQUEST["OutSum"];
        $this->id_pay = $_REQUEST["InvId"];
        $this->user = $_REQUEST["shpa"];
        $this->type = $_REQUEST["shpb"];

        $crc = strtoupper($_REQUEST["SignatureValue"]);

        $my_crc = strtoupper(md5
                ("$this->summ:"
                . "$this->id_pay:"
                . "".$rmag_options['tworobopass'].":"
                . "Shp_item=".$_REQUEST['Shp_item'].":"
                . "shpa=$this->user:"
                . "shpb=$this->type"));

        if ($my_crc !=$crc){ $this->mail_error($my_crc); die;}

        if(!$this->get_pay()) $this->insert_pay();

    }

    function interkassa(){
        global $rmag_options;

        $this->summ = $_REQUEST["ik_am"];
        $this->id_pay = $_REQUEST["ik_pm_no"];
        $this->user = $_REQUEST["ik_x_user_id"];
        $this->type = $_REQUEST["ik_x_type"];

        foreach ($_POST as $key => $value) {
            if (!preg_match('/ik_/', $key)) continue;
            $data[$key] = $value;
        }

        $ikSign = $data['ik_sign'];
        unset($data['ik_sign']);

        if ($data['ik_pw_via'] == 'test_interkassa_test_xts') {
            $secret_key = $rmag_options['intertestkey'];
        } else {
            $secret_key = $rmag_options['intersecretkey'];
        }

        ksort ($data, SORT_STRING);
        array_push($data, $secret_key);
        $signStr = implode(':', $data);
        $sign = base64_encode(md5($signStr, true));

        if ($sign !=$ikSign){ $this->mail_error($sign); die;}

        if(!$this->get_pay()) $this->insert_pay();
    }

    function mail_error($hash=false){
	global $rmag_options,$post;

	foreach($_REQUEST as $key=>$R){
		$textmail .= $key.' - '.$R.'<br>';
	}

	if($hash){
		$textmail .= 'Cформированный хеш - '.$hash.'<br>';
		$title = 'Неудачная оплата';
	}else{
		$title = 'Данные тестового платежа';
	}

	$textmail .= 'Текущий пост - '.$post->ID.'<br>';
	$textmail .= 'RESULT - '.$rmag_options['page_result_pay'].'<br>';
	$textmail .= 'SUCCESS - '.$rmag_options['page_success_pay'].'<br>';

	$email = $rmag_options['admin_email_magazin_recall'];
	if(!$email) $email = get_user_meta( 1, 'user_email', true );

	rcl_mail($email, $title, $textmail);
    }
}

function rcl_payments(){
    global $rmag_options;
    $reqs = array(0,'InvId','ik_co_id','shopId');
    if(!$rmag_options['connect_sale']) return false;
    if (isset($_REQUEST[$reqs[$rmag_options['connect_sale']]])){
        $payment = new Rcl_Payment();
    }
}
add_action('wp', 'rcl_payments');