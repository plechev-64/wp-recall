<?php

function rcl_insert_chat($chat_room,$chat_status){
    global $wpdb;

    $wpdb->insert(
        RCL_PREF.'chats',
        array(
            'chat_room'=>$chat_room,
            'chat_status'=>$chat_status
        )
    );
    
    $chat_id = $wpdb->insert_id;
    
    do_action('rcl_insert_chat',$chat_id);

    return $chat_id;

}

function rcl_get_chat($chat_id){
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM ".RCL_PREF."chats WHERE chat_id = '$chat_id'");
}

function rcl_get_chat_by_room($chat_room){
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM ".RCL_PREF."chats WHERE chat_room = '$chat_room'");
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
    global $wpdb;
    
    //получаем все сообщения в этом чате
    $messages = rcl_chat_get_messages($chat_id,$user_id);

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

function rcl_get_chats($args){
    global $wpdb;
    
    $user_id = (isset($args['user_id']))? $args['user_id']: 0;
    
    if(!$user_id) return false;
    
    $query = array(
        'join'=>array(),
        'select'=>array(),
        'where'=>array(),
        'order'=>'',
        'orderby'=>'',
        'groupby'=>''
    );
    
    $chat_status = (isset($args['chat_status']))? $args['chat_status']: '';
    
    $sql = "SELECT chats.* FROM ".RCL_PREF."chats AS chats ";
    
    if($chat_status){
        $query['where'][] = "chats.chat_status='$chat_status'";
    }
    
    if($user_id){
        $query['join'][] = "INNER JOIN ".RCL_PREF."chat_messages AS chat_messages ON chats.chat_id=chat_messages.chat_id";
        $query['where'][] = "(chat_messages.user_id='$user_id' OR chat_messages.private_key='$user_id')";
    }
    
    if($chat_status=='private'){
        $query['where'][] = "chat_messages.private_key!='0'";
    }
    
    if($query['join']){
        $sql .= " ".implode(" ",$query['join'])." ";
    }
    
    if($query['where']){
        $sql .= " WHERE ";
        $sql .= implode(" AND ",$query['where']);
    }
    
    if($query['groupby']){
        $sql .= " GROUP BY ".$query['group']." ";
    }
    
    $chats = $wpdb->get_results($sql);
    
    return $chats;
}

function rcl_chat_get_users($chat_id){
    global $wpdb;
    return $wpdb->get_col("SELECT user_id FROM ".RCL_PREF."chat_users WHERE chat_id = '$chat_id'");
}

function rcl_chat_get_user_status($chat_id,$user_id){
    global $wpdb;
    return $wpdb->get_var("SELECT user_status FROM ".RCL_PREF."chat_users WHERE chat_id = '$chat_id' AND user_id='$user_id'");
}

function rcl_chat_insert_user($chat_id, $user_id, $status = 1, $activity = 1){
    global $wpdb;
    
    $user_activity = ($activity)? current_time('mysql'): '0000-00-00 00:00:00';
    
    $result = $wpdb->insert(
        RCL_PREF.'chat_users',
        array(
            'room_place'=>$chat_id.':'.$user_id,
            'chat_id'=>$chat_id,
            'user_id'=>$user_id,
            'user_activity'=>$user_activity,
            'user_write'=>0,
            'user_status'=>$status
        )
    );

    return $result;
}

function rcl_chat_delete_message($message_id){
    global $wpdb;
    
    $result = $wpdb->query("DELETE FROM ".RCL_PREF."chat_messages WHERE message_id='$message_id'");
    
    do_action('rcl_chat_delete_message',$message_id);
    
    return $result;
}

function rcl_chat_get_messages($chat_id,$user_id = false){
    global $wpdb;
    
    $sql = "SELECT * FROM ".RCL_PREF."chat_messages ";
    
    $where = array("chat_id = '$chat_id'");
    
    if($user_id) 
        $where[] = "user_id = '$user_id'";
    
    if($where){
        $sql .= "WHERE ";
        $sql .= implode(" AND ",$where);
    }

    return $wpdb->get_results($sql);
}

function rcl_chat_get_message_meta($message_id,$meta_key){
    global $wpdb;
    return $wpdb->get_var("SELECT meta_value FROM ".RCL_PREF."chat_messagemeta WHERE message_id='$message_id' AND meta_key = '$meta_key'");
}

function rcl_chat_add_message_meta($message_id,$meta_key,$meta_value){
    global $wpdb;
    $result = $wpdb->insert(
        RCL_PREF.'chat_messagemeta',
        array(
            'message_id'=>$message_id,
            'meta_key'=>$meta_key,
            'meta_value'=>$meta_value
        )
    );
    return $result;
}

function rcl_chat_delete_message_meta($message_id,$meta_key = false){
    global $wpdb;
    
    $sql = "DELETE FROM ".RCL_PREF."chat_messagemeta WHERE message_id = '$message_id'";
    
    if($meta_key) $sql .= "AND meta_key = '$meta_key'";
    
    return $wpdb->query($sql);
}

function rcl_chat_update_user_status($chat_id,$user_id,$status){
    global $wpdb;
    
    $result = $wpdb->query("INSERT INTO ".RCL_PREF."chat_users "
        . "(`room_place`, `chat_id`, `user_id`, `user_activity`, `user_write`, `user_status`) "
        . "VALUES('$chat_id:$user_id', $chat_id, $user_id, '".current_time('mysql')."', 0, $status) "
        . "ON DUPLICATE KEY UPDATE user_status='$status'");

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
    
    $string = esc_textarea($string);
    
    if(iconv_strlen($string, 'utf-8')<=$max) return $string;

    $string = substr($string, 0, $max);
    $string = rtrim($string, "!,.-");
    $string = substr($string, 0, strrpos($string, ' '));
    return $string."… ";
}

function rcl_chat_noread_messages_amount($user_id){
    global $wpdb;
    
    $amount = $wpdb->get_var(
        "SELECT COUNT(chat_messages.message_id) FROM ".RCL_PREF."chat_messages AS chat_messages "
        . "WHERE chat_messages.private_key='$user_id' "
        . "AND chat_messages.message_status='0' "
    );
    
    return $amount;
    
}

function rcl_chat_get_important_messages($user_id,$limit){
    global $wpdb;
    
    $messages = $wpdb->get_results(
        "SELECT chat_messages.* FROM ".RCL_PREF."chat_messages AS chat_messages "
        . "INNER JOIN ".RCL_PREF."chat_messagemeta AS chat_messagemeta ON chat_messages.message_id=chat_messagemeta.message_id "
        . "WHERE chat_messagemeta.meta_key='important:$user_id' "
        . "ORDER BY chat_messages.message_time DESC "
        . "LIMIT $limit[0],$limit[1]"
        ,
        ARRAY_A
    );
    
    $messages = stripslashes_deep($messages);

    return $messages;
}

function rcl_chat_count_important_messages($user_id){
    global $wpdb;
    
    $amount = $wpdb->get_var(
        "SELECT COUNT(chat_messages.message_id) FROM ".RCL_PREF."chat_messages AS chat_messages "
        . "INNER JOIN ".RCL_PREF."chat_messagemeta AS chat_messagemeta ON chat_messages.message_id=chat_messagemeta.message_id "
        . "WHERE chat_messagemeta.meta_key='important:$user_id'"
    );
    
    return $amount;
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