<?php

class PrimeQuery {

	public $vars			 = array();
	public $object;
	public $is_frontpage	 = false;
	public $is_group		 = false;
	public $is_forum		 = false;
	public $is_topic		 = false;
	public $is_search		 = false;
	public $is_page			 = false;
	public $is_author		 = false;
	public $groups;
	public $forums;
	public $topics;
	public $posts;
	public $canonical		 = '';
	public $errors			 = array();
	public $groups_query;
	public $forums_query;
	public $topics_query;
	public $posts_query;
	public $offset			 = 0;
	public $number			 = 20;
	public $all_items		 = 0;
	public $current_page	 = 1;
	public $meta			 = array();
	public $users_data		 = array();
	public $parent_groups	 = array();
	public $last			 = array(
		'topics' => array(),
		'posts'	 => array()
	);
	public $next			 = array(
		'group'	 => 0,
		'forum'	 => 0,
		'topic'	 => 0,
		'post'	 => 0
	);

	function __construct() {
		$this->init_table_query();
	}

	function setup_vars( $vars = false ) {

		if ( $vars ) {

			$defaults = array(
				'pfm-group'	 => '',
				'pfm-forum'	 => '',
				'pfm-topic'	 => '',
				'pfm-page'	 => '',
				'pfm-author' => '',
				'pfm-search' => ''
			);

			$vars = wp_parse_args( $vars, $defaults );
		} else {

			$vars = array(
				'pfm-group'	 => get_query_var( 'pfm-group' ),
				'pfm-forum'	 => (get_query_var( 'page' )) ? get_query_var( 'page' ) : get_query_var( 'pfm-forum' ),
				'pfm-topic'	 => get_query_var( 'pfm-topic' ),
				'pfm-page'	 => get_query_var( 'pfm-page' ),
				'pfm-author' => isset( $_GET['pfm-author'] ) ? $_GET['pfm-author'] : '',
				'pfm-search' => isset( $_GET['fs'] ) ? $_GET['fs'] : ''
			);
		}

		$this->vars = apply_filters( 'pfm_vars', $vars );

		do_action( 'pfm_init_vars', $this->vars );
	}

	function init_table_query() {

		$this->groups_query	 = new PrimeGroups();
		$this->forums_query	 = new PrimeForums();
		$this->topics_query	 = new PrimeTopics();
		$this->posts_query	 = new PrimePosts();
	}

	function init_query() {

		if ( ! $this->vars )
			$this->setup_vars();

		$this->init_conditions();

		if ( ! $this->is_frontpage && ! $this->is_search && ! $this->is_author ) {

			$this->init_queried_object();

			if ( $this->is_group && ! $this->object->group_id ) {

				$this->errors['notice'][] = __( 'Group not found', 'wp-recall' );
			} else if ( $this->is_forum && ! $this->object->forum_id ) {

				$this->errors['notice'][] = __( 'Forum not found', 'wp-recall' );
			} else if ( $this->is_topic && ! $this->object->topic_id ) {

				$this->errors['notice'][] = __( 'Topic not found', 'wp-recall' );
			}

			if ( $this->errors ) {
				status_header( 404 );
			}
		}

		$errors = apply_filters( 'pfm_check_forum_errors', $this->errors, $this );

		if ( $errors && is_array( $errors ) ) {

			$this->errors = $errors;

			return;
		} else {

			$this->setup_page_data();

			$this->setup_child_items();

			$this->setup_forums_data_in_home();

			$this->setup_child_forums();

			$this->setup_canonical_url();

			$this->setup_last_items();

			$this->setup_meta();

			$this->setup_users_data();

			do_action( 'pfm_init_query', $this );
		}
	}

	function init_conditions() {

		if ( $this->vars['pfm-search'] ) {

			$this->is_search = true;
		} else if ( $this->vars['pfm-author'] ) {

			$this->is_author = true;
		} else if ( $this->vars['pfm-group'] ) {

			$this->is_group = true;
		} else if ( $this->vars['pfm-topic'] ) {

			$this->is_topic = true;
		} else if ( $this->vars['pfm-forum'] ) {

			$this->is_forum = true;
		} else {

			$this->is_frontpage = true;
		}

		if ( $this->vars['pfm-page'] ) {

			$this->is_page = true;

			$this->current_page = $this->vars['pfm-page'];
		}
	}

