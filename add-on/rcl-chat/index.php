<?php

include_once 'chats-query.php';
include_once 'core.php';

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_chat_scripts',10);
else:
    include_once 'addon-options.php';
endif;

function rcl_chat_scripts(){
    global $user_ID,$rcl_options;

    rcl_enqueue_style('rcl-chat',rcl_addon_url('style.css', __FILE__));
    rcl_enqueue_script( 'rcl-chat-sounds', rcl_addon_url('js/ion.sound.min.js', __FILE__) );
    rcl_enqueue_script( 'rcl-chat', rcl_addon_url('js/scripts.js', __FILE__) ); 
    
    $file_upload = (isset($rcl_options['chat']['file_upload']))? $rcl_options['chat']['file_upload']: 0;
    
    if($user_ID && $file_upload){
        
        $contact_panel = (isset($rcl_options['chat']['contact_panel']))? $rcl_options['chat']['contact_panel']: 0;
        
        if($contact_panel||rcl_is_office()){
            rcl_fileupload_scripts();
        }
    }

}

add_action('rcl_bar_setup','rcl_bar_add_chat_icon',10);
function rcl_bar_add_chat_icon(){
    global $user_ID,$rcl_options;
    
    if(!is_user_logged_in()) return false;
    
    //если выводится панель контактов
    if(isset($rcl_options['chat']['contact_panel'])&&$rcl_options['chat']['contact_panel']) return false;
    
    rcl_bar_add_icon('rcl-messages',
        array(
            'icon'=>'fa-envelope',
            'url'=>rcl_get_tab_permalink($user_ID,'chat'),
            'label'=>__('Messages','wp-recall'),
            'counter'=>rcl_chat_noread_messages_amount($user_ID)
        )
    );
}

add_filter('rcl_init_js_variables','rcl_init_js_chat_variables',10);
function rcl_init_js_chat_variables($data){
    global $rcl_options;
    
    $data['chat']['sounds'] = rcl_addon_url('sounds/',__FILE__);
    $data['chat']['delay'] = (isset($rcl_options['chat']['delay']) && $rcl_options['chat']['delay'])? $rcl_options['chat']['delay']: 15;
    $data['chat']['inactivity'] = (isset($rcl_options['chat']['inactivity']) && $rcl_options['chat']['inactivity'])? $rcl_options['chat']['inactivity']: 10;
    $data['chat']['file_size'] = (isset($rcl_options['chat']['file_size']) && $rcl_options['chat']['file_size'])? $rcl_options['chat']['file_size']: 2;
    
    $data['local']['empty_mess'] = __('Write something','wp-recall');
    $data['local']['max_words'] = __('Exceeds the maximum message size','wp-recall');
    $data['local']['upload_size_chat'] = sprintf(__('Exceeds the maximum file size! Max. %d MB','wp-recall'),(int)$data['chat']['file_size']);
    
    return $data;
}

add_filter('rcl_inline_styles','rcl_chat_add_inline_styles',10,2);
function rcl_chat_add_inline_styles($styles,$rgb){
 
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
        . '{background:rgba('.$rs.','.$gs.','.$bs.',0.85);}'
        . '#rcl-chat-noread-box .rcl-noread-users a.active-chat::before'
        . '{border-color: transparent rgba('.$rs.','.$gs.','.$bs.',0.85) transparent transparent;}'
        . '#rcl-chat-noread-box.left-panel .rcl-noread-users a.active-chat::before {
            border-color: transparent transparent transparent rgba('.$rs.','.$gs.','.$bs.',0.85);
        }'
        . '.rcl-chat .message-box::before 
            {border-color: transparent rgba('.$r.','.$g.','.$b.',0.15) transparent transparent;}'
        . '.rcl-chat .message-box {
                background: rgba('.$r.','.$g.','.$b.',0.15);
            }'
        . '#rcl-chat-noread-box .messages-icon .chat-new-messages{'
        . 'background: rgb('.$rs.','.$gs.','.$bs.');'
        . '}'
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
    global $user_ID;
    
    $tab_data = array(
        'id'=>'chat', 
        'name'=>__('Chat','wp-recall'),
        'supports'=>array('ajax'),
        'public'=>1,
        'icon'=>'fa-comments-o',
        'output'=>'menu',
        'content'=>array(
            array(
                'id'=>'private-contacts',
                'name'=>'Контакты',
                'icon'=>'fa-book',
                'callback'=>array(
                    'name'=>'rcl_chat_tab'
                )
            )
        )
    );
    
    if(rcl_is_office($user_ID)){
        $tab_data['content'][] = array(
                'id'=>'important-messages',
                'name'=>'Важные сообщения',
                'icon'=>'fa-star',
                'callback'=>array(
                    'name'=>'rcl_get_tab_user_important'
                )
            );
    }
    
    rcl_tab($tab_data);
}

