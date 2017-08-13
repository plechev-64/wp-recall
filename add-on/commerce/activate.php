<?php
global $wpdb;

require_once 'core.php';

if(!defined('RMAG_PREF')) define('RMAG_PREF', $wpdb->prefix."rmag_");

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
$collate = '';

if ( $wpdb->has_cap( 'collation' ) ) {
    if ( ! empty( $wpdb->charset ) ) {
        $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
    }
    if ( ! empty( $wpdb->collate ) ) {
        $collate .= " COLLATE $wpdb->collate";
    }
}

$update_table = false;

$table = RCL_PREF ."orders";

if($wpdb->get_var("show tables like '". $table . "'") != $table) {
    $update_table = true;
}

$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        order_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        order_price VARCHAR(20) NOT NULL,
        products_amount SMALLINT(5) UNSIGNED NOT NULL,
        order_details LONGTEXT NOT NULL,
        order_date DATETIME NOT NULL,
        order_status TINYINT(2) UNSIGNED NOT NULL,
        PRIMARY KEY  order_id (order_id),
          KEY user_id (user_id),
          KEY order_status (order_status)
      ) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."order_items";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        item_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT(20) UNSIGNED NOT NULL,
        product_id BIGINT(20) UNSIGNED NOT NULL,
        product_price VARCHAR(20) NOT NULL,
        product_amount SMALLINT(5) UNSIGNED NOT NULL,
        variations LONGTEXT NOT NULL,
        PRIMARY KEY  item_id (item_id),
          KEY order_id (order_id),
          KEY product_id (product_id)
      ) $collate;";

dbDelta( $sql );

if($update_table) {
    rmag_migration_table_data();
}

$rmag_options = get_option('primary-rmag-options');

if(!isset($rmag_options['sistem_related_products'])) 
    $rmag_options['sistem_related_products']=1;

if(!isset($rmag_options['title_related_products_recall'])) 
    $rmag_options['title_related_products_recall']='Рекомендуем';

if(!isset($rmag_options['size_related_products'])) 
    $rmag_options['size_related_products']=3;

if(!isset($rmag_options['primary_cur'])) 
    $rmag_options['primary_cur']='RUB';

if(!isset($rmag_options['basket_page_rmag'])){
    
    $labels = array(
        'name' => __('Products catalog','wp-recall'),
        'singular_name' => __('Product','wp-recall'),
        'add_new' => __('Add this item','wp-recall'),
        'add_new_item' => __('Add new item','wp-recall'),
        'edit_item' => __('Edit','wp-recall'),
        'new_item' => __('New','wp-recall'),
        'view_item' => __('View','wp-recall'),
        'search_items' => __('Search','wp-recall'),
        'not_found' => __('Not found','wp-recall'),
        'not_found_in_trash' => __('Cart is empty','wp-recall'),
        'parent_item_colon' => __('Parental goods','wp-recall'),
        'menu_name' => __('Products','wp-recall')
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => false,
        'supports' => array( 'title', 'editor','custom-fields','thumbnail','comments','excerpt','author'),
        'taxonomies' => array( 'prodcat','product_tag' ),
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

    register_post_type( 'products', $args );
    
    $labels = array(
        'name' => __('Product categories','wp-recall'),
        'singular_name' => __('Category','wp-recall'),
        'search_items' => __('Search','wp-recall'),
        'popular_items' => __('Popular categories','wp-recall'),
        'all_items' => __('All categories','wp-recall'),
        'parent_item' => __('Parent category','wp-recall'),
        'parent_item_colon' => __('Parent category','wp-recall'),
        'edit_item' => __('Edit category','wp-recall'),
        'update_item' => __('Update category','wp-recall'),
        'add_new_item' => __('Add a new category','wp-recall'),
        'new_item_name' => __('New category','wp-recall'),
        'separate_items_with_commas' => __('Categories separated by commas','wp-recall'),
        'add_or_remove_items' => __('Add or delete a category','wp-recall'),
        'choose_from_most_used' => __('Select to use','wp-recall'),
        'menu_name' => __('Product categories','wp-recall')
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

    register_taxonomy( 'prodcat', array('products'), $args );

    wp_insert_term(
        'Товарная категория',
        'prodcat',
        array(
              'description'=> 'Первая товарная категория. Ее можно переименовать и указать для нее свое описание.',
              'slug' => 'products_category'
        )
    );
    
    $labels = array(
        'name' => __('Product Tags','wp-recall'),
        'singular_name' => __('Product Tag','wp-recall'),
        'search_items' => __('Search','wp-recall'),
        'popular_items' => __('Popular tags','wp-recall'),
        'all_items' => __('All tags','wp-recall'),
        'parent_item' => __('Parental tag','wp-recall'),
        'parent_item_colon' => __('Parental tag','wp-recall'),
        'edit_item' => __('Edit','wp-recall'),
        'update_item' => __('Update','wp-recall'),
        'add_new_item' => __('Add a new tag','wp-recall'),
        'new_item_name' => __('New tag','wp-recall'),
        'separate_items_with_commas' => __('Tags separated by commas','wp-recall'),
        'add_or_remove_items' => __('Add or delete','wp-recall'),
        'choose_from_most_used' => __('Select to use','wp-recall'),
        'menu_name' => __('Product Tags','wp-recall')
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_in_nav_menus' => true,
        'show_ui' => true,
        'show_tagcloud' => true,
        'hierarchical' => false,
        'rewrite' => true,
        'query_var' => true
    );

    register_taxonomy( 'product_tag', array('products'), $args );
    
    wp_insert_term(
        __('Product Tag','wp-recall').' 1',
        'product_tag',
        array(
              'slug' => 'products_tag_1'
        )
    );
    
    wp_insert_term(
        __('Product Tag','wp-recall').' 2',
        'product_tag',
        array(              
              'slug' => 'products_tag_2'
        )
    );
    
    wp_insert_term(
        __('Product Tag','wp-recall').' 3',
        'product_tag',
        array(
              'slug' => 'products_tag_3'
        )
    );
    
    $rmag_options['basket_page_rmag'] = wp_insert_post(array(
        'post_title'=>__('Cart','wp-recall'),
        'post_content'=>'[basket]',
        'post_status'=>'publish',
        'post_author'=>1,
        'post_type'=>'page',
        'post_name'=>'rcl-cart'
    ));
    
    wp_insert_post(array(
        'post_title'=>__('Products catalog','wp-recall'),
        'post_content'=>'<p>'.__('Your product catalog will be displayed here. Product catalog is being generated shortcode productlist <a href="https://codeseller.ru/api-rcl/productlist/">(description shortcode)</a>. You can choose another page for display','wp-recall').'</p><br/>[productlist]',
        'post_status'=>'publish',
        'post_author'=>1,
        'post_type'=>'page',
        'post_name'=>'productlist'
    ));

}

update_option('primary-rmag-options',$rmag_options);