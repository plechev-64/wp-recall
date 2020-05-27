<?php

class Rcl_Notice {

	public $type		 = 'info'; //simple,info,error
	public $title		 = '';
	public $text		 = '';
	public $icon		 = true;
	public $class		 = '';
	public $border		 = true;
	public $cookie		 = '';
	public $cookie_time	 = 30;

	function __construct( $args ) {

		$this->init_properties( $args );

		$this->setup_icon();
		$this->setup_class();
	}

	function init_properties( $args ) {

		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[$name] ) )
				$this->$name = $args[$name];
		}
	}

	function setup_class() {

		$classes = array( 'rcl-notice', 'rcl-notice__type-' . $this->type );

		if ( $this->class )
			$classes[] = $this->class;

		if ( $this->border )
			$classes[] = 'rcl-notice__border';

		$this->class = implode( ' ', $classes );
	}

	function setup_icon() {

		if ( ! $this->icon )
			return;

		if ( ! is_string( $this->icon ) ) {
			switch ( $this->type ) {
				case 'success':
					$this->icon	 = 'fa-check-circle';
					break;
				case 'warning':
					$this->icon	 = 'fa-exclamation-circle';
					break;
				case 'info':
					$this->icon	 = 'fa-info-circle';
					break;
				case 'error':
					$this->icon	 = 'fa-exclamation-triangle';
					break;
			}
		}
	}

	function get_notice() {

		if ( ! empty( $this->cookie ) && isset( $_COOKIE[$this->cookie] ) )
			return;

		$content = '<div class="' . $this->class . '">';

		if ( ! empty( $this->icon ) )
			$content .= '<i class="rcli ' . $this->icon . '" aria-hidden="true"></i>';

		if ( ! empty( $this->cookie ) ) {
			$content .= '<div class="rcl-notice__close" data-notice_id="' . $this->cookie . '" data-notice_time="' . $this->cookie_time . '" onclick="rcl_close_notice(this);return false;"></div>';
		}

		if ( ! empty( $this->title ) )
			$content .= '<div class="rcl-notice__title">' . $this->title . '</div>';

		$content .= '<div class="rcl-notice__text">' . $this->text . '</div>';
		$content .= '</div>';

		return $content;
	}

}
