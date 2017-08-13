<?php

global $wpdb;

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

$table = RCL_PREF ."pforums";

$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        forum_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        forum_name VARCHAR(250) NOT NULL,
        forum_desc LONGTEXT NOT NULL,
        forum_slug VARCHAR(250) NOT NULL,
        forum_status VARCHAR(20) NOT NULL,
        forum_seq MEDIUMINT(7) UNSIGNED NOT NULL,
        group_id BIGINT(20) UNSIGNED NOT NULL,
        parent_id BIGINT(20) UNSIGNED NOT NULL,
        forum_closed TINYINT(1) UNSIGNED NOT NULL,
        topic_count MEDIUMINT(7) UNSIGNED NOT NULL,
        PRIMARY KEY  forum_id (forum_id),
        KEY group_id (group_id),
        KEY parent_id (parent_id)
      ) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."pforum_groups";

$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        group_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        group_name VARCHAR(250) NOT NULL,
        group_slug VARCHAR(250) NOT NULL,
        group_desc LONGTEXT NOT NULL,
        group_seq SMALLINT(5) UNSIGNED NOT NULL,
        PRIMARY KEY  group_id (group_id)
      ) $collate;";

dbDelta( $sql );

$table = RCL_PREF ."pforum_topics";

$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        topic_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        topic_name VARCHAR(250) NOT NULL,
        topic_slug VARCHAR(250) NOT NULL,
        topic_status VARCHAR(20) NOT NULL,
        forum_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        topic_fix TINYINT(1) UNSIGNED NOT NULL,
        topic_closed TINYINT(1) UNSIGNED NOT NULL,
        post_count MEDIUMINT(7) UNSIGNED NOT NULL,
        PRIMARY KEY  topic_id (topic_id),
        KEY forum_id (forum_id),
        KEY user_id (user_id)
      ) $collate;";

dbDelta( $sql ); 

$table = RCL_PREF ."pforum_posts";

$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        post_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_content LONGTEXT NOT NULL,
        post_date DATETIME NOT NULL,
        post_edit LONGTEXT NOT NULL,
        post_status VARCHAR(20) NOT NULL,
        post_index MEDIUMINT(7) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        guest_name VARCHAR(50) NOT NULL,
        guest_email VARCHAR(75) NOT NULL,
        topic_id BIGINT(20) UNSIGNED NOT NULL,
        PRIMARY KEY  post_id (post_id),
        KEY post_date (post_date),
        KEY user_id (user_id),
        KEY topic_id (topic_id)
      ) $collate;";

dbDelta( $sql ); 

$table = RCL_PREF ."pforum_meta";

$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        object_id BIGINT(20) UNSIGNED NOT NULL,
        object_type VARCHAR(75) NOT NULL,
        meta_key VARCHAR(75) NOT NULL,
        meta_value LONGTEXT NOT NULL,
        PRIMARY KEY  meta_id (meta_id),
        KEY object_id (object_id),
        KEY object_type (object_type),
        KEY meta_key (meta_key)
      ) $collate;";

dbDelta( $sql ); 

$table = RCL_PREF ."pforum_visits";

$sql = "CREATE TABLE IF NOT EXISTS ". $table . " (
        user_id BIGINT(20) UNSIGNED NOT NULL,
        group_id BIGINT(20) UNSIGNED NOT NULL,
        forum_id BIGINT(20) UNSIGNED NOT NULL,
        topic_id BIGINT(20) UNSIGNED NOT NULL,
        visit_date DATETIME NOT NULL,
        PRIMARY KEY  user_id (user_id),
        KEY group_id (group_id),
        KEY forum_id (forum_id),
        KEY topic_id (topic_id),
        KEY visitor_date (visit_date)
      ) $collate;";

dbDelta( $sql ); 

add_action('rcl_activate_prime-forum','pfm_activate_theme');
function pfm_activate_theme($addonData){
    
    $defaultTheme = 'prime-first';
    
    $forumTheme = ($theme = get_option('rcl_pforum_template'))? $theme: $defaultTheme;
    
    rcl_activate_addon($forumTheme, true, dirname(__FILE__).'/themes');
    
    if(!rcl_exist_addon($forumTheme)){
        $forumTheme = $defaultTheme;
        rcl_activate_addon($forumTheme, true, dirname(__FILE__).'/themes');
    }

    update_option('rcl_pforum_template',$forumTheme);
    
    flush_rewrite_rules();
    
}

$PfmOptions = get_option('rcl_pforum_options');

if(!isset($PfmOptions['home-page'])){
    
    $PfmOptions['home-page'] = wp_insert_post(array(
        'post_title'=>__('Forum','wp-recall'),
        'post_content'=>'[prime-forum]',
        'post_status'=>'publish',
        'post_author'=>1,
        'post_type'=>'page',
        'post_name'=>'forum'
    ));
    
    update_option('rcl_pforum_options',$PfmOptions);
    
    $admins = get_users(array('role'=>'administrator'));
    
    foreach($admins as $admin){
        update_user_meta($admin->ID,'pfm_role','administrator');
    }

}

