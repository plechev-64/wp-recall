<?php
add_action('wp_ajax_rcl_group_avatar_upload', 'rcl_group_avatar_upload');
function rcl_group_avatar_upload(){
    
    rcl_verify_ajax_nonce();

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

    global $user_ID, $rcl_options, $rcl_avatar_sizes;

    if(!$user_ID) return false;

    $upload = array();

    $group_id = $_POST['group_id'];

    $maxsize = ($rcl_options['avatar_weight'])? $rcl_options['avatar_weight']: $maxsize = 2;
    $tmpname = current_time('timestamp').'.jpg';

    if($_FILES['uploadfile']){
            foreach($_FILES['uploadfile'] as $key => $data){
                    $upload['file'][$key] = $data;
            }
    }

    $filename = $upload['file']['name'];

    $mime = explode('/',$upload['file']['type']);

    $tps = explode('.',$upload['file']['name']);
    $cnt = count($tps);
    if($cnt>2){
            $type = $mime[$cnt-1];
            $filename = str_replace('.','',$filename);
            $filename = str_replace($type,'',$filename).'.'.$type;
    }
    $filename = str_replace(' ','',$filename);


    $mb = $upload['file']['size']/1024/1024;

    if($mb>$maxsize){
        $res['error'] = __('File size exceeds maximum size','wp-recall');
        echo json_encode($res);
        exit;
    }

    $ext = explode('.',$filename);

    if($mime[0]!='image'){
        $res['error'] = __('The file is not an image','wp-recall');
        echo json_encode($res);
        exit;
    }

    $image = wp_handle_upload( $_FILES['uploadfile'], array('test_form' => FALSE) );
    if($image['file']){

        if($avatar_id = rcl_get_group_option($group_id,'avatar_id')) wp_delete_post($avatar_id,true);

        $attachment = array(
                'post_mime_type' => $image['type'],
                'post_title' => 'image_group_'.$group_id,
                'post_content' => $image['url'],
                'guid' => $image['url'],
                'post_parent' => '',
                'post_status' => 'inherit'
        );

        $imade_id = wp_insert_attachment( $attachment, $image['file'] );
        $attach_data = wp_generate_attachment_metadata( $imade_id, $image['file'] );
        wp_update_attachment_metadata( $imade_id, $attach_data );

        rcl_update_group_option($group_id,'avatar_id',$imade_id);
    }else{
        $res['error'] = __('Error','wp-recall').'!';
        echo json_encode($res);
        exit;
    }

    $res['avatar_url'] = $image['url'];
    $res['success'] = __('Avatar has been successfully uploaded','wp-recall');

    echo json_encode($res);
    exit;
}