<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class-rcl-custom-field-text
 *
 * @author Андрей
 */
class Rcl_Field_Radio extends Rcl_Field_Abstract {

	public $required;
	public $values;
	public $display = 'inline';
	public $empty_first;
	public $empty_value;
	public $childrens;
	public $value_in_key;

	function __construct( $args ) {

		if ( isset( $args['empty-first'] ) )
			$args['empty_first'] = $args['empty-first'];

		if ( isset( $args['empty-value'] ) )
			$args['empty_value'] = $args['empty-value'];

		parent::__construct( $args );
	}

	function get_options() {

		return array(
			array(
				'slug'			 => 'icon',
				'default'		 => 'fa-dot-circle-o',
				'placeholder'	 => 'fa-dot-circle-o',
				'type'			 => 'text',
				'title'			 => __( 'Icon class of  font-awesome', 'wp-recall' ),
				'notice'		 => __( 'Source', 'wp-recall' ) . ' <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">http://fontawesome.io/</a>'
			),
			array(
				'slug'		 => 'empty_first',
				'default'	 => $this->empty_first,
				'type'		 => 'text',
				'title'		 => __( 'First value', 'wp-recall' ),
				'notice'	 => __( 'Name of the first blank value, for example: "Not selected"', 'wp-recall' )
			),
			array(
				'slug'		 => 'values',
				'default'	 => $this->values,
				'type'		 => 'dynamic',
				'title'		 => __( 'Specify options', 'wp-recall' ),
				'notice'	 => __( 'specify each option in a separate field', 'wp-recall' )
			)
		);
	}

	function get_input() {

		if ( ! $this->values )
			return false;

		$content = '';

		if ( $this->empty_first ) {
			$content .= '<span class="rcl-radio-box">';
			$content .= '<input type="radio" ' . $this->get_required() . ' ' . checked( $this->value, '', false ) . ' id="' . $this->input_id . '_' . $this->rand . '" name="' . $this->input_name . '" value="' . $this->empty_value . '"> ';
			$content .= '<label class="block-label" for="' . $this->input_id . '_' . $this->rand . '">' . $this->empty_first . '</label>';
			$content .= '</span>';
		}

		$a = 0;

		if ( ! $this->empty_first && ! $this->value )
			$this->value = ($this->value_in_key) ? $this->values[0] : 0;

		foreach ( $this->values as $k => $value ) {

			if ( $this->value_in_key )
				$k = $value;

			$k = trim( $k );

			$content .= '<span class="rcl-radio-box checkbox-display-' . $this->display . '" data-value="' . $k . '">';
			$content .= '<input type="radio" ' . $this->get_required() . ' ' . checked( $this->value, $k, false ) . ' ' . $this->get_class() . ' id="' . $this->input_id . '_' . $k . $this->rand . '" name="' . $this->input_name . '" value="' . $k . '"> ';
			$content .= '<label class="block-label" for="' . $this->input_id . '_' . $k . $this->rand . '">' . $value . '</label>';
			$content .= '</span>';

			$a ++;
		}

		return $content;
	}

}
