<?php

class Rcl_Users_Balance extends Rcl_Query {
	function __construct() {

		$table = array(
			'name'	 => RMAG_PREF . "users_balance",
			'as'	 => 'rcl_users_balance',
			'cols'	 => array(
				'user_id',
				'user_balance'
			)
		);

		parent::__construct( $table );
	}

}
