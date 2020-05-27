<?php

class PrimeGroups extends Rcl_Query {
	function __construct( $as = false ) {

		$table = array(
			'name'	 => RCL_PREF . "pforum_groups",
			'as'	 => $as ? $as : 'pfm_groups',
			'cols'	 => array(
				'group_id',
				'group_name',
				'group_slug',
				'group_desc',
				'group_seq'
			)
		);

		parent::__construct( $table );
	}

}
