<?php

add_action( 'init', 'register_terms_rec_post_group' );
function register_terms_rec_post_group() {

	$labels = array(
            'name' => __('Record groups','rcl'),
            'singular_name' => __('Record groups','rcl'),
            'add_new' => __('Add entry','rcl'),
            'add_new_item' => __('Add entry','rcl'),
            'edit_item' => __('Edit','rcl'),
            'new_item' => __('New','rcl'),
            'view_item' => __('View','rcl'),
            'search_items' => __('Search','rcl'),
            'not_found' => __('Not found','rcl'),
            'not_found_in_trash' => __('Cart is empty','rcl'),
            'parent_item_colon' => __('Parent record','rcl'),
            'menu_name' => __('Record groups','rcl'),
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
add_action( 'init', 'register_taxonomy_groups' );

function register_taxonomy_groups() {

	$labels = array(
            'name' => __('Groups','rcl'),
            'singular_name' => __('Groups','rcl'),
            'search_items' => __('Search','rcl'),
            'popular_items' => __('Popular Groups','rcl'),
            'all_items' => __('All categories','rcl'),
            'parent_item' => __('Parent group','rcl'),
            'parent_item_colon' => __('Parent group','rcl'),
            'edit_item' => __('Edit','rcl'),
            'update_item' => __('Update','rcl'),
            'add_new_item' => __('To add a new','rcl'),
            'new_item_name' => __('New','rcl'),
            'separate_items_with_commas' => __('Separate with commas','rcl'),
            'add_or_remove_items' => __('To add or remove','rcl'),
            'choose_from_most_used' => __('Click to use','rcl'),
            'menu_name' => __('Groups','rcl')
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

