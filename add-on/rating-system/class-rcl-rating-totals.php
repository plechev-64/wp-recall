<?php

class Rcl_Rating_Totals extends Rcl_Query {
    
    public $table_as = 'rcl_rating_totals';
    public $table_cols = array(
                'ID',
                'object_id',
                'object_author',
                'rating_total',
                'rating_type'
            );
    
    function __construct() {       
        $this->table = RCL_PREF ."rating_totals";
    }
    
}

