<?php

class Rcl_Rating_Values extends Rcl_Query {
    
    public $table_as = 'rcl_rating_values';
    public $table_cols = array(
                'ID',
                'user_id',
                'object_id',
                'object_author',
                'rating_value',
                'rating_date',
                'rating_type'
            );
    
    function __construct() {       
        $this->table = RCL_PREF ."rating_values";
    }
    
    function get_sum_values($args){
        
        $this->query['select'] = array(
            "SUM($this->table.rating_value)"
        );
        
        return $rating->get_var($args);
        
    }
    
}

