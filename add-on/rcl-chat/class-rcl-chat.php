<?php

/**
 * @author Андрей
 */

class Rcl_Chat {
    
    public $chat_id = 0;
    public $chat = array();
    public $chat_room;
    public $chat_token;
    public $chat_status;
    public $important;
    public $file_upload;
    public $user_id;
    public $paged;
    public $userslist;
    public $avatar_size;
    public $offset;
    public $in_page;
    public $office_id;
    public $user_write;
    public $max_words;
    public $user_can;
    public $errors = array();
    public $query = array(
            'select'=>array('chat_messages.*'),
            'where'=>array(),
            'orderby'=>'chat_messages.message_id',
            'order'=>'DESC'           
        );

    function __construct($args = array()){
        global $user_ID,$rcl_options;
        
        if(!isset($args['chat_room'])) return false;

        add_filter( 'rcl_chat_message', 'wpautop', 11 );
        
        $this->user_id = $user_ID;
        $this->chat_room = $args['chat_room'];
        $this->chat_token = rcl_chat_token_encode($this->chat_room);
        
        $this->chat_status = (isset($args['chat_status']))? $args['chat_status']: 'general';
        $this->office_id = (isset($_POST['office_ID']))? $_POST['office_ID']: 0;
        $this->avatar_size = (isset($args['avatar_size']))? $args['avatar_size']: 50;
        $this->userslist = (isset($args['userslist']))? $args['userslist']: 0;
        $this->important = (isset($args['important']))? $args['important']: 0;
        $this->file_upload = (isset($args['file_upload']))? $args['file_upload']: 0;
        $this->max_words = (isset($rcl_options['chat']['words']))? $rcl_options['chat']['words']: 300;
        $this->in_page = (isset($rcl_options['chat']['in_page']))? $rcl_options['chat']['in_page']: 50;
        $this->chat = $this->get_chat_data($this->chat_room);
        $this->paged = (isset($args['paged']))? $args['paged']: 1;
        
        $this->user_write = (isset($_POST['chat']['message'])&&$_POST['chat']['message'])? 1: 0;
        
        if(!$this->chat){
            $this->setup_chat();
        }else{
            $this->chat_id = $this->chat['chat_id'];
        }
        
        $this->set_activity();
        
        $this->query['where'][] =  "chat_messages.chat_id = '$this->chat_id'";
        
        if($this->important){
            add_filter('rcl_chat_query',array(&$this,'add_important_query'),10);
        }
        
        $this->user_can = ($this->is_user_can())? 1: 0;

    }
    
    function get_chat_data($chat_room){
        global $wpdb;
        
        $chat_id = 0;
        
        if($chat_room){
            $chat = $wpdb->get_row("SELECT * FROM ".RCL_PREF."chats WHERE chat_room = '$chat_room'",ARRAY_A);
        }
        
        return $chat;
    }
    
    function read_chat($chat_id){
        global $wpdb;
        $wpdb->query("UPDATE ".RCL_PREF."chat_messages SET message_status = '1' WHERE chat_id = '$chat_id' AND user_id != '$this->user_id'");
    }
    
    function set_activity(){
        global $wpdb;
        
        $result = $wpdb->query("INSERT INTO ".RCL_PREF."chat_users "
                . "(`room_place`, `chat_id`, `user_id`, `user_activity`, `user_write`, `user_status`) "
                . "VALUES('$this->chat_id:$this->user_id', $this->chat_id, $this->user_id, '".current_time('mysql')."', 0, 1) "
                . "ON DUPLICATE KEY UPDATE user_activity = '".current_time('mysql')."', user_write='$this->user_write'");
    }
    
    function get_users_activity(){
        global $wpdb;
        $actives = $wpdb->get_results("SELECT user_id,user_write FROM ".RCL_PREF."chat_users WHERE chat_id='$this->chat_id' AND user_id!='$this->user_id' AND user_activity >= ('".current_time('mysql')."' - interval 1 minute)");
        return $actives;
    }
    
