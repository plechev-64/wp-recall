<?php

/**
 * Description of Rcl_Query
 *
 * @author Андрей
 */

class Rcl_Query {
    
    public $table;
    public $table_as;
    public $table_cols = array();
    public $fields = array();
    public $query = array(
            'select'=>array(),
            'where'=>array(),
            'join'=>array(),
            'offset' => 0,
            'number' => 30
        );
            
    function __construct() {
        
    }
    
    function set_query($args = false){
        
        if(isset($args['per_page'])){
            $args['number'] = $args['per_page'];
        }

        if(isset($args['fields'])){
            
            $fields = array();
            foreach($args['fields'] as $field){
                $fields[] = $this->table_as.'.'.$field;
            }
            
            $this->query['select'] = $fields;
            
        }
        
        if(!$this->query['select']){
            $this->query['select'][] = $this->table_as.'.*';
        }

        if($this->table_cols){
            
            foreach($this->table_cols as $col_name){
                
                if(isset($args[$col_name])){
                    
                    $this->query['where'][] = $this->table_as.".$col_name = '$args[$col_name]'";
                
                }
                
                if(isset($args['include_'.$col_name])){
                    
                    $include = (is_array($args['include_'.$col_name]))? implode(',',$args['include_'.$col_name]): $args['include_'.$col_name];
            
                    $this->query['where'][] = $this->table_as.".$col_name IN ('".implode("','",explode(',',$include))."')";

                }
                
                if(isset($args['exclude_'.$col_name])){
                    
                    $exclude = (is_array($args['exclude_'.$col_name]))? implode(',',$args['exclude_'.$col_name]): $args['exclude_'.$col_name];
            
                    $this->query['where'][] = $this->table_as.".$col_name NOT IN ('".implode("','",explode(',',$exclude))."')";

                }

            }
            
        }
        
        if(isset($args['orderby'])){
            
            $this->query['orderby'] = $this->table_as.'.'.$args['orderby'];
            $this->query['order'] = (isset($args['order']) && $args['order'])? $args['order']: 'DESC';
            
        }else if(isset($args['orderby_as_decimal'])){
            
            $this->query['orderby'] = 'CAST('.$this->table_as.'.'.$args['orderby_as_decimal'].' AS DECIMAL)';
            $this->query['order'] = (isset($args['order']) && $args['order'])? $args['order']: 'DESC';
            
        }else{
            
            $this->query['orderby'] = $this->table_as.'.'.$this->table_cols[0];
            $this->query['order'] = 'DESC';
            
        }
        
        if(isset($args['number']))
            $this->query['number'] = $args['number'];
        
        if(isset($args['offset']))
            $this->query['offset'] = $args['offset'];
        
        if(isset($args['groupby'])) 
            $this->query['groupby'] = $args['groupby'];
        
    }
    
    function reset_query(){
        $this->query = array(
            'select'=>array(),
            'where'=>array(),
            'join'=>array(),
            'offset' => 0,
            'number' => 30
        );
    }
    
    function get_query(){

        return apply_filters($this->table_as.'_query',$this->query);

    }
    
    function get_sql($query, $method = 'get'){
        
        if($method == 'get')
            $sql[] = "SELECT ".implode(',',$query['select']);
        
        if($method == 'delete')
            $sql[] = "DELETE";
        
        $sql[] = "FROM $this->table AS $this->table_as";
        
        if($query['join']){
            $sql[] = implode(' ',$query['join']);
        }
        
        if($query['where']){
            $sql[] = "WHERE ".implode(' AND ',$query['where']);
        }
        
        if(isset($query['groupby'])) 
            $sql[] = "GROUP BY ".$query['groupby'];
        
        if(isset($query['orderby'])){
            $sql[] = "ORDER BY ".$query['orderby']." ".$query['order'];
        }

        if(isset($query['offset'])){
            $sql[] = "LIMIT ".$query['offset'].",".$query['number'];
        }else if(isset($query['number'])){
            $sql[] = "LIMIT ".$query['number'];
        }
        
        $sql = implode(' ',$sql);
        
        return $sql;
    }
    
    function get_data($method = 'get_results'){
        
        global $wpdb;
        
        $query = $this->get_query();

        $sql = $this->get_sql($query);
        
        $data = $wpdb->$method($sql);
        
        $data = stripslashes_deep($data);
        
        return $data;
    }
    
    function get_var($args){
        
        $this->set_query($args);
        
        return $this->get_data('get_var');
        
    }
    
    function get_results($args){
        
        $this->set_query($args);

        return $this->get_data('get_results');
        
    }
    
    function get_row($args){
        
        $this->set_query($args);
        
        return $this->get_data('get_row');
        
    }
    
    function count($field_name = false){
        
        global $wpdb;
        
        $field_name = ($field_name)? $field_name: $this->table_cols[0];
        
        $query = $this->get_query();

        unset($query['select']);
        unset($query['offset']);
        unset($query['orderby']);
        unset($query['order']);
        unset($query['number']);

        $query['select'] = array('COUNT('.$this->table_as.'.'.$field_name.')');
        
        $sql = $this->get_sql($query);
        
        if($query['join'])
            $result = $wpdb->query($sql);
        else
            $result = $wpdb->get_var($sql);

        return $result;

    }
    
    function detele(){
        
        global $wpdb;
        
        $query = $this->get_query();

        unset($query['select']);
        
        $sql = $this->get_sql($query,'delete');

        return $wpdb->query($sql);
        
    }
    
    function insert($args){
        
        global $wpdb;

        $wpdb->insert( $this->table,  $args );

        $insert_id = $wpdb->insert_id;
        
        if(!$insert_id)
            return false;
        
        return $insert_id;
        
    }
    
    function update(){
        
    }

}