	function setup_page_data() {

		if ( $this->is_topic ) {

			$this->number	 = $this->posts_query->number;
			$this->all_items = $this->object->post_count;
		} else if ( $this->is_forum ) {

			$this->number	 = $this->topics_query->number;
			$this->all_items = $this->object->topic_count;
		} else if ( $this->is_group ) {

			$this->number	 = $this->forums_query->number;
			$this->all_items = $this->forums_query->where( array(
					'group_id'	 => $this->object->group_id,
					'parent_id'	 => 0
				) )->get_count();
		}

		$this->offset = ($this->current_page - 1) * $this->number;
	}

	function get_args_object() {

		$args = array();

		if ( $this->is_group ) {

			if ( '' != get_site_option( 'permalink_structure' ) ) {
				$args = array(
					'group_slug' => $this->vars['pfm-group']
				);
			} else {
				$args = array(
					'group_id' => $this->vars['pfm-group']
				);
			}
		} else if ( $this->is_forum ) {

			$args = array(
				'join' => array(
					array(
						['group_id', 'group_id' ], $this->groups_query->select( true )
					)
				)
			);

			if ( '' != get_site_option( 'permalink_structure' ) ) {
				$args['forum_slug'] = $this->vars['pfm-forum'];
			} else {
				$args['forum_id'] = $this->vars['pfm-forum'];
			}
		} else if ( $this->is_topic ) {

			$args = array(
				'join' => array(
					array(
						['forum_id', 'forum_id' ],
						$this->forums_query->join(
							['group_id', 'group_id' ], $this->groups_query->select( true )
						)
					),
					array(
						['topic_id', 'topic_id' ], $this->posts_query
					)
				)
			);

			if ( '' != get_site_option( 'permalink_structure' ) ) {
				$args['topic_slug'] = $this->vars['pfm-topic'];
				$args['join'][0][1]->where( ['forum_slug' => $this->vars['pfm-forum'] ] );
			} else {
				$args['topic_id'] = $this->vars['pfm-topic'];
				$args['join'][0][1]->where( ['forum_id' => $this->vars['pfm-forum'] ] );
			}
		}

		return apply_filters( 'pfm_pre_get_object', $args, $this );
	}

	function init_queried_object() {

		$args = $this->get_args_object();

		if ( ! $args )
			return false;

		if ( $this->is_group ) {
			$object = $this->groups_query->parse( $args )->get_results();
		} else if ( $this->is_forum ) {
			$object = $this->forums_query->parse( $args )->get_results();
		} else if ( $this->is_topic ) {

			$this->topics_query->reset_query();

			$object = $this->topics_query
				->parse( $args )
				->select_string( "MAX(pfm_posts.post_date) AS last_post_date" )
				->get_results();
		}

		$this->object = apply_filters( 'pfm_query_object', $object[0] );
	}

