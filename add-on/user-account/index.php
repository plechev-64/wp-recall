<?php
rcl_enqueue_style('user_account',__FILE__);

function rcl_payform($args){
    if (!class_exists('Rcl_Payform')) include_once plugin_dir_path( __FILE__ ).'rcl_payform.php';
    $form = new Rcl_Payform($args);
    return $form->payform();
}

function get_rmag_global_unit_wallet(){
	if (!defined('RMAG_PREF')){
		global $wpdb;
		global $rmag_options;
		$rmag_options = get_option('primary-rmag-options');
		define('RMAG_PREF', $wpdb->prefix."rmag_");
	}
}
add_action('init','get_rmag_global_unit_wallet',10);

if (is_admin()):
	add_action('admin_head','output_script_admin_acc_recall');
endif;

function output_script_admin_acc_recall(){
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'ajax_admin_account_recall', plugins_url('js/admin.js', __FILE__) );
}

function get_user_money($user_id=false){
    global $wpdb,$user_ID;
    if(!$user_id) $user_id = $user_ID;
    return $wpdb->get_var("SELECT count FROM ".RMAG_PREF."user_count WHERE user='$user_id'");
}

function update_user_money($newmoney,$user_id=false){
    global $user_ID,$wpdb;
    if(!$user_id) $user_id = $user_ID;

    $money = get_user_money($user_id);

    if(isset($money)) return $wpdb->update(RMAG_PREF .'user_count',
                        array( 'count' => $newmoney ),
                        array( 'user' => $user_id )
                    );

    return add_user_money($newmoney,$user_id);


}

function add_user_money($money,$user_id=false){
    global $wpdb,$user_ID;
    if(!$user_id) $user_id = $user_ID;
    return $wpdb->insert( RMAG_PREF .'user_count',
	array( 'user' => $user_id, 'count' => $money ));
}

function statistic_user_pay_page_rcl(){
	$prim = 'manage-rmag';
	if(!function_exists('wpmagazin_options_panel')){
		$prim = 'manage-wpm-options';
		add_menu_page('Recall Commerce', 'Recall Commerce', 'manage_options', $prim, 'global_recall_wpm_options');
		add_submenu_page( $prim, __('Payment systems','rcl'), __('Payment systems','rcl'), 'manage_options', $prim, 'global_recall_wpm_options');
	}

	add_submenu_page( $prim, __('Payments','rcl'), __('Payments','rcl'), 'manage_options', 'manage-wpm-cashe', 'statistic_add_cashe_wpm_recall');
}
add_action('admin_menu', 'statistic_user_pay_page_rcl',25);

