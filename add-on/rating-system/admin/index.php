<?php

require_once 'addon-settings.php';

add_action('admin_head','rcl_add_admin_rating_scripts');

function rcl_add_admin_rating_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rcl_admin_rating_scripts', plugins_url('js/scripts.js', __FILE__) );
}

add_filter('rcl_post_options','rcl_get_post_rating_options',10,2);
function rcl_get_post_rating_options($options,$post){
    $mark_v = get_post_meta($post->ID, 'rayting-none', 1);
    $options .= '<p>'.__('Disable rating for publication','wp-recall').':
        <label><input type="radio" name="wprecall[rayting-none]" value="" '.checked( $mark_v, '',false ).' />'.__('No','wp-recall').'</label>
        <label><input type="radio" name="wprecall[rayting-none]" value="1" '.checked( $mark_v, '1',false ).' />'.__('Yes','wp-recall').'</label>
    </p>';
    return $options;
}

add_filter( 'manage_users_columns', 'rcl_get_rating_admin_column' );
function rcl_get_rating_admin_column( $columns ){
	return array_merge( $columns,array( 'user_rating_admin' => __('Rating','wp-recall') ));
}

add_filter( 'manage_users_custom_column', 'rcl_get_rating_column_content', 10, 3 );
function rcl_get_rating_column_content( $custom_column, $column_name, $user_id ){
    switch( $column_name ){
        case 'user_rating_admin':
          $custom_column = '<input type="text" class="raytinguser-'.$user_id.'" size="4" value="'.rcl_get_user_rating($user_id).'">
          <input type="button" class="recall-button edit_rayting" id="user-'.$user_id.'" value="'.__('OK','wp-recall').'">';
        break;
    }
    return $custom_column;
}

rcl_ajax_action('rcl_edit_rating_user', false);
function rcl_edit_rating_user(){
    global $wpdb,$user_ID;

    $user_id = intval($_POST['user']);
    $new_rating = floatval($_POST['rayting']);

    if(!isset($new_rating)){
        wp_send_json(array('error'=>__('Rating was not updated','wp-recall')));
    }

    $rating = rcl_get_user_rating($user_id);

    $val = $new_rating - $rating;

    $args = array(
        'user_id' => $user_ID,
        'object_id' => $user_id,
        'object_author' => $user_id,
        'rating_value' => $val,
        'rating_type' => 'edit-admin'
    );

    rcl_insert_rating($args);

    wp_send_json(array(
        'success' => __('Rating updated successfully','wp-recall')
    ));

}


