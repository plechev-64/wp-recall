<?php

class Rcl_Gateway_Core {

	public $id;
	public $request;
	public $label;
	public $icon;
	public $submit;

	function __construct( $args = false ) {
		global $rmag_options, $user_ID;

		$this->init_properties( $args );

		if ( !$this->submit )
			$this->submit = __( 'Оплатить через' ) . ' "' . $this->label . '"';
	}

	function init_properties( $args ) {
		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[$name] ) )
				$this->$name = $args[$name];
		}
	}

	function options_init() {
		add_filter( 'rcl_commerce_options', (array( $this, 'add_gateway_options' ) ) );
	}

	function get_options() {
		return false;
	}

	function add_gateway_options( $optionsManager ) {

		if ( !$options = $this->get_options() )
			return $optionsManager;

		$optionsManager->add_box( $this->id, array(
			'title' => $this->label
		) )->add_group( $this->id, array(
			'title' => __( 'Настройки' ) . ' ' . $this->label
		) )->add_options( $options );

		return $optionsManager;
	}

	function construct_form( $args ) {

		if ( !isset( $args['submit'] ) )
			$args['submit'] = $this->submit;

		if ( $args['fields'] ) {

			$fields = array();
			foreach ( $args['fields'] as $field_name => $value ) {

				if ( !is_array( $value ) ) {
					$fields[] = array(
						'type'	 => 'hidden',
						'slug'	 => $field_name,
						'value'	 => $value
					);
				} else {
					$fields[] = $value;
				}
			}

			$args['fields'] = $fields;
		}

		return rcl_get_form( $args );
	}

	function result() {
		return false;
	}

	function success() {
		return false;
	}

	function get_payment( $pay_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . RMAG_PREF . "pay_results WHERE payment_id = '%s'", $pay_id ) );
	}

	function insert_payment( $args ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'time_action'	 => current_time( 'mysql' ),
			'pay_system'	 => $this->id
			) );

		$pay_status = $wpdb->insert( RMAG_PREF . 'pay_results', array(
			'payment_id'	 => $args['pay_id'],
			'user_id'		 => $args['user_id'],
			'pay_amount'	 => $args['pay_summ'],
			'time_action'	 => $args['time_action'],
			'pay_system'	 => $args['pay_system'],
			'pay_type'		 => $args['pay_type']
			)
		);

		if ( !$pay_status ) {

			rcl_add_log(
				'insert_pay: ' . __( 'Failed to add user payment', 'wp-recall' ), $args
			);

			exit;
		}

		$args['baggage_data'] = ($args['baggage_data']) ? json_decode( base64_decode( $args['baggage_data'] ) ) : false;

		$object = ( object ) $args;

		do_action( 'rcl_success_pay_system', $object );
	}

}
