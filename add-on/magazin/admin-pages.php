<?php
function wpmagazin_options_panel(){
  add_menu_page('Recall Commerce', 'Recall Commerce', 'manage_options', 'manage-rmag', 'global_wpm_orders');
	add_submenu_page( 'manage-rmag', 'Заказы', 'Заказы', 'manage_options', 'manage-rmag', 'global_wpm_orders');
	add_submenu_page( 'manage-rmag', 'Экспорт/импорт', 'Экспорт/импорт', 'manage_options', 'manage-wpm-price', 'price_and_products');
	add_submenu_page( 'manage-rmag', 'Форма заказа', 'Форма заказа', 'manage_options', 'manage-custom-fields', 'custom_fields_orders_recall');
	add_submenu_page( 'manage-rmag', 'Настройки магазина', 'Настройки магазина', 'manage_options', 'manage-wpm-options', 'global_recall_wpm_options');
	if(function_exists('global_plslider_options')) add_submenu_page('manage-rmag', 'Слайдер', 'Слайдер', 'manage_options', 'plslider_options','global_plslider_options');
  
}
add_action('admin_menu', 'wpmagazin_options_panel',20);

add_filter('admin_options_rmag','user_primary_wpm_options',5);
function user_primary_wpm_options($content){
        global $rcl_options;
	$rcl_options = get_option('primary-rmag-options');
        
        include_once RCL_PATH.'functions/rcl_options.php';
                   
        $opt = new Rcl_Options(get_key_addon_rcl(pathinfo(__FILE__)));
        
        $args = array(    
                'selected'   => $rcl_options['basket_page_rmag'],   
                'name'       => 'basket_page_rmag',
                'show_option_none' => '<span style="color:red">Не выбрано</span>',
                'echo'       => 0  
        );  
        
        $content .= $opt->options(
            'Настройки WP-RECALL-MAGAZIN',array(
            $opt->option_block(
                array(                   
                    $opt->title('Административный Email'),

                    $opt->label('Email для уведомлений'),
                    $opt->option('text',array('name'=>'admin_email_magazin_recall')),
                    $opt->notice('Если email не указан, то уведомления будут рассылаться всем пользователям сайта с правами "Администратор"')
                )
            ),
            $opt->option_block(
                array(                   
                    $opt->title('Учет товара'),

                    $opt->label('Учет товара на складе'),
                    $opt->option('select',array(
                        'name'=>'products_warehouse_recall',
                        'options'=>array('Отключено','Включено')
                    )),
                    $opt->notice('Если учет ведется, то у товаров можно будет отмечать наличие на складе. Если товар не в наличии, то кнопка на добавление товара в корзину отсутствует')
                )
            ),
            $opt->option_block(
                array(                   
                    $opt->title('Корзина'),

                    $opt->label('Порядок вывода кнопки "В корзину"'),
                    $opt->option('select',array(
                        'name'=>'add_basket_button_recall',
                        'options'=>array('Автоматически','Через шорткод')
                    )),
                    $opt->notice('На странице товара. Если шорткод, то используем [add-basket]'),

                    $opt->label('Страница оформления заказа'),
                    wp_dropdown_pages( $args ),
                    $opt->notice('Укажите страницу, где размещен шорткод [basket]'),
                )
            ),
             $opt->option_block(
                array(                   
                    $opt->title('Система похожих или рекомендуемых товаров'),

                    $opt->label('Порядок вывода'),
                    $opt->option('select',array(
                        'name'=>'sistem_related_products',
                        'options'=>array('Отключено','Включено')
                    )),
                    $opt->notice('Если учет ведется, то у товаров можно будет отмечать наличие на складе. Если товар не в наличии, то кнопка на добавление товара в корзину отсутствует'),

                    $opt->label('Заголовок блока рекомендуемых товаров'),
                    $opt->option('text',array('name'=>'title_related_products_recall')),

                    $opt->label('Количество рекомендуемых товаров'),
                    $opt->option('text',array('name'=>'size_related_products'))
                )
            ))
        );                        
	return $content;
}

function custom_fields_orders_recall(){
	global $wpdb;
        
        add_sortable_scripts();
        
	include_once RCL_PATH.'functions/rcl_editfields.php';
        $f_edit = new Rcl_EditFields('orderform');
	
	if($f_edit->verify()) $fields = $f_edit->update_fields();

	$content = '<h2>Управление полями Формы заказа</h2>
            
	'.$f_edit->edit_form(array(
            $f_edit->option('select',array(
                'name'=>'requared',
                'notice'=>'обязательное поле',
                'value'=>array('Нет','Да')
            ))
        ));
        
	echo $content;
}

