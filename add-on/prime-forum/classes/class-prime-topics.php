<?php

class PrimeTopics extends Rcl_Query{
    
    function __construct() {
        
        $table = array(
            'name' => RCL_PREF ."pforum_topics",
            'as' => 'pfm_topics',
            'cols' => array(
                'topic_id',
                'topic_name',
                'topic_slug',
                'topic_status',
                'forum_id',
                'user_id',
                'post_count',
                'closed'
            )
        );
        
        parent::__construct($table);
        
        $this->number = (pfm_get_option('topics-per-page'))? pfm_get_option('topics-per-page'): 20;
        
    }
    
}