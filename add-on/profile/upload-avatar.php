<?php
add_action('wp_ajax_rcl_avatar_upload', 'rcl_avatar_upload');
function rcl_avatar_upload(){

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
		if($_FILES['uploadfile']){
			foreach($_FILES['uploadfile'] as $key => $data){
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
		$res['error'] = 'Превышен размер!';
		echo json_encode($res);
		exit;
	}

    $ext = explode('.',$filename);

	if($mime[0]!='image'){
		$res['error'] = 'Файл не является изображением!';
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
                                $res['error'] = 'Загруженое изображение некорректно!';
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
		$res['error'] = 'Ошибка загрузки!';
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
		$res['success'] = 'Аватар успешно загружен!';
	}

	echo json_encode($res);
	exit;
}

function rcl_check_jpeg($f, $fix=false ){
# [070203]
# check for jpeg file header and footer - also try to fix it
    if ( false !== (@$fd = fopen($f, 'r+b' )) ){
        if ( fread($fd,2)==chr(255).chr(216) ){
            fseek ( $fd, -2, SEEK_END );
            if ( fread($fd,2)==chr(255).chr(217) ){
                fclose($fd);
                return true;
            }else{
                if ( $fix && fwrite($fd,chr(255).chr(217)) ){return true;}
                fclose($fd);
                return false;
            }
        }else{fclose($fd); return false;}
    }else{
        return false;
    }
}