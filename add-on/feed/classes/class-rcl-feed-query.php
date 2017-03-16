<?php

class Rcl_Feed_Query extends Rcl_Query {
    
    function __construct() { 
        
        $this->query['table'] = array(
            'name' => RCL_PREF ."feeds",
            'as' => 'rcl_feeds',
            'cols' => array(
                'feed_id',
                'user_id',
                'object_id',
                'feed_type',
                'feed_status'
            )
        );
        
    }

}

