<?php

require_once 'products-columns.php';
require_once 'products-metabox.php';
require_once 'class-rcl-history-orders.php';

add_action('admin_init','rcl_commerce_admin_scripts');
function rcl_commerce_admin_scripts(){
    
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rcl_commerce_admin_scripts', rcl_addon_url('admin/assets/scripts.js', __FILE__) );
    wp_enqueue_style( 'rcl_commerce_style', rcl_addon_url('style.css', __FILE__) );
    wp_enqueue_style( 'rcl_commerce_admin_style', rcl_addon_url('admin/assets/style.css', __FILE__) );
    
    if(isset($_GET['page']) && $_GET['page'] == 'manage-rmag'){
        rcl_datepicker_scripts();
    }
}

add_action('admin_menu', 'rcl_commerce_menu',20);
function rcl_commerce_menu(){
    add_menu_page('Rcl Commerce', 'Rcl Commerce', 'manage_options', 'manage-rmag', 'rcl_commerce_page_orders');
    $hook = add_submenu_page( 'manage-rmag', __('Orders','wp-recall'), __('Orders','wp-recall'), 'manage_options', 'manage-rmag', 'rcl_commerce_page_orders');
    add_action( "load-$hook", 'rcl_commerce_options_orders' );
    add_submenu_page( 'manage-rmag', __('Export/Import','wp-recall'), __('Export/Import','wp-recall'), 'manage_options', 'manage-wpm-price', 'rcl_commerce_export');
    add_submenu_page( 'manage-rmag', __('Variations','wp-recall'), __('Variations','wp-recall'), 'manage_options', 'manage-variations', 'rcl_commerce_page_variations');
    add_submenu_page( 'manage-rmag', __('Order form','wp-recall'), __('Order form','wp-recall'), 'manage_options', 'manage-custom-fields', 'rcl_commerce_custom_fields');
    add_submenu_page( 'manage-rmag', __('Store settings','wp-recall'), __('Store settings','wp-recall'), 'manage_options', 'manage-wpm-options', 'rmag_global_options');
}


add_filter('admin_options_rmag','rcl_commerce_page_options',5);
function rcl_commerce_page_options($content){
    require_once 'pages/addon-settings.php';
    $content .= rcl_commerce_options();
    return $content;
}

function rcl_commerce_page_orders(){
    
    if(isset($_GET['order-id'])){
        require_once 'pages/order.php';
    }else{
        require_once 'pages/orders.php';
    }

}

function rcl_commerce_custom_fields(){
    require_once 'pages/cart-fields.php';
}

function rcl_commerce_page_variations(){
    require_once 'pages/variations.php';
}

function rcl_commerce_export(){
    require_once 'pages/export-import.php';
}

function rcl_commerce_options_orders() {
    global $Rcl_History_Orders;
    $option = 'per_page';
    $args = array(
        'label' => __( 'Orders', 'wp-recall' ),
        'default' => 50,
        'option' => 'rcl_orders_per_page'
    );
    add_screen_option( $option, $args );
    $Rcl_History_Orders = new Rcl_History_Orders();
}

rcl_ajax_action('rcl_edit_admin_price_product', false);
function rcl_edit_admin_price_product(){

    $id_post = intval($_POST['id_post']);
    $price = floatval($_POST['price']);
    
    if(isset($price)){
        
        update_post_meta($id_post,'price-products',$price);
        
        $log['success']= __('The data stored','wp-recall');
        
    }else {
        
        $log['error']= __('Error','wp-recall');
        
    }
    
    wp_send_json($log);
    
}

