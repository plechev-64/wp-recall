<?php

class Rcl_Wizard_Step {

	private $title;
	private $description;
	private $options = array();

	function __construct( $args ) {
		$this->init_properties( $args );
	}

	function init_properties( $args ) {

		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[$name] ) ) {
				$this->$name = is_bool( $args[$name] ) ? ( boolean ) $args[$name] : $args[$name];
			}
		}
	}

	function get_content() {

		$content = '<div id="wizard-step-content" class="step-content">';

		$content .= '<form>';

		if ( $this->title ) {
			$content .= '<div class="step-title">';
			$content .= $this->title;
			$content .= '</div>';
		}

		if ( $this->description ) {
			$content .= '<div class="step-description">';
			$content .= $this->description;
			$content .= '</div>';
		}

		if ( $this->options ) {

			$form = new Rcl_Form( array( 'fields' => $this->options ) );

			$content .= '<div class="step-options">';
			$content .= $form->get_fields_list();
			$content .= '</div>';
		}

		$content .= '</form>';

		$content .= '</div>';

		return $content;
	}

}
