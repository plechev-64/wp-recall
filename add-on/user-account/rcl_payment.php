<?php
class Rcl_Payment{

    public $pay_id; //идентификатор платежа
    public $pay_summ; //сумма платежа
    public $pay_type; //тип платежа. 1 - пополнение личного счета, 2 - оплата заказа
    public $pay_date; //время платежа
    public $user_id; //идентификатор пользователя
    public $pay_status; //статус платежа
    public $pay_callback;

    function __construct(){

    }

    function add_payment($type,$data){
        global $rcl_payments;
        $rcl_payments[$type] = (object)$data;
    }

    function payment_process(){
        global $post,$rmag_options;

        add_action('insert_pay_rcl',array($this,'pay_account'));

        $this->pay_date = current_time('mysql');
        if($post->ID==$rmag_options['page_result_pay']) $this->get_result();
        if($post->ID==$rmag_options['page_success_pay']) $this->get_success();
    }

    function get_result(){
        global $rmag_options,$rcl_payments;

        if(isset($rcl_payments[$rmag_options['connect_sale']])){
            $obj = new $rcl_payments[$rmag_options['connect_sale']]->class;
            $method = 'result';
            $obj->$method($this);
        }else{
            return false;
        }
    }

    function get_success(){
        global $rmag_options,$rcl_payments;

        if(isset($rcl_payments[$rmag_options['connect_sale']])){
            $obj = new $rcl_payments[$rmag_options['connect_sale']]->class;
            $method = 'success';
            $obj->$method();
        }else{
            return false;
        }

        if($this->get_pay()){
                wp_redirect(get_permalink($rmag_options['page_successfully_pay'])); exit;
        } else {
                wp_die(__('A record of the payment in the database was not found','rcl'));
        }
    }

    function get_pay($data){
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RMAG_PREF ."pay_results WHERE inv_id = '%s' AND user = '%d'",$data->pay_id,$data->user_id));
    }

    function insert_pay($data){
        global $wpdb;

        $data->pay_status = $wpdb->insert( RMAG_PREF .'pay_results',
            array(
                'inv_id' => $data->pay_id,
                'user' => $data->user_id,
                'count' => $data->pay_summ,
                'time_action' => $data->pay_date
            )
        );

        if(!$data->pay_status) exit;

        do_action('insert_pay_rcl',$data);

        if($data->pay_status)
            do_action('payment_rcl',$data->user_id,$data->pay_summ,$data->pay_id,$data->pay_type);

        echo 'OK'; exit;
    }

    function pay_account($data){

        if($data->pay_type!=1) return false;

        $oldcount = rcl_get_user_money($data->user_id);

        if($oldcount) $newcount = $oldcount + $data->pay_summ;
        else $newcount = $data->pay_summ;

        rcl_update_user_money($newcount,$data->user_id);

        do_action('payment_payservice_rcl',$data->user_id,$data->pay_summ,__('Top up personal account','rcl'),2);
    }

    function get_form($args){

        global $rmag_options,$rcl_payments,$user_ID;

        $this->pay_callback = (isset($args['callback']))? $args['callback']: 'rcl_pay_order_private_account';
        $this->pay_id = $args['id_pay'];
        $this->pay_summ = $args['summ'];
        $this->pay_type = $args['type'];
        if(!$args['user_id']) $this->user_id = $user_ID;
        else $this->user_id = $args['user_id'];

       if(isset($rcl_payments[$rmag_options['connect_sale']])){
            $obj = new $rcl_payments[$rmag_options['connect_sale']]->class;
            $method = 'pay_form';
            return $obj->$method($this);
        }
    }

    function form($fields,$data,$formaction){
        global $rmag_options,$user_ID;

        $submit = ($data->pay_type==1)? __('Confirm the operation','rcl'): __('Pay through payment system','rcl');

        $form = "<form id='form-payment-".$data->pay_id."' style='display: inline;' action='".$formaction."' method=POST>"
                .$this->get_hiddens( $fields )
                ."<input class='recall-button' type=submit value='$submit'>"
                ."</form>";

        $type_p = $rmag_options['type_order_payment'];
        if($user_ID&&$type_p==2&&$data->pay_type==2)
            $form .= '<input class="recall-button" type="button" name="pay_order" onclick="'.$data->pay_callback.'(this);return false;" data-order="'.$data->pay_id.'" value="'.__('Pay personal account','rcl').'">';

        return $form;
    }

    function get_hiddens($args){
        foreach($args as $key=>$val){
            $form .= "<input type=hidden name=$key value='$val'>";
        }
        return $form;
    }

}

function rcl_mail_payment_error($hash=false){
    global $rmag_options,$post;

    foreach($_REQUEST as $key=>$R){
        $textmail .= $key.' - '.$R.'<br>';
    }

    if($hash){
        $textmail .= 'Cформированный хеш - '.$hash.'<br>';
        $title = 'Неудачная оплата';
    }else{
        $title = 'Данные платежа';
    }

    $textmail .= 'Текущий пост - '.$post->ID.'<br>';
    $textmail .= 'RESULT - '.$rmag_options['page_result_pay'].'<br>';
    $textmail .= 'SUCCESS - '.$rmag_options['page_success_pay'].'<br>';

    $email = $rmag_options['admin_email_magazin_recall'];
    if(!$email) $email = get_user_meta( 1, 'user_email', true );

    rcl_mail($email, $title, $textmail);
}

function rcl_payments(){
    global $rmag_options,$rcl_payments;

    if(!$rmag_options['connect_sale']) return false;

    if (isset($_REQUEST[$rcl_payments[$rmag_options['connect_sale']]->request])){
        $payment = new Rcl_Payment();
        $payment->payment_process();
    }
}
add_action('wp', 'rcl_payments');