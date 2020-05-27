<?php

class Rcl_Groups_Query extends Rcl_Query {
	function __construct( $as = false ) {

		$table = array(
			'name'	 => RCL_PREF . "groups",
			'as'	 => $as ? $as : 'rcl_groups',
			'cols'	 => array(
				'ID',
				'admin_id',
				'group_users',
				'group_status',
				'group_date'
			)
		);

		parent::__construct( $table );
	}

	function setup_termdata() {
		global $wpdb;

		$this->select( [
			'ID',
			'admin_id',
			'group_users',
			'group_status',
			'group_date'
		] );

		$this->query['select'][] = "wp_terms.term_id";
		$this->query['select'][] = "wp_terms.name";
		$this->query['select'][] = "wp_term_taxonomy.count";

		$this->query['join'][]	 = "INNER JOIN $wpdb->terms AS wp_terms ON rcl_groups.ID = wp_terms.term_id";
		$this->query['join'][]	 = "INNER JOIN $wpdb->term_taxonomy AS wp_term_taxonomy ON wp_terms.term_id = wp_term_taxonomy.term_id";

		$this->query['where'][]	 = "wp_term_taxonomy.taxonomy = 'groups'";
		$this->query['where'][]	 = "wp_term_taxonomy.parent = '0'";

		return $this;
	}

}

class Rcl_Groups_Users_Query extends Rcl_Query {
	function __construct( $as = false ) {

		$table = array(
			'name'	 => RCL_PREF . "groups_users",
			'as'	 => $as ? $as : 'rcl_groups_users',
			'cols'	 => array(
				'ID',
				'group_id',
				'user_id',
				'user_role',
				'status_time',
				'user_date'
			)
		);

		parent::__construct( $table );
	}

}

class Rcl_Groups_Options_Query extends Rcl_Query {
	function __construct( $as = false ) {

		$table = array(
			'name'	 => RCL_PREF . "groups_options",
			'as'	 => $as ? $as : 'rcl_groups_options',
			'cols'	 => array(
				'ID',
				'group_id',
				'option_key',
				'option_value'
			)
		);

		parent::__construct( $table );
	}

}
