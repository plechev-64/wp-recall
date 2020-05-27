<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class-usp-ajax
 *
 * @author Андрей
 */
class Rcl_Ajax {

	public $name;
	public $nopriv		 = false;
	public $rest		 = false;
	public $rest_space	 = 'rcl';
	public $rest_route	 = '';

	function __construct( $args = false ) {
		$this->init_properties( $args );
	}

	function init_properties( $args ) {

		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[$name] ) )
				$this->$name = $args[$name];
		}
	}

	function init() {

		if ( $this->rest ) {
			add_action( 'rest_api_init', array( $this, 'register_route' ) );
		} else {

			if ( is_array( $this->name ) ) {
				$name = $this->name[1];
			} else {
				$name = $this->name;
			}

			add_action( 'wp_ajax_' . $name, $this->name );

			if ( $this->nopriv )
				add_action( 'wp_ajax_nopriv_' . $name, $this->name );
		}
	}

	function register_route() {

		if ( ! $this->rest_route )
			$this->rest_route = $this->name;

		register_rest_route( $this->rest_space, '/' . $this->rest_route . '/', array(
			'methods'	 => 'POST',
			'callback'	 => $this->name
		) );
	}

	function verify() {

		if ( isset( $_POST['ajax_nonce'] ) ) {
			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
				return false;
			if ( ! wp_verify_nonce( $_POST['ajax_nonce'], 'wp_rest' ) ) {
				wp_send_json( array( 'error' => __( 'Signature verification failed', 'wp-recall' ) . '!' ) );
			}
		} else {
			check_ajax_referer( 'wp_rest' );
		}
	}

}
