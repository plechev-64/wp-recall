<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

add_action('admin_init',array('Rcl_Orders_History_Table','update_status_order'));

class Rcl_Orders_History_Table extends WP_List_Table {
    
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
        echo '.wp-list-table .column-order_author { width: 25%; }';
        echo '.wp-list-table .column-numberproducts { width: 10%; }';
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
            case 'order_author':
                return $item->order_author.': '.get_the_author_meta('user_login',$item->order_author);
            case 'numberproducts':
                return $item->numberproducts;
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
            'order_author' => __( 'Users', 'wp-recall' ),
            'numberproducts' => __( 'Number products', 'wp-recall' ),
            'order_price'    => __( 'Order sum', 'wp-recall' ),
            'order_status'    => __( 'Status', 'wp-recall' ),
            'order_date'      => __( 'Date', 'wp-recall' )
        );
         return $columns;
    }
    
    function column_order_id($item){
      $actions = array(
            'order-details'    => sprintf('<a href="?page=%s&action=%s&order=%s">'.__( 'Details', 'wp-recall' ).'</a>',$_REQUEST['page'],'order-details',$item->order_id),
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
            'not paid'    => sprintf('<a href="?page=%s&action=%s&status=%s&order=%s">'.__( 'Not paid', 'wp-recall' ).'</a>',$_REQUEST['page'],'update_status paid',1,$item->order_id),
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
    
    function column_order_author($item){
      $actions = array(
            'all-orders'    => sprintf('<a href="?page=%s&action=%s&user=%s">'.__( 'Get user orders', 'wp-recall' ).'</a>',$_REQUEST['page'],'all-orders',$item->order_author),
        );
      return sprintf('%1$s %2$s', $item->order_author.': '.get_the_author_meta('user_login',$item->order_author), $this->row_actions($actions) );
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
    
    function months_dropdown( $post_type ) {
        global $wpdb,$wp_locale;

        $months = $wpdb->get_results("
                SELECT DISTINCT YEAR( order_date ) AS year, MONTH( order_date ) AS month
                FROM ".RMAG_PREF ."orders_history
                ORDER BY order_date DESC
        ");

        $months = apply_filters( 'months_dropdown_results', $months, $post_type );
        
        $month_count = count( $months );
        if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
                return;
        
        $m = isset( $_GET['m'] ) ? $_GET['m'] : 0;
        $status = isset( $_GET['sts'] ) ? $_GET['sts'] : 0; ?>
        <label for="filter-by-status" class="screen-reader-text"><?php _e( 'Filter by date' ); ?></label>
        <?php $sts = rcl_order_statuses(); ?>
        <select name="sts" id="filter-by-status">
            <option<?php selected( $status, 0 ); ?> value="0"><?php _e( 'All', 'wp-recall' ); ?></option>
            <?php foreach ( $sts as $id=>$name ) {
                    printf( "<option %s value='%s'>%s</option>\n",
                            selected( $id, $status, false ),
                            $id,
                            $name
                    );
            } ?>
        </select>
        <select name="m" id="filter-by-date">
            <option<?php selected( $m, 0 ); ?> value="0"><?php _e( 'All dates' ); ?></option>
            <?php foreach ( $months as $arc_row ) {
                    if ( 0 == $arc_row->year )
                            continue;
                    $month = zeroise( $arc_row->month, 2 );
                    $year = $arc_row->year;
                    printf( "<option %s value='%s'>%s</option>\n",
                            selected( $m, $year .'-'. $month, false ),
                            esc_attr( $arc_row->year .'-'. $month ),
                            /* translators: 1: month name, 2: 4-digit year */
                            sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
                    );
            } ?>
        </select>
    <?php }
    
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
        
        global $order,$product,$wpdb;

	$args = array();

	if(isset($_GET['m'])&&$_GET['m']){

            $args['year'] = substr($_GET['m'],0,4);
            $args['month'] = substr($_GET['m'],5,6);

            if(isset($_GET['sts'])&&$_GET['sts']) $args['order_status'] = intval($_GET['sts']);

	}else{
            if(isset($_GET['sts'])&&$_GET['sts']){
                $args['order_status'] = intval($_GET['sts']);
            }elseif(isset($_GET['user'])&&$_GET['user']){
                $args['user_id'] = intval($_GET['user']);
            }else{
                $args['status_not_in'] = 6;
            }
	}
        
        if(isset($_POST['s'])&&$_POST['s']){
            $args['order_id'] = intval($_POST['s']);
        }
        
        $args['per_page'] = $this->per_page;
        $args['offset'] = $this->offset;
        
        $orders = rcl_get_orders($args);
        
        if(!$orders) return false;
        
        $args['count'] = 1;
        
        $this->total_items = rcl_get_orders($args);
        //$this->sum = $all_pr;
        
        foreach($orders as $order_id=>$order){ rcl_setup_orderdata($order);
            $items[] = $order;
        }

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

function rcl_orders_page_options() {
    global $Rcl_Orders;
    $option = 'per_page';
    $args = array(
        'label' => __( 'Orders', 'wp-recall' ),
        'default' => 50,
        'option' => 'rcl_orders_per_page'
    );
    add_screen_option( $option, $args );
    $Rcl_Orders = new Rcl_Orders_History_Table();
}

function rcl_admin_orders_page(){
  global $Rcl_Orders;
  
  $Rcl_Orders->prepare_items();

  echo '</pre><div class="wrap"><h2>'.__('Orders history','wp-recall').'</h2>';

  echo rcl_get_chart_orders($Rcl_Orders->items);
   ?>
    <form method="get"> 
    <input type="hidden" name="page" value="manage-rmag">    
    <?php
    $Rcl_Orders->months_dropdown('rcl_orders'); 
    submit_button( __( 'Filter', 'wp-recall' ), 'button', '', false, array('id' => 'search-submit') ); ?>
    </form>
    <form method="post">
    <input type="hidden" name="page" value="manage-rmag">    
    <?php
    $Rcl_Orders->search_box( __( 'Search', 'wp-recall' ), 'search_id' );
    
    $Rcl_Orders->display(); ?>
  </form>
</div>
<?php }

