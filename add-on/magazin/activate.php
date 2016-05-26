<?php
global $wpdb;

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

$table = RMAG_PREF ."details_orders";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        ID bigint (20) NOT NULL AUTO_INCREMENT,
        order_id INT(20) NOT NULL,
        details_order LONGTEXT NOT NULL,
        PRIMARY KEY id (id),
          KEY order_id (order_id)
    ) $collate;";

dbDelta( $sql );

$table = RMAG_PREF ."orders_history";
$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        ID bigint (20) NOT NULL AUTO_INCREMENT,
        order_id INT(20) NOT NULL,
        user_id INT(20) NOT NULL,
        product_id INT(20) NOT NULL,
        product_price INT(20) NOT NULL,
        numberproduct INT(20) NOT NULL,
        order_date DATETIME NOT NULL,
        order_status INT(10) NOT NULL,
        PRIMARY KEY id (id),
          KEY order_id (order_id),
          KEY user_id (user_id),
          KEY product_id (product_id),
          KEY order_status (order_status)
      ) $collate;";

dbDelta( $sql );

$rmag_options = get_option('primary-rmag-options');

if(!isset($rmag_options['products_warehouse_recall'])) $rmag_options['products_warehouse_recall']=0;
if(!isset($rmag_options['sistem_related_products'])) $rmag_options['sistem_related_products']=1;
if(!isset($rmag_options['title_related_products_recall'])) $rmag_options['title_related_products_recall']='Рекомендуем';
if(!isset($rmag_options['size_related_products'])) $rmag_options['size_related_products']=3;
if(!isset($rmag_options['primary_cur'])) $rmag_options['primary_cur']='RUB';
if(!isset($rmag_options['basket_page_rmag'])){
    
    $labels = array(
        'name' => 'Каталог товаров',
        'singular_name' => 'Каталог товаров',
        'add_new' => 'Добавить товар',
        'add_new_item' => 'Добавить новый товар',
        'edit_item' => 'Редактировать',
        'new_item' => 'Новое',
        'view_item' => 'Просмотр',
        'search_items' => 'Поиск',
        'not_found' => 'Не найдено',
        'not_found_in_trash' => 'Корзина пуста',
        'parent_item_colon' => 'Родительский товар',
        'menu_name' => 'Товары'
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => false,
        'supports' => array( 'title', 'editor','custom-fields','thumbnail','comments','excerpt','author'),
        'taxonomies' => array( 'prodcat' ),
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
          'name' => 'Категории',
        'singular_name' => 'Категории',
        'search_items' => 'Поиск',
        'popular_items' => 'Популярные категории',
        'all_items' => 'Все категории',
        'parent_item' => 'Родительская категория',
        'parent_item_colon' => 'Родительская категория:',
        'edit_item' => 'Редактировать категорию',
        'update_item' => 'Обновить категорию',
        'add_new_item' => 'Добавить новую категорию',
        'new_item_name' => 'Новая категория',
        'separate_items_with_commas' => 'Категории разделяются запятыми',
        'add_or_remove_items' => 'Добавить или удалить категорию',
        'choose_from_most_used' => 'Выберите для использования',
        'menu_name' => 'Категории'
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
          'name' => 'Метки товаров',
        'singular_name' => 'Метки товаров',
        'search_items' => 'Поиск',
        'popular_items' => 'Популярные',
        'all_items' => 'Все',
        'parent_item' => 'Родительская',
        'parent_item_colon' => 'Родительская:',
        'edit_item' => 'Редактировать',
        'update_item' => 'Обновить',
        'add_new_item' => 'Добавить новую',
        'new_item_name' => 'Новая',
        'separate_items_with_commas' => 'Разделяйте метки запятыми',
        'add_or_remove_items' => 'Добавить или удалить',
        'choose_from_most_used' => 'Выберите для использования',
        'menu_name' => 'Метки товаров'
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
        'Метка товара 1',
        'product_tag',
        array(
              'slug' => 'products_tag_1'
        )
    );
    
    wp_insert_term(
        'Метка товара 2',
        'product_tag',
        array(              
              'slug' => 'products_tag_2'
        )
    );
    
    wp_insert_term(
        'Метка товара 3',
        'product_tag',
        array(
              'slug' => 'products_tag_3'
        )
    );
    
    $rmag_options['basket_page_rmag'] = wp_insert_post(array(
        'post_title'=>'Корзина',
        'post_content'=>'[basket]',
        'post_status'=>'publish',
        'post_author'=>1,
        'post_type'=>'page',
        'post_name'=>'rcl-cart'
    ));
    
    wp_insert_post(array(
        'post_title'=>'Каталог товаров',
        'post_content'=>'<p>Здесь будет выводиться ваш каталог товаров. Вывод каталога товаров формируется шорткодом productlist <a href="https://codeseller.ru/api-rcl/productlist/">(описание шорткода)</a>. Вы можете выбрать другую страницу для его размещения.</p><br/>[productlist]',
        'post_status'=>'publish',
        'post_author'=>1,
        'post_type'=>'page',
        'post_name'=>'productlist'
    ));

}

update_option('primary-rmag-options',$rmag_options);