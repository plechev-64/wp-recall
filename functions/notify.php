<?php

class Rcl_Notify {
	function __construct( $text, $type ) {
		$this->type	 = $type;
		$this->text	 = $text;
		add_filter( 'notify_lk', array( $this, 'add_notify' ) );
	}

	function add_notify( $text ) {
		return rcl_get_notice( array(
			'type'	 => $this->type,
			'text'	 => $this->text
			) );
		;
	}

}
