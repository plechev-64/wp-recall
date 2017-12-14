<?php

require_once "class-rcl-payment.php";
require_once "shortcodes.php";

if(is_admin()) 
    require_once 'admin/index.php';

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_user_account_scripts',10);
endif;

function rcl_user_account_scripts(){
    rcl_enqueue_style('rcl-user-account',rcl_addon_url('style.css', __FILE__));
    rcl_enqueue_script( 'rcl-user-account', rcl_addon_url('js/scripts.js', __FILE__) );
}

add_filter('rcl_init_js_variables','rcl_init_js_account_variables',10);
function rcl_init_js_account_variables($data){
    global $user_ID;
    $data['account']['currency'] = rcl_get_primary_currency(1);
    $data['account']['balance'] = rcl_get_user_balance($user_ID);        
    return $data;
}

add_action('init','rmag_get_global_unit_wallet',10);
function rmag_get_global_unit_wallet(){
    
    if (defined('RMAG_PREF')) return false;
    
    global $wpdb;
    global $rmag_options;
    $rmag_options = get_option('primary-rmag-options');
    define('RMAG_PREF', $wpdb->prefix."rmag_");
    
}

add_action('wp', 'rcl_payments',10);
function rcl_payments(){
    global $rmag_options,$rcl_payments;

    if(!isset($rmag_options['connect_sale'])||!$rmag_options['connect_sale']) return false;
    if(!isset($rcl_payments[$rmag_options['connect_sale']])||is_array($rmag_options['connect_sale'])) return false;

    if (isset($_REQUEST[$rcl_payments[$rmag_options['connect_sale']]->request])){
        $payment = new Rcl_Payment();
        $payment->payment_process();
    }
}

add_action('rcl_success_pay_system','rcl_success_pay',10);
add_action('rcl_success_pay_balance','rcl_success_pay',10);
function rcl_success_pay($dataPay){
    do_action('rcl_success_pay',$dataPay);
}

//получение данных из таблицы произведенных платежей
function rcl_get_payments($args = false){
    require_once 'class-rcl-payments.php';
    $payments = new Rcl_Payments();
    return $payments->get_results($args);
}

function rcl_payform($attr){
    return rcl_get_pay_form($attr);
}

function rcl_get_user_balance($user_id=false){
    global $wpdb,$user_ID;
    
    if(!$user_id) $user_id = $user_ID;
    
    $balance = $wpdb->get_var($wpdb->prepare("SELECT user_balance FROM ".RMAG_PREF."users_balance WHERE user_id='%d'",$user_id));
    
    return $balance;
}

function rcl_update_user_balance($newmoney,$user_id,$comment=''){
    global $wpdb;
    
    $newmoney = round(str_replace(',','.',$newmoney), 2);

    $money = rcl_get_user_balance($user_id);

    if(isset($money)){
        
        do_action('rcl_pre_update_user_balance',$newmoney,$user_id,$comment);
        
        $result = $wpdb->update(RMAG_PREF .'users_balance',
            array( 'user_balance' => $newmoney ),
            array( 'user_id' => $user_id )
        );

        if(!$result){
            rcl_add_log(
                'rcl_update_user_balance: '.__('Failed to refresh user balance','wp-recall'),
                array($newmoney,$user_id,$comment)
            );
        }
        
        return $result;
        
    }

    return rcl_add_user_balance($newmoney,$user_id,$comment);
}

function rcl_add_user_balance($money,$user_id,$comment=''){
    global $wpdb;

    $result =  $wpdb->insert( RMAG_PREF .'users_balance',
	array( 'user_id' => $user_id, 'user_balance' => $money ));
    
    if(!$result){
        rcl_add_log(
            'rcl_add_user_balance: '.__('Failed to add user balance','wp-recall'),
            array($money,$user_id,$comment)
        );
    }
    
    do_action('rcl_add_user_balance',$money,$user_id,$comment);
    
    return $result;
}

function rcl_get_html_usercount(){
    global $user_ID,$rmag_options;
    
    $id = rand(1,100);

    $usercount = '<div class="rcl-widget-balance" id="rcl-widget-balance-'.$id.'">';

    $user_count = rcl_get_user_balance();
    if(!$user_count) $user_count = 0;

    $usercount .= '<div class="usercount" style="text-align:center;">'.$user_count.' '.rcl_get_primary_currency(1).'</div>';

    $usercount = apply_filters('count_widget_rcl',$usercount);

    if($rmag_options['connect_sale']!='') 
        $usercount .= "<div class='rcl-toggle-form-balance'>"
                . "<a class='recall-button rcl-toggle-form-link' href='#'>"
                .__("Top up",'wp-recall')
                ."</a>
            </div>
            <div class='rcl-form-balance'>               
                ".rcl_form_user_balance(array('idform'=>$id))."
            </div>";

    $usercount .= '</div>';

    return $usercount;
}

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

//пополнение баланса пользователя
add_action('rcl_success_pay_system','rcl_pay_user_balance',10);
function rcl_pay_user_balance($data){

    if($data->pay_type!=1) return false;

    $oldcount = rcl_get_user_balance($data->user_id);

    if($oldcount) $newcount = $oldcount + $data->pay_summ;
    else $newcount = $data->pay_summ;

    rcl_update_user_balance($newcount,$data->user_id,__('Top up personal account','wp-recall'));

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

    $email = get_option( 'admin_email' );

    rcl_mail($email, $title, $textmail);
}

add_action( 'widgets_init', 'rcl_widget_usercount' );
function rcl_widget_usercount() {
    register_widget( 'Rcl_Widget_user_count' );
}

class Rcl_Widget_user_count extends WP_Widget {

    function __construct() {
            $widget_ops = array( 'classname' => 'widget-user-count', 'description' => __('Personal user account','wp-recall') );
            $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'widget-user-count' );
            parent::__construct( 'widget-user-count', __('Personal account','wp-recall'), $widget_ops, $control_ops );
    }

    function widget( $args, $instance ) {
        extract( $args );

        $title = apply_filters('widget_title', $instance['title'] );
        global $user_ID;

        if ($user_ID){
            echo $before_widget;
            if ( $title ) echo $before_title . $title . $after_title;
            echo rcl_get_html_usercount();
            echo $after_widget;
        }

    }

    //Update the widget
    function update( $new_instance, $old_instance ) {
            $instance = $old_instance;
            //Strip tags from title and name to remove HTML
            $instance['title'] = strip_tags( $new_instance['title'] );
            return $instance;
    }

    function form( $instance ) {
            //Set up some default widget settings.
            $defaults = array( 'title' => __('Personal account','wp-recall'));
            $instance = wp_parse_args( (array) $instance, $defaults ); ?>
            <p>
                    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title','wp-recall'); ?></label>
                    <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
            </p>
    <?php
    }
}