add_filter('admin_options_rmag','user_account_wpm_options',10);
function user_account_wpm_options($content){
	$style = 'style="display:block;"';
	$options = get_option('primary-rmag-options');
	$content .= '<span class="title-option">'.__('Payment systems','rcl').'</span>
	<div id="options-'.get_key_addon_rcl(pathinfo(__FILE__)).'" '.$style.' class="wrap-recall-options">

                <div class="option-block">
			<h3>'.__('Payment','rcl').'</h3>
			<label>'.__('Type of payment','rcl').'</label>
			<select name="type_order_payment" size="1">';
				$type_order = $options['type_order_payment'];
				$content .= '<option value="">'.__('Funds from the personal account of the user','rcl').'</option>
				<option value="1" '.selected($type_order,1,false).'>'.__('Directly through the payment system','rcl').'</option>
				<option value="2" '.selected($type_order,2,false).'>'.__('To offer both options','rcl').'</option>
			</select>
			<small>'.__('If the connection to the payment aggregator not in use, it is possible to set only "Funds from the personal account user"!','rcl').'</small>
		</div>

		<div class="option-block">
			<h3>'.__('The connection to payment aggregator','rcl').'</h3>
			<label>'.__('Used type of connection','rcl').'</label>
			<select class="parent-select" name="connect_sale" size="1">';
				$connect_sale = $options['connect_sale'];
				$content .= '<option value="">'.__('Not used','rcl').'</option>
				<option value="1" '.selected($connect_sale,1,false).'>Robokassa</option>
				<option value="2" '.selected($connect_sale,2,false).'>Interkassa</option>
                                <option value="3" '.selected($connect_sale,3,false).'>Yandex.Kassa</option>
			</select>
		</div>
		<div class="child-select connect_sale" id="connect_sale-1">
                    <div class="option-block">
                            <h3>'.__('Connection settings ROBOKASSA','rcl').'</h3>
                            <label>'.__('The ID of the store','rcl').'</label>
                            <input type="text" name="robologin" value="'.$options['robologin'].'" size="60">
                            <label>'.__('1 Password','rcl').'</label>
                            <input type="password" name="onerobopass" value="'.$options['onerobopass'].'" size="60">
                            <label>'.__('2 Password','rcl').'</label>
                            <input type="password" name="tworobopass" value="'.$options['tworobopass'].'" size="60">
                            <label>'.__('The status of the account ROBOKASSA','rcl').'</label>
                            <select name="robotest" size="1">';
                                    $robotest = $options['robotest'];
                                    $content .= '<option value="1" '.selected($robotest,1,false).'>'.__('Test','rcl').'</option>
                                    <option value="0" '.selected($robotest,0,false).'>'.__('Work','rcl').'</option>
                            </select>
                    </div>
		</div>
                <div class="child-select connect_sale" id="connect_sale-2">
                    <div class="option-block">
                            <h3>'.__('Connection settings Interkassa','rcl').'</h3>
                            <label>'.__('Secret Key','rcl').'</label>
                            <input type="password" name="intersecretkey" value="'.$options['intersecretkey'].'" size="60">
                            <label>'.__('Test Key','rcl').'</label>
                            <input type="password" name="intertestkey" value="'.$options['intertestkey'].'" size="60">
                            <label>'.__('The ID of the store','rcl').'</label>
                            <input type="text" name="interidshop" value="'.$options['interidshop'].'" size="60">
                            <label>'.__('The status of the account Interkassa','rcl').'</label>
                            <select name="interkassatest" size="1">';
                                    $interkassatest = $options['interkassatest'];
                                    $content .= '<option value="1" '.selected($interkassatest,1,false).'>'.__('Test','rcl').'</option>
                                    <option value="0" '.selected($interkassatest,0,false).'>'.__('Work','rcl').'</option>
                            </select>
                    </div>
		</div>

                <div class="child-select connect_sale" id="connect_sale-3">
                    <div class="option-block">
                            <h3>'.__('Connection settings Yandex.Kassa').'</h3>
                            <label>'.__('ID cash','rcl').'</label>
                            <input type="text" name="shopid" value="'.$options['shopid'].'" size="60">
                            <label>'.__('The room showcases','rcl').'</label>
                            <input type="text" name="scid" value="'.$options['scid'].'" size="60">
                            <label>'.__('The secret word','rcl').'</label>
                            <input type="password" name="secret_word" value="'.$options['secret_word'].'" size="60">
                    </div>
		</div>

		<div class="option-block">
			<h3>'.__('Service page payment systems','rcl').'</h3>

			<p>1. Создайте на своем сайте четыре страницы:</p>
			- пустую для success<br>
			- пустую для result<br>
			- одну с текстом о неудачной оплате (fail)<br>
			- одну с текстом об удачной оплате<br>
			Название и URL созданных страниц могут быть произвольными.<br>
			<p>2. Укажите здесь какие страницы и для чего вы создали. </p>
			<p>3. В настройках своего аккаунта платежной системы укажите URL страницы для fail, success и result</p>

			<label>'.__('Page RESULT','rcl').'</label>';
			$args = array(
				'selected'   => $options['page_result_pay'],
				'name'       => 'page_result_pay',
				'show_option_none' => '<span style="color:red">'.__('Not selected','rcl').'</span>',
				'echo'             => 0
			);
			$content .= wp_dropdown_pages( $args );
			$content .= '<small>'.__('For Interkassa: URL of interaction','rcl').'</small>';
                        $content .= '<small>'.__('For Yandex.Cash: checkURL and avisoURL','rcl').'</small>';

			$content .= '<label>'.__('Page SUCCESS','rcl').'</label>';
			$args = array(
				'selected'   => $options['page_success_pay'],
				'name'       => 'page_success_pay',
				'show_option_none' => '<span style="color:red">'.__('Not selected','rcl').'</span>',
				'echo'             => 0
			);
			$content .= wp_dropdown_pages( $args );
			$content .= '<small>'.__('For Interkassa: successful payment URL','rcl').'</small>';

			$content .= '<label>'.__('The successful payment page','rcl').'</label>';
			$args = array(
				'selected'   => $options['page_successfully_pay'],
				'name'       => 'page_successfully_pay',
				'show_option_none' => '<span style="color:red">'.__('Not selected','rcl').'</span>',
				'echo'             => 0
			);
			$content .= wp_dropdown_pages( $args );

			$content .= '</div>
	</div>';
	return $content;
}