    function get_current_activity(){
        
        $users = $this->get_users_activity();
        
        $res = array(
            $this->user_id => $this->get_user_activity($this)
        );
        
        if($users){
            foreach($users as $user){
                $res[$user->user_id] = $this->get_user_activity($user); 
            }
        }
        
        return $res;
        
    }
    
    function get_user_activity($user){
        
        if(!$user->user_id){
            return array(
                'link'  =>  '<span>'.__('Guest','wp-recall').'</span>',
                'write' =>  0
            );
        }
        
        $write = ($user->user_id==$this->user_id)? 0: $user->user_write;
        
        return array(
                'link'  =>   '<a href="'.rcl_format_url(get_author_posts_url($user->user_id),'chat').'">'.get_the_author_meta('display_name', $user->user_id).'</a>',
                'write' =>   $write
            );
    }

    function add_error($code,$error_text){
        global $wp_errors;
        $wp_errors = new WP_Error();
        $wp_errors->add( $code, $error_text );
        return $wp_errors;
    }
    
    function is_errors(){
        global $wp_errors;
        if($wp_errors->errors) return true;
        return false;
    }
    
    function errors(){
        global $wp_errors;
        return $wp_errors;
    }
    
    function add_message($message,$attachment = false){

        $result = $this->insert_message($this->chat_id,$this->user_id,$message);
        
        if($this->is_errors()) 
            return $this->errors();
        
        if($attachment){
            rcl_chat_add_message_meta($result['message_id'],'attachment',$attachment);
            $result['attachment'] = $attachment;
        }
        
        do_action('rcl_chat_add_message',$result);

        return $result;
    }
    
    function setup_chat(){
        
        if(!$this->chat_id){
            $this->chat_id = $this->insert_chat($this->chat_room,$this->chat_status);
        }

        if($this->is_errors()) 
            return $this->errors();

        return $this->chat_id;
    }
    
    function insert_message($chat_id,$user_id,$message_text){
        global $wpdb;
        
        $private_key = 0;
                
        if($this->chat['chat_status']=='private'){
            $key = explode(':',$this->chat['chat_room']);
            $private_key = ($key[1]==$this->user_id)? $key[2]: $key[1];
            
            $user_block = get_user_meta($private_key,'rcl_black_list:'.$this->user_id);
            
            if($user_block){
                $this->add_error('insert_message',__('You have been blocked on chat','wp-recall'));
                return $this->errors();
            }
        }

        $message = array(
                'chat_id'=>$chat_id,
                'user_id'=>$user_id,
                'message_content'=>$message_text,
                'message_time'=>current_time('mysql'),
                'private_key'=>$private_key,
                'message_status'=>0,
            );
        
        $message = apply_filters('rcl_pre_insert_chat_message',$message);
        
        $result = $wpdb->insert(
            RCL_PREF.'chat_messages',
            $message
        );
        
        if(!$result){ 
            $this->add_error('insert_message',__('The message was not added','wp-recall'));
            return $this->errors();
        }
        
        do_action('rcl_chat_insert_message',$chat_id);
        
        $message['message_id'] = $wpdb->insert_id;
        
        $message = stripslashes_deep($message);
        
        return $message;

    }
    
    function insert_chat($chat_room,$chat_status){
        global $wpdb;
        
        $result = $wpdb->insert(
            RCL_PREF.'chats',
            array(
                'chat_room'=>$chat_room,
                'chat_status'=>$chat_status
            )
        );
        
        if(!$result){
            $this->add_error('insert_chat',__('Chat was not created','wp-recall'));
            return $this->errors();
        }
        
        $chat_id = $wpdb->insert_id;
        
        do_action('rcl_insert_chat',$chat_id);
        
        return $chat_id;

    }

