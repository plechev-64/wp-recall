<?php

require_once 'classes/class-rcl-users-balance.php';

require_once 'classes/class-rcl-payment-core.php';
require_once 'classes/class-rcl-payment-form.php';
require_once 'classes/class-rcl-payment-process.php';

require_once 'classes/class-rcl-gateways.php';

require_once 'classes/class-rcl-gateway-core.php';
require_once 'classes/class-gateway-balance.php';
//require_once 'classes/class-gateway-robokassa.php';
//require_once 'classes/class-gateway-freekassa.php';
//require_once "rcl-payments-gateway/index.php";

require_once "classes/class-rcl-payment.php";

require_once "functions-core.php";
require_once "functions-ajax.php";
require_once "addon-widgets.php";
require_once "addon-shortcodes.php";

if ( is_admin() )
	require_once 'admin/index.php';

if ( ! is_admin() ):
	add_action( 'rcl_enqueue_scripts', 'rcl_user_account_scripts', 10 );
endif;
function rcl_user_account_scripts() {
	rcl_enqueue_style( 'rcl-user-account', rcl_addon_url( 'assets/css/style.css', __FILE__ ) );
	rcl_enqueue_script( 'rcl-user-account', rcl_addon_url( 'assets/js/scripts.js', __FILE__ ) );
}

add_filter( 'rcl_init_js_variables', 'rcl_init_js_account_variables', 10 );
function rcl_init_js_account_variables( $data ) {
	global $user_ID;

	$data['account']['currency'] = rcl_get_primary_currency( 1 );

	if ( $user_ID )
		$data['account']['balance'] = rcl_get_user_balance( $user_ID );

	return $data;
}

add_action( 'init', 'rmag_get_global_unit_wallet', 10 );
function rmag_get_global_unit_wallet() {

	if ( defined( 'RMAG_PREF' ) )
		return false;

	global $wpdb;
	global $rmag_options;
	$rmag_options = get_option( 'primary-rmag-options' );
	define( 'RMAG_PREF', $wpdb->prefix . "rmag_" );
}

add_action( 'init', 'rcl_payments_gateway_init', 1 );
function rcl_payments_gateway_init() {
	global $rcl_gateways;

	$rcl_gateways = new Rcl_Gateways();

	do_action( 'rcl_payments_gateway_init' );
}

add_action( 'wp', 'rcl_payments_process', 10 );
function rcl_payments_process() {

	$Process = new Rcl_Payment_Process();

	if ( $gateway_id = $Process->get_id_is_payment() ) {

		$Process->payment_process( $gateway_id );
	}
}

add_action( 'rcl_success_pay_system', 'rcl_success_pay', 10 );
add_action( 'rcl_success_pay_balance', 'rcl_success_pay', 10 );
function rcl_success_pay( $dataPay ) {
	do_action( 'rcl_success_pay', $dataPay );
}

//пополнение баланса пользователя
add_action( 'rcl_success_pay_system', 'rcl_pay_user_balance', 10 );
function rcl_pay_user_balance( $data ) {

	if ( $data->pay_type != 1 )
		return false;

	$oldcount = rcl_get_user_balance( $data->user_id );

	if ( $oldcount )
		$newcount	 = $oldcount + $data->pay_summ;
	else
		$newcount	 = $data->pay_summ;

	rcl_update_user_balance( $newcount, $data->user_id, __( 'Top up personal account', 'wp-recall' ) );
}

add_action( 'delete_user', 'rcl_delete_user_balance', 10 );
function rcl_delete_user_balance( $user_id ) {
	global $wpdb;
	return $wpdb->query( $wpdb->prepare( "DELETE FROM " . RMAG_PREF . "users_balance WHERE user_id='%d'", $user_id ) );
}
