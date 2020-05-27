<?php

class PrimeForums extends Rcl_Query {
	function __construct( $as = false ) {

		$table = array(
			'name'	 => RCL_PREF . "pforums",
			'as'	 => $as ? $as : 'pfm_forums',
			'cols'	 => array(
				'forum_id',
				'forum_name',
				'forum_desc',
				'forum_slug',
				'forum_status',
				'forum_seq',
				'group_id',
				'parent_id',
				'topic_count',
				'forum_closed'
			)
		);

		parent::__construct( $table );

		$this->number = (pfm_get_option( 'forums-per-page' )) ? pfm_get_option( 'forums-per-page' ) : 20;
	}

}
