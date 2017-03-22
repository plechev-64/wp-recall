<?php

function rcl_insert_attachment($attachment,$image,$id_post=false){
    $attach_id = wp_insert_attachment( $attachment, $image['file'], $id_post );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $image['file'] );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    if(!$id_post) rcl_update_tempgallery($attach_id,$image['url']);

    return rcl_get_html_attachment($attach_id,$attachment['post_mime_type']);
}

add_action('wp_ajax_rcl_imagepost_upload', 'rcl_imagepost_upload');
add_action('wp_ajax_nopriv_rcl_imagepost_upload', 'rcl_imagepost_upload');
function rcl_imagepost_upload(){
    global $rcl_options,$user_ID;

    rcl_verify_ajax_nonce();

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');
    
    $id_post = false;

    if(isset($_POST['post_id']) && $_POST['post_id']!='undefined' && $_POST['post_id']){
        $id_post = intval($_POST['post_id']);
        $post = get_post($id_post);
    }
        
    $post_type = $_POST['post_type'];

    $valid_types = apply_filters('rcl_upload_valid_types',array('gif', 'jpg', 'png', 'jpeg'),$post_type);

    $files = array();
    foreach($_FILES['uploadfile'] as $key=>$fls){
        foreach($fls as $k=>$data){
            $files[$k][$key] = $data;
        }
    }

    foreach($files as $k=>$file){

        $filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

        if (!in_array(strtolower($filetype['ext']), $valid_types)){ 
            echo json_encode(array('error'=>__('Banned file extension. Resolved:','wp-recall').' '.implode(', ',$valid_types)));
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
            
            if(!$user_ID){   
                $attachment['post_content'] = $_COOKIE['PHPSESSID'];
            }

            $res[$k]['string'] = rcl_insert_attachment($attachment,$image,$id_post);
        }

    }
    
    do_action('rcl_post_upload',$post);

    echo json_encode($res);
    exit;
}