add_action('admin_init','rcl_read_exportfile');
function rcl_read_exportfile(){
    global $wpdb;

    if(!isset($_POST['_wpnonce'])||!wp_verify_nonce( $_POST['_wpnonce'], 'get-csv-file' )) return false;

    $importData = array(
        'fields' => array(
            'ID',
            'post_status'
        ),
        'taxonomies' => array(
            'prodcat',
            'product_tag'
        ),
        'meta' => array()
    );
    
    if(isset($_POST['product']['fields'])){
        
        $importData['fields'] = array_merge($importData['fields'],$_POST['product']['fields']);
        
    }
    
    if(isset($_POST['product']['meta'])){
        
        $importData['meta'] = $_POST['product']['meta'];
        
    }
    
    $posts = $wpdb->get_results("SELECT ".implode(',',$importData['fields'])." FROM $wpdb->posts WHERE post_type = 'products' AND post_status!='draft'");
    
    if(!$posts) return false;
    
    $xml = new DomDocument('1.0','utf-8');
    $products = $xml->appendChild($xml->createElement('products'));
    
    foreach($posts as $post){

        $termData = array();
        foreach($importData['taxonomies'] as $taxonomy){
            $termData[$taxonomy] = get_the_terms( $post->ID, $taxonomy );
        }
        
        if($importData['meta']){
            $postmeta = $wpdb->get_results("SELECT meta_key,meta_value FROM $wpdb->postmeta WHERE post_id='$post->ID' AND meta_key IN ('".implode("','",$importData['meta'])."')");
        
            $metas = array();
            foreach($postmeta as $meta){
                $metas[$meta->meta_key] = maybe_unserialize($meta->meta_value);
            }
        }
        
        $product = $products->appendChild($xml->createElement('product'));
        
        foreach($importData as $k => $fields){
            
            if($k == 'fields'){
                $data = $product->appendChild($xml->createElement($k));
                foreach($fields as $field){
                    if(isset($post->$field)){
                        $pField = $data->appendChild($xml->createElement($field));
                        $pField->appendChild($xml->createTextNode($post->$field));
                    }
                }

                continue;
            
            }
            
            if($k == 'taxonomies'){
                $data = $product->appendChild($xml->createElement($k));
                foreach($termData as $taxonomy => $terms){
                    if(!$terms) continue;
                    $values = array();
                    foreach($terms as $term) $values[] = ($taxonomy == 'prodcat')? $term->term_id: $term->name;
                    $tax = $data->appendChild($xml->createElement($taxonomy));
                    $tax->appendChild($xml->createTextNode(implode(',',$values)));
                }

            }
            
            if($k == 'meta'){
                
                if(!$fields) continue;
                
                $meta = $product->appendChild($xml->createElement('meta'));
                
                foreach($fields as $i => $metadata){
            
                    if(is_numeric($i)){
                        $data = $meta->appendChild($xml->createElement($metadata));
                        $data->appendChild($xml->createTextNode((isset($metas[$metadata])? $metas[$metadata]: '')));
                        continue;
                    }
                    
                    $parent = $meta->appendChild($xml->createElement($i));
                    
                    foreach($metadata as $metaKey){
                        $child = $parent->appendChild($xml->createElement($metaKey));
                        $child->appendChild($xml->createTextNode((isset($metas[$i][$metaKey])? $metas[$i][$metaKey]: '')));
                    }
                
                }
                
            }
            
        }
        
    }
    
    $filename = 'products.xml';
    $filepath = wp_normalize_path(plugin_dir_path( __FILE__ ).'xml/'.$filename);
    
    $xml->formatOutput = true;
    $xml->save($filepath);

    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Type: text/xml; charset=utf-8');
    readfile($filepath);
    exit;
    
}

function rcl_import_product($product){
    
    $fields = (array)$product->fields;
    
    $postData = array();
    foreach($fields as $fieldName => $val){
        $postData[$fieldName] = $val;
    }

    $postData['post_type'] = 'products';
    
    if($postData['ID']){
        $postID = wp_update_post($postData);
    }else{
        $postData['post_author'] = 1;
        $postID = wp_insert_post($postData);
    }
    
    if(!$postID) return false;
    
    if($product->taxonomies){
        $taxonomies = (array)$product->taxonomies;
        foreach($taxonomies as $tax => $terms){
            wp_set_post_terms( $postID, array_map('trim',explode(',',$terms)), $tax );
        }
    }
    
    $meta = (array)$product->meta;
    
    foreach($meta as $metaKey => $metaValue){
        
        if(is_object($metaValue)) $metaValue = (array)$metaValue;
        
        if($metaValue){

            if(is_array($metaValue)){
                
                foreach($metaValue as $k=>$value) 
                    if(is_object($value)) 
                        $metaValue[$k] = array();
                    
            }
            
            update_post_meta($postID, $metaKey, $metaValue);
            
        }else
            delete_post_meta($postID, $metaKey);
        
    }

    return $postData['ID']? $postData['post_title']: true;
    
}

