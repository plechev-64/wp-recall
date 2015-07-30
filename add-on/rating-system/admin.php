<?php

if (is_admin()):
	add_action('admin_head','rcl_admin_rating_scripts');
endif;

function rcl_admin_rating_scripts(){
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'rcl_admin_rating_scripts', plugins_url('js/admin.js', __FILE__) );
}

add_filter('rcl_post_options','rcl_post_rating_options',10,2);
function rcl_post_rating_options($options,$post){
    $mark_v = get_post_meta($post->ID, 'rayting-none', 1);
    $options .= '<p>'.__('To disable the rating for publication','rcl').':
        <label><input type="radio" name="wprecall[rayting-none]" value="" '.checked( $mark_v, '',false ).' />'.__('No','rcl').'</label>
        <label><input type="radio" name="wprecall[rayting-none]" value="1" '.checked( $mark_v, '1',false ).' />'.__('Yes','rcl').'</label>
    </p>';
    return $options;
}

function rcl_user_rating_admin_column( $columns ){
	return array_merge( $columns,array( 'user_rayting_admin' => "Рейтинг" ));
}
add_filter( 'manage_users_columns', 'rcl_user_rating_admin_column' );

function rcl_user_rating_admin_content( $custom_column, $column_name, $user_id ){
    switch( $column_name ){
          case 'user_rayting_admin':
              $custom_column = '<input type="text" class="raytinguser-'.$user_id.'" size="5" value="'.rcl_get_user_rating($user_id).'">
              <input type="button" class="recall-button edit_rayting" id="user-'.$user_id.'" value="'.__('OK','rcl').'">';
          break;
    }
    return $custom_column;
}
add_filter( 'manage_users_custom_column', 'rcl_user_rating_admin_content', 10, 3 );

function rcl_edit_rating_user(){
	global $wpdb;
	$user = intval($_POST['user']);
	$rayting = intval($_POST['rayting']);

	if($rayting){

            rcl_update_user_rating(array('user_id'=>$user,'rating_total'=>$rayting));

            $log['otvet']=100;

	}else {
		$log['otvet']=1;
	}
	echo json_encode($log);
    exit;
}
if(is_admin()) add_action('wp_ajax_rcl_edit_rating_user', 'rcl_edit_rating_user');

