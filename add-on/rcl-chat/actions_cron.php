<?php

add_action('rcl_cron_daily','rcl_chat_daily_delete_messages',10);
function rcl_chat_daily_delete_messages(){
    global $wpdb,$rcl_options;
    
    $max = (isset($rcl_options['chat']['messages_amount']))? $rcl_options['chat']['messages_amount']: 100;
    
    if(!$max) return false;
    
    $chats = $wpdb->get_results(
            "SELECT chats.*, COUNT(chat_messages.message_id) AS amount_messages "
            . "FROM ".RCL_PREF."chats AS chats "
            . "INNER JOIN ".RCL_PREF."chat_messages AS chat_messages ON chats.chat_id=chat_messages.chat_id "
            . "WHERE chats.chat_status='private' "
            . "AND chat_messages.message_id NOT IN ("
                . "SELECT message_id FROM ".RCL_PREF."chat_messagemeta "
                . "WHERE meta_key LIKE 'important:%'"
            . ") "
            . "GROUP BY chats.chat_id "
            . "HAVING COUNT(chat_messages.message_id) > '$max'"
    );
    
    if(!$chats) return false;
    
    foreach($chats as $chat){
        
        if($chat->amount_messages <= $max) continue;
        
        $amount_delete = $chat->amount_messages - $max;
        
        $messages = $wpdb->get_results("SELECT message_id,message_status,private_key FROM ".RCL_PREF."chat_messages "
                . "WHERE message_id NOT IN ("
                    . "SELECT message_id FROM ".RCL_PREF."chat_messagemeta "
                    . "WHERE meta_key LIKE 'important:%'"
                . ") "
                . "AND chat_id='".$chat->chat_id."' "
                . "ORDER BY message_id ASC "
                . "LIMIT $amount_delete"
        );
        
        if(!$messages) continue;

        foreach($messages as $message){
            
            if($message->private_key && !$message->message_status) continue;
            
            rcl_chat_delete_message($message->message_id);
        }
        
    }

}

add_action('rcl_cron_daily','rcl_chat_delete_unattached_attachments',20);
function rcl_chat_delete_unattached_attachments(){
    global $wpdb;
    
    $unattaches = $wpdb->get_col(
        "SELECT ID FROM $wpdb->posts "
        . "WHERE post_type='attachment' "
        . "AND post_excerpt='rcl_chat_attachment:unattached'"
    );
    
    if(!$unattaches) return;

    foreach( $unattaches as $attachment_id )
        wp_delete_attachment( $attachment_id );
}

add_action('rcl_cron_hourly','rcl_chat_send_notify_messages',10);
function rcl_chat_send_notify_messages(){
    global $wpdb;
    
    $mailtext = rcl_get_option('messages_mail');

    $mess = $wpdb->get_results("SELECT * FROM ".RCL_PREF."chat_messages WHERE message_status='0' && private_key!='0' && message_time  > date_sub('".current_time('mysql')."', interval 1 hour)");
    
    if(!$mess) return false;
    
    $messages = array();
    foreach($mess as $m){
        $messages[$m->private_key][$m->user_id][] = $m->message_content;
    }
    
    rcl_add_log(__('Send notifications on unread messages','wp-recall'));

    foreach($messages as $addressat_id=>$data){
        $content = '';
        $to = get_the_author_meta('user_email',$addressat_id);

        $cnt = count($data);

        foreach($data as $author_id=>$array_messages){
            $url = rcl_get_tab_permalink($author_id,'chat');
            $content .= '<div style="overflow:hidden;clear:both;">
                <p>'.__('You were sent a private message','wp-recall').'</p>
                <div style="float:left;margin-right:15px;">'.get_avatar($author_id,60).'</div>'
                . '<p>'.__('from the user','wp-recall').' '.get_the_author_meta('display_name',$author_id).'</p>';
            
                if($mailtext) 
                    $content .= '<p><b>'.__('Message text','wp-recall').':</b></p>'
                            . '<p>'.implode('<br>',$array_messages).'</p>';
            
                $content .= '<p>'.__('You can read the message by clicking on the link:','wp-recall').' <a href="'.$url.'">'.$url.'</a></p>'
                . '</div>';
        }

        $title = __('For you','wp-recall').' '.$cnt.' '.__('new messages','wp-recall');
        
        rcl_mail($to, $title, $content);
    }

}

