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
class Rcl_Field_Select extends Rcl_Field_Abstract {

	public $required;
	public $empty_first;
	public $values;
	public $childrens;
	public $key_in_data;
	public $value_in_key;

	function __construct( $args ) {

		if ( isset( $args['empty-first'] ) )
			$args['empty_first'] = $args['empty-first'];

		parent::__construct( $args );
	}

	function get_options() {

		return array(
			array(
				'slug'			 => 'icon',
				'default'		 => 'fa-list',
				'placeholder'	 => 'fa-list',
				'class'			 => 'rcl-iconpicker',
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

		$content = '<select ' . $this->get_required() . ' name="' . $this->input_name . '" id="' . $this->input_id . '" ' . $this->get_class() . '>';

		if ( $this->empty_first )
			$content .= '<option value="">' . $this->empty_first . '</option>';

		if ( $this->values ) {
			foreach ( $this->values as $k => $value ) {

				$data = ($this->key_in_data) ? 'data-key="' . $k . '"' : '';

				if ( $this->value_in_key )
					$k = $value;

				$content .= '<option ' . selected( $this->value, $k, false ) . ' ' . $data . ' value="' . trim( $k ) . '">' . $value . '</option>';
			}
		}

		$content .= '</select>';

		return $content;
	}

}
