<?php

add_action( 'init', 'register_type_post_group',10 );
function register_type_post_group() {

    $labels = array(
        'name' => __('Groups records','wp-recall'),
        'singular_name' => __('Groups records','wp-recall'),
        'add_new' => __('Add entry','wp-recall'),
        'add_new_item' => __('Add entry','wp-recall'),
        'edit_item' => __('Edit','wp-recall'),
        'new_item' => __('New','wp-recall'),
        'view_item' => __('View','wp-recall'),
        'search_items' => __('Search','wp-recall'),
        'not_found' => __('Not found','wp-recall'),
        'not_found_in_trash' => __('Cart is empty','wp-recall'),
        'parent_item_colon' => __('Parent record','wp-recall'),
        'menu_name' => __('Groups records','wp-recall'),
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => false,
        'supports' => array( 'title', 'editor','custom-fields', 'comments', 'thumbnail', 'author'),
        'taxonomies' => array( 'groups' ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 10,
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post'
    );

    register_post_type( 'post-group', $args );
}

add_action( 'init', 'register_taxonomy_groups',10 );
function register_taxonomy_groups() {

	$labels = array(
            'name' => __('Groups','wp-recall'),
            'singular_name' => __('Groups','wp-recall'),
            'search_items' => __('Search','wp-recall'),
            'popular_items' => __('Popular Groups','wp-recall'),
            'all_items' => __('All categories','wp-recall'),
            'parent_item' => __('Parent group','wp-recall'),
            'parent_item_colon' => __('Parent group','wp-recall'),
            'edit_item' => __('Edit','wp-recall'),
            'update_item' => __('Update','wp-recall'),
            'add_new_item' => __('Add a new group','wp-recall'),
            'new_item_name' => __('New','wp-recall'),
            'separate_items_with_commas' => __('Separate with commas','wp-recall'),
            'add_or_remove_items' => __('Add or delete','wp-recall'),
            'choose_from_most_used' => __('Click to use','wp-recall'),
            'menu_name' => __('Groups','wp-recall')
	);

	$args = array(
            'labels' => $labels,
            'public' => true,
            'show_in_nav_menus' => true,
            'show_ui' => true,
            'show_tagcloud' => true,
            'hierarchical' => true,
            'rewrite' => true,
            'query_var' => true
	);

	register_taxonomy( 'groups', array('post-group'), $args );
}
