<?php

/**
 * Description of Rcl_Query
 *
 * @author Андрей
 */

class Rcl_Query {
    
    public $args = array();
    public $query = array(
            'table' => array(),
            'select' => array(),
            'where' => array(),
            'join' => array(),
            'offset' => 0,
            'number' => 30
        );
            
    function __construct($table = false) {
        
        if($table)
            $this->query['table'] = $table;
        
    }

    function set_query($args = false){
        
        $this->args = esc_sql($args);
        
        if(!$this->query['table']){
            
            if(isset($this->args['table'])){

                $this->query['table'] = $this->args['table'];

            }
            
        }
        
        //получаем устаревшие указания кол-ва значений на странице
        //и приводим к number
        if(isset($this->args['per_page'])){
            $this->args['number'] = $this->args['per_page'];
        }else if(isset($this->args['inpage'])){
            $this->args['number'] = $this->args['inpage'];
        }else if(isset($this->args['in_page'])){
            $this->args['number'] = $this->args['in_page'];
        }

        if(isset($this->args['fields'])){
            
            $this->set_fields($this->args['fields']);
            
        }else{
            
            if(!$this->query['select']){
                $this->query['select'][] = $this->query['table']['as'].'.*';
            }
            
        }
        
        if(isset($this->args['distinct'])){
            $this->query['select'][0] = 'DISTINCT '.$this->query['select'][0];
        }

        if($this->query['table']['cols']){
            
            if(isset($this->args['include']) && $this->args['include']){
                    
                $this->query['where'][] = $this->query['table']['as'].".".$this->query['table']['cols'][0]." IN (".$this->get_string_in($this->args['include']).")";

            }
            
            if(isset($this->args['exclude']) && $this->args['exclude']){
                    
                $this->query['where'][] = $this->query['table']['as'].".".$this->query['table']['cols'][0]." NOT IN (".$this->get_string_in($this->args['exclude']).")";

            }
            
            foreach($this->query['table']['cols'] as $col_name){
                
                if(isset($this->args[$col_name])){
                    
                    if($this->args[$col_name] === 'is_null'){
                        $this->query['where'][] = $this->query['table']['as'].".$col_name IS NULL";
                    }else{
                        $this->query['where'][] = $this->query['table']['as'].".$col_name = '".$this->args[$col_name]."'";
                    }

                }
                
                if(isset($this->args[$col_name.'__in'])  && ($this->args[$col_name.'__in'] || $this->args[$col_name.'__in'] === 0)){
                    
                    $this->query['where'][] = $this->query['table']['as'].".$col_name IN (".$this->get_string_in($this->args[$col_name.'__in']).")";
                    
                }
                
                if(isset($this->args[$col_name.'__not_in'])  && ($this->args[$col_name.'__not_in'] || $this->args[$col_name.'__not_in'] === 0)){

                    $this->query['where'][] = $this->query['table']['as'].".$col_name NOT IN (".$this->get_string_in($this->args[$col_name.'__not_in']).")";
                    
                }
                
                if(isset($this->args[$col_name.'__from'])  && ($this->args[$col_name.'__from'] || $this->args[$col_name.'__from'] === 0)){
                    
                    $colName = is_numeric($this->args[$col_name.'__from'])? "CAST(".$this->query['table']['as'].".$col_name AS DECIMAL)": $this->query['table']['as'].".".$col_name;
                    
                    $this->query['where'][] = $colName." >= '".$this->args[$col_name.'__from']."'";
                    
                }
                
                if(isset($this->args[$col_name.'__to'])  && ($this->args[$col_name.'__to'] || $this->args[$col_name.'__to'] === 0)){
                    
                    $colName = is_numeric($this->args[$col_name.'__to'])? "CAST(".$this->query['table']['as'].".$col_name AS DECIMAL)": $this->query['table']['as'].".".$col_name;

                    $this->query['where'][] = $colName." <= '".$this->args[$col_name.'__to']."'";
                    
                }
                
                if(isset($this->args[$col_name.'__like'])  && ($this->args[$col_name.'__like'] || $this->args[$col_name.'__like'] === 0)){

                    $this->query['where'][] = $col_name." LIKE '%".$this->args[$col_name.'__like']."%'";
                    
                }

            }

            if(isset($this->args['date_query'])){
                
                $this->set_date_query($this->args['date_query']);
                
            }
            
            if(isset($this->args['join_query'])){
                
                $this->set_join_query($this->args['join_query']);

            }
            
        }
        
        if(isset($this->args['orderby'])){
            
            if($this->args['orderby'] == 'rand'){
                $this->query['orderby'] = $this->query['table']['as'].'.'.$this->query['table']['cols'][0];
                $this->query['order'] = 'RAND()';
            }else{
                $this->query['orderby'] = $this->query['table']['as'].'.'.$this->args['orderby'];
                $this->query['order'] = (isset($this->args['order']) && $this->args['order'])? $this->args['order']: 'DESC';
            }

        }else if(isset($this->args['orderby_as_decimal'])){
            
            $this->query['orderby'] = 'CAST('.$this->query['table']['as'].'.'.$this->args['orderby_as_decimal'].' AS DECIMAL)';
            $this->query['order'] = (isset($this->args['order']) && $this->args['order'])? $this->args['order']: 'DESC';
            
        }else if(isset($this->args['order'])){
            
            $this->query['order'] = $this->args['order'];
            
        }else{
            
            $this->query['orderby'] = $this->query['table']['as'].'.'.$this->query['table']['cols'][0];
            $this->query['order'] = 'DESC';
            
        }
        
        if(isset($this->args['number']))
            $this->query['number'] = $this->args['number'];
        
        if(isset($this->args['offset']))
            $this->query['offset'] = $this->args['offset'];
        
        if(isset($this->args['groupby'])) 
            $this->query['groupby'] = $this->args['groupby'];
        
        if(isset($this->args['return_as']))
            $this->query['return_as'] = $this->args['return_as'];

    }
    