function global_wpm_orders(){

	global $wpdb;
	//update_history_wallet();
	echo '<h2>Управление заказами</h2>
			<div style="width:1050px">';//начало блока настроек профиля
	$n=0;
	$s=0;
	if($_GET['remove-trash']==101&&wp_verify_nonce( $_GET['_wpnonce'], 'delete-trash-rmag')) $wpdb->query("DELETE FROM ".RMAG_PREF ."orders_history WHERE status = '6'");
	
	list( $year, $month, $day, $hour, $minute, $second ) = preg_split( '([^0-9])', current_time('mysql') );
	
	if($_POST['filter-date']){
		if($_POST['year']){
			$like = $_POST['year'];
			if($_POST['month']) $like .= '-'.$_POST['month'];
			$like .= '%';
			$get = 'WHERE time_action  LIKE "'.$like.'"';
		}
	
		if($_POST['status']) $get .= ' AND status = "'.$_POST['status'].'"';
		$get .= ' ORDER BY ID DESC';
		$orders = $wpdb->get_results("SELECT * FROM ".RMAG_PREF ."orders_history ".$get);
	}else{
		$order_by = "ORDER BY ID DESC";
		if($_GET['status']){
			$get = $_GET['status'];
			$where = "WHERE status = '$get'";		
		}elseif($_GET['order']){
			$get = $_GET['order'];
			$where = "WHERE inv_id = '$get'";		
		}elseif($_GET['user']){
			$get = $_GET['user'];
			$where = "WHERE user = '$get'";		
		}elseif($_GET['date']||$_POST['filter-date']){		
			$get = $_GET['date'];
			$where = "WHERE time_action  LIKE '$get%'";		
		}else{
			
			$_POST['year']=$year;$_POST['month']=$month;
			$where = "WHERE status != '6' AND time_action LIKE '$year-$month%' ";		
		}
                
                $where = apply_filters('string_where_get_orders',$where);
                
		$orders = $wpdb->get_results("SELECT * FROM ".RMAG_PREF ."orders_history $where $order_by");
	}

if($_GET['order']){

	if($_POST['submit_message']){
            if($_POST['email_author']) $email_author = $_POST['email_author'];
            else $email_author = 'noreply@'.$_SERVER['HTTP_HOST'];
            $user_email = get_the_author_meta('user_email',$_POST['address_message']);
            $result_mess = rcl_mail($user_email, $_POST['title_message'], $_POST['text_message']);
	}

	$a=0;
	foreach((array)$orders as $sing_order){
            $sumprise += "$sing_order->price"*"$sing_order->count";
            $a++;			
	}
        
        $header_tb = array(
            '№ п/п',
            'Наименование товара',
            'Цена',
            'Количество',
            'Сумма',
            'Статус',
        );
        
	echo '<h3>ID заказа: '.$_GET['order'].'</h3>'
                . '<table class="widefat">'
                . '<tr>';
        
        foreach($header_tb as $h){
            echo '<th>'.$h.'</th>';
        }

        echo '</tr>';

        foreach((array)$orders as $order){
		$n++;

                if($order->inv_id==$_GET['order']){
                        $user_login = get_the_author_meta('user_login',$order->user);
                        echo '<tr>'
                        . '<td>'.$n.'</td>'
                                . '<td>'.get_the_title($order->product).'</td>'
                                . '<td>'.$order->price.'</td>'
                                . '<td>'.$order->count.'</td>'
                                . '<td>'.$order->price*$order->count.'</td>'
                                . '<td>'.get_status_name($order->status).'</td>'
                                . '</tr>';						
                }
	}
	if($n==$a) echo '<tr><td colspan="4">Сумма заказа</td><td colspan="2">'.$sumprise.'</td></tr>';
	$args = array( 'wpautop' => 1  
			,'media_buttons' => 1  
			,'textarea_name' => 'text_message'
			,'textarea_rows' => 15  
			,'tabindex' => null  
			,'editor_css' => ''  
			,'editor_class' => 'contentarea'  
			,'teeny' => 0  
			,'dfw' => 0  
			,'tinymce' => 1  
			,'quicktags' => 1  
		);
	$get_fields = get_option( 'custom_profile_field' );
	//$get_fields = unserialize( $get_fields);	
				
	foreach((array)$get_fields as $custom_field){				
		$slug = $custom_field['slug'];
			if($custom_field['type']=='text'&&get_the_author_meta($slug,$order->user))
			$show_custom_field .= '<p><b>'.$custom_field['title'].':</b> <span>'.get_the_author_meta($slug,$order->user).'</span></p>';
			if($custom_field['type']=='select'&&get_the_author_meta($slug,$order->user)||$custom_field['type']=='radio'&&get_the_author_meta($slug,$order->user))
				$show_custom_field .= '<p><b>'.$custom_field['title'].':</b> <span>'.get_the_author_meta($slug,$order->user).'</span></p>';
			if($custom_field['type']=='checkbox'){
				$chek = explode('#',$custom_field['field_select']);
				$count_field = count($chek);					
				$n=0;
				for($a=0;$a<$count_field;$a++){
					$slug_chek = $slug.'_'.$a;
					if(get_the_author_meta($slug_chek,$order->user)){
					$n++;
						if($n==1) $chek_field .= get_the_author_meta($slug_chek,$order->user);
							else $chek_field .= ', '.get_the_author_meta($slug_chek,$order->user);
					}
				}
				if($n!=0) $show_custom_field .= '<p><b>'.$custom_field['title'].': </b>'.$chek_field.'</p>';
			}					
			if($custom_field['type']=='textarea'&&get_the_author_meta($slug,$order->user))
				$show_custom_field .= '<p><b>'.$custom_field['title'].':</b></p><p>'.get_the_author_meta($slug,$order->user).'</p>';
	}
	
	$details_order = $wpdb->get_var("SELECT details_order FROM ".RMAG_PREF ."details_orders WHERE order_id = '$order->inv_id'");
	
	echo '</table>
	<form><input type="button" value="Назад" onClick="history.back()"></form><div style="text-align:right;"><a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=manage-rmag">Показать все заказы</a></div>
	<h3>Все заказы пользователя: <a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=manage-rmag&user='.$order->user.'">'.$user_login.'</a></h3>
	<h3>Информация о пользователе:</h3><p><b>Имя</b>: '.get_the_author_meta('display_name',$order->user).'</p><p><b>Email</b>: '.get_the_author_meta('user_email',$order->user).'</p>'.$show_custom_field;
	if($details_order) echo '<h3>Детали заказа:</h3>'.$details_order;
	if($result_mess) echo '<h3 style="color:green;">Сообщение было отправлено!</h3>';
	echo '
	<style>.form_message input[type="text"], .form_message textarea{width:450px;padding:5px;}</style>
	<h3>Написать пользователю сообщение на почту '.get_the_author_meta('user_email',$order->user).'</h3>
	<form method="post" action="" class="form_message" >
	<p><b>Почта отправителя</b> (по-умолчанию "noreply@'.$_SERVER['HTTP_HOST'].'")</p>
	<input type="text" name="email_author" value="'.$_POST['email_author'].'">
	<p><b>Тема письма</b></p>
	<input type="text" name="title_message" value="'.$_POST['title_message'].'">
	<p><b>Текст сообщения</b></p>';
	
	$textmail = "<p>Добрый день!</p>
	<p>Вы или кто то другой оформил заказ на сайте ".get_bloginfo('name')."</p>
	<h3>Детали заказа:</h3>
	".get_email_table_order_rcl($orders,$_GET['order'],$sumprise)."
	<p>Ваш заказ ожидает оплаты. Вы можете произвести оплату своего заказа любым из предложенных способ из своего личного кабинета <a href='".get_author_posts_url($order->user)."'>".get_author_posts_url($order->user)."</a> или просто пополнив свой личный счет на сайте <a href='http://".$_SERVER['HTTP_HOST']."'>http://".$_SERVER['HTTP_HOST']."<p>
	____________________________________________________________________________
	Это письмо было сформировано автоматически не надо отвечать на него";
	
	if($_POST['text_message']) $textmail = $_POST['text_message'];
	
	wp_editor( $textmail, 'textmessage', $args );
	echo '<input type="hidden" name="address_message" value="'.$order->user.'">
	<p><input type="submit" name="submit_message" value="Отправить"></p>
	</form>';
	
	echo $table;
}else{

$inv_id =0;
$all_pr =0;
foreach((array)$orders as $order){
	$all_pr += $order->price*$order->count;
	if($inv_id != $order->inv_id){
		$inv_id = $order->inv_id;
		if($inv_id == $order->inv_id){
			$n++;		
		}
	}
}

$table .= '<h3>Всего заказов: '.$n.' на '.$all_pr.' рублей</h3>
<form action="" method="post">';
$table .= '<select name="status">';
$table .= '<option value="">Все заказы</option>';
for($a=1;$a<=6;$a++){
	$table .= '<option value="'.$a.'" '.selected($a,$_POST['status'],false).'>'.get_status_name($a).'</option>';
}
$table .= '</select>';
$table .= '<select name="month"><option value="">За все месяцы</option>';

    /*$months = array(
        '01'=>'январь',
        '02'=>'февраль',
        '03'=>'март',
        '04'=>'апрель',
        '05'=>'май',
        '06'=>'июнь',
        '07'=>'июль',
        '08'=>'август',
        '09'=>'сентябрь',
        '10'=>'октябрь',
        '11'=>'ноябрь',
        '12'=>'декабрь',
    );*/

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
if($_GET['status']==6) $table .= '<a href="'.wp_nonce_url('/wp-admin/admin.php?page=manage-rmag&remove-trash=101','delete-trash-rmag').'">Очистить корзину</a>';

    $cols = array('Заказ ID','Пользователь','Сумма заказа','Дата и время','Статус','Смена статуса','Действие');

    $cols = apply_filters('header_table_orders_rcl',$cols);

    $table .= '</form>
    <table class="widefat"><tr>';
    foreach($cols as $col){
            $table .= '<th>'.$col.'</th>';
    }
    $table .= '</tr>';

$inv_id = 0;

foreach((array)$orders as $sing_order){
	$sumprise[$sing_order->inv_id] += "$sing_order->price"*"$sing_order->count";				
}

    foreach((array)$orders as $order){
	if($inv_id != $order->inv_id){
		
		$inv_id = $order->inv_id;
	
		/*foreach((array)$orders as $sing_order){
			if($inv_id == $sing_order->inv_id){
				$sumprise[$inv_id] += "$sing_order->price"*"$sing_order->count";		
			}			
		}*/


		/*for($a=1;$a<7;$a++){
			$radioform .= '<input type="radio" class="status-'.$inv_id.'" '.checked($a,$order->status,false).' name="'.$inv_id.'" value="'.$a.'">'.$a;
		}*/
		$radioform .= '<select id="status-'.$inv_id.'" name="status-'.$inv_id.'">';
		for($a=1;$a<7;$a++){
                    $radioform .= '<option '.selected($a,$order->status,false).' value="'.$a.'">'.get_status_name($a).'</option>';
		}
		$radioform .= '</select>';
		
		if($order->status==6) $delete = '<input type="button" class="button-primary delete-order" id="'.$inv_id.'" value="Удалить">';
		$button = '<input type="button" class="button-secondary select_status" id="'.$inv_id.'" value="Изменить статус"> '.$delete;
		$user_id = $order->user;
		$user_login = get_the_author_meta('user_login',$order->user);
		$time = substr($order->time_action, -9);
		$date = substr($order->time_action, 0, 10);
                
		$pagelink = get_bloginfo('wpurl').'/wp-admin/admin.php?page=manage-rmag';
		
		$cols_content = array(
                    '<a href="'.$pagelink.'&order='.$inv_id.'">Заказ '.$inv_id.'</a>',
                    '<a href="'.$pagelink.'&user='.$user_id.'">'.$user_login.'</a>',
                    $sumprise[$inv_id],
                    '<a href="'.$pagelink.'&date='.$date.'">'.$date.'</a>'.$time,
                    '<a href="'.$pagelink.'&status='.$order->status.'"><span class="change-'.$inv_id.'">'.get_status_name($order->status).'</span></a>',
                    $radioform,
                    $button
		);
		
		$cols_content = apply_filters('content_table_orders_rcl',$cols_content,$order->user);
		
		$table .= '<tr id="row-'.$inv_id.'">';
		
		foreach($cols_content as $content){
			$table .= '<td>'.$content.'</td>';
		}
		
		$table .= '</tr>';
		$radioform = '';
		$delete = '';
	}	
    }

    $cnt_cols = count($cols);
    if($_GET['status']!=6) $table .= '<tr><td align="right" colspan="'.$cnt_cols.'"><a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=manage-rmag&status=6">Перейти в корзину</a></td></tr>';
    $table .= '</table>';

    echo $table;

if($_GET['user']||$_GET['status']||$_GET['date'])echo '<form><input type="button" value="Назад" onClick="history.back()"></form><div style="text-align:right;"><a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=manage-rmag">Показать текущие заказы</a></div>';
}

echo '</div>';//конец блока заказов
}

