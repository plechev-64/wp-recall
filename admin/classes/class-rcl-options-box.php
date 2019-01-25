<?php

class Rcl_Options_Box {

	public $box_id;
	public $title;
	public $icon = 'fa-cog';
	public $groups;
	public $option_name;

	function __construct( $box_id, $args, $option_name ) {

		$this->box_id = $box_id;

		$this->option_name = $option_name;

		$this->init_properties( $args );
	}

	function init_properties( $args ) {

		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[$name] ) )
				$this->$name = $args[$name];
		}
	}

	function add_group( $group_id, $args = false ) {
		$this->groups[$group_id] = new Rcl_Options_Group( $group_id, $args, $this->option_name );
		return $this->group( $group_id );
	}

	function group( $group_id ) {
		return $this->groups[$group_id];
	}

	function get_content() {

		$content = '<div id="' . $this->box_id . '-options-box" class="options-box" data-box="' . $this->box_id . '">';

		foreach ( $this->groups as $group ) {

			$content .= $group->get_content();
		}

		$content .= '</div>';

		return $content;
	}

}
