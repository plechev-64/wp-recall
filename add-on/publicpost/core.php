<?php

function rcl_get_postslist( $post_type, $type_name = false ) {

	global $user_ID, $user_LK, $post;

	$postStatus = array( 'publish' );

	if ( rcl_is_office( $user_ID ) ) {
		$postStatus[]	 = 'trash';
		$postStatus[]	 = 'pending';
		$postStatus[]	 = 'private';
		$postStatus[]	 = 'draft';
	}

	$args = array(
		'numberposts'		 => -1,
		'orderby'			 => 'date',
		'order'				 => 'DESC',
		'post_type'			 => $post_type,
		'post_status'		 => $postStatus,
		'author'			 => $user_LK,
		'suppress_filters'	 => true,
		'fields'			 => 'ids'
	);

	$ids = get_posts( $args );

	if ( !$ids )
		return __( 'Публикаций пока не было', 'wp-recall' );

	$perPage = 30;

	$rclnavi = new Rcl_PageNavi( $post_type . '-navi', count( $ids ), array( 'in_page' => $perPage ) );

	$args['offset']		 = $rclnavi->offset;
	$args['numberposts'] = $perPage;
	$args['fields']		 = false;

	$posts = get_posts( $args );

	if ( rcl_get_template_path( 'posts-list-' . $post_type . '.php', __FILE__ ) )
		$templateName	 = 'posts-list-' . $post_type . '.php';
	else
		$templateName	 = 'posts-list.php';

	$content = $rclnavi->pagenavi();

	$content .= '<div class="rcl-posts-list">';

	foreach ( $posts as $post ) {
		setup_postdata( $post );

		$content .= rcl_get_include_template( $templateName, __FILE__ );
	}

	$content .= '</div>';

	$content .= $rclnavi->pagenavi();

	wp_reset_postdata();

	return $content;
}

function rcl_the_post_status() {
	global $post;

	$status = '';

	switch ( $post->post_status ) {
		case 'pending': $status	 = __( 'to be approved', 'wp-recall' );
			break;
		case 'trash': $status	 = __( 'deleted', 'wp-recall' );
			break;
		case 'draft': $status	 = __( 'draft', 'wp-recall' );
			break;
		case 'private': $status	 = __( 'private', 'wp-recall' );
			break;
		default: $status	 = __( 'published', 'wp-recall' );
	}

	echo $status;
}

function rcl_tab_postform( $master_id ) {
	return do_shortcode( '[public-form form_id="' . rcl_get_option( 'form-lk', 1 ) . '"]' );
}

function rcl_is_limit_editing( $post_date ) {

	$timelimit = apply_filters( 'rcl_time_editing', rcl_get_option( 'time_editing' ) );

	if ( $timelimit ) {
		$hours = (strtotime( current_time( 'mysql' ) ) - strtotime( $post_date )) / 3600;
		if ( $hours > $timelimit )
			return true;
	}

	return false;
}

function rcl_get_custom_fields_edit_box( $post_id, $post_type = false, $form_id = 1 ) {

	$post = get_post( $post_id );

	$RclForm = new Rcl_Public_Form( array(
		'post_type'	 => $post->post_type,
		'post_id'	 => $post_id,
		'form_id'	 => $form_id
		) );

	$fields = $RclForm->get_custom_fields();

	if ( !$fields )
		return false;

	$content = '<div class="rcl-custom-fields-box">';

	foreach ( $fields as $field_id => $field ) {

		if ( !isset( $field->slug ) )
			continue;

		$field->value = ($post_id) ? get_post_meta( $post_id, $field->slug, 1 ) : '';

		$content .= '<div class="rcl-custom-field">';

		$content .= '<label>' . $field->get_title() . '</label>';

		$content .= '<div class="field-value">';
		$content .= $field->get_field_input();
		$content .= '</div>';

		$content .= '</div>';
	}

	$content .= '</div>';

	return $content;
}

function rcl_update_post_custom_fields( $post_id, $id_form = false ) {

	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	$post = get_post( $post_id );

	$formFields = new Rcl_Public_Form_Fields( $post->post_type, array(
		'form_id' => $id_form
		) );

	$fields = $formFields->get_custom_fields();

	if ( $fields ) {

		$POST = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

		foreach ( $fields as $field_id => $field ) {

			$value = isset( $POST[$field_id] ) ? $POST[$field_id] : false;

			if ( $field->type == 'checkbox' ) {
				$vals = array();

				$count_field = count( $field->values );

				if ( $value && is_array( $value ) ) {
					foreach ( $value as $val ) {
						for ( $a = 0; $a < $count_field; $a++ ) {
							if ( $field['values'][$a] == $val ) {
								$vals[] = $val;
							}
						}
					}
				}

				if ( $vals ) {
					update_post_meta( $post_id, $field_id, $vals );
				} else {
					delete_post_meta( $post_id, $field_id );
				}
			} else if ( $field->type == 'file' ) {

				$attach_id = rcl_upload_meta_file( $field, $post->post_author, $post_id );

				if ( $attach_id )
					update_post_meta( $post_id, $field_id, $attach_id );
			}else {

				if ( $value ) {
					update_post_meta( $post_id, $field_id, $value );
				} else {
					if ( get_post_meta( $post_id, $field_id, 1 ) )
						delete_post_meta( $post_id, $field_id );
				}
			}

			if ( $field->type == 'uploader' && $value ) {
				//удаляем записи из временной библиотеки

				foreach ( $value as $attach_id ) {
					rcl_delete_temp_media( $attach_id );
				}
			}
		}
	}
}

function rcl_button_fast_edit_post( $post_id ) {
	return '<a class="rcl-edit-post rcl-service-button" data-post="' . $post_id . '" onclick="rcl_edit_post(this); return false;"><i class="rcli fa-pencil-square-o"></i></a>';
}

function rcl_button_fast_delete_post( $post_id ) {
	return '<a class="rcl-delete-post rcl-service-button" data-post="' . $post_id . '" onclick="return confirm(\'' . __( 'Are you sure?', 'wp-recall' ) . '\')? rcl_delete_post(this): false;"><i class="rcli fa-trash"></i></a>';
}