    function get_chat(){
        
        if($this->chat_id&&$this->chat_status=='private'){
            $this->read_chat($this->chat_id);
        }
        
        $content = '<script>'
                . 'rcl_init_chat("'.$this->chat_token.'",'.$this->file_upload.');'
                . '</script>';

        $content .= '<div class="rcl-chat chat-'.$this->chat_status.'" data-token="'.$this->chat_token.'">';
                    
                    $content .= $this->get_messages_box();
                        
                    $content .= '<div class="chat-form">'
                            
                                    . $this->get_form()
 
                                . '</div>'
                            . '</div>';
        
        return $content;
        
    }
    
    function get_form(){
        global $user_ID;
        
        if(!$user_ID){
            $content = '<div class="chat-notice">'
                    . '<span class="notice-error">'.__('To post messages in the chat you need to login','wp-recall').'</span>'
                    . '</div>'
                    . '<form><input type="hidden" name="chat[token]" value="'.$this->chat_token.'"></form>';
            return $content;
        }

        $content = '<form action="" method="post">'
                    . '<div class="chat-form-media">'
                    . rcl_get_smiles('chat-area-'.$this->chat_id);

                if($this->file_upload){
                   $content .= '<span class="rcl-chat-uploader">'
                                . '<i class="fa fa-paperclip" aria-hidden="true"></i>'
                                . '<input name="chat-upload" type="file">'
                           . '</span>';
                }

                $content .= '</div>'
                    . '<textarea maxlength="'.$this->max_words.'" onkeyup="rcl_chat_words_count(event,this);" id="chat-area-'.$this->chat_id.'" name="chat[message]"></textarea>'
                    . '<span class="words-counter">'.$this->max_words.'</span>'
                    . '<input type="hidden" name="chat[token]" value="'.$this->chat_token.'">'
                    . '<input type="hidden" name="chat[status]" value="'.$this->chat_status.'">'
                    . '<input type="hidden" name="chat[userslist]" value="'.$this->userslist.'">'
                    . '<input type="hidden" name="chat[file_upload]" value="'.$this->file_upload.'">'
                    . '<div class="chat-preloader-file"></div>'
                    . '<a href="#" class="recall-button chat-submit" onclick="rcl_chat_add_message(this);return false;"><i class="fa fa-reply"></i> '.__('Send','wp-recall').'</a>'
                . '</form>';

        return $content;
    }
    
    function userslist(){
        $content = '<div class="chat-users-box">'
                        . '<span>'.__('In chat','wp-recall').':</span>'
                        . '<div class="chat-users"></div>'
                 . '</div>';
        return $content;
    }
    
    function get_messages_box(){
        
        $navi = false;
        
        $amount_messages = $this->count_messages();
        
        $content = '<div class="chat-content">';
        
        if($this->userslist)
            $content .= $this->userslist();

        $content .= '<div class="chat-messages-box">';
        
            $content .= '<div class="chat-meta">';
            if($this->user_id)
                $content .= $this->important_manager();
            $content .= '</div>';

            $content .= '<div class="chat-messages">';
        
        if($amount_messages){
            
            add_filter('rcl_page_link_attributes','rcl_chat_add_page_link_attributes',10);

            $pagenavi = new Rcl_PageNavi('rcl-chat',$amount_messages,array('in_page'=>$this->in_page,'ajax'=>true,'current_page'=>$this->paged));

            $this->offset = $pagenavi->offset;

            $messages = $this->get_messages();

            krsort($messages);

            foreach($messages as $k=>$message){
                $content .= $this->get_message_box($message);
            }

            $navi = $pagenavi->pagenavi();

            remove_filter('rcl_page_link_attributes','rcl_chat_add_page_link_attributes',10);

        }else{
            if($this->important)
                $notice = __('Important messages in this chat no','wp-recall');
            else
                $notice = __('There will be history','wp-recall');
            
            $content .= sprintf('<span class="anons-message">%s</span>',$notice);
        }

        $content .= '</div>';
        
        $content .= '<div class="chat-meta">';
        
        $content .= '<div class="chat-status"><span>......<i class="fa fa-pencil" aria-hidden="true"></i></span></div>';

        if($navi){
            $content .= $navi;
        }
        
        $content .= '</div>';

        $content .= '</div>';

        $content .= '</div>';
        
        return $content;
    }
    