	function get_args_child_items() {

		if ( $this->is_search ) {

			$args = array(
				'number'	 => $this->number,
				'offset'	 => $this->offset,
				'join'		 => array(
					array(
						['topic_id', 'topic_id' ], $this->posts_query
					)
				),
				'groupby'	 => $this->topics_query->get_colname( 'topic_id' )
			);

			if ( $this->vars['pfm-forum'] ) {
				$args['forum_id'] = $this->vars['pfm-forum'];
			}

			if ( $this->vars['pfm-group'] ) {
				$args['join'][] = array(
					['forum_id', 'forum_id' ],
					$this->forums_query->where( ['group_id' => $this->vars['pfm-group'] ] )
				);
			}
		}if ( $this->is_author ) {

			$args = array(
				'number'	 => $this->number,
				'offset'	 => $this->offset,
				'user_id'	 => $this->vars['pfm-author'],
				'join'		 => array(
					array(
						['topic_id', 'topic_id' ], $this->posts_query
					)
				),
				'groupby'	 => $this->topics_query->get_colname( 'topic_id' )
			);
		} else if ( $this->is_frontpage ) {

			$args = array(
				'number'	 => -1,
				'order'		 => 'ASC',
				'orderby'	 => 'group_seq',
				'join'		 => array(
					array(
						['group_id', 'group_id', 'LEFT' ], $this->forums_query
					)
				),
				'groupby'	 => $this->groups_query->get_colname( 'group_id' )
			);
		} else if ( $this->is_group && $this->object ) {

			$args = array(
				'group_id'	 => $this->object->group_id,
				'parent_id'	 => 0,
				'number'	 => $this->number,
				'offset'	 => $this->offset,
				'order'		 => 'ASC',
				'orderby'	 => 'forum_seq',
				'join'		 => array(
					array(
						['forum_id', 'parent_id', 'LEFT' ], RQ::tbl( new PrimeForums( 'forums2' ) )
					)
				),
				'groupby'	 => $this->forums_query->get_colname( 'forum_id' )
			);
		} else if ( $this->is_forum && $this->object ) {

			$args = array(
				'forum_id'	 => $this->object->forum_id,
				'join'		 => array(
					array(
						['topic_id', 'topic_id' ], $this->posts_query
					)
				),
				'offset'	 => $this->offset,
				'number'	 => $this->number,
				'groupby'	 => $this->topics_query->get_colname( 'topic_id' )
			);
		} else if ( $this->is_topic && $this->object ) {
			global $wpdb;

			$args = array(
				'topic_id'	 => $this->object->topic_id,
				'number'	 => $this->number,
				'offset'	 => $this->offset,
				'order'		 => 'ASC',
				'orderby'	 => 'post_date'
			);
		}

		return apply_filters( 'pfm_pre_get_child_items', $args, $this );
	}

	function setup_child_items() {

		$args = $this->get_args_child_items();

		if ( $this->is_search ) {

			$this->topics_query->reset_query();

			$this->topics_query->parse( $args );

			$this->topics_query->where_string( "(pfm_topics.topic_name LIKE '%" . $this->vars['pfm-search'] . "%' "
				. "OR pfm_posts.post_content LIKE '%" . $this->vars['pfm-search'] . "%')" );

			$this->all_items = $this->topics_query->get_count();

			$this->topics_query->select_string( "pfm_topics.*" );
			$this->topics_query->select_string( "MAX(pfm_posts.post_date) AS last_post_date" );

			$this->topics_query = apply_filters( 'pfm_search_posts_query', $this->topics_query );

			$this->topics = apply_filters( 'pfm_search_posts', $this->topics_query->get_results() );
		}if ( $this->is_author ) {

			$this->topics_query->reset_query();

			$this->topics_query->parse( $args );

			$this->all_items = $this->topics_query->get_count();

			$this->topics_query->select_string( "pfm_topics.*" );
			$this->topics_query->select_string( "MAX(pfm_posts.post_date) AS last_post_date" );

			$this->topics_query = apply_filters( 'pfm_author_posts_query', $this->topics_query );

			$this->topics = apply_filters( 'pfm_author_posts', $this->topics_query->get_results() );
		} else if ( $this->is_frontpage ) {

			$this->groups_query->reset_query();

			$this->groups_query->parse( $args );

			$this->groups_query->select_string( "pfm_groups.*" );
			$this->groups_query->select_string( "COUNT(pfm_forums.forum_id) AS forum_count" );

			$this->groups_query = apply_filters( 'pfm_groups_query', $this->groups_query );

			$this->groups = apply_filters( 'pfm_groups', $this->groups_query->get_results() );
		} else if ( $this->is_group ) {

			$this->forums_query->reset_query();

			$this->forums_query->parse( $args );

			$this->groups_query->select_string( "pfm_forums.*" );
			$this->groups_query->select_string( "COUNT(DISTINCT pfm_forums2.forum_id) AS subforum_count" );

			$this->forums_query = apply_filters( 'pfm_forums_query', $this->forums_query );

			$this->forums = apply_filters( 'pfm_forums', $this->forums_query->get_results() );
		} else if ( $this->object && $this->is_forum ) {

			$this->topics_query->reset_query();
			$this->topics_query->parse( $args );

			$this->topics_query->select_string( "pfm_topics.*" );
			$this->topics_query->select_string( "MAX(pfm_posts.post_date) AS last_post_date" );

			$this->topics_query->query['orderby'] = "topic_fix DESC, MAX(pfm_posts.post_date)";

			$this->topics_query = apply_filters( 'pfm_topics_query', $this->topics_query );

			$this->topics = apply_filters( 'pfm_topics', $this->topics_query->get_results() );
		} else if ( $this->object && $this->is_topic ) {

			$this->posts_query = apply_filters( 'pfm_posts_query', $this->posts_query );

			$this->posts = apply_filters( 'pfm_posts', $this->posts_query->parse( $args )->get_results() );
		}
	}

