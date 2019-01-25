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
class Rcl_Field_Checkbox extends Rcl_Field_Abstract {

	public $required;
	public $values;
	public $value_in_key;

	function __construct( $args ) {
		parent::__construct( $args );
	}

	function get_options() {

		return array(
			array(
				'slug'			 => 'icon',
				'default'		 => 'fa-check-square',
				'placeholder'	 => 'fa-check-square',
				'class'			 => 'rcl-iconpicker',
				'type'			 => 'text',
				'title'			 => __( 'Icon class of  font-awesome', 'wp-recall' ),
				'notice'		 => __( 'Source', 'wp-recall' ) . ' <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">http://fontawesome.io/</a>'
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

	function get_value() {

		if ( !$this->value )
			return false;

		return implode( ', ', $this->value );
	}

	function get_input() {

		if ( !$this->values )
			return false;

		$currentValues = (is_array( $this->value )) ? $this->value : array();

		$this->class = ($this->required) ? 'required-checkbox' : '';

		$content = '';

		foreach ( $this->values as $k => $value ) {

			if ( $this->value_in_key )
				$k = $value;

			$checked = checked( in_array( $k, $currentValues ), true, false );

			$content .= '<span class="rcl-checkbox-box">';
			$content .= '<input ' . $this->get_required() . ' ' . $checked . ' id="' . $this->input_id . '_' . $k . $this->rand . '" type="checkbox" ' . $this->get_class() . ' name="' . $this->input_name . '[]" value="' . trim( $k ) . '"> ';
			$content .= '<label class="block-label" for="' . $this->input_id . '_' . $k . $this->rand . '">';
			$content .= $value;
			$content .= '</label>';
			$content .= '</span>';
		}

		return $content;
	}

}
