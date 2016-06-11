<?php
include_once 'orders-history.php';

function wpmagazin_options_panel(){
    add_menu_page('Recall Commerce', 'Recall Commerce', 'manage_options', 'manage-rmag', 'rmag_manage_orders');
    $hook = add_submenu_page( 'manage-rmag', __('Orders','wp-recall'), __('Orders','wp-recall'), 'manage_options', 'manage-rmag', 'rmag_manage_orders');
        add_action( "load-$hook", 'rcl_orders_page_options' );
    add_submenu_page( 'manage-rmag', __('Export/Import','wp-recall'), __('Export/Import','wp-recall'), 'manage_options', 'manage-wpm-price', 'rmag_export');
    add_submenu_page( 'manage-rmag', __('Order form','wp-recall'), __('Order form','wp-recall'), 'manage_options', 'manage-custom-fields', 'rmag_custom_fields');
    add_submenu_page( 'manage-rmag', __('Store settings','wp-recall'), __('Store settings','wp-recall'), 'manage_options', 'manage-wpm-options', 'rmag_global_options');
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
            'name'       => 'global[basket_page_rmag]',
            'show_option_none' => __('Not selected','wp-recall'),
            'echo'       => 0
        );

        $content .= $opt->options(
            __('Settings','wp-recall').' WP-RECALL-MAGAZIN',array(
            $opt->option_block(
                array(
                    $opt->title(__('General settings','wp-recall')),

                    $opt->label(__('Email Notification','wp-recall')),
                    $opt->option('email',array('name'=>'admin_email_magazin_recall')),
                    $opt->notice(__('If email is not specified, a notification will be sent to all users of the website with the rights of the "Administrator"','wp-recall')),

                    $opt->label(__('The margin on the goods (%)','wp-recall')),
                    $opt->option('number',array('name'=>'margin_product')),
                    $opt->notice(__('If zero or nothing, the margin on the goods not being used','wp-recall'))
                )
            ),
            $opt->option_block(
                array(
                    $opt->title(__('Drawing up of an order','wp-recall')),

                    $opt->label(__('Register at registration','wp-recall')),
                    $opt->option('select',array(
                        'name'=>'buyer_register',
                        'default'=>1,
                        'options'=>array(__('Disabled','wp-recall'),__('Included','wp-recall'))
                    )),
                    $opt->notice(__('If enabled , the user is automatically registered on the site if successful ordering','wp-recall'))
                )
            ),
            $opt->option_block(
                array(
                    $opt->title(__('Accounting product','wp-recall')),

                    $opt->label(__('Accounting for goods in stock','wp-recall')),
                    $opt->option('select',array(
                        'name'=>'products_warehouse_recall',
                        'options'=>array(__('Disabled','wp-recall'),__('Included','wp-recall'))
                    )),
                    $opt->notice(__('If records are maintained , then the goods will be possible to observe the presence of the warehouse. If the goods are not available , the button on the product to add to cart is not','wp-recall'))
                )
            ),
            $opt->option_block(
                array(
                    $opt->title(__('Cart','wp-recall')),

                    $opt->label(__('The procedure for withdrawal of the button " Add to Cart "','wp-recall')),
                    $opt->option('select',array(
                        'name'=>'add_basket_button_recall',
                        'options'=>array(__('Automatically','wp-recall'),__('Through shortcode','wp-recall'))
                    )),
                    $opt->notice(__('On the product page . If shortcode , then use the [add-basket]','wp-recall')),

                    $opt->label(__('Page checkout','wp-recall')),
                    wp_dropdown_pages( $args ),
                    $opt->notice(__('Specify the page that hosts the shortcode [basket]','wp-recall')),
                )
            ),
             $opt->option_block(
                array(
                    $opt->title(__('System or similar featured products','wp-recall')),

                    $opt->label(__('The order of withdrawal','wp-recall')),
                    $opt->option('select',array(
                        'name'=>'sistem_related_products',
                        'options'=>array(__('Disabled','wp-recall'),__('Included','wp-recall'))
                    )),

                    $opt->label(__('Title block featured products','wp-recall')),
                    $opt->option('text',array('name'=>'title_related_products_recall')),

                    $opt->label(__('Number of featured products','wp-recall')),
                    $opt->option('number',array('name'=>'size_related_products'))
                )
            ),
             $opt->option_block(
                array(
                    $opt->title(__('Currency and courses','wp-recall')),
			$opt->label(__('The base currency','wp-recall')),
			$opt->option('select',array(
                        'name'=>'primary_cur',
                        'options'=>rcl_get_currency()
                    )),
                    $opt->label(__('Secondary currency','wp-recall')),
                    $opt->option('select',array(
                        'name'=>'multi_cur',
                        'parent'=>true,
                        'options'=>array(__('Disabled','wp-recall'),__('Included','wp-recall'))
                    )
                    ),
                    $opt->child(
                        array(
                            'name'=>'multi_cur',
                            'value'=>1
                        ),
                        array(
                            $opt->label(__('Select a currency','wp-recall')),
                            $opt->option('select',array(
                                    'name'=>'secondary_cur',
                                    'options'=>rcl_get_currency()
                            )),
                            $opt->label(__('Course','wp-recall')),
                            $opt->option('text',array('name'=>'curse_currency')),
                            $opt->notice(__('Enter the secondary currency exchange rate in relation to the principal. For example: 1.3','wp-recall'))
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

	$content = '<h2>'.__('Field Management Order Form','wp-recall').'</h2>

	'.$f_edit->edit_form(array(
            $f_edit->option('select',array(
                'name'=>'requared',
                'notice'=>__('required field','wp-recall'),
                'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
            ))
        ));

	echo $content;
}

function rmag_manage_orders(){
    global $wpdb;

    $n=0;
    $s=0;
    if($_GET['remove-trash']==101&&wp_verify_nonce( $_GET['_wpnonce'], 'delete-trash-rmag'))
            $wpdb->query($wpdb->prepare("DELETE FROM ".RMAG_PREF ."orders_history WHERE order_status = '%d'",6));

    if(isset($_GET['action'])&&$_GET['action']=='order-details'){
    
        echo '<h2>'.__('Order management','wp-recall').'</h2>
			<div style="width:1050px">';

	global $order,$product;

	$order = rcl_get_order($_GET['order']);

	if($_POST['submit_message']){
		if($_POST['email_author']) $email_author = sanitize_email($_POST['email_author']);
		else $email_author = 'noreply@'.$_SERVER['HTTP_HOST'];
		$user_email = get_the_author_meta('user_email',intval($_POST['address_message']));
		$result_mess = rcl_mail($user_email, sanitize_text_field($_POST['title_message']), force_balance_tags($_POST['text_message']));
	}

	$header_tb = array(
		'№ п/п',
		__('Name product','wp-recall'),
		__('Price','wp-recall'),
		__('Amount','wp-recall'),
		__('Sum','wp-recall'),
		__('Status','wp-recall'),
	);

	echo '<h3>'.__('ID order','wp-recall').': '.$_GET['order'].'</h3>'
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
                    <td colspan="4">'.__('Sum order','wp-recall').'</td>
                    <td colspan="2">'.$order->order_price.'</td>
		</tr>
	</table>';

	$get_fields = get_option( 'rcl_profile_fields' );

	$cf = new Rcl_Custom_Fields();

	foreach((array)$get_fields as $custom_field){
            $meta = get_the_author_meta($custom_field['slug'],$order->order_author);
            $show_custom_field .= $cf->get_field_value($custom_field,$meta);
	}

	$details_order = rcl_get_order_details($order->order_id);

	echo '<form><input type="button" value="'.__('Ago','wp-recall').'" onClick="history.back()"></form>'
                . '<div style="text-align:right;">'
                    . '<a href="'.admin_url('admin.php?page=manage-rmag').'">'.__('Show all orders','wp-recall').'</a>
                </div>
	<h3>'.__('All orders user','wp-recall').': <a href="'.admin_url('admin.php?page=manage-rmag&user='.$order->order_author).'">'.$user_login.'</a></h3>
	<h3>'.__('Information about the user','wp-recall').':</h3>'
                . '<p><b>'.__('Name','wp-recall').'</b>: '.get_the_author_meta('display_name',$order->order_author).'</p>'
                . '<p><b>'.__('Email','wp-recall').'</b>: '.get_the_author_meta('user_email',$order->order_author).'</p>'.$show_custom_field;
	if($details_order) echo '<h3>'.__('Order details','wp-recall').':</h3>'.$details_order;
	if($result_mess) echo '<h3 style="color:green;">'.__('The message was sent','wp-recall').'!</h3>';
	echo '<style>.form_message input[type="text"], .form_message textarea{width:450px;padding:5px;}</style>
	<h3>'.__('Email a user an activation e-mail','wp-recall').' '.get_the_author_meta('user_email',$order->order_author).'</h3>
	<form method="post" action="" class="form_message" >
	<p><b></b> ('.__('by default','wp-recall').' "noreply@'.$_SERVER['HTTP_HOST'].'")</p>
	<input type="text" name="email_author" value="'.sanitize_email($_POST['email_author']).'">
	<p><b>'.__('The subject of the email','wp-recall').'</b></p>
	<input type="text" name="title_message" value="'.sanitize_text_field($_POST['title_message']).'">
	<p><b>'.__('Text messages','wp-recall').'</b></p>';

	$textmail = "<p>".__('Good day','wp-recall')."!</p>
	<p>".__('You or someone else place your order on the website','wp-recall')." ".get_bloginfo('name')."</p>
	<h3>".__('Order details','wp-recall').":</h3>
	".rcl_get_include_template('order.php',__FILE__)."
	<p>".__('Your order is awaiting payment. You can pay your order with any of the proposed method from your personal account or just adding to your personal account on the website','wp-recall')." <a href='".get_bloginfo('wpurl')."'>".get_bloginfo('wpurl')."<p>
	____________________________________________________________________________
	<p>".__('This letter was generated automatically and don`t need to answer it','wp-recall').'</p>';

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
	<p><input type="submit" name="submit_message" value="'.__('Send','wp-recall').'"></p>
	</form>';

	echo $table;
        
        echo '</div>';//конец блока заказов

    }else{

        rcl_admin_orders_page();

    }

}

add_action('admin_init','rcl_read_exportfile');
function rcl_read_exportfile(){
	global $wpdb;

	if(!isset($_POST['_wpnonce'])||!wp_verify_nonce( $_POST['_wpnonce'], 'get-csv-file' )) return false;

	$file_name = 'products.xml';
	$file_src    = plugin_dir_path( __FILE__ ).'xml/'.$file_name;

	$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

	$sql_field = "ID";
	if($_POST['post_title']==1) $sql_field .= ',post_title';
	if($_POST['post_content']==1) $sql_field .= ',post_content';
        if($_POST['post_excerpt']==1) $sql_field .= ',post_excerpt';
	$sql_field .= ',post_status';

	$posts = $wpdb->get_results("SELECT $sql_field FROM ".$wpdb->prefix ."posts WHERE post_type = 'products' AND post_status!='draft'");
	$postmeta = $wpdb->get_results("SELECT meta_key FROM ".$wpdb->prefix ."postmeta GROUP BY meta_key ORDER BY meta_key");

	$sql_field = explode(',',$sql_field);
	$cnt = count($sql_field);

	if($posts){
	$xml .= "<posts>\n";
		foreach($posts as $post){
			
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
                        
                        $trms = array();
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
                        
                        $trms = array();
                        $terms = get_the_terms( $post->ID, 'product_tag' );
			$xml .= "<product_tag>";
			if($terms){
                            foreach($terms as $term){
                                $trms[] = $term->name;
                            }
                            $xml .= "<![CDATA[".implode(',',$trms)."]]>";
			}else{
                            $xml .= "<![CDATA[0]]>";
			}
			$xml .= "</product_tag>\n";

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
	$table_price .='<h2>'.__('Export/import data','wp-recall').'</h2><form method="post" action="">
	'.wp_nonce_field('get-csv-file','_wpnonce',true,false).'
	<p><input type="checkbox" name="post_title" checked value="1"> '.__('Add a title','wp-recall').'</p>
	<p><input type="checkbox" name="post_content" checked value="1"> '.__('Add a description','wp-recall').'</p>
        <p><input type="checkbox" name="post_excerpt" value="1"> '.__('Add a short description','wp-recall').'</p>
	<h3>'.__('Custom fields goods','wp-recall').':</h3><table><tr>';

	$fields = array(
		'price-products'=>__('The price of the product in the default currency','wp-recall'),
		'amount_product'=>__('The quantity of goods in stock','wp-recall'),
		'reserve_product'=>__('The goods in reserve','wp-recall'),
		'type_currency'=>__('The currency value of the goods','wp-recall'),
		'curse_currency'=>__('The course is for more currency for a product','wp-recall'),
		'margin_product'=>__('The mark on the product','wp-recall'),
		'outsale'=>'1 - '.__('the item is no longer available','wp-recall'),
		'related_products_recall'=>__('ID commodity category is displayed in the unit or recommended similar products','wp-recall'),
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

	$table_price .='</tr><tr><td colspan="2" align="right">'
                . '<input type="submit" name="get_csv_file" value="'.__('Upload products to a file','wp-recall').'"></td></tr></table>
	'.wp_nonce_field('get-csv-file','_wpnonce',true,false).'
        </form>';

	$table_price .='<form method="post" action="" enctype="multipart/form-data">
	'.wp_nonce_field('add-file-csv','_wpnonce',true,false).'
	<p>
	<input type="file" name="file_csv" value="1">
	<input type="submit" name="add_file_csv" value="'.__('To import products from a file','wp-recall').'"><br>
	<small><span style="color:red;">'.__('Attention','wp-recall').'!</span> '.__('Blank cells XML file do not participate in the update of the characteristics of the goods','wp-recall').'<br>
	'.__('The values of custom fields to be deleted using the file should be replaced in the file with an asterisk (*)','wp-recall').'</small>
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
                                        $product_tag = false;
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
                                                    if ($key == "product_tag"){
                                                        $product_tag = html_entity_decode($m1[2][$n]);
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
                                                    $post[$col->Field] = "$col->Field = '".$data[$col->Field]."'";
                                                    $args[$col->Field] = "{$data[$col->Field]}";
                                                }
                                                unset($data[$col->Field]);
                                                flush();
                                            }
					}

					if(!$ID){
                                            $args['post_type'] = 'products';
                                            $ID = wp_insert_post($args);
                                            $action = __('was created and added','wp-recall');
					}else{
                                            if (count($post)>0){    
                                                
                                                $sql = "UPDATE $wpdb->posts SET ".implode(",",$post)." WHERE ID = '$ID'";
                                                $res = $wpdb->query($sql);
                                                if($res) $action = __('was updated','wp-recall');
                                                else $action = __('was not updated','wp-recall');
                                            }
					}
					unset($post);

					if (count($data)){
                                            foreach ($data as $key => $value){
                                                if($value!='*') update_post_meta($ID, $key, $value);
                                                else $emptyFields[$key][] = $ID;
                                            }
					}
                                        
                                        //$args = array();
                                        if($prodcat){
                                            //$args['tax_input'] = array('prodcat'=>explode(',',$prodcat));
                                            wp_set_post_terms( $ID, explode(',',$prodcat), 'prodcat' );
                                        }
                                        if($product_tag){
                                            //$args['tax_input'] = array('product_tag'=>explode(',',$product_tag));
                                            wp_set_post_terms( $ID, explode(',',$product_tag), 'product_tag' );
                                        }

                                        do_action('rcl_upload_product_data',$ID,$data);

					unset($data);
					$updated++;
					echo "{$updated}. ".__('Product','wp-recall')." {$ID} $action<br>";
					flush();
				}

				if($emptyFields){
					foreach($emptyFields as $key=>$ids){
						$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_key='%s' AND post_id IN (".rcl_format_in($ids).")",$key,$ids));
					}
				}

			}else{
				echo '<div class="error">'.__('Invalid format of the downloaded file! Only valid XML','wp-recall').'</div>';
			}
	}
}