	function setup_forums_data_in_home() {

		if ( ! pfm_get_option( 'view-forums-home' ) )
			return false;

		if ( ! $this->is_frontpage || ! $this->groups )
			return false;

		$this->forums_query->reset_query();

		$groups = (pfm_get_option( 'forums-home-list' )) ? array_map( 'trim', explode( ',', pfm_get_option( 'forums-home-list' ) ) ) : false;

		if ( ! $groups ) {
			$groups = array();
			foreach ( $this->groups as $group ) {
				$groups[] = $group->group_id;
			}
		}

		$this->parent_groups = $groups;

		$this->forums = $this->forums_query
			->select_string( "pfm_forums.*" )
			->where( [
				'group_id__in'	 => $groups,
				'parent_id'		 => 0,
			] )
			->join(
				['group_id', 'group_id', 'LEFT' ], RQ::tbl( new PrimeGroups() )
			)
			->join(
				['forum_id', 'parent_id', 'LEFT' ], RQ::tbl( new PrimeForums( 'forums2' ) )
				->select( ['count' => ['subforum_count' => 'forum_id' ] ] )
			)
			->groupby( $this->forums_query->get_colname( 'forum_id' ) )
			->order( 'ASC' )
			->orderby( array(
				'pfm_groups.group_seq'	 => 'ASC',
				'pfm_forums.forum_seq'	 => 'ASC'
				), false, false )
			->limit( -1 )
			->get_results();
	}

	function setup_child_forums() {

		if ( ! $this->is_forum )
			return false;

		$this->forums_query->reset_query();

		$args = array(
			'group_id'	 => $this->object->group_id,
			'parent_id'	 => $this->object->forum_id,
			'number'	 => -1,
			'order'		 => 'ASC',
			'orderby'	 => 'forum_seq',
			'join'		 => array(
				array(
					['forum_id', 'parent_id', 'LEFT' ], RQ::tbl( new PrimeForums( 'forums2' ) )
				)
			),
			'groupby'	 => $this->forums_query->get_colname( 'forum_id' )
		);

		$this->forums = $this->forums_query->parse( $args )
			->select_string( "pfm_forums.*" )
			->select_string( "COUNT(DISTINCT forums2.forum_id) AS subforum_count" )
			->get_results();
	}

	function setup_last_items() {

		if ( ! $this->is_topic ) {

			if ( $this->forums ) {

				$this->last['topics'] = $this->get_forums_last_topic( $this->forums );

				$this->last['posts'] = $this->get_forums_last_post( $this->forums );
			}

			if ( $this->topics ) {

				$posts = $this->get_topics_last_post( $this->topics );

				$this->last['posts'] = $this->last['posts'] ? array_merge( $this->last['posts'], $posts ) : $posts;
			}

			$this->last = wp_unslash( $this->last );
		}
	}

	function get_forums_last_post( $forums ) {
		global $wpdb;

		$forumIDs = array();

		foreach ( $forums as $forum ) {
			$forumIDs[] = $forum->forum_id;
		}

		$sql = "SELECT "
			. "MAX(p.post_id) AS post_id "
			. "FROM " . RCL_PREF . "pforum_posts AS p "
			. "INNER JOIN  " . RCL_PREF . "pforum_topics AS t ON p.topic_id = t.topic_id "
			. "WHERE t.forum_id IN (" . implode( ',', $forumIDs ) . ") "
			. "GROUP BY t.forum_id";

		$postIdx = $wpdb->get_col( $sql );

		if ( ! $postIdx )
			return false;

		$sql = "SELECT "
			. "p.post_id,"
			. "p.post_date,"
			. "p.post_index,"
			. "p.topic_id,"
			. "p.user_id,"
			. "t.forum_id, "
			. "t.topic_slug "
			. "FROM " . RCL_PREF . "pforum_posts AS p "
			. "INNER JOIN  " . RCL_PREF . "pforum_topics AS t ON p.topic_id = t.topic_id "
			. "WHERE p.post_id IN (" . implode( ',', $postIdx ) . ")";

		$posts = $wpdb->get_results( $sql );

		return $posts;
	}

