<?php

function rcl_insert_attachment($attachment,$image,$id_post=false){
    $attach_id = wp_insert_attachment( $attachment, $image['file'], $id_post );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $image['file'] );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    if(!$id_post) rcl_update_tempgallery($attach_id,$image['url']);

    return rcl_get_html_attachment($attach_id,$attachment['post_mime_type']);
}

function rcl_add_attachment_thumbnail_button($content,$attachment_id,$mime){
        
    if($mime[0] != 'image') return $content;

    $content .= '<span class="set-thumbnail-post">'
             . '<a class="recall-button" href="#" onclick="rcl_get_post_thumbnail_html('.$attachment_id.');return false;">'.__('Make thumbnail','wp-recall').'</a>'
             . '</span>';

    return $content;

}

rcl_ajax_action('rcl_imagepost_upload', true);
function rcl_imagepost_upload(){
    global $user_ID;

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
    
    $formFields = new Rcl_Public_Form_Fields(array('post_type' => $post_type));
    
    if($formFields->exist_active_field('post_thumbnail'))
        add_filter('rcl_post_attachment_html','rcl_add_attachment_thumbnail_button', 10, 3);
    
    $valid_types = (isset($_POST['ext_types']) && $_POST['ext_types'])? array_map('trim',explode(',',$_POST['ext_types'])): array('jpeg', 'jpg', 'png', 'gif');

    $valid_types = apply_filters('rcl_upload_valid_types',$valid_types,$post_type);

    $files = array();
    foreach($_FILES['uploadfile'] as $key=>$fls){
        foreach($fls as $k=>$data){
            $files[$k][$key] = $data;
        }
    }

    foreach($files as $k=>$file){

        $filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

        if (!in_array(strtolower($filetype['ext']), $valid_types)){ 
            wp_send_json(array('error'=>__('Banned file extension. Resolved:','wp-recall').' '.implode(', ',$valid_types)));
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
            
            $attach_id = wp_insert_attachment( $attachment, $image['file'], $id_post );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $image['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            if(!$id_post) 
                rcl_update_tempgallery($attach_id,$image['url']);

            $res[$k]['string'] = rcl_get_html_attachment($attach_id,$attachment['post_mime_type']);
            $res[$k]['thumbnail_image'] = wp_get_attachment_image( $attach_id, 'thumbnail');
            $res[$k]['attachment_id'] = $attach_id;
        }

    }
    
    do_action('rcl_post_upload',$post);

    wp_send_json($res);

}