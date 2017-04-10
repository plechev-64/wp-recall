<?php

class Rcl_Log {

    public $log_path;
    
    function __construct($args = false) {
        
        if($args)
            $this->init_properties($args);

        if(!$this->log_path){
            
            $logDir = RCL_TAKEPATH.'logs/';
        
            if(!file_exists($logDir)) wp_mkdir_p( $logDir );
            
            $this->log_path = $logDir . date('Y-m-d').'.log';
            
        }
 
    }
    
    function init_properties($args){
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
    }
    
    function insert_title($title){

        $this->insert_log(date('H:i:s') . " " . $title);
        
    }
    
    function insert_log($data){
        
        if (!is_string($data)) {
            $data = print_r($data, true);
        }
        
        file_put_contents($this->log_path, $data . "\n", FILE_APPEND);
    }
    
}
