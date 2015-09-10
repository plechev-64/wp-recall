<?php
function wpmagazin_options_panel(){
  add_menu_page('Recall Commerce', 'Recall Commerce', 'manage_options', 'manage-rmag', 'rmag_manage_orders');
	add_submenu_page( 'manage-rmag', 'Заказы', 'Заказы', 'manage_options', 'manage-rmag', 'rmag_manage_orders');
	add_submenu_page( 'manage-rmag', 'Экспорт/импорт', 'Экспорт/импорт', 'manage_options', 'manage-wpm-price', 'rmag_export');
	add_submenu_page( 'manage-rmag', 'Форма заказа', 'Форма заказа', 'manage_options', 'manage-custom-fields', 'rmag_custom_fields');
	add_submenu_page( 'manage-rmag', 'Настройки магазина', 'Настройки магазина', 'manage_options', 'manage-wpm-options', 'rmag_global_options');
}
add_action('admin_menu', 'wpmagazin_options_panel',20);

add_filter('admin_options_rmag','rmag_primary_options',5);
function rmag_primary_options($content){
        global $rcl_options;
	$rcl_options = get_option('primary-rmag-options');

        include_once RCL_PATH.'functions/rcl_options.php';

        $opt = new Rcl_Options(rcl_key_addon(pathinfo(__FILE__)));

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
                    $opt->title('Общие настройки'),

                    $opt->label('Email для уведомлений'),
                    $opt->option('email',array('name'=>'admin_email_magazin_recall')),
                    $opt->notice('Если email не указан, то уведомления будут рассылаться всем пользователям сайта с правами "Администратор"'),

					$opt->label('Наценка на товары (%)'),
                    $opt->option('number',array('name'=>'margin_product')),
                    $opt->notice('Если ноль или ничего нет, то наценка на товары не используется')
                )
            ),
            $opt->option_block(
                array(
                    $opt->title('Оформление заказа'),

                    $opt->label('Регистрация при оформлении'),
                    $opt->option('select',array(
                        'name'=>'noreg_order',
                        'options'=>array('Включено','Отключено')
                    )),
                    $opt->notice('Если включено, то пользователь автоматически регистрируется на сайте при успешном оформлении заказа')
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
                    $opt->option('number',array('name'=>'size_related_products'))
                )
            ),
             $opt->option_block(
                array(
                    $opt->title('Валюта и курсы'),
			$opt->label('Основная валюта'),
			$opt->option('select',array(
                        'name'=>'primary_cur',
                        'options'=>rcl_get_currency()
                    )),
                    $opt->label('Второстепенная валюта'),
                    $opt->option('select',array(
                        'name'=>'multi_cur',
                        'parent'=>true,
                        'options'=>array('Отключено','Включено')
                    )
                    ),
                    $opt->child(
                        array(
                            'name'=>'multi_cur',
                            'value'=>1
                        ),
                        array(
                            $opt->label('Выберите валюту'),
                            $opt->option('select',array(
                                    'name'=>'secondary_cur',
                                    'options'=>rcl_get_currency()
                            )),
                            $opt->label('Курс'),
                            $opt->option('text',array('name'=>'curse_currency')),
                            $opt->notice('Укажите курс второстепенной валюты по отношению к основной. Например: 1.3')
                        )
                    )
                )
            ))
        );
	return $content;
}

