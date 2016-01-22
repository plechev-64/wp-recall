<?php
class Rcl_Rating{

    public $number = false;
    public $days = false;
    public $rating_type = 'post';
    public $object_author = '';
    public $per_page = 10;
    public $paged = 1;
    public $offset = 0;
    public $orderby = '';
    public $order = 'DESC';
    public $template = 'post';
    public $include = '';
    public $exclude = '';
    public $is_count = 0;
    public $query = array();    

    function __construct($args){
        
        $this->init_properties($args);
        
        $this->query['orderby'] = $this->orderby;
        $this->query['order'] = $this->order;
        
        if($this->rating_type) $this->rating_type = explode(',',$this->rating_type);
        if($this->object_author) $this->object_author = explode(',',$this->object_author);
        
        if($this->rating_type){
            add_filter('rcl_ratings_query',array($this,'add_rating_types'));
        }
        
        if($this->object_author){
            add_filter('rcl_ratings_query',array($this,'add_object_authors'));
        }
        
        if($this->days){
            add_filter('rcl_ratings_query',array($this,'add_query_days'));
        }
    }
    
    function remove_data(){
        remove_all_filters('rcl_ratings_query');
        remove_all_filters('rcl_ratings');
    }

    function init_properties($args){

        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
    }
    
    function count_values(){
        global $wpdb;
        if($this->number){
            $users = $this->get_values();
            return count($users);
        }else{   
            
            $query_string = $this->query_string('count');
            
            //if($this->query['relation']=='OR'){
                return $wpdb->query( $query_string );
            //}else{
                //return $wpdb->get_var( $query_string );
            //}
        }
    }
    
    function get_values($args = false){
        global $wpdb;

        if($args) $this->init_properties($args);

        $values = $wpdb->get_results( $this->query_string() );

        $values = apply_filters('rcl_ratings',$values);

        return $values;
    }

    function query_string($count=false){
        global $wpdb,$active_addons,$rcl_options;

        if($count) $this->is_count = 1;
        
        $query = array(
            'select'    => array(),
            'join'      => array(),
            'where'     => array(),
            'relation'     => 'AND',
            'group'     => '',
        ); 

        if($count){

            $query['select'] = array(
                "COUNT(ratings.object_id)"
            );

        }else{

            $query['select'] = array(
                "ratings.object_id"
                , "ratings.object_author"
                , "ratings.rating_total"
                , "ratings.rating_type"
            );

        }

        //if($query['include'][0]) $query['where'][] = "ratings.ID IN (".implode(',',$query['include']).")";
        //if($query['exclude'][0]) $query['where'][] = "ratings.ID NOT IN (".implode(',',$query['exclude']).")";
        
        $this->query = $query;
        
        $query = apply_filters('rcl_ratings_query',$this);
        //print_r($query);
        $query_string = "SELECT "
            . implode(", ",$query->query['select'])." "
            . "FROM ".RCL_PREF."rating_totals AS ratings "
            . implode(" ",$query->query['join'])." ";

        if($query->query['where']) $query_string .= "WHERE ".implode(' '.$query->query['relation'].' ',$query->query['where'])." ";
        if($query->query['group']) $query_string .= "GROUP BY ".$query->query['group']." ";

        if(!$query->is_count){
            if(!$query->query['orderby']) $query->query['orderby'] = "CAST(ratings.rating_total AS DECIMAL)";
            $query_string .= "ORDER BY ".$query->query['orderby']." ".$this->order." ";
            $query_string .= "LIMIT ".$this->offset.",".$this->per_page;
        }
        
        //if(!$count)  echo $query_string;

        if($this->is_count)
            $this->is_count = false;

        return $query_string;

    }
    
    function add_rating_types($query){
        $query->query['where'][] = "ratings.rating_type IN ('".implode("','",$this->rating_type)."')";
        return $query;
    }
    
    function add_object_authors($query){
        $query->query['where'][] = "ratings.object_author IN ('".implode("','",$this->object_author)."')";
        return $query;
    }
    
    function add_query_days($query){
        if(!$this->is_count){
            $query->query['select'][] = "SUM(rating_values.rating_value) AS time_sum";
            $query->query['orderby'] = "time_sum";
        }
        
        $query->query['join'][] = "INNER JOIN ".RCL_PREF."rating_values AS rating_values ON ratings.object_id = rating_values.object_id";
        $query->query['where'][] = "rating_values.rating_date > ('".current_time('mysql')."' - INTERVAL ".$this->days." DAY)";
        $query->query['where'][] = "ratings.rating_type = rating_values.rating_type";
        $query->query['where'][] = "ratings.object_author = rating_values.object_author";
        $query->query['group'] = "ratings.object_id";
        
        return $query;
    }

}