function rcl_chat_tab($office_id){
    global $user_ID;
    
    if($office_id==$user_ID){
        return rcl_get_tab_user_contacts($office_id);
    } 
    
    if($user_ID){
        $chatdata = rcl_get_chat_private($office_id);
        $chat = $chatdata['content'];
    }else{
       $chat = '<div class="chat-notice">'
                . '<span class="notice-error">'.__('Sign in to send a message to the user','wp-recall').'</span>'
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
    $attrs['href'] = '#';
    $attrs['data']['post'] = false;
    
    return $attrs;
}

function rcl_get_tab_user_contacts(){
    global $user_ID;

    $content = '<h3>'.__('User contacts','wp-recall').'</h3>';
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
            . "LIMIT 18446744073709551615 "
            . ") "
            . "AS t "
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
        . "WHERE chat_messages.private_key!='0' "
        . "AND (chat_messages.user_id='$user_id' OR chat_messages.private_key='$user_id') "
        . "AND chat_users.user_id='$user_id' "
        . "AND chat_users.user_status!='0' "
        . "GROUP BY chat_messages.chat_id "
    );
    
    if(!$amount){
        return '<p>'.__('No contacts yet. Start a chat with another user on his page','wp-recall').'</p>';
    }
    
    $inpage = 20;
    
    $pagenavi = new Rcl_PageNavi('chat-contacts',$amount,array('in_page'=>$inpage));
    
    $messages = rcl_get_user_contacts($user_id,array($pagenavi->offset,$inpage));
    
    foreach($messages as $k=>$message){
        $messages[$k]['user_id'] = ($message['user_id']==$user_id)? $message['private_key']: $message['user_id'];
        $messages[$k]['author_id'] = $message['user_id'];
    }

    $content = '<div class="rcl-chat-contacts">';
    
    $content .= '<div class="contacts-counter"><span>'.__('Total number of contacts','wp-recall').': '.$amount.'</span></div>';
    
    foreach($messages as $message){
        
        $class = (!$message['message_status'])? 'noread-message': '';
        
        $content .= '<div class="contact-box" data-contact="'.$message['user_id'].'">';
        $content .= '<a href="#" title="'.__('Delete contact','wp-recall').'" onclick="rcl_chat_remove_contact(this,'.$message['chat_id'].');return false;"><i class="fa fa-times" aria-hidden="true"></i></a>';
        $content .= '<a class="chat-contact '.$class.'" href="'.rcl_get_tab_permalink($message['user_id'],'chat').'">';
        
        $content .= '<div class="avatar-contact">'
                        . get_avatar($message['user_id'],50)
                . '</div>';
        
        $content .= '<div class="message-content">'
                . '<div class="message-meta">'
                    . '<span class="author-name">'.get_the_author_meta('display_name',$message['user_id']).'</span>'
                    . '<span class="time-message">'.rcl_human_time_diff($message['message_time']).' '.__('ago','wp-recall').'</span>'
                . '</div>'
                . '<div class="message-text">'
                . (($user_id == $message['author_id'])? '<span class="master-avatar">'.get_avatar($user_id,25).'</span>': '')
                . rcl_chat_excerpt($message['message_content'])
                . '</div>'
                . '</div>';
        
        $content .= '</a>';
        
        $content .= '</div>';
    }
    
    $content .= '</div>';
    
    $content .= $pagenavi->pagenavi();
    
    return $content;
}

