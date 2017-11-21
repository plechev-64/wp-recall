<?php

function rcl_get_chats($args){
    $chats = new Rcl_Chats_Query();
    return $chats->get_results($args);
}

function rcl_get_chat($chat_id){
    $chats = new Rcl_Chats_Query();
    return $chats->get_row(array(
        'chat_id' => $chat_id
    ));
}

function rcl_get_chat_by_room($chat_room){
    $chats = new Rcl_Chats_Query();
    return $chats->get_row(array(
        'chat_room' => $chat_room
    ));
}

function rcl_insert_chat($chat_room,$chat_status){
    global $wpdb;

    $result = $wpdb->insert(
        RCL_PREF.'chats',
        array(
            'chat_room'=>$chat_room,
            'chat_status'=>$chat_status
        )
    );
    
    if(!$result){
        rcl_add_log('rcl_insert_chat: '.__('Failed to add chat','wp-recall'), array($chat_room,$chat_status));
    }
    
    $chat_id = $wpdb->insert_id;
    
    do_action('rcl_insert_chat',$chat_id);

    return $chat_id;

}

function rcl_delete_chat($chat_id){
    global $wpdb;
    
    $result = $wpdb->query("DELETE FROM ".RCL_PREF."chats WHERE chat_id='$chat_id'");
    
    do_action('rcl_delete_chat',$chat_id);
    
    return $result;
}

add_action('rcl_delete_chat','rcl_chat_remove_users',10);
function rcl_chat_remove_users($chat_id){
    global $wpdb;
    
    $result = $wpdb->query("DELETE FROM ".RCL_PREF."chat_users WHERE chat_id='$chat_id'");
    
    do_action('rcl_chat_remove_users',$chat_id);
    
    return $result;
}

add_action('rcl_chat_remove_users','rcl_chat_remove_messages',10);
add_action('rcl_chat_delete_user','rcl_chat_remove_messages',10,2);
function rcl_chat_remove_messages($chat_id,$user_id = false){
    
    $args = array(
        'chat_id' => $chat_id
    );
    
    if($user_id)
        $args['user_id'] = $user_id;
    
    //получаем все сообщения в этом чате
    $messages = rcl_chat_get_messages($args);

    if($messages){
        foreach($messages as $message){
            //удаляем сообщение с метаданными
            rcl_chat_delete_message($message->message_id);
        }
    }
    
    do_action('rcl_chat_remove_messages',$chat_id,$user_id);
    
    return $result;
}

function rcl_chat_delete_user($chat_id,$user_id){
    global $wpdb;
    
    $result = $wpdb->query("DELETE FROM ".RCL_PREF."chat_users WHERE chat_id='$chat_id' AND user_id='$user_id'");
    
    do_action('rcl_chat_delete_user',$chat_id,$user_id);
    
    return $result;
}

function rcl_chat_get_users($chat_id){
    $users = new Rcl_Chat_Users_Query();
    return $users->get_col(array(
        'chat_id' => $chat_id,
        'fields' => array(
            'user_id'
        )
    ));
}

function rcl_chat_get_user_status($chat_id,$user_id){
    $users = new Rcl_Chat_Users_Query();
    return $users->get_var(array(
        'chat_id' => $chat_id,
        'user_id' => $user_id,
        'fields' => array(
            'user_status'
        )
    ));
}

function rcl_chat_insert_user($chat_id, $user_id, $status = 1, $activity = 1){
    global $wpdb;
    
    $user_activity = ($activity)? current_time('mysql'): '0000-00-00 00:00:00';
    
    $args = array(
        'room_place'=>$chat_id.':'.$user_id,
        'chat_id'=>$chat_id,
        'user_id'=>$user_id,
        'user_activity'=>$user_activity,
        'user_write'=>0,
        'user_status'=>$status
    );
    
    $result = $wpdb->insert(
        RCL_PREF.'chat_users',
        $args
    );
    
    if(!$result){
        rcl_add_log('rcl_chat_insert_user: '.__('Failed to add user to the chat','wp-recall'), $args);
    }

    return $result;
}

function rcl_chat_delete_message($message_id){
    global $wpdb;
    
    $result = $wpdb->query("DELETE FROM ".RCL_PREF."chat_messages WHERE message_id='$message_id'");
    
    do_action('rcl_chat_delete_message',$message_id);
    
    return $result;
}

function rcl_chat_get_messages($args){ 
    $messages = new Rcl_Chat_Messages_Query();
    return $messages->get_results($args);
}

function rcl_chat_get_message_meta($message_id,$meta_key){
    $messages = new Rcl_Chat_Messagemeta_Query();
    return $messages->get_var(array(
        'message_id' => $message_id,
        'meta_key' => $meta_key,
        'fields' => array(
            'meta_value'
        )
    ));
}

