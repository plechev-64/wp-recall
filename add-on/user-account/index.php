<?php

rcl_enqueue_style('user_account',__FILE__);

include_once "rcl_payment.php";
include_once 'gateways/robokassa.php';
include_once 'gateways/interkassa.php';
include_once 'gateways/walletone.php';
//include_once 'gateways/yandexkassa.php';

if(is_admin()) include_once 'payments.php';
if(is_admin()) require_once 'addon-options.php';

function rcl_payform($args){
    $payment = new Rcl_Payment();
    return $payment->get_form($args);
}

function rmag_get_global_unit_wallet(){
    if (!defined('RMAG_PREF')){
            global $wpdb;
            global $rmag_options;
            $rmag_options = get_option('primary-rmag-options');
            define('RMAG_PREF', $wpdb->prefix."rmag_");
    }
}
add_action('init','rmag_get_global_unit_wallet',10);

if (is_admin()):
	add_action('admin_head','rcl_admin_user_account_scripts');
endif;

function rcl_admin_user_account_scripts(){
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'rcl_admin_user_account_scripts', plugins_url('js/admin.js', __FILE__) );
}

function rcl_get_user_money($user_id=false){
    global $wpdb,$user_ID;
    if(!$user_id) $user_id = $user_ID;
    return $wpdb->get_var($wpdb->prepare("SELECT user_balance FROM ".RMAG_PREF."users_balance WHERE user_id='%d'",$user_id));
}

function rcl_update_user_money($newmoney,$user_id,$comment=''){
    global $wpdb;
    
    $newmoney = round(str_replace(',','.',$newmoney), 2);

    $money = rcl_get_user_money($user_id);

    if(isset($money)){
        
        do_action('rcl_pre_update_user_money',$newmoney,$user_id,$comment);
        
        return $wpdb->update(RMAG_PREF .'users_balance',
            array( 'user_balance' => $newmoney ),
            array( 'user_id' => $user_id )
        );
        
        do_action('rcl_update_user_money',$newmoney,$user_id,$comment);
        
    }

    return rcl_add_user_money($newmoney,$user_id,$comment);
}

function rcl_add_user_money($money,$user_id,$comment=''){
    global $wpdb;

    $result =  $wpdb->insert( RMAG_PREF .'users_balance',
	array( 'user_id' => $user_id, 'user_balance' => $money ));
    
    do_action('rcl_insert_user_money',$money,$user_id,$comment);
    
    return $result;
}

// создаем допколонку для вывода баланса пользователя
function rcl_balance_user_admin_column( $columns ){

  return array_merge( $columns,
    array( 'balance_user_recall' => __("Balance",'wp-recall') )
  );

}
add_filter( 'manage_users_columns', 'rcl_balance_user_admin_column' );

function rcl_balance_user_admin_content( $custom_column, $column_name, $user_id ){
global $wpdb;

  switch( $column_name ){
    case 'balance_user_recall':
          $user_count = rcl_get_user_money($user_id);
	  $custom_column = '<input type="text" class="balanceuser-'.$user_id.'" size="4" value="'.$user_count.'"><input type="button" class="recall-button edit_balance" id="user-'.$user_id.'" value="Ок">';
          $custom_column = apply_filters('balans_column_rcl',$custom_column,$user_id);
          break;
  }
  return $custom_column;

}
add_filter( 'manage_users_custom_column', 'rcl_balance_user_admin_content', 10, 3 );

/*************************************************
Пополнение личного счета пользователя
*************************************************/
function rcl_add_count_user(){
    global $user_ID;

    rcl_verify_ajax_nonce();

    if($user_ID&&$_POST['count']){

        $amount = intval($_POST['count']);
        $id_pay = current_time('timestamp');

        $log['redirectform'] = rcl_payform(array('id_pay'=>$id_pay,'summ'=>$amount,'type'=>1));
        $log['otvet']=100;

    } else {
            $log['otvet']=1;
    }
    echo json_encode($log);
    exit;
}
if(is_admin()) add_action('wp_ajax_rcl_add_count_user', 'rcl_add_count_user');

/*************************************************
Меняем баланс пользователя из админки
*************************************************/
function rcl_edit_balance_user(){

    $user_id = intval($_POST['user']);
    $balance = floatval(str_replace(',','.',$_POST['balance']));

    rcl_update_user_money($balance,$user_id,__('The change in the balance','wp-recall'));

    $log['otvet']=100;
    $log['user']=$user_id;
    $log['balance']=$balance;

    echo json_encode($log);
    exit;
}
if(is_admin()) add_action('wp_ajax_rcl_edit_balance_user', 'rcl_edit_balance_user');