// создаем допколонку для вывода баланса пользователя
function balance_user_recall_admin_column( $columns ){

  return array_merge( $columns,
    array( 'balance_user_recall' => __("Balance",'rcl') )
  );

}
add_filter( 'manage_users_columns', 'balance_user_recall_admin_column' );

function balance_user_recall_content( $custom_column, $column_name, $user_id ){
global $wpdb;

  switch( $column_name ){
    case 'balance_user_recall':
          $user_count = get_user_money($user_id);
	  $custom_column = '<input type="text" class="balanceuser-'.$user_id.'" size="4" value="'.$user_count.'"><input type="button" class="recall-button edit_balance" id="user-'.$user_id.'" value="Ок">';
          $custom_column = apply_filters('balans_column_rcl',$custom_column,$user_id);
          break;
  }
  return $custom_column;

}
add_filter( 'manage_users_custom_column', 'balance_user_recall_content', 10, 3 );

function statistic_add_cashe_wpm_recall(){
    global $wpdb;
	if($_POST['action']=='trash'){
		$cnt = count($_POST['addcashe']);
		for($a=0;$a<$cnt;$a++){
			$id = $_POST['addcashe'][$a];
			if($id) $wpdb->query("DELETE FROM ".RMAG_PREF ."pay_results WHERE ID = '$id'");
		}
	}

	if($_GET['paged']) $page = $_GET['paged'];
	else $page=1;

	$inpage = 30;
	$start = ($page-1)*$inpage;

	list( $year, $month, $day, $hour, $minute, $second ) = preg_split( '([^0-9])', current_time('mysql') );

	if($_POST['filter-date']){

		if($_POST['year']){
			$like = $_POST['year'];
			if($_POST['month']) $like .= '-'.$_POST['month'];
			$like .= '%';
			$get = 'WHERE time_action  LIKE "'.$like.'"';
		}

		$get .= ' ORDER BY ID DESC';
		$statistic = $wpdb->get_results("SELECT * FROM ".RMAG_PREF ."pay_results ".$get);
		$count_adds = count($statistic);

	}else{
		if($_GET['user']){
			$get = $_GET['user'];
			$get_data = '&user='.$get;
			$statistic = $wpdb->get_results("SELECT * FROM ".RMAG_PREF ."pay_results WHERE user = '$get' ORDER BY ID DESC LIMIT $start,$inpage");
			$count_adds = $wpdb->get_var("SELECT COUNT(ID) FROM ".RMAG_PREF ."pay_results WHERE user = '$get'");
		}elseif($_GET['date']){
			$get = $_GET['date'];
			$get_data = '&date='.$get;
			$statistic = $wpdb->get_results("SELECT * FROM ".RMAG_PREF ."pay_results WHERE time_action LIKE '$get%' ORDER BY ID DESC LIMIT $start,$inpage");
			$count_adds = $wpdb->get_var("SELECT COUNT(ID) FROM ".RMAG_PREF ."pay_results WHERE time_action LIKE '$get%'");
		}else{

			$_POST['year']=$year;$_POST['month']=$month;
			$where = "WHERE time_action LIKE '$year-$month%' ";

			$statistic = $wpdb->get_results("SELECT * FROM ".RMAG_PREF ."pay_results $where ORDER BY ID DESC");
			$count_adds = $wpdb->get_var("SELECT COUNT(ID) FROM ".RMAG_PREF ."pay_results $where");
		}

		$cnt = count($statistic);
	}

	$all=0;
	foreach($statistic as $st){
		$all += $st->count;
	}

	if($count_adds) $sr = floor($all/$count_adds);
	else $sr = 0;

        $n=0;
        $table_tr = '';

        $chart = get_chart_payments($statistic);

	foreach((array)$statistic as $add){
		$n++;
		$time = substr($add->time_action, -9);
		$date = substr($add->time_action, 0, 10);
		$table_tr .= '<tr>'
                            . '<th class="check-column" scope="row"><input id="delete-addcashe-'.$add->ID.'" type="checkbox" value="'.$add->ID.'" name="addcashe[]"></th>'
                            . '<td>'.$n.'</td>'
                            . '<td><a href="/wp-admin/admin.php?page=manage-wpm-cashe&user='.$add->user.'">'.get_the_author_meta('user_login',$add->user).'</a></td>'
                            . '<td>'.$add->inv_id.'</td>'
                            . '<td>'.$add->count.'</td>'
                            . '<td><a href="/wp-admin/admin.php?page=manage-wpm-cashe&date='.$date.'">'.$date.'</a>'.$time.'</td>'
                        . '</tr>';
	}

        if(!isset($_GET['date'])&&!isset($_GET['user'])){
            $date_ar = explode('-',$date);
            if($date_ar[1]==$month) $cntday = $day;
            else $cntday = 30;
            $day_pay = floor($all/$cntday);
        }
	$all_pr = ' на сумму '.$all.' рублей (Средний чек: '.$sr.'р.)';

	$table = '
	<div class="wrap"><h2>Приход средств через платежные системы</h2>
        <h3>Статистика</h3>
	<p>Всего переводов: '.$count_adds.$all_pr.'</p>';
        if($day_pay) $table .= '<p>Средняя выручка за сутки: '.$day_pay.'р.</p>';

        $table .= $chart;

	$table .= '<form action="" method="post" class="alignright">';
	$table .= '<select name="month"><option value="">За все время</option>';
	for($a=1;$a<=12;$a++){
		switch($a){
			case 1: $month = 'январь'; $n = '01'; break;
			case 2: $month = 'февраль'; $n = '02'; break;
			case 3: $month = 'март'; $n = '03'; break;
			case 4: $month = 'апрель'; $n = '04'; break;
			case 5: $month = 'май'; $n = '05'; break;
			case 6: $month = 'июнь'; $n = '06'; break;
			case 7: $month = 'июль'; $n = '07'; break;
			case 8: $month = 'август'; $n = '08'; break;
			case 9: $month = 'сентябрь'; $n = '09'; break;
			case 10: $month = 'октябрь'; $n = $a; break;
			case 11: $month = 'ноябрь'; $n = $a; break;
			case 12: $month = 'декабрь'; $n = $a; break;
		}
		$table .= '<option value="'.$n.'" '.selected($n,$_POST['month'],false).'>'.$month.'</option>';
	}
	$table .= '</select>';
	$table .= '<select name="year">';
	for($a=2013;$a<=$year+1;$a++){
		$table .= '<option value="'.$a.'" '.selected($a,$_POST['year'],false).'>'.$a.'</option>';
	}
	$table .= '</select>';
	$table .= '<input type="submit" value="Фильтровать" name="filter-date" class="button-secondary">';
	$table .= '</form>';

	$table .= '<form action="" method="post">
	<div class="tablenav top">
		<div class="alignleft actions">
		<select name="action">
			<option selected="selected" value="-1">Действия</option>
			<option value="trash">Удалить</option>
		</select>
		<input id="doaction" class="button action" type="submit" value="Применить" name="">
		</div>
	</div>
	<table class="widefat"><tr><th class="check-column" scope="row"></th><th class="manage-column">№пп</th><th class="manage-column">Пользователь</th><th class="manage-column">ID платежа</th><th class="manage-column">Сумма платежа</th><th class="manage-column">Дата и время</th></tr>';

	$table .= $table_tr;

	$table .= '</table></form>';

	$table .= admin_navi_rcl($inpage,$count_adds,$page,'manage-wpm-cashe',$get_data);

	$table .= '</div>';

	echo $table;
}