    function query(){

        $this->query['from'] =  RCL_PREF."chat_messages AS chat_messages";  
        $this->query['offset']  =  $this->offset;
        $this->query['inpage']  =  $this->in_page;

        $query = apply_filters('rcl_chat_query',$this->query);
        
        return $query;
    }
    
    function get_sql($query){
        
        $sql[] = "SELECT ".implode(',',$query['select']);
        
        $sql[] = "FROM ".$query['from'];
        
        if($query['join']){
            $sql[] = implode(' ',$query['join']);
        }
        
        if($query['where']){
            $sql[] = "WHERE ".implode(' AND ',$query['where']);
        }
        if(isset($query['orderby'])){
            $sql[] = "ORDER BY ".$query['orderby']." ".$query['order'];
        }
        if(isset($query['offset'])){
            $sql[] = "LIMIT ".$query['offset'].",".$query['inpage'];
        }
        
        return implode(' ',$sql);
    }
    
    function get_messages(){
        
        global $wpdb;

        $messages = $wpdb->get_results($this->get_sql($this->query()),ARRAY_A);
        
        $messages = stripslashes_deep($messages);
        
        $messages = apply_filters('rcl_chat_messages',$messages);
        
        return $messages;
    }
    
    function count_messages(){
        
        global $wpdb;
        
        $query = $this->query();

        unset($query['select']);
        unset($query['offset']);
        unset($query['orderby']);
        
        $query['select'][] = 'COUNT(chat_messages.message_id)';

        $count = $wpdb->get_var($this->get_sql($query));

        return $count;
    }
    
    function get_message_box($message){
        
        $class = ($message['user_id']==$this->user_id)? 'nth': '';
        
        $content = '<div class="chat-message '.$class.'" data-message="'.$message['message_id'].'">'
            . '<span class="user-avatar">';
        
            if($message['user_id']!=$this->user_id) 
                $content .= '<a href="'.rcl_format_url(get_author_posts_url($message['user_id']),'chat').'">';
            
                $content .= get_avatar($message['user_id'],$this->avatar_size);
                
            if($message['user_id']!=$this->user_id)     
                $content .= '</a>';
            
            $content .= '</span>';
                
            if($this->user_id)
                $content .= $this->message_manager($message);
                    
                    $content .= '<div class="message-wrapper">'
                        . '<div class="message-box">'
                            . '<span class="author-name">'.get_the_author_meta('display_name',$message['user_id']).'</span>'
                            . '<div class="message-text">';

                                $content .= $this->the_content($message['message_content']);
                                
                                if($message['attachment'])
                                    $content .= $this->the_attachment($message['attachment']);
                                
                            $content .= '</div>'
                        . '</div>'
                    . '<span class="message-time"><i class="fa fa-clock-o" aria-hidden="true"></i> '.$message['message_time'].'</span>'
            . '</div>'
        . '</div>';
        
        return $content;
    }
    
    function message_manager($message){
        global $rcl_options,$current_user;
        
        $class = ($message['important'])? 'active-important': '';
        
        $content .= '<div class="message-manager">';
        
            $content .= '<span class="message-important '.$class.'">'
                            . '<a href="#" onclick="rcl_chat_message_important('.$message['message_id'].'); return false;">'
                                . '<i class="fa fa-star" aria-hidden="true"></i>'
                            . '</a>'
                        . '</span>';

            if ( $this->user_can ){

                $content .= '<span class="message-delete">'
                            . '<a href="#" onclick="rcl_chat_delete_message('.$message['message_id'].'); return false;">'
                                . '<i class="fa fa-trash" aria-hidden="true"></i>'
                            . '</a>'
                        . '</span>';
            }

        $content .= '</div>';
        
        return $content;
    }
    
