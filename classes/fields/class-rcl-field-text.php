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
class Rcl_Field_Text extends Rcl_Field_Abstract {

	public $required;
	public $placeholder;
	public $maxlength;
	public $pattern;
	public $class;

	function __construct( $args ) {
		parent::__construct( $args );
	}

	function get_options() {

		return array(
			array(
				'slug'			 => 'icon',
				'default'		 => 'fa-file-text-o',
				'placeholder'	 => 'fa-file-text-o',
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
			),
			array(
				'slug'		 => 'pattern',
				'default'	 => $this->pattern,
				'type'		 => 'text',
				'title'		 => __( 'Pattern', 'wp-recall' )
			)
		);
	}

	function get_input() {
		return '<input type="' . $this->type . '" ' . $this->get_pattern() . ' ' . $this->get_maxlength() . ' ' . $this->get_required() . ' ' . $this->get_placeholder() . ' ' . $this->get_class() . ' name="' . $this->input_name . '" id="' . $this->input_id . '" value=\'' . $this->value . '\'/>';
	}

	function get_value() {

		if ( !$this->value )
			return false;

		if ( $this->type == 'email' )
			return '<a rel="nofollow" target="_blank" href="mailto:' . $this->value . '">' . $this->value . '</a>';
		if ( $this->type == 'url' )
			return '<a rel="nofollow" target="_blank" href="' . $this->value . '">' . $this->value . '</a>';

		return $this->value;
	}

}
