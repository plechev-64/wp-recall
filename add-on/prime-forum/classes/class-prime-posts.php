<?php

class PrimePosts extends Rcl_Query {

	public $serialize = ['post_edit' ];

	function __construct( $as = false ) {

		$table = array(
			'name'	 => RCL_PREF . "pforum_posts",
			'as'	 => $as ? $as : 'pfm_posts',
			'cols'	 => array(
				'post_id',
				'post_content',
				'user_id',
				'guest_name',
				'guest_email',
				'post_date',
				'post_edit',
				'post_status',
				'post_index',
				'topic_id'
			)
		);

		parent::__construct( $table );

		$this->number = (pfm_get_option( 'posts-per-page' )) ? pfm_get_option( 'posts-per-page' ) : 20;
	}

}
