<?php

/** deprecated * */
//удалить после перехода всех платежных систем

global $rclOldGatewaysData;

class Rcl_Payment extends Rcl_Gateway_Core {
	function add_payment( $type, $data ) {
		global $rclOldGatewaysData;

		$rclOldGatewaysData[$type] = array(
			'request'	 => $data['request'],
			'label'		 => $data['name'],
			'submit'	 => __( 'Оплатить через' ) . ' ' . $data['name'],
			'icon'		 => $data['image']
		);

		rcl_gateway_register( $type, get_class( $this ) );
	}

	function get_pay( $data ) {
		return parent::get_payment( $data->pay_id );
	}

	function insert_pay( $data ) {

		$data = ( array ) $data;

		return parent::insert_payment( $data );
	}

	function get_form( $data ) {

		return $this->pay_form( $data );
	}

	function form( $fields, $data, $formaction ) {

		return $this->construct_form( array(
				'action' => $formaction,
				'method' => 'post',
				'fields' => $fields
			) );
	}

}