function rcl_get_tab_user_important($user_id){
    
    $amount_messages = rcl_chat_count_important_messages($user_id);
    
    if(!$amount_messages){
        return '<div class="chat-notice">'
                . '<span class="notice-error">'.__('No important messages yet','wp-recall').'</span>'
                . '</div>'; 
    }

    require_once 'class-rcl-chat.php';
    
    $chat = new Rcl_Chat();
    
    $navi = false;
        
    $content = '<div class="rcl-chat">';

        $content .= '<div class="chat-content">';

            $content .= '<div class="chat-messages-box">';

                $content .= '<div class="chat-messages">';

                $pagenavi = new Rcl_PageNavi('rcl-chat',$amount_messages,array('in_page'=>$chat->query['number']));

                $chat->offset = $pagenavi->offset;

                $messages = rcl_chat_get_important_messages($user_id,array($pagenavi->offset,$chat->query['number']));

                $messages = rcl_chat_messages_add_important_meta($messages);

                krsort($messages);

                foreach($messages as $k=>$message){
                    $content .= $chat->get_message_box($message);
                }

                $content .= '</div>';

            $content .= '</div>';

        $content .= '</div>';

    $content .= '</div>';
    
    $content .= $pagenavi->pagenavi();

    return $content;
    
}

add_action('wp_footer','rcl_get_last_chats_box',10);
function rcl_get_last_chats_box(){
    global $user_ID,$user_LK,$rcl_options;
    
    if(!isset($rcl_options['chat']['contact_panel'])||!$rcl_options['chat']['contact_panel']) return false;

    $messages = rcl_get_user_contacts($user_ID,array(0,5));
    
    if(!$messages) return false;

    foreach($messages as $message){
        $user_id = ($message['user_id']==$user_ID)? $message['private_key']: $message['user_id'];
        $users[$user_id]['status'] = (!$message['message_status']&&$message['private_key']==$user_ID)? 0: 1;
        $users[$user_id]['chat_id'] = $message['chat_id'];
    }
    
    $new_counter = rcl_chat_noread_messages_amount($user_ID);
    
    $class = array();
    
    $class[] = (!isset($rcl_options['chat']['place_contact_panel'])||!$rcl_options['chat']['place_contact_panel'])? 'right-panel': 'left-panel';
    
    $class[] = (isset($_COOKIE['rcl_chat_contact_panel'])&&$_COOKIE['rcl_chat_contact_panel'])? '': 'hidden-contacts';

    echo '<div id="rcl-chat-noread-box" class="'.implode(' ',$class).'">';
    
        echo '<div class="rcl-mini-chat"></div>';

        echo '<div class="rcl-noread-users">';
            echo '<span class="messages-icon">'
                    . '<a href="'.rcl_get_tab_permalink($user_ID,'chat').'" onclick="return rcl_chat_shift_contact_panel();">'
                    . '<i class="fa fa-envelope" aria-hidden="true"></i>';
            
                    if($new_counter){
                        echo '<span class="chat-new-messages">'.$new_counter.'</span>';
                    }

                    echo '</a>'
                . '</span>'
                . '<div class="chat-contacts">';
                    
        foreach($users as $user_id=>$data){
            
            if($user_id==$user_LK) continue;
            
            echo '<span class="rcl-chat-user contact-box" data-contact="'.$user_id.'">';
            echo '<a class="chat-delete-contact" href="#" title="'.__('Delete contact','wp-recall').'" onclick="rcl_chat_remove_contact(this,'.$data['chat_id'].');return false;"><i class="fa fa-times" aria-hidden="true"></i></a>';
            echo '<a href="#" onclick="rcl_get_mini_chat(this,'.$user_id.'); return false;">';
            if(!$data['status']) 
                echo '<i class="fa fa-commenting" aria-hidden="true"></i>';
            echo get_avatar($user_id,40);
            echo '</a>';
            echo '</span>';
        }
        
        echo '<span class="more-contacts">'
            . '<a href="'.rcl_get_tab_permalink($user_ID,'chat').'">'
            . '. . .';
            echo '</a>'
        . '</span>';
        
        echo '</div>';

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
    global $user_ID;
    
    $file_upload = (isset($atts['file_upload']))? $atts['file_upload']: 0;
    
    if($user_ID&&$file_upload)
        rcl_fileupload_scripts();
    
    require_once 'class-rcl-chat.php';
    $chat = new Rcl_Chat($atts);
    return $chat->get_chat();
}

add_action('rcl_chat','rcl_chat_reset_oembed_filter');
function rcl_chat_reset_oembed_filter(){
    remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
}

include_once 'actions.php';
include_once 'actions_cron.php';
include_once 'actions_ajax.php';