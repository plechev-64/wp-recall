<?php

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_support_cover_uploader_scripts',10);
endif;

function rcl_support_cover_uploader_scripts(){   
    global $user_ID;
    if(rcl_is_office($user_ID)){
        rcl_fileupload_scripts();
        rcl_crop_scripts();
        rcl_enqueue_script( 'cover-uploader', RCL_URL.'functions/supports/js/uploader-cover.js',false,true );
    }
}

add_filter('rcl_init_js_variables','rcl_init_js_cover_variables',10);
function rcl_init_js_cover_variables($data){
    global $user_ID;
    
    if(rcl_is_office($user_ID)){
        $data['profile']['cover_size'] = 1;
        $data['local']['upload_size_cover'] = sprintf(__('Exceeds the maximum image size! Max. %s MB','wp-recall'),1);
        $data['local']['title_image_upload'] = __('Image being loaded','wp-recall');
    }
    
    return $data;
}

add_action('rcl_area_top','rcl_add_cover_uploader_button',10);
function rcl_add_cover_uploader_button(){
    global $user_ID;
    if(rcl_is_office($user_ID)){
        echo '<span class="rcl-cover-icon" title="'.__('Upload background','wp-recall').'">
                <i class="fa fa-image"></i>
                <input type="file" id="rcl-cover-upload" accept="image/*" name="cover-file">
            </span>';
    }
}

rcl_ajax_action('rcl_cover_upload', false);
function rcl_cover_upload(){
    
    rcl_verify_ajax_nonce();

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');
    
    global $user_ID;

    $upload = array();
    $coord = array();

    $tmpname = current_time('timestamp').'.jpg';

    $dir_path = RCL_UPLOAD_PATH.'covers/';
    $dir_url = RCL_UPLOAD_URL.'covers/';
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
        
        if($_FILES['cover-file']){
            foreach($_FILES['cover-file'] as $key => $data){
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

    if($mb > rcl_get_option('avatar_weight',2)){
        
        wp_send_json(array(
            'error' => __('Size exceeded','wp-recall')
        ));

    }

    $ext = explode('.',$filename);

    if($mime[0]!='image'){

        wp_send_json(array(
            'error' => __('The file is not an image','wp-recall')
        ));

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

                    wp_send_json(array(
                        'error' => __('The downloaded image is incorrect','wp-recall')
                    ));

                }
                $image = imagecreatefromjpeg($upload['file']['tmp_name']);
            }

            imagecopy($thumb, $image, 0, 0, $left, $top, $width, $height);
        }
        imagejpeg($thumb, $tmp_path.$tmpname, 100);
        
    }

    $filename = $user_ID.'.jpg';
    $srcfile_url = $dir_url.$filename;

    $file_src = $dir_path.$filename;
    
    do_action('rcl_before_cover_upload');

    if($coord){
        $rst = rcl_crop($tmp_path.$tmpname,$thumb_width,$thumb_height,$file_src);
    }else{
        $rst = rcl_crop($upload['file']['tmp_name'],$width,$height,$file_src);
    }


    if ( is_wp_error( $rst )){

        wp_send_json(array(
            'error' => __('Download error','wp-recall')
        ));

    }

    update_user_meta( $user_ID,'rcl_cover',$srcfile_url );
    
    do_action('rcl_cover_upload');

    if(!$coord) copy($file_src,$tmp_path.$tmpname);

    wp_send_json(array(
        'cover_url' => $tmp_url.$tmpname,
        'success' => __('Image successfully uploaded','wp-recall')
    ));

}