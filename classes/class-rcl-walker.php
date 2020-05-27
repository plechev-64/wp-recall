<?php

class Rcl_Walker {

	public $items = array();

	function __construct( $items ) {

		$this->items = $items;
	}

	function get_item( $name, $value ) {

		if ( ! $this->items )
			return false;

		foreach ( $this->items as $item ) {

			if ( isset( $item->$name ) && $item->$name == $value )
				return $item;
		}

		return false;
	}

	function get_item_value( $byname, $nameValue, $getName ) {

		if ( ! $this->items )
			return false;

		if ( ! $item = $this->get_item( $byname, $nameValue ) )
			return false;

		return isset( $item->$getName ) ? $item->$getName : false;
	}

	function get_items( $args = false ) {

		if ( ! $this->items )
			return false;

		if ( ! $args )
			return $this->items;

		$items = array();
		foreach ( $this->items as $item ) {

			$correct = true;
			foreach ( $args as $key => $value ) {
				if ( is_array( $value ) ) {
					if ( ! in_array( $item->$key, $value ) ) {
						$correct = false;
						break;
					}
				} else {
					if ( $item->$key != $value ) {
						$correct = false;
						break;
					}
				}
			}

			if ( $correct )
				$items[] = $item;
		}

		return $items;
	}

	function get_field_values( $field_name ) {

		if ( ! $this->items )
			return false;

		$fields = array();
		foreach ( $this->items as $item ) {
			if ( ! isset( $item->$field_name ) )
				continue;
			$fields[] = $item->$field_name;
		}

		return $fields;
	}

	function get_index_values( $index_field, $value_field ) {

		if ( ! $this->items )
			return false;

		$pack = array();
		foreach ( $this->items as $item ) {
			if ( ! isset( $item->$index_field ) || ! isset( $item->$value_field ) )
				continue;
			$pack[$item->$index_field] = $item->$value_field;
		}

		return $pack;
	}

	function is_set( $name, $value ) {

		if ( ! $this->items )
			return false;

		foreach ( $this->items as $item ) {

			if ( isset( $item->$name ) && $item->$name == $value )
				return true;
		}

		return false;
	}

	function count( $args = false ) {
		return count( $this->get_items( $args ) );
	}

}
