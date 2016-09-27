<?php

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

include_once "rcl_payment.php";

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

function rcl_get_user_balance($user_id=false){
    global $wpdb,$user_ID;
    if(!$user_id) $user_id = $user_ID;
    
    $cachekey = json_encode(array('rcl_get_user_balance',$user_id));
    $cache = wp_cache_get( $cachekey );
    
    if ( $cache )
        return $cache;
    
    $balance = $wpdb->get_var($wpdb->prepare("SELECT user_balance FROM ".RMAG_PREF."users_balance WHERE user_id='%d'",$user_id));
    
    wp_cache_add( $cachekey, $balance );
    
    return $balance;
}

function rcl_update_user_balance($newmoney,$user_id,$comment=''){
    global $wpdb;
    
    $newmoney = round(str_replace(',','.',$newmoney), 2);

    $money = rcl_get_user_balance($user_id);

    if(isset($money)){
        
        do_action('rcl_pre_update_user_balance',$newmoney,$user_id,$comment);
        
        return $wpdb->update(RMAG_PREF .'users_balance',
            array( 'user_balance' => $newmoney ),
            array( 'user_id' => $user_id )
        );
        
    }

    return rcl_add_user_balance($newmoney,$user_id,$comment);
}

function rcl_add_user_balance($money,$user_id,$comment=''){
    global $wpdb;

    $result =  $wpdb->insert( RMAG_PREF .'users_balance',
	array( 'user_id' => $user_id, 'user_balance' => $money ));
    
    do_action('rcl_add_user_balance',$money,$user_id,$comment);
    
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
          $user_count = rcl_get_user_balance($user_id);
	  $custom_column = '<input type="text" class="balanceuser-'.$user_id.'" size="4" value="'.$user_count.'"><input type="button" class="recall-button edit_balance" id="user-'.$user_id.'" value="Ok">';
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
    
    if(!$_POST['count']){
        $log['error'] = __('Enter the amount to replenish','wp-recall');
        echo json_encode($log);
        exit;
    }

    if($user_ID){

        $amount = intval($_POST['count']);
        $id_pay = current_time('timestamp');
        
        $args = array(
            'id_pay'=>$id_pay,
            'description'=>__("Completion of a personal account from",'wp-recall').' '.get_the_author_meta('user_email',$user_ID),
            'id_form'=>$_POST['id_form'],
            'summ'=>$amount,
            'type'=>1
        );

        $log['redirectform'] =  rcl_payform($args);
        $log['otvet']=100;

    } else {
        $log['error'] = __('Error','wp-recall');
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

    rcl_update_user_balance($balance,$user_id,__('The change in the balance','wp-recall'));

    $log['otvet']=100;
    $log['user']=$user_id;
    $log['balance']=$balance;

    echo json_encode($log);
    exit;
}
if(is_admin()) add_action('wp_ajax_rcl_edit_balance_user', 'rcl_edit_balance_user');

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
                .__("Deposit",'wp-recall')
                ."</a>
            </div>
            <div class='rcl-form-balance'>               
                ".rcl_form_user_balance(array('idform'=>$id))."
            </div>";

    $usercount .= '</div>';

    return $usercount;
}

add_shortcode('rcl-form-balance','rcl_form_user_balance');
function rcl_form_user_balance($attr=false){
    global $user_ID,$rcl_payments,$rmag_options;
    
    if(!$user_ID) return '<p align="center">'.__("To make a payment please log in",'wp-recall').'</p>';

    extract(shortcode_atts(array(
        'idform' => rand(1,1000)
    ),
    $attr));
    
    $form = array(
        'fields' => array('<input class=value-user-count name=count type=number value=>'),
        'notice' => '',
        'submit' => '<input class="rcl-get-form-pay recall-button" type=submit value=Отправить>'
    );
    
    if(!is_array($rmag_options['connect_sale'])&&isset($rcl_payments[$rmag_options['connect_sale']])){
        $connect = $rcl_payments[$rmag_options['connect_sale']];
        $background = (isset($connect->image))? 'style="background:url('.$connect->image.') no-repeat center;"': '';       
        $form['notice'] = '<span class="form-notice">'
                        . '<span class="thumb-connect" '.$background.'></span> '.__('Payment via','wp-recall').' '
                        .$connect->name
                        .'</span>';
    }
    
    $form = apply_filters('rcl_user_balance_form',$form);
    
    if(!is_array($form['fields'])) return false;
    
    $content = '<div class=rcl-form-add-user-count id=rcl-form-balance-'.$idform.'>
                    <p class="form-balance-notice">'.__("Enter the amount to replenish",'wp-recall').'</p>
                    <form class=rcl-form-input>';
                        foreach($form['fields'] as $field){
                            $content .= '<span class="form-field">'.$field.'</span>';
                        }
                        if(isset($form['notice'])&&$form['notice']) 
                            $content .= '<span class="form-field">'.$form['notice'].'</span>';
                        $content .= '<span class="form-submit">'.$form['submit'].'</span>'
                    .'</form>
                    <div class=rcl-result-box></div>
                </div>';
                        
    return $content;
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

	function __construct() {
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