    function set_fields($fields){
        
        if(!$fields) return false;
        
        foreach($fields as $field){
            if(!in_array($field,$this->query['table']['cols'])) continue;
            $this->query['select'][] = $this->query['table']['as'].'.'.$field;
        }
        
    }
    
    function set_date_query($date_query){
        
        foreach($date_query as $date){
                    
            if(!isset($date['column'])) continue;

            if(!isset($date['compare']))
                $date['compare'] = '=';

            if($date['compare'] == '='){

                $datetime = array();

                if(isset($date['year'])) 
                    $this->query['where'][] = "YEAR(".$this->query['table']['as'].".".$date['column'].") = '".$date['year']."'";

                if(isset($date['month'])) 
                    $this->query['where'][] = "MONTH(".$this->query['table']['as'].".".$date['column'].") = '".$date['month']."'";

                if(isset($date['day'])) 
                    $this->query['where'][] = "DAY(".$this->query['table']['as'].".".$date['column'].") = '".$date['day']."'";

            }else if($date['compare'] == 'BETWEEN'){

                if(!isset($date['value'])) continue;

                $this->query['where'][] = "(".$this->query['table']['as'].".".$date['column']." BETWEEN CAST('".$date['value'][0]."' AS DATE) AND CAST('".$date['value'][1]."' AS DATE))";

            }else{
                
                $this->query['where'][] = $this->query['table']['as'].".".$date['column']." ".$date['compare']." '".$date['value']."'";
                
            }

        }
        
    }
    
    function set_join_query($joins){
                
        foreach($joins as $join){

            $joinTable = $join['table'];

            if(!$joinTable) continue;

            $joinOn = false;
            foreach($this->query['table']['cols'] as $col_name){

                if(isset($join['on_'.$col_name])){
                    $joinOn = $col_name; break;
                }

            }

            if(!$joinOn) continue;

            $joinType = (isset($join['join']))? $join['join']: 'INNER';

            $this->query['join'][] = $joinType." JOIN ".$joinTable['name']." AS ".$joinTable['as']." ON ".$this->query['table']['as'].".".$joinOn." = ".$joinTable['as'].".".$join['on_'.$joinOn];

            $joinQuery = new Rcl_Query();

            $joinQuery->set_query($join);

            $this->query['select'] = array_merge($this->query['select'], $joinQuery->query['select']);
            $this->query['where'] = array_merge($this->query['where'], $joinQuery->query['where']);
            $this->query['join'] = array_merge($this->query['join'], $joinQuery->query['join']);

        }
        
    }
    
    function get_string_in($data){
        
        $vars = (is_array($data))? $data: explode(',',$data);
            
        $vars = array_map('trim',$vars);

        $array = array();
        foreach($vars as $var){
            if(is_numeric($var))
                $array[] = $var;
            else
                $array[] = "'$var'";
        }
        
        return implode(',',$array);
    }
    
    function reset_query(){
        $this->query = array(
            'table' => array(
                'name' => $this->query['table']['name'],
                'as' => $this->query['table']['as'],
                'cols' => $this->query['table']['cols']
            ),
            'select'=>array(),
            'where'=>array(),
            'where_or'=>array(),
            'join'=>array(),
            'offset' => 0,
            'number' => 30
        );
    }
    
    function get_query(){

        return apply_filters('rcl_table_'.$this->query['table']['as'].'_query',$this->query);

    }
    
