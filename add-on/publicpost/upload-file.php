<?php

add_action('wp_ajax_rcl_imagepost_upload', 'rcl_imagepost_upload');
function rcl_imagepost_upload(){
    global $rcl_options,$user_ID;

    rcl_verify_ajax_nonce();

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

    if(!$user_ID) return false;

    if(isset($_POST['post_id'])&&$_POST['post_id']!='undefined') $id_post = intval($_POST['post_id']);
    
    $post_type = base64_decode($_POST['post_type']);
    
    $post = get_post($id_post);

    $valid_types = apply_filters('rcl_upload_valid_types',array('gif', 'jpg', 'png', 'jpeg'),$post_type);

    $files = array();
    foreach($_FILES['uploadfile'] as $key=>$fls){
        foreach($fls as $k=>$data){
            $files[$k][$key] = $data;
        }
    }

    foreach($files as $k=>$file){

        $filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

        if (!in_array($filetype['ext'], $valid_types)){ 
            echo json_encode(array('error'=>'Запрещенное расширение файла. Разрешено: '.implode(', ',$valid_types)));
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