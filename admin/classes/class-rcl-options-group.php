<?php

class Rcl_Options_Group {

	public $group_id;
	public $title;
	public $options;
	public $extend;
	public $option_values = array();

	function __construct( $group_id, $args = false, $option_name ) {

		$this->group_id = $group_id;

		$this->option_name = $option_name;

		$this->option_values = get_site_option( $this->option_name );

		if ( $args )
			$this->init_properties( $args );
	}

	function init_properties( $args ) {

		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[$name] ) )
				$this->$name = $args[$name];
		}
	}

	function get_value( $option, $default = false, $group = false ) {

		if ( $group ) {
			if ( isset( $this->option_values[$group][$option] ) ) {
				if ( $this->option_values[$group][$option] || is_numeric( $this->option_values[$group][$option] ) ) {
					return $this->option_values[$group][$option];
				}
			}
		} else {
			if ( isset( $this->option_values[$option] ) ) {
				if ( $this->option_values[$option] || is_numeric( $this->option_values[$option] ) ) {
					return $this->option_values[$option];
				}
			}
		}

		return $default;
	}

	function add_options( $options ) {

		$optionValues = get_site_option( $this->option_name );

		foreach ( $options as $option ) {

			$this->add_option( $option );
		}
	}

	function add_option( $option ) {

		$option_id	 = $option['slug'];
		$default	 = isset( $option['default'] ) ? $option['default'] : false;
		$group		 = isset( $option['group'] ) && $option['group'] ? $option['group'] : false;

		if ( ! isset( $option['value'] ) )
			$option['value'] = $this->get_value( $option_id, $default, $group );

		if ( $group ) {
			$option['input_name'] = $this->option_name . '[' . $option['group'] . '][' . $option_id . ']';
		} else {
			$option['input_name'] = $this->option_name . '[' . $option_id . ']';
		}

		$this->options[$option_id] = Rcl_Option::setup_option( $option );

		if ( isset( $option['childrens'] ) ) {
			foreach ( $option['childrens'] as $parentValue => $childFields ) {

				if ( ! is_array( $childFields ) )
					continue;

				foreach ( $childFields as $childField ) {

					$childField['parent'] = array(
						'id'	 => $option_id,
						'value'	 => $parentValue
					);

					$this->add_option( $childField );
				}
			}
		}
	}

	function get_content() {

		if ( ! $this->options )
			return false;

		$content = '<div id="' . $this->group_id . '-options-group" class="options-group ' . ($this->extend ? 'extend-options' : '') . '" data-group="' . $this->group_id . '">';

		if ( $this->title )
			$content .= '<span class="group-title">' . $this->title . '</span>';

		foreach ( $this->options as $option ) {

			$args = array( 'classes' => array( 'rcl-option' ) );

			if ( $option->extend ) {
				$args['classes'][] = 'extend-options';
			}

			$content .= $option->get_field_html( $args );
		}

		$content .= '</div>';

		return $content;
	}

}
