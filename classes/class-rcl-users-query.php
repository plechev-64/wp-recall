<?php

class Rcl_Users_Query extends Rcl_Query{
    
    function __construct() {
        global $wpdb;

        $table = array(
            'name' => $wpdb->users,
            'as' => 'wp_users',
            'cols' => array(
                'ID',
                'user_login',
                'user_email',
                'user_registered',
                'display_name'
            )
        );
        
        parent::__construct($table);

    }

}
