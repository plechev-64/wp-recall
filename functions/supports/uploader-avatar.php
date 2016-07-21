<?php

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_support_avatar_uploader_scripts',10);
endif;

function rcl_support_avatar_uploader_scripts(){   
    if(rcl_is_office()){
        rcl_fileupload_scripts();
        rcl_crop_scripts();
        rcl_enqueue_script( 'avatar-uploader', RCL_URL.'functions/supports/js/uploader-avatar.js',false,true );
    }
}

add_filter('rcl_init_js_variables','rcl_init_js_avatar_variables',10);
function rcl_init_js_avatar_variables($data){
    global $rcl_options,$user_LK,$user_ID;
    
    if($user_LK==$user_ID){
        $size_ava = (isset($rcl_options['avatar_weight'])&&$rcl_options['avatar_weight'])? $rcl_options['avatar_weight']: 2;
    
        $data['profile']['avatar_size'] = $size_ava;
        $data['local']['upload_size_avatar'] = sprintf(__('Exceeds the maximum size for a picture! Max. %s MB','wp-recall'),$size_ava);
        $data['local']['title_image_upload'] = __('The image being loaded','wp-recall');
        $data['local']['title_webcam_upload'] = __('Image from the camera','wp-recall');
    }
    
    return $data;
}

add_filter('after-avatar-rcl','rcl_button_avatar_upload',11,2);
function rcl_button_avatar_upload($content,$author_lk){
    global $user_ID;

    if($user_ID!=$author_lk) return $content;

    if( isset($_SERVER["HTTPS"])&&$_SERVER["HTTPS"] == 'on') 
        rcl_webcam_scripts();
    
    $avatar = get_user_meta($author_lk,'rcl_avatar',1);

    if($avatar){
        $content .= '<a title="'.__('Delete avatar','wp-recall').'" class="rcl-avatar-delete" href="'.wp_nonce_url( rcl_format_url(get_author_posts_url($author_lk)).'rcl-action=delete_avatar', $user_ID ).'"><i class="fa fa-times"></i></a>';
    }

    $content .= '
    <div id="userpic-upload">
        <span id="file-upload" class="fa fa-download">
            <input type="file" id="userpicupload" accept="image/*" name="userpicupload">
        </span>';
    $content .= @( !isset($_SERVER["HTTPS"])||$_SERVER["HTTPS"] != 'on' ) ? '':  '<span id="webcamupload" class="fa fa-camera"></span>';
    $content .= '</div>
    <span id="avatar-upload-progress"></span>';
    
    return $content;
}

add_action('wp','rcl_delete_avatar_action');
function rcl_delete_avatar_action(){
    global $wpdb,$user_ID,$rcl_avatar_sizes;
    if ( !isset( $_GET['rcl-action'] )||$_GET['rcl-action']!='delete_avatar' ) return false;
    if( !wp_verify_nonce( $_GET['_wpnonce'], $user_ID ) ) wp_die('Error');

    $result = delete_user_meta($user_ID,'rcl_avatar');

    if (!$result) wp_die('Error');

    $dir_path = RCL_UPLOAD_PATH.'avatars/';
    foreach($rcl_avatar_sizes as $key=>$size){
        unlink($dir_path.$user_ID.'-'.$size.'.jpg');
    }
    unlink($dir_path.$user_ID.'.jpg');

    wp_redirect( rcl_format_url(get_author_posts_url($user_ID)).'rcl-avatar=deleted' );  exit;
}

add_action('wp','rcl_notice_avatar_deleted');
function rcl_notice_avatar_deleted(){
    if (isset($_GET['rcl-avatar'])&&$_GET['rcl-avatar']=='deleted') 
        rcl_notice_text(__('Your avatar has been removed','wp-recall'),'success');
}

