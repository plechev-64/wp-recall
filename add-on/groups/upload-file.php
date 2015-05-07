<?php
add_action('wp_ajax_rcl_postgroup_upload', 'rcl_postgroup_upload');
function rcl_postgroup_upload(){
	
	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	if(isset($_POST['id_group'])&&$_POST['id_group']!='undefined') $id_group = inval($_POST['id_group']);

	$image = wp_handle_upload( $_FILES['uploadfile'], array('test_form' => FALSE) );
	if($image['file']){
		$attachment = array(
			'post_mime_type' => $image['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', basename($image['file'])),
			'post_content' => 'gallery_group_'.$term_id,
			'guid' => $image['url'],
			'post_parent' => '',
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