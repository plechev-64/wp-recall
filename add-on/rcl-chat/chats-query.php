<?php

class Rcl_Chats_Query extends Rcl_Query{
    
    function __construct() {

        $table = array(
            'name' => RCL_PREF ."chats",
            'as' => 'rcl_chats',
            'cols' => array(
                'chat_id',
                'chat_room',
                'chat_status'
            )
        );
        
        parent::__construct($table);

    }
    
}

class Rcl_Chat_Users_Query extends Rcl_Query{
    
    function __construct() {

        $table = array(
            'name' => RCL_PREF ."chat_users",
            'as' => 'rcl_chat_users',
            'cols' => array(
                'room_place',
                'chat_id',
                'user_id',
                'user_activity',
                'user_write',
                'user_status'
            )
        );
        
        parent::__construct($table);

    }
    
}

class Rcl_Chat_Messages_Query extends Rcl_Query{
    
    function __construct() {

        $table = array(
            'name' => RCL_PREF ."chat_messages",
            'as' => 'rcl_chat_messages',
            'cols' => array(
                'message_id',
                'chat_id',
                'user_id',
                'message_content',
                'message_time',
                'private_key',
                'message_status'
            )
        );
        
        parent::__construct($table);

    }
    
}

class Rcl_Chat_Messagemeta_Query extends Rcl_Query{
    
    function __construct() {

        $table = array(
            'name' => RCL_PREF ."chat_messagemeta",
            'as' => 'rcl_chat_messagemeta',
            'cols' => array(
                'meta_id',
                'message_id',
                'meta_key',
                'meta_value'
            )
        );
        
        parent::__construct($table);

    }
    
}