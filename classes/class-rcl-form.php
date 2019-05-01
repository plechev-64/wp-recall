<?php

class Rcl_Form extends Rcl_Fields {

	public $class		 = '';
	public $action		 = '';
	public $method		 = 'post';
	public $submit;
	public $submit_args;
	public $nonce_name	 = '';
	public $onclick;
	public $values		 = array();

	function __construct( $args = false ) {

		$this->init_properties( $args );

		$this->fields = array();

		parent::__construct( $args['fields'] );
	}

	function init_properties( $args ) {

		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[$name] ) )
				$this->$name = $args[$name];
		}
	}

	function get_form( $args = false ) {

		$content = '<div class="rcl-form preloader-parent' . ($this->class ? ' ' . $this->class : '') . '">';

		$content .= '<form method="' . $this->method . '" action="' . $this->action . '">';

		foreach ( $this->fields as $field_id => $field ) {

			if ( ! isset( $field->value ) )
				$field->value = (isset( $this->values[$field->slug] )) ? $this->values[$field->slug] : null;

			if ( $field->type == 'hidden' ) {
				$content .= $field->get_field_input();
				continue;
			}

			$content .= '<div id="field-' . $field->slug . '" class="form-field field-type-' . $field->slug . ' rcl-option">';

			if ( $field->title ) {
				$content .= '<span class="field-title">';
				$content .= $field->get_title();
				$content .= '</span>';
			}

			$content .= $field->get_field_input();

			$content .= '</div>';
		}

		$content .= '<div class="submit-box">';

		if ( $this->onclick ) {
			$content .= rcl_get_button( wp_parse_args( $this->submit_args, array(
				'label'		 => $this->submit,
				'icon'		 => 'fa-check-circle',
				'onclick'	 => $this->onclick
				) ) );
		} else {
			$content .= rcl_get_button( wp_parse_args( $this->submit_args, array(
				'label'	 => $this->submit,
				'icon'	 => 'fa-check-circle',
				'submit' => true
				) ) );
		}

		$content .= '</div>';

		if ( $this->nonce_name )
			$content .= wp_nonce_field( $this->nonce_name, '_wpnonce', true, false );

		$content .= '</form>';

		$content .= '</div>';

		return $content;
	}

}