    function get_sql($query = false, $method = 'get'){
        
        if(!$query)
            $query = $this->get_query();
        
        if($method == 'get')
            $sql[] = "SELECT ".implode(',',$query['select']);
        
        if($method == 'delete')
            $sql[] = "DELETE";
        
        $sql[] = "FROM ".$this->query['table']['name']." AS ".$this->query['table']['as'];
        
        if($query['join']){
            $sql[] = implode(' ',$query['join']);
        }
        
        $where = array();
        
        if($query['where']){
            $where[] = implode(' AND ',$query['where']);
        }
        
        if(isset($query['where_or']) && $query['where_or']){
            
            if($query['where']) 
                $where_or[] = 'OR'; 
            
            $where_or[] = implode(' OR ',$query['where_or']);

            $where[] = implode(' ',$where_or);
        }
        
        if($where)
            $sql[] = "WHERE ".implode(' ',$where);
        
        if(isset($query['groupby'])) 
            $sql[] = "GROUP BY ".$query['groupby'];
        
        if(isset($query['orderby'])){
            $sql[] = "ORDER BY ".$query['orderby']." ".$query['order'];
        }

        if(isset($query['number']) && $query['number'] > 0){
            
            if(isset($query['offset'])){
                $sql[] = "LIMIT ".$query['offset'].",".$query['number'];
            }else if(isset($query['number'])){
                $sql[] = "LIMIT ".$query['number'];
            }
            
        }
        
        $sql = implode(' ',$sql);
        
        return $sql;
    }
    
    function get_data($method = 'get_results'){
        
        global $wpdb;
        
        $query = $this->get_query();
        
        if(isset($this->args['cache'])){
            $cachekey = json_encode($query);
            $cache = wp_cache_get( $cachekey );
            if ( $cache )
                return $cache;
        }
        
        $return_as = (isset($query['return_as']))? $query['return_as']: false;     

        $sql = $this->get_sql($query);
        
        if(isset($query['return_as']))
            $data = $wpdb->$method($sql,$query['return_as']);
        else
            $data = $wpdb->$method($sql);
        
        if(isset($this->args['unserialise']) && $this->args['unserialise']){
            
            $unserialise = $this->args['unserialise'];
            
            if(is_array($data)){
                foreach($data as $k=>$item){
                    if(is_object($item)){
                        if(isset($item->$unserialise))
                            $data[$k]->$unserialise = maybe_unserialize($item->$unserialise);
                    }
                }
            }else if(is_object($data)){
                if(isset($data->$unserialise))
                    $data->$this->args['unserialise'] = maybe_unserialize($data->$unserialise);
            }
            
        }

        $data = wp_unslash($data);
        
        if(isset($this->args['cache']))
            wp_cache_add( $cachekey, $data );

        return $data;
    }
    
    function get_var($args){
        
        $this->set_query($args);
        
        $result = $this->get_data('get_var');
        
        $this->reset_query();
        
        return $result;
        
    }
    
    function get_results($args){
        
        $this->set_query($args);

        $result = $this->get_data('get_results');
        
        $this->reset_query();
        
        return $result;
        
    }
    
    function get_row($args){
        
        $this->set_query($args);
        
        $result = $this->get_data('get_row');
        
        $this->reset_query();
        
        return $result;
        
    }
    
    function get_col($args){
        
        $this->set_query($args);
        
        $result = $this->get_data('get_col');
        
        $this->reset_query();
        
        return $result;
        
    }
    
    function count($args = false, $field_name = false){
        
        global $wpdb;
        
        if($args)
            $this->set_query($args);
        
        $field_name = ($field_name)? $field_name: $this->query['table']['cols'][0];
        
        $query = $this->get_query();

        unset($query['select']);
        unset($query['offset']);
        unset($query['orderby']);
        unset($query['order']);
        unset($query['number']);

        $query['select'] = array('COUNT('.$query['table']['as'].'.'.$field_name.')');
        
        $sql = $this->get_sql($query);
        
        if(isset($query['groupby']) && $query['groupby'])
            $result = $wpdb->query($sql);
        else
            $result = $wpdb->get_var($sql);
        
        return $result;

    }
    
    function sum($args = false, $field_name = false){
        
        global $wpdb;
        
        if($args)
            $this->set_query($args);
        
        $field_name = ($field_name)? $field_name: $this->query['table']['cols'][0];
        
        $query = $this->get_query();

        unset($query['select']);
        unset($query['offset']);
        unset($query['orderby']);
        unset($query['order']);
        unset($query['number']);

        $query['select'] = array('SUM('.$query['table']['as'].'.'.$field_name.')');
        
        $sql = $this->get_sql($query);
        
        if(isset($query['groupby']) && $query['groupby'])
            $result = $wpdb->query($sql);
        else
            $result = $wpdb->get_var($sql);
        
        return $result;

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