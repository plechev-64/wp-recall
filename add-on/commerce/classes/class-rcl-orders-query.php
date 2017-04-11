<?php

class Rcl_Orders_Query extends Rcl_Query {
    
    function __construct() { 

        $table = array(
            'name' => RCL_PREF ."orders",
            'as' => 'rcl_orders',
            'cols' => array(
                'order_id',
                'user_id',
                'order_price',
                'products_amount',
                'order_details',
                'order_date',
                'order_status'
            )
        );
        
        parent::__construct($table);
        
    }

}

class Rcl_Order_Items_Query extends Rcl_Query {
    
    function __construct() { 

        $table = array(
            'name' => RCL_PREF ."order_items",
            'as' => 'rcl_order_items',
            'cols' => array(
                'order_id',
                'product_id',
                'product_price',
                'product_amount',
                'variations'
            )
        );
        
        parent::__construct($table);
        
    }

}

