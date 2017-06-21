<?php

class PrimePosts extends Rcl_Query{
    
    function __construct() {

        $table = array(
            'name' => RCL_PREF ."pforum_posts",
            'as' => 'pfm_posts',
            'cols' => array(
                'post_id',
                'post_content',
                'user_id',
                'guest_name',
                'guest_email',
                'post_date',
                'post_edit',
                'post_status',
                'post_index',
                'forum_id',
                'topic_id'
            )
        );
        
        parent::__construct($table);
        
        $this->number = (pfm_get_option('posts-per-page'))? pfm_get_option('posts-per-page'): 20;
        
    }
    
}