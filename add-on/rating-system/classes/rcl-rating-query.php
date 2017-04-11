<?php

class Rcl_Rating_Users_Query extends Rcl_Query {

    function __construct() {       
        
        $table = array(
            'name' => RCL_PREF ."rating_users",
            'as' => 'rcl_rating_users',
            'cols' => array(
                'user_id',
                'rating_total'
            )
        );
        
        parent::__construct($table);
        
    }
    
}

class Rcl_Rating_Totals_Query extends Rcl_Query {

    function __construct() {       

        $table = array(
            'name' => RCL_PREF ."rating_totals",
            'as' => 'rcl_rating_totals',
            'cols' => array(
                'ID',
                'object_id',
                'object_author',
                'rating_total',
                'rating_type'
            )
        );
        
        parent::__construct($table);
        
    }
    
}

class Rcl_Rating_Values_Query extends Rcl_Query {
    
    function __construct() {       
        
        $table = array(
            'name' => RCL_PREF ."rating_values",
            'as' => 'rcl_rating_values',
            'cols' => array(
                'ID',
                'user_id',
                'object_id',
                'object_author',
                'rating_value',
                'rating_date',
                'rating_type'
            )
        );
        
        parent::__construct($table);
        
    }
    
    function get_sum_values($args){
        
        $this->query['select'] = array(
            "SUM(".$this->query['table']['as'].".rating_value)"
        );
        
        return $this->get_var($args);
        
    }
    
}

