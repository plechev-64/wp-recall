<?php

class Rcl_Payment_Core {

	public $page_result;
	public $page_success;
	public $page_fail;
	public $page_successfully;

	function __construct( $args = array() ) {
		global $rmag_options;

		if ( $args ) {
			$this->init_properties( $args );
		}

		if ( !$this->page_result && isset( $rmag_options['page_result_pay'] ) )
			$this->page_result = $rmag_options['page_result_pay'];

		if ( !$this->page_success && isset( $rmag_options['page_success_pay'] ) )
			$this->page_success = $rmag_options['page_success_pay'];

		if ( !$this->page_fail && isset( $rmag_options['page_fail_pay'] ) )
			$this->page_fail = $rmag_options['page_fail_pay'];

		if ( !$this->page_successfully && isset( $rmag_options['page_successfully_pay'] ) )
			$this->page_successfully = $rmag_options['page_successfully_pay'];
	}

	function init_properties( $args ) {
		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[$name] ) )
				$this->$name = $args[$name];
		}
	}

}