add_action('wp_ajax_rcl_avatar_upload', 'rcl_avatar_upload');
function rcl_avatar_upload(){
    
        rcl_verify_ajax_nonce();

	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	require_once(ABSPATH . "wp-admin" . '/includes/media.php');

	global $user_ID, $rcl_options, $rcl_avatar_sizes;

	if(!$user_ID) return false;

	$upload = array();
	$coord = array();

	$maxsize = ($rcl_options['avatar_weight'])? $rcl_options['avatar_weight']: $maxsize = 2;
	$tmpname = current_time('timestamp').'.jpg';

	$dir_path = RCL_UPLOAD_PATH.'avatars/';
	$dir_url = RCL_UPLOAD_URL.'avatars/';
	if(!is_dir($dir_path)){
		mkdir($dir_path);
		chmod($dir_path, 0755);
	}

	$tmp_path = $dir_path.'tmp/';
	$tmp_url = $dir_url.'tmp/';
	if(!is_dir($tmp_path)){
		mkdir($tmp_path);
		chmod($tmp_path, 0755);
	}else{
		foreach (glob($tmp_path.'*') as $file){
			unlink($file);
		}
	}

	if($_POST['src']){
		$data = $_POST['src'];
		$data = str_replace('data:image/png;base64,', '', $data);
		$data = str_replace(' ', '+', $data);
		$data = base64_decode($data);
		$upload['file']['type'] = 'image/png';
		$upload['file']['name'] = $tmpname;
		$upload['file']['tmp_name'] = $tmp_path.$tmpname;
		$upload['file']['size'] = file_put_contents($upload['file']['tmp_name'], $data);
                $mime = explode('/',$upload['file']['type']);
	}else{
		if($_FILES['userpicupload']){
			foreach($_FILES['userpicupload'] as $key => $data){
				$upload['file'][$key] = $data;
			}
		}

		if($_POST['coord']){
			$viewimg = array();
			list($coord['x'],$coord['y'],$coord['w'],$coord['h']) =  explode(',',$_POST['coord']);
			list($viewimg['width'],$viewimg['height']) =  explode(',',$_POST['image']);
		}

                $mime = explode('/',$upload['file']['type']);

		$tps = explode('.',$upload['file']['name']);
		$cnt = count($tps);
		if($cnt>2){
			$type = $mime[$cnt-1];
			$filename = str_replace('.','',$filename);
			$filename = str_replace($type,'',$filename).'.'.$type;
		}
		$filename = str_replace(' ','',$filename);
	}

	$mb = $upload['file']['size']/1024/1024;

	if($mb>$maxsize){
		$res['error'] = __('Size exceeded','wp-recall');
		echo json_encode($res);
		exit;
	}

    $ext = explode('.',$filename);

	if($mime[0]!='image'){
		$res['error'] = __('The file is not an image','wp-recall');
		echo json_encode($res);
		exit;
	}

	list($width,$height) = getimagesize($upload['file']['tmp_name']);

	if($coord){

		//Отображаемые размеры
		$view_width = $viewimg['width'];
		$view_height = $viewimg['height'];

		//Получаем значение коэфф. увеличения и корректируем значения окна crop
		$pr = 1;
		if($view_width<$width){
			$pr = $width/$view_width;
		}

		$left = $pr*$coord['x'];
		$top = $pr*$coord['y'];

		$thumb_width = $pr*$coord['w'];
		$thumb_height = $pr*$coord['h'];

		$thumb = imagecreatetruecolor($thumb_width, $thumb_height);

		if($ext[1]=='gif'){
			$image = imageCreateFromGif($upload['file']['tmp_name']);
			imagecopy($thumb, $image, 0, 0, $left, $top, $width, $height);
		}else{
                    if($mime[1]=='png'){
                        $image = imageCreateFromPng($upload['file']['tmp_name']);
                    }else{
                        $jpg = rcl_check_jpeg($upload['file']['tmp_name'], true );
                        if(!$jpg){
                                $res['error'] = __('The downloaded image is incorrect','wp-recall');
                                echo json_encode($res);
                                exit;
                        }
                        $image = imagecreatefromjpeg($upload['file']['tmp_name']);
                    }

                    imagecopy($thumb, $image, 0, 0, $left, $top, $width, $height);
		}
		imagejpeg($thumb, $tmp_path.$tmpname, 100);

		$src_size = $thumb_width;
	}

	if(!$src_size){
		if($width>$height) $src_size = $height;
		else $src_size = $width;
	}
        
        array_map("unlink", glob($dir_path.$user_ID."-*.jpg"));

	$rcl_avatar_sizes[999] = $src_size;
	foreach($rcl_avatar_sizes as $key=>$size){
		$filename = '';
		if($key!=999){
			$filename = $user_ID.'-'.$size.'.jpg';
		}else{
			$filename = $user_ID.'.jpg';
			$srcfile_url = $dir_url.$filename;
		}
		$file_src = $dir_path.$filename;

		if($coord){
			$rst = rcl_crop($tmp_path.$tmpname,$size,$size,$file_src);
		}else{
			$rst = rcl_crop($upload['file']['tmp_name'],$size,$size,$file_src);
		}
	}

	if (!$rst){
		$res['error'] = __('Error download','wp-recall');
		echo json_encode($res);
		exit;
	}

	if($rst){

                if(function_exists('ulogin_get_avatar')){
                    delete_user_meta($user_ID, 'ulogin_photo');
                }

		update_user_meta( $user_ID,'rcl_avatar',$srcfile_url );

		if(!$coord) copy($file_src,$tmp_path.$tmpname);

		$res['avatar_url'] = $tmp_url.$tmpname;
		$res['success'] = __('Avatar successfully uploaded','wp-recall');
	}

	echo json_encode($res);
	exit;
}