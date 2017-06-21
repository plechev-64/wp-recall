<?php

class PrimeMeta extends Rcl_Query{
    
    function __construct() {
        
        $table = array(
            'name' => RCL_PREF ."pforum_meta",
            'as' => 'pfm_meta',
            'cols' => array(
                'meta_id',
                'object_id',
                'object_type',
                'meta_key',
                'meta_value'
            )
        );
        
        parent::__construct($table);
    }
    
}

