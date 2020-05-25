<?php

class Rcl_Option extends Rcl_Field {
	static function setup_option( $args ) {

		$object = parent::setup( $args );

		$object->extend = (isset( $args['extend'] )) ? $args['extend'] : false;

		return $object;
	}

}