function price_and_products(){
global $wpdb;
	
	$table_price .='<style>table{min-width:500px;width:50%;margin:20px 0;}table td{border:1px solid #ccc;padding:3px;}</style>';
	$postmeta = $wpdb->get_results("SELECT meta_key FROM ".$wpdb->prefix ."postmeta GROUP BY meta_key ORDER BY meta_key");
	$table_price .='<h2>Экспорт/импорт данных</h2><form method="post" action="'.plugins_url("impexp.php", __FILE__).'">
	'.wp_nonce_field('get-csv-file','_wpnonce',true,false).'
	<p><input type="checkbox" name="post_title" value="1"> Добавить заголовок</p>
	<p><input type="checkbox" name="post_content" value="1"> Добавить описание</p>
	<h3>Произвольные поля:</h3><table><tr>';
	$table_price .= '<b>price-products</b> - Цена товара<br />
	<b>amount_product</b> - количество в наличии<br />
	<b>reserve_product</b> - товары в резерве<br />
	<b>related_products_recall</b> - ID товарной категории выводимой в блоке рекомендуемых или похожих товаров<br />';
	$n=1;	
	foreach ($postmeta as $key){
		if (strpos($key->meta_key, "goods_id") === FALSE && strpos($key->meta_key , "_") !== 0){
		$n++;
			$table_price .= '<td><input type="checkbox" name="'.$key->meta_key.'" value="1"> '.$key->meta_key.'</td>';
			if($n%2) $table_price .= '</tr><tr>';
		}
	}
	$table_price .='</tr><tr><td colspan="2" align="right"><input type="submit" name="get_csv_file" value="Выгрузить товары в файл"></td></tr></table>
	</form>';
	
	$table_price .='<form method="post" action="" enctype="multipart/form-data">
	'.wp_nonce_field('add-file-csv','_wpnonce',true,false).'
	<p><input type="file" name="file_csv" value="1"><input type="submit" name="add_file_csv" value="Импортировать товары из файла"></p>
	</form>';
	echo $table_price;

	if($_FILES['file_csv']&&wp_verify_nonce( $_POST['_wpnonce'], 'add-file-csv' )){
		$file_name = $_FILES['file_csv']['name'];
		$rest = substr($file_name, -4);//получаем расширение файла		
			if($rest=='.xml'){
				$filename = $_FILES['file_csv']['tmp_name'];
				$f1 = current(wp_upload_dir()) . "/" . basename($filename);
				copy($filename,$f1);

				$handle = fopen($f1, "r");
				$posts = array();
				if ($handle){
					while ( !feof($handle) ){
						$string = rtrim(fgets($handle));						
						if ( false !== strpos($string, '<post>') ){
							$post = '';
							$doing_entry = true;
							continue;
						}
						if ( false !== strpos($string, '</post>') ){
							$doing_entry = false;
							$posts[] = $post;
							continue;
						}
						if ( $doing_entry ){
							$post .= $string . "\n";
						}
					}
				}
				fclose($handle);
								
				$posts_columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->posts}");
				$updated = 0;
				foreach((array)$posts as $value){
					$data = array();
					$post = array();
					if (preg_match_all('|<(.+?)><!\[CDATA\[(.*?)\]\]></.+?>|s', $value, $m1)||preg_match_all('|<(.+?)>(.*?)</.+?>|s', $value, $m1) ){		
							foreach ($m1[1] as $n => $key){
							if ($key == "category") continue;
							if ($key == "tag") continue;					
							$data[$key] = html_entity_decode($m1[2][$n]);
							flush();
						}
					}
					reset($posts_columns);
					foreach ($posts_columns as $col){
						if ( isset($data[$col->Field]) ){							
						if ($col->Field == "ID"){
							$ID	= $data[$col->Field];												
						}else{
							$post[$col->Field] = "{$col->Field} = '{$data[$col->Field]}'";						
						}
							unset($data[$col->Field]);
							flush();							
						}
					}	

					if (count($post)>0){
						$wpdb->query("UPDATE {$wpdb->posts} SET ".implode(',',$post)." WHERE ID = {$ID}");
					}
					unset($post);
									
					if (count($data)){
						foreach ($data as $key => $value){							
								update_post_meta($ID, $key, $value);
						}
					}		
					unset($data);
					$updated++;
					echo "{$updated}. Товар {$ID} был обновлен<br>";
					flush();
																
				}
			}else{
				echo '<div class="error">Неверный формат загруженного файла! Допустимо только XML</div>';
			}
	}
}