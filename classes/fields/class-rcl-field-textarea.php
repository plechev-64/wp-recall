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
class Rcl_Field_TextArea extends Rcl_Field_Abstract {

	public $required;
	public $placeholder;
	public $maxlength;

	function __construct( $args ) {
		parent::__construct( $args );
	}

	function get_options() {

		return array(
			array(
				'slug'			 => 'icon',
				'default'		 => 'fa-comment',
				'placeholder'	 => 'fa-comment',
				'class'			 => 'rcl-iconpicker',
				'type'			 => 'text',
				'title'			 => __( 'Icon class of  font-awesome', 'wp-recall' ),
				'notice'		 => __( 'Source', 'wp-recall' ) . ' <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">http://fontawesome.io/</a>'
			),
			array(
				'slug'		 => 'placeholder',
				'default'	 => $this->placeholder,
				'type'		 => 'text',
				'title'		 => __( 'Placeholder', 'wp-recall' )
			),
			array(
				'slug'		 => 'maxlength',
				'default'	 => $this->maxlength,
				'type'		 => 'number',
				'title'		 => __( 'Maxlength', 'wp-recall' ),
				'notice'	 => __( 'maximum number of symbols per field', 'wp-recall' )
			)
		);
	}

	function get_input() {
		return '<textarea name="' . $this->input_name . '" ' . $this->get_maxlength() . ' ' . $this->get_required() . ' ' . $this->get_placeholder() . ' ' . $this->get_class() . ' id="' . $this->input_id . '" rows="5" cols="50">' . $this->value . '</textarea>';
	}

	function get_value() {

		if ( !$this->value )
			return false;

		return nl2br( $this->value );
	}

}