function rmag_custom_fields(){
	global $wpdb;

        rcl_sortable_scripts();

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

function rmag_manage_orders(){

	global $wpdb;

	echo '<h2>Управление заказами</h2>
			<div style="width:1050px">';//начало блока настроек профиля
	$n=0;
	$s=0;
	if($_GET['remove-trash']==101&&wp_verify_nonce( $_GET['_wpnonce'], 'delete-trash-rmag'))
                $wpdb->query($wpdb->prepare("DELETE FROM ".RMAG_PREF ."orders_history WHERE order_status = '%d'",6));

if($_GET['order-id']){

	global $order,$product;

	$order = rcl_get_order($_GET['order-id']);

	if($_POST['submit_message']){
		if($_POST['email_author']) $email_author = sanitize_email($_POST['email_author']);
		else $email_author = 'noreply@'.$_SERVER['HTTP_HOST'];
		$user_email = get_the_author_meta('user_email',intval($_POST['address_message']));
		$result_mess = rcl_mail($user_email, sanitize_text_field($_POST['title_message']), force_balance_tags($_POST['text_message']));
	}

	$header_tb = array(
		'№ п/п',
		'Наименование товара',
		'Цена',
		'Количество',
		'Сумма',
		'Статус',
	);

	echo '<h3>ID заказа: '.$_GET['order_id'].'</h3>'
                . '<table class="widefat">'
                . '<tr>';

	foreach($header_tb as $h){
		echo '<th>'.$h.'</th>';
	}

	echo '</tr>';

	foreach($order->products as $product){
		$n++;
		$user_login = get_the_author_meta('user_login',$product->user_id);
		echo '<tr>'
			. '<td>'.$n.'</td>'
			. '<td>'.get_the_title($product->product_id).'</td>'
			. '<td>'.$product->product_price.'</td>'
			. '<td>'.$product->numberproduct.'</td>'
			. '<td>'.$product->product_price.'</td>'
			. '<td>'.rcl_get_status_name_order($product->order_status).'</td>'
		. '</tr>';

	}
	echo '<tr>
			<td colspan="4">Сумма заказа</td>
			<td colspan="2">'.$order->order_price.'</td>
		</tr>
	</table>';

	$get_fields = get_option( 'custom_profile_field' );

	$cf = new Rcl_Custom_Fields();

	foreach((array)$get_fields as $custom_field){
		$meta = get_the_author_meta($custom_field['slug'],$order->order_author);
		$show_custom_field .= $cf->get_field_value($custom_field,$meta);
	}

	$details_order = rcl_get_order_details($order->order_id);

	echo '<form><input type="button" value="Назад" onClick="history.back()"></form><div style="text-align:right;"><a href="'.admin_url('admin.php?page=manage-rmag').'">Показать все заказы</a></div>
	<h3>Все заказы пользователя: <a href="'.admin_url('admin.php?page=manage-rmag&user='.$order->order_author).'">'.$user_login.'</a></h3>
	<h3>Информация о пользователе:</h3><p><b>Имя</b>: '.get_the_author_meta('display_name',$order->order_author).'</p><p><b>Email</b>: '.get_the_author_meta('user_email',$order->order_author).'</p>'.$show_custom_field;
	if($details_order) echo '<h3>Детали заказа:</h3>'.$details_order;
	if($result_mess) echo '<h3 style="color:green;">Сообщение было отправлено!</h3>';
	echo '<style>.form_message input[type="text"], .form_message textarea{width:450px;padding:5px;}</style>
	<h3>Написать пользователю сообщение на почту '.get_the_author_meta('user_email',$order->order_author).'</h3>
	<form method="post" action="" class="form_message" >
	<p><b>Почта отправителя</b> (по-умолчанию "noreply@'.$_SERVER['HTTP_HOST'].'")</p>
	<input type="text" name="email_author" value="'.sanitize_email($_POST['email_author']).'">
	<p><b>Тема письма</b></p>
	<input type="text" name="title_message" value="'.sanitize_text_field($_POST['title_message']).'">
	<p><b>Текст сообщения</b></p>';

	$textmail = "<p>Добрый день!</p>
	<p>Вы или кто то другой оформил заказ на сайте ".get_bloginfo('name')."</p>
	<h3>Детали заказа:</h3>
	".rcl_get_include_template('order.php',__FILE__)."
	<p>Ваш заказ ожидает оплаты. Вы можете произвести оплату своего заказа любым из предложенных способ из своего личного кабинета или просто пополнив свой личный счет на сайте <a href='".get_bloginfo('wpurl')."'>".get_bloginfo('wpurl')."<p>
	____________________________________________________________________________
	Это письмо было сформировано автоматически не надо отвечать на него";

	if($_POST['text_message']) $textmail = force_balance_tags($_POST['text_message']);

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

	wp_editor( $textmail, 'textmessage', $args );

	echo '<input type="hidden" name="address_message" value="'.$order->order_author.'">
	<p><input type="submit" name="submit_message" value="Отправить"></p>
	</form>';

	echo $table;

}else{

	global $order,$product;
	$all_pr =0;

	list( $year, $month, $day, $hour, $minute, $second ) = preg_split( '([^0-9])', current_time('mysql') );

	$args = array();

	if($_POST['filter-date']){

		if($_POST['year']){
			$args['year'] = $_POST['year'];
			if($_POST['month']) $args['month'] = sanitize_text_field($_POST['month']);
		}

		if($_POST['status']) $args['order_status'] = intval($_POST['status']);

		$orders = rcl_get_orders($args);

	}else{
		if($_GET['status']){
			$args['order_status'] = intval($_GET['status']);
		}elseif($_GET['user']){
			$args['user_id'] = intval($_GET['user']);
                }elseif($_GET['search_order']){
                    $args['order_id'] = intval($_GET['search_order']);
                    $args['user_id'] = intval($_GET['search_order']);
                    $args['search'] = true;
                }else{
			$args['status_not_in'] = 6;
			$args['year'] = $year;
			$args['month'] = $month;
			$_POST['year'] = $year;
			$_POST['month'] = $month;
		}
        //$where = apply_filters('string_where_get_orders',$where);
	}

	$orders = rcl_get_orders($args);

        if($orders){
            foreach($orders as $rdr){
                $n++;
                foreach($rdr as $prods){
                    $all_pr += $prods->product_price*$prods->numberproduct;
                }
            }
        }

        //if(!isset($_GET['status'])||$_GET['status']!=6)
            $table .= rcl_get_chart_orders($orders);

	$table .= '<h3>Всего заказов: '.$n.' на '.$all_pr.' рублей</h3>';

        $table .= '<form method="get" action="'.admin_url('admin.php?page=manage-rmag').'"><p class="search-box">
	<label class="screen-reader-text" for="order-search-input">Поиск заказов:</label>
	<input type="search" id="order-search-input" name="search_order" placeholder="ID заказа или покупателя" value="">
	<input type="submit" id="search-submit" class="button" value="Поиск заказов">
        <input type="hidden" name="page" value="manage-rmag">
        </p></form>';

	$table .= '<form action="" method="post">';

	$table .= '<select name="status">';
	$table .= '<option value="">Все заказы</option>';
	for($a=1;$a<=6;$a++){
		$table .= '<option value="'.$a.'" '.selected($a,$_POST['status'],false).'>'.rcl_get_status_name_order($a).'</option>';
	}
	$table .= '</select>';

	$table .= '<select name="month">';
	$months = array('За все месяцы','январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь');
	foreach($months as $k=>$month){
		if($k) $k = zeroise($k, 2);
		$table .= '<option value="'.$k.'" '.selected($k,$_POST['month'],false).'>'.$month.'</option>';
	}
	$table .= '</select>';

	$table .= '<select name="year">';
	for($a=2013;$a<=$year+1;$a++){
	$table .= '<option value="'.$a.'" '.selected($a,$_POST['year'],false).'>'.$a.'</option>';
	}
	$table .= '</select>';
	$table .= '<input type="submit" value="Фильтровать" name="filter-date" class="button-secondary">';
	if($_GET['status']==6) $table .= '<a href="'.wp_nonce_url(admin_url('admin.php?page=manage-rmag&remove-trash=101'),'delete-trash-rmag').'">Очистить корзину</a>';
	$table .= '</form>';

        if(!$orders){ echo $table; exit;}

        $cols = array('Заказ ID','Пользователь','Сумма заказа','Дата и время','Статус','Смена статуса','Действие');
	$cols = apply_filters('header_table_orders_rcl',$cols);

	$table .= '<table class="widefat"><tr>';
	foreach($cols as $col){
            $table .= '<th>'.$col.'</th>';
	}
	$table .= '</tr>';

    foreach($orders as $order_id=>$order){ rcl_setup_orderdata($order);
        $radioform .= '<select id="status-'.$order_id.'" name="status-'.$order_id.'">';
        for($a=1;$a<7;$a++){
            $radioform .= '<option '.selected($a,$order->order_status,false).' value="'.$a.'">'.rcl_get_status_name_order($a).'</option>';
        }
        $radioform .= '</select>';

        if($order->order_status==6) $delete = '<input type="button" class="button-primary delete-order" id="'.$order_id.'" value="Удалить">';
        $button = '<input type="button" class="button-secondary select_status" id="'.$order_id.'" value="Изменить статус"> '.$delete;
        $user_id = $order->order_author;

        $pagelink = admin_url('admin.php?page=manage-rmag');

        $cols_content = array(
                '<a href="'.$pagelink.'&order-id='.$order_id.'">Заказ '.$order_id.'</a>',
                '<a href="'.$pagelink.'&user='.$user_id.'">'.get_the_author_meta('user_login',$user_id).'</a>',
                $order->order_price,
                $order->order_date,
                '<a href="'.$pagelink.'&status='.$order->order_status.'"><span class="change-'.$order_id.'">'.rcl_get_status_name_order($order->order_status).'</span></a>',
                $radioform,
                $button
        );

        $cols_content = apply_filters('content_table_orders_rcl',$cols_content,$user_id);

        $table .= '<tr id="row-'.$order_id.'">';

        foreach($cols_content as $content){
                $table .= '<td>'.$content.'</td>';
        }

        $table .= '</tr>';
        $radioform = '';
        $delete = '';

    }

    $cnt_cols = count($cols);
    if($_GET['status']!=6) $table .= '<tr><td align="right" colspan="'.$cnt_cols.'"><a href="'.admin_url('admin.php?page=manage-rmag&status=6').'">Перейти в корзину</a></td></tr>';
    $table .= '</table>';

    echo $table;

if($_GET['user']||$_GET['status']||$_GET['date'])echo '<form><input type="button" value="Назад" onClick="history.back()"></form><div style="text-align:right;"><a href="'.admin_url('admin.php?page=manage-rmag').'">Показать текущие заказы</a></div>';
}

echo '</div>';//конец блока заказов
}

add_action('admin_init','rcl_read_exportfile');
function rcl_read_exportfile(){
	global $wpdb;
	//print_r($_POST);exit;
	if(!isset($_POST['_wpnonce'])||!wp_verify_nonce( $_POST['_wpnonce'], 'get-csv-file' )) return false;

	$file_name = 'products.xml';
	$file_src    = plugin_dir_path( __FILE__ ).'xml/'.$file_name;

	$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

	$sql_field = "ID";
	if($_POST['post_title']==1) $sql_field .= ',post_title';
	if($_POST['post_content']==1) $sql_field .= ',post_content';
	$sql_field .= ',post_status';

	$posts = $wpdb->get_results("SELECT $sql_field FROM ".$wpdb->prefix ."posts WHERE post_type = 'products' AND post_status!='draft'");
	$postmeta = $wpdb->get_results("SELECT meta_key FROM ".$wpdb->prefix ."postmeta GROUP BY meta_key ORDER BY meta_key");

	$sql_field = explode(',',$sql_field);
	$cnt = count($sql_field);

	if($posts){
	$xml .= "<posts>\n";
		foreach($posts as $post){
			$trms = array();
			$xml .= "<post>\n";
			for($a=0;$a<$cnt;$a++){
				$xml .= "<".$sql_field[$a].">";
				if($a==0) $xml .= $post->$sql_field[$a];
				else $xml .= "<![CDATA[".$post->$sql_field[$a]."]]>";
				$xml .= "</".$sql_field[$a].">\n";
			}
			foreach ($postmeta as $key){
				if (strpos($key->meta_key, "goods_id") === FALSE && strpos($key->meta_key , "_") !== 0){
					if($_POST[$key->meta_key]==1){
						$xml .= "<".$key->meta_key.">";
						$xml .= "<![CDATA[".get_post_meta($post->ID, $key->meta_key, true)."]]>";
						$xml .= "</".$key->meta_key.">\n";
					}
				}
			}

			$terms = get_the_terms( $post->ID, 'prodcat' );
			$xml .= "<prodcat>";
			if($terms){
				foreach($terms as $term){
					$trms[] = $term->term_id;
				}
				$xml .= "<![CDATA[".implode(',',$trms)."]]>";
			}else{
				$xml .= "<![CDATA[0]]>";
			}
			$xml .= "</prodcat>\n";

			$xml .= "</post>\r";
		}
	$xml .= "</posts>";
	}

	$f = fopen($file_src, 'w');
	if(!$f)exit;
	fwrite($f, $xml);
	fclose($f);

	header('Content-Description: File Transfer');
	header('Content-Disposition: attachment; filename="'.$file_name.'"');
	header('Content-Type: text/xml; charset=utf-8');
	readfile($file_src);
	exit;

}

function rmag_export(){
global $wpdb;

	$table_price .='<style>table{min-width:500px;width:50%;margin:20px 0;}table td{border:1px solid #ccc;padding:3px;}</style>';
	$postmeta = $wpdb->get_results("SELECT meta_key FROM ".$wpdb->prefix ."postmeta GROUP BY meta_key ORDER BY meta_key");
	$table_price .='<h2>Экспорт/импорт данных</h2><form method="post" action="">
	'.wp_nonce_field('get-csv-file','_wpnonce',true,false).'
	<p><input type="checkbox" name="post_title" checked value="1"> Добавить заголовок</p>
	<p><input type="checkbox" name="post_content" checked value="1"> Добавить описание</p>
	<h3>Произвольные поля товаров:</h3><table><tr>';

	$fields = array(
		'price-products'=>'Цена товара в основной валюте',
		'amount_product'=>'Количество товара в наличии',
		'reserve_product'=>'Товары в резерве',
		'type_currency'=>'Валюта стоимости товара',
		'curse_currency'=>'Курс доп.валюты для товара',
		'margin_product'=>'Наценка на товар',
		'outsale'=>'1 - товар снят с продажи',
		'related_products_recall'=>'ID товарной категории выводимой в блоке рекомендуемых или похожих товаров',
	);

	$fields = apply_filters('products_field_list',$fields);

	foreach($fields as $key=>$name){
		$table_price .= '<b>'.$key.'</b> - '.$name.'<br />';
	}

	if($postmeta){
		$n=1;
		foreach ($postmeta as $key){
			if(!isset($fields[$key->meta_key])) continue;
			if (strpos($key->meta_key, "goods_id") === FALSE && strpos($key->meta_key , "_") !== 0){
				$n++;
				$check = (isset($fields[$key->meta_key]))?1:0;
				$table_price .= '<td><input '.checked($check,1,false).' type="checkbox" name="'.$key->meta_key.'" value="1"> '.$key->meta_key.'</td>';
				if($n%2) $table_price .= '</tr><tr>';
			}
		}
	}

	$table_price .='</tr><tr><td colspan="2" align="right"><input type="submit" name="get_csv_file" value="Выгрузить товары в файл"></td></tr></table>
	'.wp_nonce_field('get-csv-file','_wpnonce',true,false).'
        </form>';

	$table_price .='<form method="post" action="" enctype="multipart/form-data">
	'.wp_nonce_field('add-file-csv','_wpnonce',true,false).'
	<p>
	<input type="file" name="file_csv" value="1">
	<input type="submit" name="add_file_csv" value="Импортировать товары из файла"><br>
	<small><span style="color:red;">Внимание!</span> Пустые ячейки XML-файла не участвуют в обновлении характеристик товара<br>
	Значения произвольных полей удаляемые через файл должны заменяться в файле знаком звездочки (*)</small>
	</p>
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
				$emptyFields = array();

				foreach((array)$posts as $value){
					$ID = false;
					$prodcat = false;
					$data = array();
					$args = array();
					$post = array();
					//echo $updated.': '.$value.'<br>';
					if (preg_match_all('|<(.+?)><!\[CDATA\[(.*?)\]\]></.+?>|s', $value, $m1)||preg_match_all('|<(.+?)>(.*?)</.+?>|s', $value, $m1) ){
						foreach ($m1[1] as $n => $key){
							if ($key == "prodcat"){
								$prodcat = html_entity_decode($m1[2][$n]);
								continue;
							}
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
								$args[$col->Field] = "{$data[$col->Field]}";
							}
							unset($data[$col->Field]);
							flush();
						}
					}

					if(!$ID){
						$args['tax_input'] = array('prodcat'=>explode(',',$prodcat));
						$args['post_type'] = 'products';
						$ID = wp_insert_post($args);
						$action = 'создан и добавлен';
					}else{
						if (count($post)>0){
							$wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET %s WHERE ID = '%d'",implode(',',$post),$ID));
							$action = 'обновлен';
						}
					}
					unset($post);

					if (count($data)){
						foreach ($data as $key => $value){
							if($value!='*') update_post_meta($ID, $key, $value);
							else $emptyFields[$key][] = $ID;
						}
					}

                                        do_action('rcl_upload_product_data',$ID,$data);

					unset($data);
					$updated++;
					echo "{$updated}. Товар {$ID} был $action<br>";
					flush();
				}

				if($emptyFields){
					foreach($emptyFields as $key=>$ids){
						$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_key='%s' AND post_id IN (".rcl_format_in($ids).")",$key,$ids));
					}
				}

			}else{
				echo '<div class="error">Неверный формат загруженного файла! Допустимо только XML</div>';
			}
	}
}