/*************************************************
Пополнение личного счета пользователя
*************************************************/
function add_count_user_recall(){
	if($_POST['count']){

            $log['redirectform'] = rcl_payform(array('id_pay'=>rand(0,100000000),'summ'=>esc_sql($_POST['count']),'type'=>1));
            $log['otvet']=100;

	} else {
		$log['otvet']=1;
	}
	echo json_encode($log);
	exit;
}
add_action('wp_ajax_add_count_user_recall', 'add_count_user_recall');

/*************************************************
Меняем баланс пользователя из админки
*************************************************/
function edit_balance_user_recall(){
	global $wpdb;
	$user_id = esc_sql($_POST['user']);
	$balance = esc_sql($_POST['balance']);

	if($_POST['balance']!==''){

            $oldusercount = get_user_money($user_id);

            $new_cnt = $balance - $oldusercount;

            if(!$new_cnt) return false;

            if($new_cnt<0) $type = 1;
            else $type = 2;

            update_user_money($balance,$user_id);

            $new_cnt = abs((int)$new_cnt);
            do_action('admin_edit_user_count_rcl',$user_id,$new_cnt,__('The change in the balance','rcl'),$type);

            $log['otvet']=100;
            $log['user']=$user_id;
            $log['balance']=$balance;

	} else {
		$log['otvet']=1;
	}
	echo json_encode($log);
    exit;
}
add_action('wp_ajax_edit_balance_user_recall', 'edit_balance_user_recall');

