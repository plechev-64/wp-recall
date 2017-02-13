<?php

require_once 'payments.php';
require_once 'settings.php';

add_action('admin_head','rcl_admin_user_account_scripts');
function rcl_admin_user_account_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rcl_admin_user_account_scripts', plugins_url('js/scripts.js', __FILE__) );
}

// создаем допколонку для вывода баланса пользователя
add_filter( 'manage_users_columns', 'rcl_balance_user_admin_column' );
function rcl_balance_user_admin_column( $columns ){

  return array_merge( $columns,
    array( 'balance_user_recall' => __("Balance",'wp-recall') )
  );

}

add_filter( 'manage_users_custom_column', 'rcl_balance_user_admin_content', 10, 3 );
function rcl_balance_user_admin_content( $custom_column, $column_name, $user_id ){

  switch( $column_name ){
    case 'balance_user_recall':
        $user_count = rcl_get_user_balance($user_id);
        $custom_column = '<input type="text" class="balanceuser-'.$user_id.'" size="4" value="'.$user_count.'"><input type="button" class="recall-button edit_balance" id="user-'.$user_id.'" value="Ok">';
        $custom_column = apply_filters('balans_column_rcl',$custom_column,$user_id);
    break;

  }
  return $custom_column;

}

function rcl_get_chart_payments($pays){
    global $chartData,$chartArgs;

    if(!$pays) return false;

    $chartArgs = array();
    $chartData = array(
        'title' => __('Income dynamics','wp-recall'),
        'title-x' => __('Time period','wp-recall'),
        'data'=>array(
            array(__('"Days/Months"','wp-recall'), __('"Payments (PCs.)"','wp-recall'), __('"Income (thousands)"','wp-recall'))
        )
    );

    foreach($pays as $pay){
        $pay = (object)$pay;
        rcl_setup_chartdata($pay->time_action,$pay->pay_amount);
    }

    return rcl_get_chart($chartArgs);
}

/*************************************************
Меняем баланс пользователя из админки
*************************************************/
add_action('wp_ajax_rcl_edit_balance_user', 'rcl_edit_balance_user');
function rcl_edit_balance_user(){

    $user_id = intval($_POST['user']);
    $balance = floatval(str_replace(',','.',$_POST['balance']));

    rcl_update_user_balance($balance,$user_id,__('Balance changed','wp-recall'));

    $log['otvet']=100;
    $log['user']=$user_id;
    $log['balance']=$balance;

    echo json_encode($log);
    exit;
}
