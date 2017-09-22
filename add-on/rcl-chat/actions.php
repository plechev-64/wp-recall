<?php

add_filter('rcl_chat_messages','rcl_chat_messages_add_important_meta',10);
function rcl_chat_messages_add_important_meta($messages){
    global $wpdb,$user_ID;
    
    if(!$messages) return $messages;
    
    $ids = array();
    foreach($messages as $message){
        $ids[] = $message['message_id'];
    }
    
    $metas = $wpdb->get_results("SELECT * FROM ".RCL_PREF."chat_messagemeta WHERE message_id IN (".implode(',',$ids).") AND meta_key = 'important:$user_ID' AND meta_value = '1'");
    
    if(!$metas) return $messages;
    
    $important = array();
    foreach($metas as $meta){
        $important[$meta->message_id] = $meta->meta_value;
    }
    
    foreach($messages as $k=>$message){
        $messages[$k]['important'] = (isset($important[$message['message_id']]))? 1: 0;
    }
    
    return $messages;
}

add_filter('rcl_chat_messages','rcl_chat_messages_add_attachments_meta',10);
function rcl_chat_messages_add_attachments_meta($messages){
    global $wpdb,$user_ID;
    
    if(!$messages) return $messages;
    
    $ids = array();
    foreach($messages as $message){
        $ids[] = $message['message_id'];
    }
    
    $metas = $wpdb->get_results("SELECT * FROM ".RCL_PREF."chat_messagemeta WHERE message_id IN (".implode(',',$ids).") AND meta_key = 'attachment'");
    
    if(!$metas) return $messages;
    
    $attachments = array();
    foreach($metas as $meta){
        $attachments[$meta->message_id] = $meta->meta_value;
    }
    
    foreach($messages as $k=>$message){
        $messages[$k]['attachment'] = (isset($attachments[$message['message_id']]))? $attachments[$message['message_id']]: 0;
    }
    
    return $messages;
}

add_action('rcl_chat_insert_message','rcl_chat_add_user_contact',10);
function rcl_chat_add_user_contact($message){
    global $wpdb;
    $chat = rcl_get_chat($message['chat_id']);
    if($chat->chat_status=='private'){
        $result = $wpdb->update(
            RCL_PREF.'chat_users',
            array(
                'user_status'=>1
            ),
            array(
                'chat_id'=>$message['chat_id'],
                'user_status'=>0
            )
        );
    }
}

add_filter('rcl_pre_insert_chat_message','rcl_chat_check_message_blocked',10);
function rcl_chat_check_message_blocked($message){
    global $user_ID;
   
    if(!defined( 'DOING_AJAX' ) || !DOING_AJAX) return $message;
    if(!$message['private_key']) return $message;
    
    if(get_user_meta($message['private_key'],'rcl_black_list:'.$user_ID)){
        wp_send_json(array('error'=>__('You have been blocked on this chat','wp-recall')));
    }
    
    return $message;
}

add_action('rcl_chat_add_message','rcl_chat_update_attachment_data',10);
function rcl_chat_update_attachment_data($message){
    
    if(!isset($message['attachment'])) return false;
    
    wp_update_post(array(
        'ID'=>$message['attachment'],
        'post_excerpt'=>'rcl_chat_attachment:'.$message['message_id']
    ));
    
}

add_action('rcl_insert_chat','rcl_chat_insert_private_users',10);
function rcl_chat_insert_private_users($chat_id){
    global $wpdb;
    
    $chat = rcl_get_chat($chat_id);
    
    if($chat->chat_status=='private'){

        $key = explode(':',$chat->chat_room);
        
        rcl_chat_insert_user($chat_id,$key[1],1,0);
        rcl_chat_insert_user($chat_id,$key[2],1,0);
        
    }
}

add_action('rcl_chat_delete_message','rcl_chat_delete_message_data',10);
function rcl_chat_delete_message_data($message_id){
    
    $attachment_id = rcl_chat_get_message_meta($message_id,'attachment');

    if( $attachment_id ){
        wp_delete_attachment( $attachment_id );    
    }
    
    rcl_chat_delete_message_meta($message_id);
    
}

add_action('delete_attachment','rcl_chat_delete_message_attachment',10);
function rcl_chat_delete_message_attachment($attachment_id){
    global $wpdb;
    return $wpdb->query("DELETE FROM ".RCL_PREF."chat_messagemeta WHERE meta_value='$attachment_id' AND meta_key = 'attachment'");
}

add_action('delete_user','rcl_chat_delete_userdata',10);
function rcl_chat_delete_userdata($user_id){
    
    $Users = new Rcl_Chat_Users_Query();
    $Chats = new Rcl_Chats_Query();
    
    //получаем все чаты пользователя
    $chats = $Chats->get_results(array(
        'fields' => array(
            'chat_id',
            'chat_status'
        ),
        'number' => -1,
        'join_query' => array(
            array(
                'table' => $Users->query['table'],
                'on_chat_id' => 'chat_id',
                'user_id' => $user_id,
                'fields' => false
            )
        )
    ));
    
    if($chats){
        
        foreach($chats as $chat){

            //Если чат приватный
            if($chat->chat_status == 'private'){

                //удаляем чат с пользователями, сообщениями и их метаданными
                rcl_delete_chat($chat->chat_id);
            
                continue;
            }
            
            //удаляем из чата пользователя со всеми его сообщениями и их металаданными
            rcl_chat_delete_user($chat->chat_id,$user_id);
            
        }
        
    }
    
}
