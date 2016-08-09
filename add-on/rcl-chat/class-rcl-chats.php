<?php

class Rcl_Chats{
    
    public $query = array(
                'join'=>array(),
                'select'=>array('chats.*'),
                'where'=>array(),
                'order'=>'',
                'orderby'=>'',
                'groupby'=>''
            );
    
    function __construct(){
        
    }
    
    function query(){
        $query = apply_filters('rcl_chats_query',$this->query);
    }
    
    function get_sql($query){
        
        $sql = "SELECT ".implode(',',$query['select'])." FROM ".RCL_PREF."chats AS chats ";
        
        if($query['join']){
            $sql .= implode(" ",$this->query['join']);
        }

        if($query['where']){
            $sql .= "WHERE ";
            $sql .= implode(" AND ",$query['where']);
        }

        if($query['groupby']){
            $sql .= " GROUP BY ".$query['group']." ";
        }
        
        $sql = apply_filters('rcl_chats_sql',$sql);
        
        return $sql;
    }
    
    function get_chats(){
        global $wpdb;
        $chats = $wpdb->get_results($this->get_sql($this->query));
        return $chats;
    }
    
}

