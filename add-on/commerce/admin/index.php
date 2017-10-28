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