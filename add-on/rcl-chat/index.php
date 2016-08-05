<?php

include_once 'core.php';

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_chat_scripts',10);
else:
    include_once 'addon-options.php';
endif;

function rcl_chat_scripts(){
    global $user_ID;

    rcl_enqueue_style('rcl-chat',rcl_addon_url('style.css', __FILE__));
    rcl_enqueue_script( 'rcl-chat-sounds', rcl_addon_url('js/ion.sound.min.js', __FILE__) );
    rcl_enqueue_script( 'rcl-chat', rcl_addon_url('js/scripts.js', __FILE__) ); 
        
    if($user_ID)    
        rcl_fileupload_scripts();

}

add_filter('rcl_init_js_variables','rcl_init_js_chat_variables',10);
function rcl_init_js_chat_variables($data){
    global $rcl_options;
    $data['chat']['sounds'] = rcl_addon_url('sounds/',__FILE__);
    $data['chat']['words'] = (isset($rcl_options['chat']['words']))? $rcl_options['chat']['words']: 400;
    $data['chat']['delay'] = (isset($rcl_options['chat']['delay']))? $rcl_options['chat']['delay']*1000: 15000;
    $data['chat']['inactivity'] = (isset($rcl_options['chat']['inactivity']))? $rcl_options['chat']['inactivity']: 10;
    $data['chat']['file_size'] = (isset($rcl_options['chat']['file_size']))? $rcl_options['chat']['file_size']: 2;
    return $data;
}

add_action('rcl_bar_setup','rcl_bar_add_chat_icon',10);
function rcl_bar_add_chat_icon(){
    global $user_ID;
    
    if(!is_user_logged_in()) return false;
    
    rcl_bar_add_icon('rcl-messages',
        array(
            'icon'=>'fa-envelope',
            'url'=>rcl_format_url(get_author_posts_url($user_ID),'chat'),
            'label'=>__('Messages','wp-recall'),
            'counter'=>rcl_chat_noread_messages_amount($user_ID)
        )
    );
}

add_filter('rcl_inline_styles','rcl_chat_add_inline_styles',10,2);
function rcl_chat_add_inline_styles($styles,$rgb){
    global $rcl_options;
 
    list($r, $g, $b) = $rgb;

    // разбиваем строку на нужный нам формат
    $rs = round($r * 0.95);
    $gs = round($g * 0.95);
    $bs = round($b * 0.95);

    // $r $g $b - родные цвета от кнопки
    // $rs $gs $bs - темный оттенок от кнопки
    $styles .= '#rcl-chat-noread-box .rcl-noread-users,'
        . '.rcl-mini-chat .rcl-chat-panel,'
            . '.rcl-chat .important-manager .important-shift'
        //. '.rcl-chat .message-box'
        . '{background:rgba('.$rs.','.$gs.','.$bs.',0.85);}'
        . '#rcl-chat-noread-box .rcl-noread-users a.active-chat::before'
        . '{border-color: transparent rgba('.$rs.','.$gs.','.$bs.',0.85) transparent transparent;}'
        //. '.rcl-chat .message-box::before 
            //{border-color: transparent rgba('.$rs.','.$gs.','.$bs.',0.85) transparent transparent;}'
        . '.rcl-chat .message-box::before 
            {border-color: transparent rgba('.$r.','.$g.','.$b.',0.15) transparent transparent;}'
        . '.rcl-chat .message-box {
                background: rgba('.$r.','.$g.','.$b.',0.15);
            }'
        . '.rcl-chat .nth .message-box::before 
            {border-color: transparent rgba('.$r.','.$g.','.$b.',0.35) transparent transparent;}'
        . '.rcl-chat .nth .message-box {
                background: rgba('.$r.','.$g.','.$b.',0.35);
            }
            .rcl-chat .message-manager a{
                color:rgb('.$rs.','.$gs.','.$bs.');
            }';
    
    return $styles;
}

