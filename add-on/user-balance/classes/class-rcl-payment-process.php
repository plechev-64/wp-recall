<?php

class Rcl_Payment_Process extends Rcl_Payment_Core {

	public $post_id	 = 0;
	public $ids		 = array();

	function __construct() {
		global $post, $rmag_options;

		parent::__construct();

		$this->post_id = $post->ID;

		$this->ids = $rmag_options['connect_sale'];

		if ( !is_array( $this->ids ) )
			$this->ids = array_map( 'trim', explode( ',', $this->ids ) );
	}

	function get_id_is_payment() {

		if ( !Rcl_Gateways()->gateways )
			return false;

		foreach ( Rcl_Gateways()->gateways as $id => $className ) {

			if ( !in_array( $id, $this->ids ) )
				continue;

			if ( isset( $_REQUEST[Rcl_Gateways()->gateway( $id )->request] ) )
				return $id;
		}

		return false;
	}

	function payment_process( $gateway_id ) {

		switch ( $this->post_id ) {
			case $this->page_result: Rcl_Gateways()->gateway( $gateway_id )->result( $this );
				break;
			case $this->page_success: Rcl_Gateways()->gateway( $gateway_id )->success( $this );
				break;
		}
	}

}
