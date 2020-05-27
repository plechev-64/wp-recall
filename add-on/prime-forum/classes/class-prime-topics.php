<?php

class PrimeTopics extends Rcl_Query {
	function __construct( $as = false ) {

		$table = array(
			'name'	 => RCL_PREF . "pforum_topics",
			'as'	 => $as ? $as : 'pfm_topics',
			'cols'	 => array(
				'topic_id',
				'topic_name',
				'topic_slug',
				'topic_status',
				'topic_closed',
				'topic_fix',
				'forum_id',
				'user_id',
				'post_count',
			)
		);

		parent::__construct( $table );

		$this->number = (pfm_get_option( 'topics-per-page' )) ? pfm_get_option( 'topics-per-page' ) : 20;
	}

}
