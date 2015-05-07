<?php
add_action('wp_ajax_rcl_avatar_upload', 'rcl_avatar_upload');
function rcl_avatar_upload(){
	
	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	global $user_ID,$rcl_options;

	if(!$user_ID) return false;

	if($rcl_options['avatar_weight']) $weight = $rcl_options['avatar_weight'];
	else $weight = '2';

	$mb = $_FILES['filedata']['size']/1024/1024;
	//print_r($_FILES); exit;
	if($mb>$weight){
		$res['result'] = '<div class="error">Превышен размер!</div>';
		echo json_encode($res);
		exit;
	}

	if($_FILES['files']){
		foreach($_FILES['files'] as $key => $data){
			$upload['file'][$key] = $data[0];
		}
	}

	if($_FILES['filedata']){
		foreach($_FILES['filedata'] as $key => $data){
			$upload['file'][$key] = $data;
		}
	}

	//print_r($_FILES['files']);
	//print_r($upload['file']);

	$avatar = wp_handle_upload( $upload['file'], array('test_form' => FALSE) );

        $mime = explode('/',$avatar['type']);
	if($mime[0]!='image'){
		$res['result'] = '<div class="error">Файл не является изображением!</div>';
		echo json_encode($res);
		exit;
	}

        if(function_exists('ulogin_get_avatar')){
		delete_user_meta($user_ID, 'ulogin_photo');
	}

	if(get_option('avatar_user_'.$user_ID)){
		$attachment_id = get_option('avatar_user_'.$user_ID);
		wp_delete_attachment( $attachment_id );
	}

	$attachment = array(
		'post_mime_type' => $avatar['type'],
		'post_title' => 'avatar user'.$user_ID,
		'post_content' => 'image',
		'post_status' => 'inherit'
	);

	$attach_id = wp_insert_attachment( $attachment, $avatar['file'], 0 );
	if (is_wp_error($attach_id)) {
		$error = "Error: $attach_id <br />";
	}
	if($error == ''){
		if(function_exists('has_image_size')) $thumb_size = has_image_size('thumbnail');
		if( !$thumb_size ) {
			add_image_size( 'thumbnail', 150, 150, true );
		}

		$attach_data = wp_generate_attachment_metadata( $attach_id, $avatar['file'] );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		if(function_exists('has_image_size')&&!$thumb_size) remove_image_size('thumbnail');

		$result = update_option( 'avatar_user_'.$user_ID, $attach_id );
		if($result){
			$res['result'] = '<div class="success">Аватар загружен!</div>';
		}
	}

	echo json_encode($res);
	exit;
}
?>