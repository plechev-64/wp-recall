<?php
add_action('wp_ajax_rcl_imagepost_upload', 'rcl_imagepost_upload');
function rcl_imagepost_upload(){
	global $rcl_options,$user_ID;

	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	if(!$user_ID) return false;

	if(isset($_POST['post_id'])&&$_POST['post_id']!='undefined') $id_post = intval($_POST['post_id']);

	$image = wp_handle_upload( $_FILES['uploadfile'], array('test_form' => FALSE) );

        $mime = explode('/',$image['type']);
	if($mime[0]!='image') exit;

	if($image['file']){
		$attachment = array(
			'post_mime_type' => $image['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename($image['file'])),
			'post_content' => '',
			'guid' => $image['url'],
			'post_parent' => $id_post,
			'post_author' => $user_ID,
			'post_status' => 'inherit'
		);

		$res['string'] = rcl_insert_attachment($attachment,$image,$id_post);
		echo json_encode($res);
		exit;
	}else{
		echo 'error';
	}
}