add_action('init','rcl_add_chat_tab',10);
function rcl_add_chat_tab(){
    rcl_tab('chat','rcl_chat_tab',__('Chat','wp-recall'),array('public'=>1,'ajax-load'=>false,'class'=>'fa-comments-o'));
}

function rcl_chat_tab($office_id){
    global $user_ID;
    
    if($office_id==$user_ID) 
        return rcl_get_tab_user_contacts();
    
    if($user_ID){
        $chatdata = rcl_get_chat_private($office_id);
        $chat = $chatdata['content'];
    }else{
       $chat = '<div class="chat-notice">'
                . '<span class="notice-error">'.__('Sign in to be in correspondence with the user','wp-recall').'</span>'
                . '</div>'; 
    }
    
    return $chat;
}

function rcl_get_chat_private($user_id,$args=array()){
    global $user_ID,$rcl_options;
    
    $chat_room = rcl_get_private_chat_room($user_id,$user_ID);
    
    $file_upload = (isset($rcl_options['chat']['file_upload']))? $rcl_options['chat']['file_upload']: 0;

    $args = array_merge(array(
        'userslist'=>1,
        'file_upload'=>$file_upload,
        'chat_status'=>'private',
        'chat_room'=>$chat_room
    ),$args);
    
    require_once 'class-rcl-chat.php';
    $chat = new Rcl_Chat($args);

    return array(
                'content'=>$chat->get_chat(),
                'token' =>$chat->chat_token
            );
}

function rcl_chat_add_page_link_attributes($attrs){
    
    $attrs['onclick'] = 'rcl_chat_navi(this); return false;';
    $attrs['class'] = 'rcl-chat-page-link';
    
    return $attrs;
}

function rcl_get_tab_user_contacts(){
    global $user_ID;

    $content = '<h3>'.__('The user contacts','wp-recall').'</h3>';
    $content .= rcl_get_user_contacts_list($user_ID);
    
    return $content;
}

function rcl_get_user_contacts($user_id,$limit){
    global $wpdb;

    $messages = $wpdb->get_results(
        "SELECT t.* FROM ( "
        . "SELECT chat_messages.* FROM ".RCL_PREF."chat_messages AS chat_messages "
        . "INNER JOIN ".RCL_PREF."chat_users AS chat_users ON chat_messages.chat_id=chat_users.chat_id "
        . "WHERE chat_messages.private_key!='0' "
        . "AND (chat_messages.user_id='$user_id' OR chat_messages.private_key='$user_id') "
        . "AND chat_users.user_id='$user_id' "
        . "AND chat_users.user_status!='0' "
        . "ORDER BY chat_messages.message_time DESC "
        . ") "
        . " AS t "
        . "GROUP BY t.chat_id "
        . "ORDER BY t.message_time DESC "
        . "LIMIT $limit[0],$limit[1]"
        ,
        ARRAY_A
    );
    
    $messages = stripslashes_deep($messages);

    return $messages;
}

