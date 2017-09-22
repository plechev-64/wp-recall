<?php

rcl_ajax_action('rcl_chat_remove_contact', false);
function rcl_chat_remove_contact(){
    global $user_ID;
    
    rcl_verify_ajax_nonce();
    
    $chat_id = intval($_POST['chat_id']);
    
    rcl_chat_update_user_status($chat_id,$user_ID,0);
    
    $res['remove'] = true;
    
    wp_send_json($res);
    
}

rcl_ajax_action('rcl_get_chat_page', true);
function rcl_get_chat_page(){
    
    rcl_verify_ajax_nonce();
    
    $chat_page = intval($_POST['page']);
    $in_page = intval($_POST['in_page']);
    $important = intval($_POST['important']);
    $chat_token = $_POST['token'];
    $chat_room = rcl_chat_token_decode($chat_token);
    
    if(!rcl_get_chat_by_room($chat_room)) 
        return false;
    
    require_once 'class-rcl-chat.php';
    
    $chat = new Rcl_Chat(
        array(
            'chat_room'=>$chat_room,
            'paged'=>$chat_page,
            'important'=>$important,
            'in_page'=>$in_page
        )
    );
  
    $res['content'] = $chat->get_messages_box();

    wp_send_json($res);

}

rcl_ajax_action('rcl_chat_add_message', false);
function rcl_chat_add_message(){
    global $user_ID;
    
    rcl_verify_ajax_nonce();
    
    $POST = wp_unslash($_POST['chat']);

    $chat_room = rcl_chat_token_decode($POST['token']);
    
    if(!rcl_get_chat_by_room($chat_room)) 
        return false;
    
    require_once 'class-rcl-chat.php';
    $chat = new Rcl_Chat(array('chat_room'=>$chat_room));

    $result = $chat->add_message($POST['message'],$POST['attachment']);
    
    if ( $result->errors ){
        $res['errors'] = $result->errors;    
        wp_send_json($res);
    }

    if(isset($result['errors'])){
        wp_send_json($result);
    }

    $res['content'] = $chat->get_message_box($result);

    wp_send_json($res);

}

rcl_ajax_action('rcl_get_chat_private_ajax', false);
function rcl_get_chat_private_ajax(){
    
    rcl_verify_ajax_nonce();
    
    $user_id = intval($_POST['user_id']);
    
    $chatdata = rcl_get_chat_private($user_id,array('avatar_size'=>30,'userslist'=>0));
    
    $chat = '<div class="rcl-chat-panel">'
            . '<a href="'.rcl_get_tab_permalink($user_id,'chat').'"><i class="fa fa-search-plus" aria-hidden="true"></i></a>'
            . '<a href="#" onclick="rcl_chat_close(this);return false;"><i class="fa fa-times" aria-hidden="true"></i></a>'
            . '</div>';
    $chat .= $chatdata['content'];

    $result['content'] = $chat;
    $result['chat_token'] = $chatdata['token'];
    
    wp_send_json($result);

}

rcl_ajax_action('rcl_chat_message_important', false);
function rcl_chat_message_important(){
    global $user_ID;
    
    rcl_verify_ajax_nonce();
    
    $message_id = intval($_POST['message_id']);
    
    $important = rcl_chat_get_message_meta($message_id,'important:'.$user_ID);
    
    if($important){
        rcl_chat_delete_message_meta($message_id,'important:'.$user_ID);
    }else{
        rcl_chat_add_message_meta($message_id,'important:'.$user_ID,1);
    }

    $result['important'] = ($important)? 0: 1;
    
    wp_send_json($result);

}

rcl_ajax_action('rcl_chat_important_manager_shift', false);
function rcl_chat_important_manager_shift(){
    global $user_ID;
    
    rcl_verify_ajax_nonce();
    
    $chat_token = $_POST['token'];
    $status_important = $_POST['status_important'];
    $chat_room = rcl_chat_token_decode($chat_token);
    
    if(!rcl_get_chat_by_room($chat_room)) 
        return false;
    
    require_once 'class-rcl-chat.php';
    $chat = new Rcl_Chat(array('chat_room'=>$chat_room,'important'=>$status_important));
 
    $res['content'] = $chat->get_messages_box();

    wp_send_json($res);

}

rcl_ajax_action('rcl_chat_delete_attachment', false);
function rcl_chat_delete_attachment(){
    global $user_ID;
    
    rcl_verify_ajax_nonce();
    
    $attachment_id = intval($_POST['attachment_id']);
    
    if(!$attachment_id) return false;
    
    if(!$post = get_post($attachment_id))
            return false;
    
    if($post->post_author!=$user_ID)
        return false;
    
    wp_delete_attachment($attachment_id);
    
    $result['remove'] = true;
    
    wp_send_json($result);

}

rcl_ajax_action('rcl_chat_ajax_delete_message', false);
function rcl_chat_ajax_delete_message(){
    global $current_user;
    
    rcl_verify_ajax_nonce();
    
    if(!$message_id = intval($_POST['message_id']))
            return false;

    if ( $current_user->user_level >= rcl_get_option('consol_access_rcl',7) ){
        rcl_chat_delete_message($message_id);
    }
    
    $result['remove'] = true;
    
    wp_send_json($result);

}

rcl_ajax_action('rcl_chat_upload', false);
function rcl_chat_upload(){
    global $rcl_options;

    rcl_verify_ajax_nonce();
    
    #допустимое расширение
    $valid_types = (isset($rcl_options['chat']['file_types'])&&$rcl_options['chat']['file_types'])? $rcl_options['chat']['file_types']: 'jpeg, jpg, png, zip, mp3';
    
    $valid_types = array_map('trim',explode(',',$valid_types));
    
    $timestamp = current_time('timestamp');
    
    $file = $_FILES['chat-upload'];

    $filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
    
    $type = $filetype['ext'];
    
    if (!in_array($type, $valid_types)){ 
        wp_send_json(array('error'=>__('Forbidden file extension. Allowed:','wp-recall').' '.implode(', ',$valid_types)));
    }
    
    if($upload = wp_handle_upload( $file, array('test_form' => FALSE) )){
        
        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
        require_once(ABSPATH . "wp-admin" . '/includes/file.php');
        require_once(ABSPATH . "wp-admin" . '/includes/media.php');

        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
            'post_content' => '',
            'post_excerpt' => 'rcl_chat_attachment:unattached',
            'guid' => $upload['url'],
            'post_parent' => 0,
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        
        $result['success'] = true;
        $result['attachment_id'] = $attach_id;
        $result['input_html'] = '<input type="hidden" name="chat[attachment]" value="'.$attach_id.'">';
        $result['icon_html'] = wp_get_attachment_image( $attach_id, array(100,100) ,true );
        
    }else{
        
        $result['error'] = true;
        
    }

    wp_send_json($result);

}

