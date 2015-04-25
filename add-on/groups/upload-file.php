<?php
$path_parts = pathinfo(__FILE__);
preg_match_all("/(?<=)[A-z0-9\-\/\.\_\s\�]*(?=wp\-content)/i", $path_parts['dirname'], $string_value);
require_once( $string_value[0][0].'/wp-load.php' );

require_once(ABSPATH . "wp-admin" . '/includes/image.php');
require_once(ABSPATH . "wp-admin" . '/includes/file.php');
require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	if(isset($_GET['id_group'])&&$_GET['id_group']!='undefined') $id_group = $_GET['id_group'];

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

		$res['string'] = insert_post_attachment_rcl($attachment,$image,$id_post);
		echo json_encode($res);
		exit;
	}else{
		echo 'error';
	}
?>