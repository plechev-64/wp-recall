<?php

require_once 'class-rcl-payments-history.php';
require_once 'addon-settings.php';

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
rcl_ajax_action('rcl_edit_balance_user', false);
function rcl_edit_balance_user(){

    $user_id = intval($_POST['user']);
    $balance = floatval(str_replace(',','.',$_POST['balance']));
    
    if(!$user_id){
        wp_send_json(array('error'=>__('Balance was not changed','wp-recall')));
    }

    rcl_update_user_balance($balance,$user_id,__('Balance changed','wp-recall'));

    wp_send_json(array(
        'success' => __('Balance successfully changed','wp-recall'),
        'user_id' => $user_id,
        'balance' => $balance
    ));

}

add_action('admin_menu', 'rcl_statistic_user_pay_page',25);
function rcl_statistic_user_pay_page(){
    $prim = 'manage-rmag';
    if(!function_exists('rcl_commerce_menu')){
        $prim = 'manage-wpm-options';
        add_menu_page('Rcl Commerce', 'Rcl Commerce', 'manage_options', $prim, 'rmag_global_options');
        add_submenu_page( $prim, __('Payment systems','wp-recall'), __('Payment systems','wp-recall'), 'manage_options', $prim, 'rmag_global_options');
    }

    $hook = add_submenu_page( $prim, __('Payments','wp-recall'), __('Payments','wp-recall'), 'manage_options', 'manage-wpm-cashe', 'rcl_admin_statistic_cashe');
    add_action( "load-$hook", 'rcl_payments_page_options' );
}

function rcl_payments_page_options() {
    global $Rcl_Payments_History;
    $option = 'per_page';
    $args = array(
        'label' => __( 'Payments', 'wp-recall' ),
        'default' => 50,
        'option' => 'rcl_payments_per_page'
    );
    add_screen_option( $option, $args );
    $Rcl_Payments_History = new Rcl_Payments_History();
}

function rcl_admin_statistic_cashe(){
  global $Rcl_Payments_History;
  
  $Rcl_Payments_History->prepare_items();
  $sr = ($Rcl_Payments_History->sum)? floor($Rcl_Payments_History->sum/$Rcl_Payments_History->total_items): 0;
  
  echo '</pre><div class="wrap"><h2>'.__('Payment history','wp-recall').'</h2>';

  echo '<p>'.__('All payments','wp-recall').': '.$Rcl_Payments_History->total_items.' '.__('for the amount of','wp-recall').' '.$Rcl_Payments_History->sum.' '.rcl_get_primary_currency(1).' ('.__('Average check','wp-recall').': '.$sr.' '.rcl_get_primary_currency(1).')</p>';
  echo '<p>'.__('Total in the system','wp-recall').': '.$Rcl_Payments_History->sum_balance.' '.rcl_get_primary_currency(1).'</p>';
  //echo '<p>Средняя выручка за сутки: '.$day_pay.' '.rcl_get_primary_currency(1).'</p>';
  echo rcl_get_chart_payments($Rcl_Payments_History->items);
   ?>
    <form method="get"> 
    <input type="hidden" name="page" value="manage-wpm-cashe">    
    <?php
    $Rcl_Payments_History->months_dropdown('rcl_payments'); 
    submit_button( __( 'Filter', 'wp-recall' ), 'button', '', false, array('id' => 'search-submit') ); ?>
    </form>
    <form method="post">
    <input type="hidden" name="page" value="manage-wpm-cashe">    
    <?php
    $Rcl_Payments_History->search_box( __( 'Search', 'wp-recall' ), 'search_id' );
    
    $Rcl_Payments_History->display(); ?>
  </form>
</div>
<?php }
