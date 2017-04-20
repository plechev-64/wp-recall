<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

add_action('admin_init',array('Rcl_History_Orders','update_status_order'));

class Rcl_History_Orders extends WP_List_Table {

    var $per_page = 50;
    var $current_page = 1;
    var $total_items;
    var $offset = 0;
    var $sum = 0;

    function __construct(){
        global $status, $page;
        parent::__construct( array(
            'singular'  => __( 'order', 'wp-recall' ),
            'plural'    => __( 'orders', 'wp-recall' ),
            'ajax'      => false
        ) );

        $this->per_page = $this->get_items_per_page('rcl_orders_per_page', 50);
        $this->current_page = $this->get_pagenum();
        $this->offset = ($this->current_page-1)*$this->per_page;

        add_action( 'admin_head', array( &$this, 'admin_header' ) );            
    }

    function admin_header() {
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'manage-rmag' != $page )
        return;
        echo '<style type="text/css">';
        echo '.wp-list-table .column-order_id { width: 10%; }';
        echo '.wp-list-table .column-user_id { width: 25%; }';
        echo '.wp-list-table .column-products_amount { width: 10%; }';
        echo '.wp-list-table .column-order_price { width: 10%;}';
        echo '.wp-list-table .column-order_status { width: 30%;}';
        echo '.wp-list-table .column-order_date { width: 15%;}';
        echo '</style>';
    }

    function no_items() {
        _e( 'No orders found.', 'wp-recall' );
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) { 
            case 'order_id':
                return $item->order_id;
            case 'user_id':
                return $item->user_id.': '.get_the_author_meta('user_login',$item->user_id);
            case 'products_amount':
                return $item->products_amount;
            case 'order_price':
                return $item->order_price;
            case 'order_status':
                return rcl_get_status_name_order($item->order_status);
            case 'order_date':
                return $item->order_date;
            default:
                return print_r( $item, true ) ;
        }
    }

    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'order_id' => __( 'Order ID', 'wp-recall' ),
            'user_id' => __( 'Users', 'wp-recall' ),
            'products_amount' => __( 'Number of products', 'wp-recall' ),
            'order_price'    => __( 'Order sum', 'wp-recall' ),
            'order_status'    => __( 'Status', 'wp-recall' ),
            'order_date'      => __( 'Date', 'wp-recall' )
        );
         return $columns;
    }

    function column_order_id($item){
      $actions = array(
            'order-details'    => sprintf('<a href="?page=%s&action=%s&order-id=%s">'.__( 'Details', 'wp-recall' ).'</a>',$_REQUEST['page'],'order-details',$item->order_id),
        );
      return sprintf('%1$s %2$s', $item->order_id, $this->row_actions($actions) );
    }

    function column_order_status($item){

        $status = array(
            1=>'not paid',
            2=>'paid',
            3=>'sent',
            4=>'received',
            5=>'closed',
            6=>'trash'
        );

        $actions = array(
            'not paid'    => sprintf('<a href="?page=%s&action=%s&status=%s&order=%s">'.__( 'Not paid', 'wp-recall' ).'</a>',$_REQUEST['page'],'update_status',1,$item->order_id),
            'paid'    => sprintf('<a href="?page=%s&action=%s&status=%s&order=%s">'.__( 'Paid', 'wp-recall' ).'</a>',$_REQUEST['page'],'update_status',2,$item->order_id),          
            'sent'    => sprintf('<a href="?page=%s&action=%s&status=%s&order=%s">'.__( 'Sent', 'wp-recall' ).'</a>',$_REQUEST['page'],'update_status',3,$item->order_id),
            'received'    => sprintf('<a href="?page=%s&action=%s&status=%s&order=%s">'.__( 'Received', 'wp-recall' ).'</a>',$_REQUEST['page'],'update_status',4,$item->order_id),
            'closed'    => sprintf('<a href="?page=%s&action=%s&status=%s&order=%s">'.__( 'Closed', 'wp-recall' ).'</a>',$_REQUEST['page'],'update_status',5,$item->order_id),
            'trash'    => sprintf('<a href="?page=%s&action=%s&status=%s&order=%s">'.__( 'Trash', 'wp-recall' ).'</a>',$_REQUEST['page'],'update_status',6,$item->order_id),
            'delete'    => sprintf('<a href="?page=%s&action=%s&order=%s">'.__( 'Delete', 'wp-recall' ).'</a>',$_REQUEST['page'],'delete',$item->order_id),
          );

        unset($actions[$status[$item->order_status]]);

        return sprintf('%1$s %2$s', rcl_get_status_name_order($item->order_status), $this->row_actions($actions) );
    }

    function column_user_id($item){
      $actions = array(
            'all-orders'    => sprintf('<a href="?page=%s&action=%s&user=%s">'.__( 'All user orders', 'wp-recall' ).'</a>',$_REQUEST['page'],'all-orders',$item->user_id),
        );
      return sprintf('%1$s %2$s', $item->user_id.': '.get_the_author_meta('user_login',$item->user_id), $this->row_actions($actions) );
    }

    function get_bulk_actions() {
      $actions = rcl_order_statuses();
      $actions['delete'] = __( 'Delete', 'wp-recall' );
      return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="orders[]" value="%s" />', $item->order_id
        );    
    }

    static function update_status_order(){
        global $wpdb;

        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'manage-rmag' != $page ) return;

        if(isset($_REQUEST['action'])){
            if(isset($_POST['action'])){
                if(!isset($_POST['orders'])) return;
                $action = $_POST['action'];
                foreach($_POST['orders'] as $order_id){
                    switch($action){
                        case 'delete': rcl_delete_order($order_id); break;
                        default: rcl_update_status_order($order_id,$action);
                    }
                }
                wp_redirect($_POST['_wp_http_referer']);exit;                
            }
            if(isset($_GET['action'])){
                switch($_GET['action']){
                    case 'update_status': return rcl_update_status_order($_REQUEST['order'],$_REQUEST['status']);
                    case 'delete': return rcl_delete_order($_REQUEST['order']);
                }

                return;
            }

        }
    }    

    function get_data(){

        $args = array();

        if(isset($_GET['date-start'])&&$_GET['date-start']){

            $args['date_query'][] = array(
                'value' => array($_GET['date-start'],$_GET['date-end']),
                'compare' => 'BETWEEN',
                'column' => 'order_date'
            );

            if(isset($_GET['sts'])&&$_GET['sts']) 
                $args['order_status'] = intval($_GET['sts']);

        }else{
            if(isset($_GET['sts'])&&$_GET['sts']){
                $args['order_status'] = intval($_GET['sts']);
            }elseif(isset($_GET['user'])&&$_GET['user']){
                $args['user_id'] = intval($_GET['user']);
            }else{
                $args['order_status__not_in'] = 6;
            }
        }

        if(isset($_POST['s'])&&$_POST['s']){
            $args['order_id'] = intval($_POST['s']);
        }

        $args['number'] = $this->per_page;
        $args['offset'] = $this->offset;
           
        $this->total_items = rcl_count_orders($args);

        if(!$this->total_items) return false;

        $items = rcl_get_orders($args);

        return $items;

    }

    function prepare_items() {

        $data = $this->get_data();
        $this->_column_headers = $this->get_column_info();
        $this->set_pagination_args( array(
            'total_items' => $this->total_items,
            'per_page'    => $this->per_page
        ) );

        $this->items = $data;

    }
}