function rcl_get_user_contacts_list($user_id){
    global $wpdb;
    
    $amount = $wpdb->query(
        "SELECT COUNT(chat_messages.chat_id) FROM ".RCL_PREF."chat_messages AS chat_messages "
        . "INNER JOIN ".RCL_PREF."chat_users AS chat_users ON chat_messages.chat_id=chat_users.chat_id "
        . "WHERE chat_messages. private_key!='0' "
        . "AND (chat_messages.user_id='$user_id' OR chat_messages.private_key='$user_id') "
        . "AND chat_users.user_id='$user_id' "
        . "AND chat_users.user_status!='0' "
        . "GROUP BY chat_messages.chat_id "
    );
    
    if(!$amount){
        return '<p>'.__('While there is no contact. Start a chat with another user on his page','wp-recall').'</p>';
    }
    
    $inpage = 20;
    
    $pagenavi = new Rcl_PageNavi('chat-contacts',$amount,array('in_page'=>$inpage));
    
    $messages = rcl_get_user_contacts($user_id,array($pagenavi->offset,$inpage));
    
    foreach($messages as $k=>$message){
        $messages[$k]['user_id'] = ($message['user_id']==$user_id)? $message['private_key']: $message['user_id'];
    }

    $content = '<div class="rcl-chat-contacts">';
    
    $content .= '<div class="contacts-counter"><span>'.__('Total contacts','wp-recall').': '.$amount.'</span></div>';
    
    foreach($messages as $message){
        
        $class = (!$message['message_status'])? 'noread-message': '';
        
        $content .= '<div class="contact-box" data-contact="'.$message['user_id'].'">';
        $content .= '<a href="#" title="'.__('Delete contact','wp-recall').'" onclick="rcl_chat_remove_contact(this,'.$message['chat_id'].');return false;"><i class="fa fa-times" aria-hidden="true"></i></a>';
        $content .= '<a class="chat-contact '.$class.'" href="'.rcl_format_url(get_author_posts_url($message['user_id']),'chat').'">';
        
        $content .= '<div class="avatar-contact">'
                        . get_avatar($message['user_id'],50)
                . '</div>';
        
        $content .= '<div class="message-content">'
                . '<div class="message-meta">'
                    . '<span class="author-name">'.get_the_author_meta('display_name',$message['user_id']).'</span>'
                    . '<span class="time-message">'.rcl_human_time_diff($message['message_time']).' '.__('ago','wp-recall').'</span>'
                . '</div>'
                . '<div class="message-text">'.rcl_chat_excerpt($message['message_content']).'</div>'
                . '</div>';
        
        $content .= '</a>';
        
        $content .= '</div>';
    }
    
    $content .= '</div>';
    
    $content .= $pagenavi->pagenavi();
    
    return $content;
}

add_action('wp_footer','rcl_get_last_chats_box');
function rcl_get_last_chats_box(){
    global $user_ID;
    
    $messages = rcl_get_user_contacts($user_ID,array(0,5));
    
    if(!$messages) return false;
    
    //$users = array(317,8138,30,31,21);
    foreach($messages as $message){
        $user_id = ($message['user_id']==$user_ID)? $message['private_key']: $message['user_id'];
        $users[$user_id]['status'] = (!$message['message_status']&&$message['private_key']==$user_ID)? 0: 1;
        $users[$user_id]['chat_id'] = $message['chat_id'];
    }
    
    echo '<div id="rcl-chat-noread-box">';
    
        echo '<div class="rcl-mini-chat"></div>';

        echo '<div class="rcl-noread-users">';
            echo '<span class="messages-icon">'
                    . '<a href="'.rcl_format_url(get_author_posts_url($user_ID),'chat').'"><i class="fa fa-envelope" aria-hidden="true"></i></a>'
                . '</span>';
        foreach($users as $user_id=>$data){
            echo '<span class="rcl-chat-user contact-box" data-contact="'.$user_id.'">';
            echo '<a href="#" title="'.__('Delete contact','wp-recall').'" onclick="rcl_chat_remove_contact(this,'.$data['chat_id'].');return false;"><i class="fa fa-times" aria-hidden="true"></i></a>';
            echo '<a href="#" onclick="rcl_get_mini_chat(this,'.$user_id.'); return false;">';
            if(!$data['status']) 
                echo '<i class="fa fa-commenting" aria-hidden="true"></i>';
            echo get_avatar($user_id,40);
            echo '</a>';
            echo '</span>';
        }

        echo '</div>';

    echo '</div>';
}

function rcl_get_private_chat_room($user_1,$user_2){
    return ($user_1<$user_2)? 'private:'.$user_1.':'.$user_2: 'private:'.$user_2.':'.$user_1;
}

function rcl_chat_disable_oembeds() {
    remove_action('wp_head', 'wp_oembed_add_host_js');
}
add_action('init', 'rcl_chat_disable_oembeds', 9999);

add_shortcode('rcl-chat','rcl_chat_shortcode');
function rcl_chat_shortcode($atts){
    require_once 'class-rcl-chat.php';
    $chat = new Rcl_Chat($atts);
    return $chat->get_chat();
}

include_once 'actions.php';
include_once 'actions_cron.php';