function rcl_get_html_usercount(){
    global $user_ID,$rmag_options;

    $usercount = '<div id="user-count-rcl">';

    $user_count = rcl_get_user_money();
    if(!$user_count) $user_count = 0;

    $usercount .= '<div class="usercount" style="text-align:center;">'.$user_count.' '.rcl_get_primary_currency(1).'</div>';


    $usercount = apply_filters('count_widget_rcl',$usercount);

    if($rmag_options['connect_sale']!='') $usercount .= "<p align='right'><a class='go_to_add_count' href='#'>".__("Deposit",'wp-recall')."</a></p>
    <div class='count_user'>
    <p>".__("To recharge your account",'wp-recall')."</p>
    <div>
    <p style='margin-bottom: 10px;'><label>".__("Enter the amount required",'wp-recall')."</label></p>
        <input class='value_count_user' size='4' type='text' value=''>
        <input class='add_count_user recall-button' type='button' value='".__("Send",'wp-recall')."'>
    </div>
    <div class='redirectform' style='margin:10px 0;text-align:center;'></div>
    </div>";

    $usercount .= '</div>';

    return $usercount;
}

add_filter('file_scripts_rcl','rcl_get_useraccount_scripts');
function rcl_get_useraccount_scripts($script){

	$ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,";

	$script .= "
            /* Пополняем личный счет пользователя */
            jQuery('body').on('click','.add_count_user',function(){
                var count = jQuery('.value_count_user');
                var addcount = count.val();
                var dataString = 'action=rcl_add_count_user&count='+addcount;
                dataString += '&ajax_nonce='+Rcl.nonce;
                jQuery.ajax({
                        ".$ajaxdata."
                        success: function(data){
                                if(data['otvet']==100){
                                        jQuery('.redirectform').html(data['redirectform']);
                                } else {
                                   alert('Ошибка проверки данных.');
                                }
                        }
                });
                return false;
            });

            jQuery('body').on('click','.go_to_add_count',function(){
                jQuery('.count_user').slideToggle();
                return false;
            });
	";
	return $script;
}

add_filter('rcl_functions_js','rcl_add_user_count_functions');
function rcl_add_user_count_functions($string){
    $ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,";
    $string .= "/* Оплачиваем заказ средствами из личного счета */
    function rcl_pay_order_private_account(e){
        var idorder = jQuery(e).data('order');
        var dataString = 'action=rcl_pay_order_private_account&idorder='+ idorder;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
        ".$ajaxdata."
        success: function(data){
                if(data['otvet']==100){
                    jQuery('.order_block').find('.pay_order').each(function() {
                            if(jQuery(e).attr('name')==data['idorder']) jQuery(e).remove();
                    });
                    jQuery('.redirectform').html(data['recall']);
                    jQuery('.usercount').html(data['count']);
                    jQuery('.order-'+data['idorder']+' .remove_order').remove();
                    jQuery('#manage-order').remove();
                }else{
                    rcl_notice('Недостаточно средств на счету! Сумма заказа: '+data['recall'],'error');
                }
        }
        });
        return false;
    }";
    return $string;
}

function rcl_get_chart_payments($pays){
    global $chartData,$chartArgs;

    if(!$pays) return false;

    $chartArgs = array();
    $chartData = array(
        'title' => __('Income dynamics','wp-recall'),
        'title-x' => __('The time period','wp-recall'),
        'data'=>array(
            array(__('"Days/Months"','wp-recall'), __('"Payments (PCs.)"','wp-recall'), __('"Income (thousands)"','wp-recall'))
        )
    );

    foreach($pays as $pay){
        $pay = (object)$pay;
        rcl_setup_chartdata($pay->time_action,$pay->count);
    }

    return rcl_get_chart($chartArgs);
}

add_shortcode('rcl-usercount','rcl_shortcode_usercount');
function rcl_shortcode_usercount(){
	return rcl_get_html_usercount();
}

add_action( 'widgets_init', 'rcl_widget_usercount' );
function rcl_widget_usercount() {
    register_widget( 'Rcl_Widget_user_count' );
}

class Rcl_Widget_user_count extends WP_Widget {

	function Rcl_Widget_user_count() {
		$widget_ops = array( 'classname' => 'widget-user-count', 'description' => __('Personal account of the user','wp-recall') );
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