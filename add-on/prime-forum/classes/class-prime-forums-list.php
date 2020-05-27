<?php

class PrimeForumsList {

	public $forums	 = array();
	public $groups	 = array();
	public $parents	 = array();

	function __construct() {
		$this->groups	 = $this->get_groups();
		$this->forums	 = $this->get_forums();
	}

	function get_forums() {

		return RQ::tbl( new PrimeForums() )->select( [
					'forum_id',
					'group_id',
					'forum_name',
					'parent_id'
				] )->orderby( 'forum_name', 'ASC' )
				->number( -1 )
				->get_results();
	}

	function get_groups() {

		return RQ::tbl( new PrimeGroups() )
				->select( [
					'group_id',
					'group_name'
				] )->orderby( 'group_seq', 'ASC' )
				->limit( -1 )
				->get_results();
	}

	function get_parent_forums( $group_id ) {

		if ( ! $this->forums )
			return false;

		$forums = array();

		foreach ( $this->forums as $forum ) {

			if ( $forum->parent_id ) {
				$this->parents[] = $forum->parent_id;
				continue;
			}

			if ( $group_id != $forum->group_id )
				continue;

			$forums[] = $forum;
		}

		array_unique( $this->parents );

		return $forums;
	}

	function get_children_forums( $forum_id ) {

		$forums = array();

		foreach ( $this->forums as $forum ) {

			if ( $forum_id != $forum->parent_id )
				continue;

			$forums[] = $forum;
		}

		return $forums;
	}

	function get_list() {

		$content = '<select name="forum_id">';

		foreach ( $this->groups as $group ) {

			$forums = $this->get_parent_forums( $group->group_id );

			if ( ! $forums )
				continue;

			$content .= '<optgroup label="' . $group->group_name . '">';

			$content .= $this->get_forums_list( $forums, 0 );

			$content .= '</optgroup>';
		}

		$content .= '</select>';

		return $content;
	}

	function get_forums_list( $forums, $level ) {

		$content = '';

		foreach ( $forums as $forum ) {

			$content .= '<option value="' . $forum->forum_id . '">' . str_pad( '', $level * 3, "-- ", STR_PAD_LEFT ) . $forum->forum_name . '</option>';

			if ( ! in_array( $forum->forum_id, $this->parents ) )
				continue;

			$childrens = $this->get_children_forums( $forum->forum_id );

			$content .= $this->get_forums_list( $childrens, $level + 1 );
		}

		return $content;
	}

}