	function get_topics_last_post( $topics ) {
		global $wpdb;

		$topicIDs = array();

		foreach ( $topics as $topic ) {
			$topicIDs[] = $topic->topic_id;
		}

		$sql = "SELECT "
			. "MAX(post_id) AS post_id "
			. "FROM " . RCL_PREF . "pforum_posts "
			. "WHERE topic_id IN (" . implode( ',', $topicIDs ) . ") "
			. "GROUP BY topic_id";

		$postIdx = $wpdb->get_col( $sql );

		if ( ! $postIdx )
			return false;

		$sql = "SELECT "
			. "post_id,"
			. "post_date,"
			. "post_index,"
			. "topic_id,"
			. "user_id "
			. "FROM " . RCL_PREF . "pforum_posts "
			. "WHERE post_id IN (" . implode( ',', $postIdx ) . ") "
			. "ORDER BY post_id DESC";

		$posts = $wpdb->get_results( $sql );

		return $posts;
	}

	function get_forums_last_topic( $forums ) {
		global $wpdb;

		$forumIDs = array();

		foreach ( $forums as $forum ) {
			$forumIDs[] = $forum->forum_id;
		}

		$topicIdx = RQ::tbl( new PrimeTopics() )->select( ['max' => ['post_id' => 'topic_id' ] ] )
				->where( ['forum_id__in' => $forumIDs ] )
				->groupby( 'forum_id' )->get_col();

		if ( ! $topicIdx )
			return false;

		$topics = RQ::tbl( new PrimeTopics() )->select( [
				"topic_id",
				"topic_name",
				"forum_id",
				"topic_slug",
				"user_id"
			] )->where( ['topic_id__in' => $topicIdx ] )->get_results();

		return $topics;
	}

	function search_forum_last_topic( $forum_id ) {

		if ( ! $this->last['topics'] )
			return false;

		foreach ( $this->last['topics'] as $topic ) {
			if ( $forum_id == $topic->forum_id )
				return $topic;
		}

		return false;
	}

	function search_forum_last_post( $forum_id ) {

		if ( ! $this->last['posts'] )
			return false;

		foreach ( $this->last['posts'] as $post ) {
			if ( ! isset( $post->forum_id ) )
				continue;
			if ( $forum_id == $post->forum_id )
				return $post;
		}

		return false;
	}

	function search_topic_last_post( $topic_id ) {

		if ( ! $this->last['posts'] )
			return false;

		foreach ( $this->last['posts'] as $post ) {
			if ( $topic_id == $post->topic_id )
				return $post;
		}

		return false;
	}

	function setup_canonical_url() {

		$url = false;

		if ( $this->is_group ) {

			$url = pfm_get_group_permalink( $this->object->group_id );
		} else if ( $this->is_forum ) {

			$url = pfm_get_forum_permalink( $this->object->forum_id );
		} else if ( $this->is_topic ) {

			$url = pfm_get_topic_permalink( $this->object->topic_id );
		}

		if ( $url ) {

			if ( $this->is_page ) {
				if ( '' != get_site_option( 'permalink_structure' ) ) {
					$url = untrailingslashit( $url ) . '/page/' . $this->current_page;
					$url = user_trailingslashit( $url );
				} else {
					$url = add_query_arg( array( 'pfm-page' => $this->current_page ), $url );
				}
			}

			$this->canonical = $url;
		}
	}