rcl_ajax_action('rcl_ajax_import_products');
function rcl_ajax_import_products(){
    global $wpdb;
    
    rcl_verify_ajax_nonce();
    
    $path = $_POST['path'];
    
    $xml = simplexml_load_file($path);
    
    if(!$xml){
        wp_send_json(array(
            'error' => __('Файл не был найден!')
        ));
    }
    
    $status = $_POST['status'];
    $page = $_POST['page'];
    $number = $_POST['number'];
    $count = $_POST['count']? $_POST['count']: count($xml->product); //$_POST['count'];
    $progress = $_POST['progress'];
    
    $offset = ($page-1) * $number;
    
    $result = array(
        'status' => 'work'
    );

    switch($status){
        case 'work':
            
            $i = 0;
            foreach($xml->product as $product){ 
                
                $i++;
            
                if($offset && $i <= $offset) continue;
                
                $postData = rcl_import_product($product);
                
                $logText = $postData === true? 'Создан новый продукт "'.$product->fields->post_title.'"': 'Обновлены данные "'.$product->fields->post_title.'"';
                
                $log[] = '<div>'.$i.' '.$logText.'</div>';
                
                if($i >= $offset + $number) break;
                
            }

            $stepName = 'Импортировано '.$i.' из '.$count;
            
            $progress += 100/ceil($count/$number);

            $page ++;

        break;
    }
    
    if($i >= $count){
        $stepName = 'Процесс импорта завершен '.'Импортировано '.$i.' из '.$count;
        $status = 'end';
        unlink($path);
    }

    $result['status'] = $status;
    $result['page'] = $page;
    $result['count'] = $count;
    $result['number'] = $number;
    $result['path'] = $path;
    
    if(isset($progress) && $progress)
        $result['progress'] = $progress;
    
    if(isset($stepName) && $stepName)
        $result['name'] = $stepName;
    
    if(isset($log) && $log)
        $result['log'] = $log;
    
    echo json_encode($result); exit;
    
}



function rcl_get_chart_orders($orders){
    global $order,$chartData,$chartArgs;

    if(!$orders) return false;

    $chartArgs = array();
    $chartData = array(
        'title' => __('Finance','wp-recall'),
        'title-x' => __('Period of time','wp-recall'),
        'data'=>array(
            array('"'.__('Days/Months','wp-recall').'"', '"'.__('Payments (pcs.)','wp-recall').'"', '"'.__('Income (tsd.)','wp-recall').'"')
        )
    );

    foreach($orders as $order){
        rcl_setup_chartdata($order->order_date,$order->order_price);
    }

    return rcl_get_chart($chartArgs);
}

add_filter('rcl_custom_field_options','rcl_add_cart_profile_field_option',10,3);
function rcl_add_cart_profile_field_option($options, $field, $type){
    
    if($type != 'profile') return $options;
    
    $options[] = array(
        'type' => 'radio',
        'slug'=>'order',
        'title'=>__('display at checkout for guests','wp-recall'),
        'values'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
    );
    
    return $options;
    
}

add_action('rcl_add_dashboard_metabox', 'rcl_add_commerce_metabox');
function rcl_add_commerce_metabox($screen){
    add_meta_box( 'rcl-commerce-metabox', __('Last orders','wp-recall'), 'rcl_commerce_metabox', $screen->id, 'side' );
}

function rcl_commerce_metabox(){
    
    $orders = rcl_get_orders(array('number'=>5));
    
    if(!$orders){
        echo '<p>'.__('No orders yet','wp-recall').'</p>'; return;
    }
    
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<tr>'
        . '<th>'.__('Order','wp-recall').'</th>'
        . '<th>'.__('Buyer','wp-recall').'</th>'
        . '<th>'.__('Q-ty','wp-recall').'</th>'
        . '<th>'.__('Sum','wp-recall').'</th>'
        . '<th>'.__('Status','wp-recall').'</th>'
        . '</tr>';
    foreach($orders as $order){
        echo '<tr>'
        . '<td><a href="'.admin_url('admin.php?page=manage-rmag&action=order-details&order-id='.$order->order_id).'" target="_blank">'.$order->order_id.'</a></td>'
        . '<td>'.get_the_author_meta('user_login',$order->user_id).'</td>'
        . '<td>'.$order->products_amount.'</td>'
        . '<td>'.$order->order_price.' '.rcl_get_primary_currency(2).'</td>'
        . '<td>'.rcl_get_status_name_order($order->order_status).'</td>'
        . '</tr>';
    }
    echo '</table>';
    echo '<p><a href="'.admin_url('admin.php?page=manage-rmag').'" target="_blank">'.__('Go to orders manager','wp-recall').'</a></p>';
}