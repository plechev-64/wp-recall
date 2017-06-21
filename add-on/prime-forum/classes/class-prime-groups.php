<?php

class PrimeGroups extends Rcl_Query{
    
    function __construct() {
        
        $table = array(
            'name' => RCL_PREF ."pforum_groups",
            'as' => 'pfm_groups',
            'cols' => array(
                'group_id',
                'group_name',
                'group_slug',
                'group_desc',
                'group_seq'
            )
        );
        
        parent::__construct($table);
    }
    
}
