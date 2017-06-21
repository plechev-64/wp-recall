<?php

class PrimeVisits extends Rcl_Query{
    
    function __construct() {
        
        $table = array(
            'name' => RCL_PREF ."pforum_visits",
            'as' => 'pfm_visits',
            'cols' => array(
                'user_id',
                'group_id',
                'forum_id',
                'topic_id',
                'visit_date'
            )
        );
        
        parent::__construct($table);
    }
    
}