	function setup_meta() {
		global $wpdb;

		if ( $this->is_frontpage )
			return false;

		$PrimeMeta = new PrimeMeta();

		$table	 = $PrimeMeta->table['name'];
		$as		 = $PrimeMeta->table['as'];

		$childrens = array();

		if ( $this->is_group ) {

			if ( $this->forums ) {
				foreach ( $this->forums as $forum ) {
					$childrens[] = $forum->forum_id;
				}
			}

			$parentID		 = $this->object->group_id;
			$parentType		 = 'group';
			$childrenType	 = 'forum';
		} else if ( $this->is_forum ) {

			if ( $this->topics ) {
				foreach ( $this->topics as $topic ) {
					$childrens[] = $topic->topic_id;
				}
			}

			$parentID		 = $this->object->forum_id;
			$parentType		 = 'forum';
			$childrenType	 = 'topic';
		} else if ( $this->is_topic ) {

			$authors = array();

			if ( $this->posts ) {

				foreach ( $this->posts as $post ) {
					$childrens[] = $post->post_id;
					$authors[]	 = $post->user_id;
				}

				$authors = array_unique( $authors );
			}

			$parentID		 = $this->object->topic_id;
			$parentType		 = 'topic';
			$childrenType	 = 'post';
		}

		$sql = "SELECT "
			. "$as.object_id, "
			. "$as.object_type, "
			. "$as.meta_key, "
			. "$as.meta_value
				FROM $table AS $as
				WHERE $as.object_type = '$parentType'
					AND $as.object_id = '$parentID'";

		if ( $childrens ) {
			$sql .= " UNION
					SELECT " .
				$as . "2.object_id, "
				. $as . "2.object_type, "
				. $as . "2.meta_key, "
				. $as . "2.meta_value
					FROM $table AS " . $as . "2
					WHERE " . $as . "2.object_type = '$childrenType'
						AND " . $as . "2.object_id IN (" . implode( ',', $childrens ) . ")";
		}

		if ( $this->is_topic && $authors ) {
			$sql .= " UNION
					SELECT "
				. $as . "3.object_id, "
				. $as . "3.object_type, "
				. $as . "3.meta_key, "
				. $as . "3.meta_value
					FROM $table AS " . $as . "3
					WHERE " . $as . "3.object_type = 'author'
						AND " . $as . "3.object_id IN (" . implode( ',', $authors ) . ")";
		}

		$this->meta = $wpdb->get_results( $sql );
	}

	function search_meta_value( $object_id, $object_type, $meta_key ) {

		if ( ! $this->meta )
			return false;

		foreach ( $this->meta as $meta ) {

			if (
				$object_id == $meta->object_id && $object_type == $meta->object_type && $meta_key == $meta->meta_key
			) {
				return maybe_unserialize( $meta->meta_value );
			}
		}

		return false;
	}

	function setup_users_data() {

		$userIds = array();

		if ( $this->is_frontpage || $this->is_group || $this->is_forum || $this->is_topic ) {

			if ( $this->posts ) {
				foreach ( $this->posts as $post ) {
					$userIds[] = $post->user_id;
				}
			}

			if ( $this->last['topics'] ) {
				foreach ( $this->last['topics'] as $topic ) {
					$userIds[] = $topic->user_id;
				}
			}

			if ( $this->last['posts'] ) {
				foreach ( $this->last['posts'] as $post ) {
					$userIds[] = $post->user_id;
				}
			}
		}

		$userIds = array_unique( apply_filters( 'pfm_users', $userIds ) );

		if ( ! $userIds )
			return false;

		global $wpdb;

		$fields = array(
			'ID',
			'display_name'
		);

		if ( $this->is_topic ) {
			$fields[] = 'user_registered';
		}

		$query = new Rcl_Query( array(
			'name'	 => $wpdb->users,
			'as'	 => 'wp_users',
			'cols'	 => $fields
			) );

		$argsQuery = apply_filters( 'pfm_users_data_query', array(
			'ID__in' => $userIds,
			'number' => -1,
			'select' => $fields
			) );

		$users = $query->parse( $argsQuery )->get_results();

		$this->users_data = array();
		foreach ( $users as $user ) {
			$this->users_data[$user->ID] = $user;
		}
	}

	function get_user_data( $user_id, $dataName ) {

		if ( ! isset( $this->users_data[$user_id] ) )
			return false;

		return (isset( $this->users_data[$user_id]->$dataName )) ? $this->users_data[$user_id]->$dataName : false;
	}

}
