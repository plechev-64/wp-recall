<?php
class Rcl_Payment{

    public $pay_id; //идентификатор платежа
    public $pay_summ; //сумма платежа
    public $pay_type; //тип платежа. 1 - пополнение личного счета, 2 - оплата заказа
    public $pay_date; //время платежа
    public $user_id; //идентификатор пользователя
    public $pay_status; //статус платежа
    public $pay_callback;
    public $description;
    public $connect = array();
    public $method = 'post';

    function __construct(){

    }

    function add_payment($type,$data){
        global $rcl_payments;
        $rcl_payments[$type] = (object)$data;
    }

    function payment_process($connect=false){
        global $post,$rmag_options;

        add_action('insert_pay_rcl',array($this,'pay_account'));

        $this->pay_date = current_time('mysql');
        if($post->ID==$rmag_options['page_result_pay']) $this->get_result($connect);
        if($post->ID==$rmag_options['page_success_pay']) $this->get_success($connect);
    }

    function get_result($connect){
        global $rmag_options,$rcl_payments;
        
        if(!$connect) $connect = $rmag_options['connect_sale'];

        if(isset($rcl_payments[$connect])){
            $obj = new $rcl_payments[$connect]->class;
            $method = 'result';
            $obj->$method($this);
        }else{
            return false;
        }
    }

    function get_success($connect){
        global $rmag_options,$rcl_payments;
        
        if(!$connect) $connect = $rmag_options['connect_sale'];

        if(isset($rcl_payments[$connect])){
            $obj = new $rcl_payments[$connect]->class;
            $method = 'success';
            $obj->$method();
        }else{
            return false;
        }

        if($this->get_pay()){
                wp_redirect(get_permalink($rmag_options['page_successfully_pay'])); exit;
        } else {
                wp_die(__('A record of the payment in the database was not found','wp-recall'));
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

    }

    function pay_account($data){

        if($data->pay_type!=1) return false;

        $oldcount = rcl_get_user_balance($data->user_id);

        if($oldcount) $newcount = $oldcount + $data->pay_summ;
        else $newcount = $data->pay_summ;

        rcl_update_user_balance($newcount,$data->user_id,__('Top up personal account','wp-recall'));

    }

    function get_form($args){

        global $rmag_options,$rcl_payments,$user_ID;
        
        $args = apply_filters('rcl_payform_args',$args);
        
        $type_connect = (isset($args['connect']))? $args['connect']: $rmag_options['connect_sale'];
        
        $types_connect = (is_array($type_connect))? $type_connect: array($type_connect);

        $this->pay_id = $args['id_pay'];
        $this->pay_summ = $args['summ'];
        $this->pay_type = $args['type'];
        $this->description = (isset($args['description']))? $args['description']: '';        
        
        if(!isset($args['user_id'])||!$args['user_id'])
            $this->user_id = $user_ID;
        else
            $this->user_id = $args['user_id'];
        
        $content = '<div class="rcl-types-connects">';
        
        foreach($types_connect as $type){

            if(isset($rcl_payments[$type])){
                 $connect = $rcl_payments[$type];
                 $class = $connect->class;
                 $this->connect = array(
                     'name'=>$connect->name,
                     'image'=>$connect->image
                 );
                 $obj = new $class;
                 $method = 'pay_form';
                 $content .= $obj->$method($this);
             }else{
                 $content .= '<div class="error"><p class="error">'.__('Error! Not configured the connection to the payment aggregator.','wp-recall').'</p></div>';
             }
        
        }
        
        $content .= '</div>';
        
        return $content;
        
    }

    function form($fields,$data,$formaction){
        global $rmag_options,$user_ID;
        
        $fields = apply_filters('rcl_pay_form_fields',$fields,$data);

        $submit = ($data->pay_type==1)? __('Confirm the operation','wp-recall'): __('To pay via','wp-recall').' "'.$data->connect['name'].'"';
        
        $form = '<div class="rcl-pay-form">';
        
        $background = (isset($data->connect['image'])&&$data->pay_type==2)? 'style="background-image: url('.$data->connect['image'].');"': '';
        
        $form .= "<div class='rcl-pay-button'>"
                    . "<form id='form-payment-".$data->pay_id."' action='".$formaction."' method=$data->method>"
                    . $this->get_hiddens( $fields )
                    . "<span class='rcl-connect-submit' ".$background.">"
                        . "<input class='recall-button' type=submit value='$submit'>"
                    . "</span>"
                    . "</form>"
                . "</div>";

        $form .= '</div>';

        return $form;
    }
    
    function personal_account_pay_form($pay_id, $args = array()){
        
        $pay_callback = (isset($args['callback']))? $args['callback']: 'rcl_pay_order_private_account';
        $submit = (isset($args['submit']))? $args['submit']: __('Pay personal account','wp-recall');
        
        $form = '<div class="rcl-account-pay">';
            $form .= '<div class="rcl-pay-form">';

            $form .= '<div class="rcl-pay-button">'
                        . '<span class=rcl-connect-submit><i class="fa fa-credit-card" aria-hidden="true"></i>'
                        . '<input class="recall-button" type="button" name="pay_order" onclick="'.$pay_callback.'(this);return false;" data-order="'.$pay_id.'" value="'.$submit.'">'
                        . '</span>'
                    . '</div>';

            $form .= '</div>';
        $form .= '</div>';
        
        return $form;
    }

    function get_hiddens($args){
        foreach($args as $key=>$val){
            $form .= "<input type=hidden name=$key value='$val'>";
        }
        return $form;
    }

}

function rcl_mail_payment_error($hash=false,$other=false){
    global $rmag_options,$post;
    
    if($other){
        foreach($other as $k=>$v){
            $textmail .= $k.' - '.$v.'<br>';
        }
    }

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
    if(!$email) $email = get_the_author_meta( 'user_email',1 );

    rcl_mail($email, $title, $textmail);
}

function rcl_payments(){
    global $rmag_options,$rcl_payments;

    if(!isset($rmag_options['connect_sale'])||!$rmag_options['connect_sale']) return false;
    if(!isset($rcl_payments[$rmag_options['connect_sale']])||is_array($rmag_options['connect_sale'])) return false;

    if (isset($_REQUEST[$rcl_payments[$rmag_options['connect_sale']]->request])){
        $payment = new Rcl_Payment();
        $payment->payment_process();
    }
}
add_action('wp', 'rcl_payments',10);