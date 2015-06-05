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
	//print_r($upload['file']); exit;
        /*Array (
            [name] => Hydrangeas.jpg
            [type] => image/jpeg
            [tmp_name] => Z:\tmp\php7170.tmp
            [error] => 0 [size] => 620542 )*/

        $ext = explode('.',$upload['file']['name']);
        $mime = explode('/',$upload['file']['type']);
	if($mime[0]!='image'){
            //print_r($mime); exit;
            $res['result'] = '<div class="error">Файл не является изображением!</div>';
            echo json_encode($res);
            exit;
	}

        if(function_exists('ulogin_get_avatar')){
		delete_user_meta($user_ID, 'ulogin_photo');
	}

        $dir_path = TEMP_PATH.'avatars/';
        $dir_url = TEMP_URL.'avatars/';
        if(!is_dir($dir_path)){
            mkdir($dir_path);
            chmod($dir_path, 0755);
        }

        $filename = $user_ID.'.'.$ext[1];
        $file_src = $dir_path.$filename;

        require_once(RCL_PATH.'functions/rcl_crop.php');
        $crop = new Rcl_Crop();
        $rst = $crop->get_crop($upload['file']['tmp_name'],250,250,$file_src);

        if (!$rst){
            $res['result'] = '<div class="error">Ошибка!</div>';
            echo json_encode($res);
            exit;
        }

        $result = update_user_meta( $user_ID,'rcl_avatar',$dir_url.$filename );
        if($result){
                $res['result'] = '<div class="success">Аватар загружен!</div>';
        }


	echo json_encode($res);
	exit;
}