add_action('wp','active_add_wallet_count_rcl');
function active_add_wallet_count_rcl(){
    global $rcl_options;
    if(!isset($rcl_options['wallet_usercount'])) return false;
    if($rcl_options['wallet_usercount']==1) add_filter('wallet_tabs_rcl','add_wallet_count_rcl',5);
}

function add_wallet_count_rcl($content){
    $content .= '<h3>'.__('Personal account of the user','rcl').':</h3>'.get_html_usercount_rcl();
    return $content;
}

function get_usercount_rcl($user_id){
    global $wpdb;
    return $wpdb->get_var("SELECT count FROM ".RMAG_PREF ."user_count WHERE user = '$user_id'");
}

function get_html_usercount_rcl(){
    global $wpdb,$user_ID,$rmag_options;

    $usercount = '<div id="user-count-rcl">';

    $user_count = get_user_money();
    if(!$user_count) $user_count = 0;

    $usercount .= '<div class="usercount" style="text-align:center;">'.$user_count.' '.__('RUB','rcl').'</div>';


    $usercount = apply_filters('count_widget_rcl',$usercount);

    if($rmag_options['connect_sale']!='') $usercount .= "<p align='right'><a class='go_to_add_count' href='#'>".__("Deposit",'rcl')."</a></p>
    <div class='count_user'>
    <h3>".__("To recharge your account",'rcl')."</h3>
    <div>
    <p style='margin-bottom: 10px;'><label>".__("Enter the amount required",'rcl')."</label></p>
        <input class='value_count_user' size='4' type='text' value=''>
        <input class='add_count_user recall-button' type='button' value='".__("Send",'rcl')."'>
    </div>
    <div class='redirectform' style='margin:10px 0;text-align:center;'></div>
    </div>";

    $usercount .= '</div>';

    return $usercount;
}

add_action( 'widgets_init', 'widget_user_count' );

function widget_user_count() {
	register_widget( 'Widget_user_count' );
}

class Widget_user_count extends WP_Widget {

	function Widget_user_count() {
		$widget_ops = array( 'classname' => 'widget-user-count', 'description' => __('Personal account of the user','rcl') );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'widget-user-count' );
		$this->WP_Widget( 'widget-user-count', __('Personal account','rcl'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
            extract( $args );

            $title = apply_filters('widget_title', $instance['title'] );
            global $user_ID;

            if ($user_ID){
                echo $before_widget;
                if ( $title ) echo $before_title . $title . $after_title;
                echo get_html_usercount_rcl();
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
		$defaults = array( 'title' => __('Personal account','rcl'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title','rcl'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
	<?php
	}
}

add_filter('file_scripts_rcl','get_scripts_user_account_rcl');
function get_scripts_user_account_rcl($script){

	$ajaxdata = "type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',";

	$script .= "
		/* Пополняем личный счет пользователя */
			jQuery('.add_count_user').live('click',function(){
					var count = jQuery('.value_count_user');
					var addcount = count.val();
					var dataString = 'action=add_count_user_recall&count='+addcount;

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
		/* Оплачиваем заказ средствами из личного счета */
			jQuery('.pay_order').live('click',function(){
				var idorder = jQuery(this).data('order');
				var dataString = 'action=pay_order_in_count_recall&idorder='+ idorder;

				jQuery.ajax({
				".$ajaxdata."
				success: function(data){
					if(data['otvet']==100){
						jQuery('.order_block').find('.pay_order').each(function() {
							if(jQuery(this).attr('name')==data['idorder']) jQuery(this).remove();
						});
						jQuery('.redirectform').html(data['recall']);
						jQuery('.usercount').html(data['count']+' рублей');
						jQuery('.order-'+data['idorder']+' .remove_order').remove();
						jQuery('#manage-order').remove();
					}else{
						alert('Недостаточно средств на счету! Сумма заказа: '+data['recall']);
					}
				}
				});
				return false;
			});
		jQuery('.go_to_add_count').click(function(){
			jQuery('.count_user').slideToggle();
			return false;
		});
	";
	return $script;
}

function get_chart_payments($pays){
    global $chartData,$chartArgs;

    if(!$pays) return false;

    $chartArgs = array();
    $chartData = array(
        'title' => __('Income dynamics','rcl'),
        'title-x' => __('The time period','rcl'),
        'data'=>array(
            array(__('"Days/Months"','rcl'), __('"Payments (PCs.)"','rcl'), __('"Income (thousands)"','rcl'))
        )
    );

    foreach($pays as $pay){
        setup_chartdata($pay->time_action,$pay->count);
    }

    return get_chart_rcl($chartArgs);
}

require_once("rcl_payment.php");