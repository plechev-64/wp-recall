<?php

class Rcl_Users_Query extends Rcl_Query {
	function __construct( $as = false ) {
		global $wpdb;

		$table = array(
			'name'	 => $wpdb->users,
			'as'	 => $as ? $as : 'wp_users',
			'cols'	 => array(
				'ID',
				'user_login',
				'user_email',
				'user_registered',
				'display_name',
				'user_nicename'
			)
		);

		parent::__construct( $table );
	}

}

class Rcl_User_Action extends Rcl_Query {
	function __construct( $as = false ) {

		$table = array(
			'name'	 => RCL_PREF . 'user_action',
			'as'	 => $as ? $as : 'rcl_user_action',
			'cols'	 => array(
				'ID',
				'user_id',
				'time_action'
			)
		);

		parent::__construct( $table );
	}

}

class Rcl_Temp_Media extends Rcl_Query {
	function __construct( $as = false ) {

		$table = array(
			'name'	 => RCL_PREF . 'temp_media',
			'as'	 => $as ? $as : 'rcl_temp_media',
			'cols'	 => array(
				'media_id',
				'user_id',
				'uploader_id',
				'session_id',
				'upload_date'
			)
		);

		parent::__construct( $table );
	}

}
