<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

add_action('admin_init',array('Rcl_Payments_List_Table','delete_payment'));

class Rcl_Payments_List_Table extends WP_List_Table {
    
    var $per_page = 50;
    var $current_page = 1;
    var $total_items;
    var $offset = 0;
    var $sum_balance;
    var $sum = 0;
    var $query = array(
            'select'    => array(),
            'join'      => array(),
            'where'     => array(),
            'group'     => '',
            'orderby'   => 'payments.ID',
            'relation'   => 'AND',
            'order'   => 'DESC',
            'include'   => '',
            'exclude'   => ''
        );
	
    function __construct(){
        global $status, $page;
        parent::__construct( array(
            'singular'  => __( 'payment', 'wp-recall' ),
            'plural'    => __( 'payments', 'wp-recall' ),
            'ajax'      => false
        ) );
        
        $this->per_page = $this->get_items_per_page('rcl_payments_per_page', 50);
        $this->current_page = $this->get_pagenum();
        $this->offset = ($this->current_page-1)*$this->per_page;

        add_action( 'admin_head', array( &$this, 'admin_header' ) );            
    }
	
    function admin_header() {
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'manage-wpm-cashe' != $page )
        return;
        echo '<style type="text/css">';
        echo '.wp-list-table .column-payment_number { width: 5%; }';
        echo '.wp-list-table .column-payment_user { width: 30%; }';
        echo '.wp-list-table .column-payment_id { width: 15%; }';
        echo '.wp-list-table .column-payment_sum { width: 10%;}';
        echo '.wp-list-table .column-payment_date { width: 15%;}';
        echo '.wp-list-table .column-pay_system { width: 15%;}';
        echo '.wp-list-table .column-pay_type { width: 10%;}';
        echo '</style>';
    }

    function no_items() {
        _e( 'No payments found.', 'wp-recall' );
    }

    function column_default( $item, $column_name ) {

        switch( $column_name ) { 
            case 'payment_number':
                return $item[ 'ID' ];
            case 'payment_user':
                return $item[ 'user_id' ].': '.get_the_author_meta('user_login',$item[ 'user_id' ]);
            case 'payment_id':
                return $item[ 'payment_id' ];
            case 'payment_sum':
                return $item[ 'pay_amount' ];
            case 'payment_date':
                return $item[ 'time_action' ];
            case 'pay_system':
                return $item[ 'pay_system' ];
            case 'pay_type':
                return $item[ 'pay_type' ];
            default:
                return print_r( $item, true ) ;
        }
    }

    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'payment_number' => '№',
            'payment_user' => __( 'Users', 'wp-recall' ),
            'payment_id'    => __( 'Payments ID', 'wp-recall' ),
            'payment_sum'    => __( 'Sum', 'wp-recall' ),
            'payment_date'      => __( 'Date', 'wp-recall' ),
            'pay_system'      => __( 'Payment system', 'wp-recall' ),
            'pay_type'      => __( 'Payment type', 'wp-recall' )
        );
         return $columns;
    }

    function column_payment_user($item){
      $actions = array(				
            'delete'    => sprintf('<a href="?page=%s&action=%s&payment=%s">'.__( 'Delete payment', 'wp-recall' ).'</a>',$_REQUEST['page'],'delete',$item['ID']),
            'all-payments'    => sprintf('<a href="?page=%s&action=%s&user_id=%s">'.__( 'All user payments', 'wp-recall' ).'</a>',$_REQUEST['page'],'all-payments',$item['user_id']),
        );
      return sprintf('%1$s %2$s', $item[ 'user_id' ].': '.get_the_author_meta('user_login',$item[ 'user_id' ]), $this->row_actions($actions) );
    }

    function get_bulk_actions() {
      $actions = array(
            'delete'    => __( 'Delete', 'wp-recall' ),
      );
      return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="payments[]" value="%s" />', $item['ID']
        );    
    }
    
    function months_dropdown( $post_type ) {
        global $wpdb,$wp_locale;

        $months = $wpdb->get_results("
                SELECT DISTINCT YEAR( time_action ) AS year, MONTH( time_action ) AS month
                FROM ".RMAG_PREF ."pay_results
                ORDER BY time_action DESC
        ");

        $months = apply_filters( 'months_dropdown_results', $months, $post_type );
        
        $month_count = count( $months );
        if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
                return;
        
        $m = isset( $_GET['m'] ) ? $_GET['m'] : 0; ?>
        <label for="filter-by-date" class="screen-reader-text"><?php _e( 'Filter by date' ); ?></label>
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
    
    static function delete_payment(){
        global $wpdb;
        
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'manage-wpm-cashe' != $page ) return;
        
        if($_REQUEST['action']=='delete'){
            
            if(isset($_REQUEST['payment'])){
                $payment = $_REQUEST['payment'];
                $wpdb->query($wpdb->prepare("DELETE FROM ".RMAG_PREF ."pay_results WHERE ID = '%d'",$payment));
            }
            
            if(isset($_REQUEST['payments'])){  
                $payments = $_REQUEST['payments'];
                $cnt = count($payments);
                for($a=0;$a<$cnt;$a++){
                    $id = intval($payments[$a]);
                    if($id) $wpdb->query($wpdb->prepare("DELETE FROM ".RMAG_PREF ."pay_results WHERE ID = '%d'",$id));
                }
            }
	}
    }
    
    function count_items(){
        global $wpdb;
        $query_string = $this->query_string('count');
        return $wpdb->get_var( $query_string );
    }
    
    function get_sum(){
        global $wpdb;
        $query_string = $this->query_string('sum');
        return $wpdb->get_var( $query_string );
    }
    
    function get_items(){
        global $wpdb;
        $query_string = $this->query_string();
        return $wpdb->get_results( $query_string,ARRAY_A );
    }
    
    function query_string($count=false){
        global $wpdb,$rcl_options;

        if($count=='count'){

            $this->query['select'] = array(
                "COUNT(payments.ID)"
            );

        }else if($count=='sum'){

            $this->query['select'] = array(
                "SUM(payments.pay_amount)"
            );

        }else{

            $this->query['select'] = array(
                "payments.*"
            );

        }

        if($this->include) $this->query['where'][] = "payments.ID IN ($this->include)";
        if($this->exclude) $this->query['where'][] = "payments.ID NOT IN ($this->exclude)";

        $query_string = "SELECT "
            . implode(", ",$this->query['select'])." "
            . "FROM ".RMAG_PREF."pay_results AS payments "
            . implode(" ",$this->query['join'])." ";

        if($this->query['where']) $query_string .= "WHERE ".implode(' '.$this->query['relation'].' ',$this->query['where'])." ";
        if($this->query['group']) $query_string .= "GROUP BY ".$this->query['group']." ";

        if(!$count){
            if(!$this->query['orderby']) $this->query['orderby'] = "payments.".$this->query['orderby'];
            $query_string .= "ORDER BY ".$this->query['orderby']." ".$this->query['order']." ";
            $query_string .= "LIMIT $this->offset,$this->per_page";
        }

        return $query_string;

    }
    
    function get_sum_balance(){
        global $wpdb;
        return $wpdb->get_var("SELECT SUM(CAST(user_balance AS DECIMAL)) FROM ".RMAG_PREF."users_balance WHERE user_balance!='0'");
    }
    
    function get_data(){

        if(isset($_POST['s'])){

            $this->query['where'][] = "(user_id = '".$_POST['s']."' OR payment_id = '".$_POST['s']."')";
            
            if(isset($_GET['m'])&&$_GET['m']){ 
            
                $this->query['where'][] = "time_action LIKE '".$_GET['m']."-%'";

            }
            
        }else if(isset($_GET['m'])&&$_GET['m']){ 
            
            $this->query['where'][] = "time_action LIKE '".$_GET['m']."-%'";
            
        }else if($_GET['user_id']){
            
            $this->query['where'][] = "user_id = '".$_GET['user_id']."'";
            
        }
        
        $this->total_items = $this->count_items();
        $this->sum = $this->get_sum();
        $this->sum_balance = $this->get_sum_balance();
        $items = $this->get_items();
        
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

add_action('admin_menu', 'rcl_statistic_user_pay_page',25);
function rcl_statistic_user_pay_page(){
    $prim = 'manage-rmag';
    if(!function_exists('wpmagazin_options_panel')){
            $prim = 'manage-wpm-options';
            add_menu_page('Recall Commerce', 'Recall Commerce', 'manage_options', $prim, 'rmag_global_options');
            add_submenu_page( $prim, __('Payment systems','wp-recall'), __('Payment systems','wp-recall'), 'manage_options', $prim, 'rmag_global_options');
    }

    $hook = add_submenu_page( $prim, __('Payments','wp-recall'), __('Payments','wp-recall'), 'manage_options', 'manage-wpm-cashe', 'rcl_admin_statistic_cashe');
    add_action( "load-$hook", 'rcl_payments_page_options' );
}

function rcl_payments_page_options() {
    global $Rcl_Payments;
    $option = 'per_page';
    $args = array(
        'label' => __( 'Payments', 'wp-recall' ),
        'default' => 50,
        'option' => 'rcl_payments_per_page'
    );
    add_screen_option( $option, $args );
    $Rcl_Payments = new Rcl_Payments_List_Table();
}

function rcl_admin_statistic_cashe(){
  global $Rcl_Payments;
  
  $Rcl_Payments->prepare_items();
  $sr = ($Rcl_Payments->sum)? floor($Rcl_Payments->sum/$Rcl_Payments->total_items): 0;
  
  echo '</pre><div class="wrap"><h2>'.__('Payment history','wp-recall').'</h2>';

  echo '<p>'.__('All payments','wp-recall').': '.$Rcl_Payments->total_items.' '.__('for the amount of','wp-recall').' '.$Rcl_Payments->sum.' '.rcl_get_primary_currency(1).' ('.__('Average check','wp-recall').': '.$sr.' '.rcl_get_primary_currency(1).')</p>';
  echo '<p>'.__('Total in the system','wp-recall').': '.$Rcl_Payments->sum_balance.' '.rcl_get_primary_currency(1).'</p>';
  //echo '<p>Средняя выручка за сутки: '.$day_pay.' '.rcl_get_primary_currency(1).'</p>';
  echo rcl_get_chart_payments($Rcl_Payments->items);
   ?>
    <form method="get"> 
    <input type="hidden" name="page" value="manage-wpm-cashe">    
    <?php
    $Rcl_Payments->months_dropdown('rcl_payments'); 
    submit_button( __( 'Filter', 'wp-recall' ), 'button', '', false, array('id' => 'search-submit') ); ?>
    </form>
    <form method="post">
    <input type="hidden" name="page" value="manage-wpm-cashe">    
    <?php
    $Rcl_Payments->search_box( __( 'Search', 'wp-recall' ), 'search_id' );
    
    $Rcl_Payments->display(); ?>
  </form>
</div>
<?php }