function rcl_chat_add_message_meta($message_id,$meta_key,$meta_value){
    global $wpdb;
    
    $args = array(
        'message_id'=>$message_id,
        'meta_key'=>$meta_key,
        'meta_value'=>$meta_value
    );
    
    $result = $wpdb->insert(
        RCL_PREF.'chat_messagemeta',
        $args
    );
    
    if(!$result){
        rcl_add_log('rcl_chat_add_message_meta: '.__('Failed to send mets data of the message','wp-recall'), $args);
    }
    
    return $result;
}

function rcl_chat_delete_message_meta($message_id,$meta_key = false){
    global $wpdb;
    
    $sql = "DELETE FROM ".RCL_PREF."chat_messagemeta WHERE message_id = '$message_id'";
    
    if($meta_key) $sql .= "AND meta_key = '$meta_key'";
    
    $result = $wpdb->query($sql);
    
    return $result;
}

function rcl_chat_update_user_status($chat_id,$user_id,$status){
    global $wpdb;
    
    $result = $wpdb->query("INSERT INTO ".RCL_PREF."chat_users "
        . "(`room_place`, `chat_id`, `user_id`, `user_activity`, `user_write`, `user_status`) "
        . "VALUES('$chat_id:$user_id', $chat_id, $user_id, '".current_time('mysql')."', 0, $status) "
        . "ON DUPLICATE KEY UPDATE user_status='$status'");
    
    if(!$result){
        rcl_add_log('rcl_chat_update_user_status: '.__('Failed to refresh user status in the chat','wp-recall'), array($chat_id,$user_id,$status));
    }

    return $result;
}

function rcl_chat_token_encode($chat_room){
    return base64_encode($chat_room);
}

function rcl_chat_token_decode($chat_token){
    return base64_decode($chat_token);
}

function rcl_chat_excerpt($string){
    $max = 120;
    
    $string = wp_kses($string, array());
    
    if(iconv_strlen($string, 'utf-8') <= $max) return $string;

    $string = substr($string, 0, $max);
    $string = rtrim($string, "!,.-");
    $string = substr($string, 0, strrpos($string, ' '));
    return $string."… ";
}

function rcl_chat_noread_messages_amount($user_id){
    $messages = new Rcl_Chat_Messages_Query();
    return $messages->count(array(
        'private_key' => $user_id,
        'message_status' => 0
    ));
}

function rcl_chat_get_important_messages($user_id,$limit){
    
    $messages = new Rcl_Chat_Messages_Query();
    $meta = new Rcl_Chat_Messagemeta_Query();
    
    $args = array(
        'join_query' => array(
            array(
                'join' => 'INNER',
                'table' => $meta->query['table'],
                'on_message_id' => 'message_id',
                'meta_key' => 'important:'.$user_id,
                'fields' => false
            )
        ),
        'orderby' => 'message_time',
        'offset' => $limit[0],
        'number' => $limit[1],
        'return_as' => ARRAY_A
    );
    
    $messagesData = stripslashes_deep($messages->get_results($args));

    return $messagesData;
}

function rcl_chat_count_important_messages($user_id){
    
    $messages = new Rcl_Chat_Messages_Query();
    $meta = new Rcl_Chat_Messagemeta_Query();
    
    $args = array(
        'join_query' => array(
            array(
                'join' => 'INNER',
                'table' => $meta->query['table'],
                'on_message_id' => 'message_id',
                'meta_key' => 'important:'.$user_id
            )
        )
    );

    return $messages->count($args);
}

function rcl_chat_get_new_messages($post){
    global $user_ID;

    $chat_room = rcl_chat_token_decode($post->token);
    
    if(!rcl_get_chat_by_room($chat_room)) 
        return false;
    
    $content = '';
    
    require_once 'class-rcl-chat.php';
    $chat = new Rcl_Chat(array(
                'chat_room'=>$chat_room,
                'user_write'=> $post->user_write
            ));
    
    if($post->last_activity){

        $chat->query['where'][] = "message_time > '$post->last_activity'";
        if($user_ID) $chat->query['where'][] = "user_id != '$user_ID'";

        $messages = $chat->get_messages();

        if($messages){

            krsort($messages);

            foreach($messages as $k=>$message){
                $content .= $chat->get_message_box($message);
            }
            
            $chat->read_chat($chat->chat_id);

        }

        $res['content'] = $content;

    }

    if($activity = $chat->get_current_activity()) 
            $res['users'] = $activity;    
    
    $res['success'] = true;
    $res['token'] = $post->token;    
    $res['current_time'] = current_time('mysql');

    return $res;
}