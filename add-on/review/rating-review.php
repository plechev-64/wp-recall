<?php
if(!is_admin()) add_action('init','rcl_register_rating_review_type');
if(is_admin()) add_action('admin_init','rcl_register_rating_review_type');
function rcl_register_rating_review_type(){
	global $active_addons;
	if(!$active_addons['rating-system']) return false;
	rcl_register_rating_type(array('rating_type'=>'rcl-review','type_name'=>'Отзывы','icon'=>'fa-trophy'));
        rcl_register_rating_type(array('rating_type'=>'review-content','type_name'=>'Текст отзыва','style'=>true,'icon'=>'fa-commenting-o'));
}

add_action('rcl_add_review','rcl_add_rating_user_review',10,2);
function rcl_add_rating_user_review($author_rev,$user_rev){
	global $wpdb,$rcl_options,$rcl_rating_types;

	if($rcl_options['rating_user_rcl-review']==1){

		$review = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RCL_PREF."profile_otziv WHERE user_id = '%d' AND author_id = '%d'",$user_rev,$author_rev));

		if(!$review) return false;

		$args = array(
			'user_id' => $review->author_id,
			'object_id' => $review->ID,
			'object_author' => $review->user_id,
			'rating_value' => $review->status,
			'rating_type' => 'rcl-review'
		);

		rcl_insert_rating($args);
	}
}

add_action('rcl_delete_review','rcl_delete_rating_user_review');
function rcl_delete_rating_user_review($review){
	global $rcl_options,$rcl_rating_types;

	if($rcl_options['rating_user_rcl-review']==1){

		$args = array(
			'user_id' => $review->author_id,
			'object_id' => $review->ID,
			'object_author' => $review->user_id,
			'rating_type' => 'rcl-review'
		);

		rcl_delete_rating($args);
	}
}