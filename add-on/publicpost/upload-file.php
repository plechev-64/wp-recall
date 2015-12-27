<?php
add_action('wp_ajax_rcl_imagepost_upload', 'rcl_imagepost_upload');
//add_action('wp_ajax_nopriv_rcl_imagepost_upload', 'rcl_imagepost_upload');
function rcl_imagepost_upload(){
	global $rcl_options,$user_ID;
        
        rcl_verify_ajax_nonce();

	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	if(!$user_ID) return false;

	if(isset($_POST['post_id'])&&$_POST['post_id']!='undefined') $id_post = intval($_POST['post_id']);
        
        $post = get_post($id_post);

	$files = array();
	foreach($_FILES['uploadfile'] as $key=>$fls){
		foreach($fls as $k=>$data){
			$files[$k][$key] = $data;
		}
	}
        
	foreach($files as $k=>$file){

            $mime = explode('/',$file['type']);
            
            if($post->post_type=='post'){
                $valid_types = array("gif", "jpg", "png", "jpeg");
                if (!in_array($mime[1], $valid_types)){ 
                    echo json_encode(array('error'=>'Недозволенное расширение файла. Используйте только: .gif, .png, .jpg'));
                    exit;
                } 
            }

            if($mime[1]=='php'||$mime[1]=='html'||$mime[1]=='txt'||$mime[1]=='javascript'){ 
                echo json_encode(array('error'=>'Запрещенное расширение файла.'));
                exit;
            }

            $image = wp_handle_upload( $file, array('test_form' => FALSE) );

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

                $res[$k]['string'] = rcl_insert_attachment($attachment,$image,$id_post);
            }

	}

	echo json_encode($res);
	exit;
}