    function is_user_can(){
        global $current_user,$rcl_options;
        
        $access = (isset($rcl_options['consol_access_rcl']))? $rcl_options['consol_access_rcl']: 7;
        $user_can = ($current_user->user_level >= $access)? 1: 0;
        
        return apply_filters('rcl_chat_check_user_can',$user_can);
        
    }
    
    function the_content($content){
        global $rcl_options;

        $content = esc_textarea($content);
        
        $content = popuplinks(make_clickable($content));
        
        $oembed = (isset($rcl_options['chat']['oembed']))? $rcl_options['chat']['oembed']: 0;
        
        if($oembed&&function_exists('wp_oembed_get')){
            $links='';
            preg_match_all('/href="([^"]+)"/', $content, $links);
            foreach( $links[1] as $link ){
                $m_lnk = wp_oembed_get($link,array('width'=>300,'height'=>300));
                if($m_lnk){
                    $content = str_replace('<a href="'.$link.'" rel="nofollow">'.$link.'</a>','',$content);
                    $content .= $m_lnk;
                }
            }
        }
        
        if(function_exists('convert_smilies')) 
            $content = str_replace( 'style="height: 1em; max-height: 1em;"', '', convert_smilies( $content ) );
        
        $content = apply_filters('rcl_chat_message',$content);

        return $content;

    }
    
    function the_attachment($attachment_id){
        
        if ( !$post = get_post( $attachment_id ) )
		return false;

        
        if ( ! $file = get_attached_file( $attachment_id ) ) {
		return false;
	}

	$check = wp_check_filetype( $file );
	if ( empty( $check['ext'] ) ) {
		return false;
	}
        
	$ext = $check['ext'];
        $attach_url = wp_get_attachment_url($attachment_id);
        
        if(in_array( $ext, array( 'jpg', 'jpeg', 'jpe', 'gif', 'png' ) )){
            
            $type = 'image';
            $media = '<a target="_blank" rel="fancybox" href="'.$attach_url.'"><img src="'.wp_get_attachment_image_url($attachment_id,array(300,300)).'"></a>';
            
        }else if(in_array( $ext, wp_get_audio_extensions() )){
            
            $type = 'audio';
            $media = wp_audio_shortcode(array('mp3'=>$attach_url));
            
        }else if(in_array( $ext, wp_get_video_extensions() )){
            
            $type = 'video';
            $media = wp_video_shortcode(array('src'=>$attach_url));
            
        }else{
            $type = 'archive';
            $media = '<a target="_blank" href="'.$attach_url.'">'.wp_get_attachment_image( $attachment_id, array(30,30) ,true ).' '.$post->post_title.'.'.$ext.'</a>';
            
        }
        
        $content = '<div class="message-attachment" data-attachment="'.$attachment_id.'">';
        $content .= '<div class="'.$type.'-attachment">'.$media.'</div>';
        $content .= '</div>';
        
        return $content;
    }
    
    function important_manager(){
        
        $status = ($this->important)? 0: 1;
        $class = ($this->important)? 'fa-star-half-o': 'fa-star';
        
        $content = '<div class="important-manager">'
                    . '<a href="#" class="important-shift" onclick="rcl_chat_important_manager_shift(this,'.$status.');return false;">'
                        . '<i class="fa '.$class.'" aria-hidden="true"></i>'
                    . '</a>'
                . '</div>';
        
        return $content;
    }
    
    function add_important_query($query){
        $query['join'][] = "INNER JOIN ".RCL_PREF."chat_messagemeta AS chat_messagemeta ON chat_messages.message_id=chat_messagemeta.message_id";
        $query['where'][] = "chat_messagemeta.meta_key='important:$this->user_id'";
        return $query;
    }
    
}