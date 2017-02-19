<?php

class Rcl_Rating_Users extends Rcl_Query {
    
    public $table_as = 'rcl_rating_users';
    public $table_cols = array(
                'user_id',
                'rating_total'
            );
    
    function __construct() {       
        $this->table = RCL_PREF ."rating_users";
    }
    
}

