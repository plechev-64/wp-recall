<?php

class Rcl_Gateways {

	public $gateways = array();

	function __construct() {

	}

	function add_gateway( $gateway_id, $gatewayClassName ) {
		$this->gateways[$gateway_id] = $gatewayClassName;
	}

	function gateway( $gateway_id ) {


		if ( ! isset( $this->gateways[$gateway_id] ) )
			return false;

		$className = $this->gateways[$gateway_id];

		$gateway = new $className();

		global $rclOldGatewaysData;
		if ( ! $gateway->label && isset( $rclOldGatewaysData[$gateway_id] ) ) {
			$gateway->init_properties( $rclOldGatewaysData[$gateway_id] );
		}

		$gateway->id = $gateway_id;

		return $gateway;
	}

}
