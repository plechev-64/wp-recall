<?php

class Rcl_Orders_Query extends Rcl_Query {

	public $serialize = array( 'order_details' );

	function __construct( $as = false ) {

		$table = array(
			'name'	 => RCL_PREF . "orders",
			'as'	 => $as ? $as : 'rcl_orders',
			'cols'	 => array(
				'order_id',
				'user_id',
				'order_price',
				'products_amount',
				'order_details',
				'order_date',
				'order_status'
			)
		);

		parent::__construct( $table );
	}

}

class Rcl_Order_Items_Query extends Rcl_Query {

	public $serialize = array( 'variations' );

	function __construct( $as = false ) {

		$table = array(
			'name'	 => RCL_PREF . "order_items",
			'as'	 => $as ? $as : 'rcl_order_items',
			'cols'	 => array(
				'order_id',
				'product_id',
				'product_price',
				'product_amount',
				'variations'
			)
		);

		parent::__construct( $table );
	}

}
