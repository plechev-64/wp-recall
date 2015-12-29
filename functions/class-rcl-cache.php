<?php

class Rcl_Cache{
    
    public $time_cache;
    public $is_cache;
    public $filepath;
    public $last_update;
    public $file_exists;
    
    function __construct($timecache=0){
        global $rcl_options;

        $this->is_cache = (isset($rcl_options['use_cache'])&&$rcl_options['use_cache'])? $rcl_options['use_cache']: 0;
        $this->time_cache = (isset($rcl_options['cache_time'])&&$rcl_options['cache_time'])? $rcl_options['cache_time']: 3600;
        if($timecache) $this->time_cache = $timecache;
    }
    
    function get_file($string){
        $namecache = md5($string);
        $cachepath = RCL_UPLOAD_PATH.'cache/';
        $filename = $namecache.'.txt';
        $this->filepath = $cachepath.$filename;
        $this->file_exists = 0;
        
        if(!file_exists($cachepath)){                
            mkdir($cachepath);
            chmod($cachepath, 0755);
        }
        
        if(!file_exists($this->filepath)) return false;
        
        $this->last_update = filemtime($this->filepath);
        $endcache = $this->last_update+$this->time_cache;

        $this->file_exists = 1;

        $file = array(
            'filename'=>$filename,
            'filepath'=>$this->filepath,
            'last_update'=>$this->last_update,
            'is_old'=> ($endcache<current_time('timestamp',1))? 1: 0,
        );
        
        return (object)$file;
    }
    
    function get_cache(){
        if(!$this->file_exists) return false;
        return file_get_contents($this->filepath).'<!-- Tab cached:'.date('d.m.Y H:i',$this->last_update).' -->';
    }

    function update_cache($content){
        if(!$this->filepath) return false;
        $f = fopen($this->filepath, 'w+');                   
        fwrite($f, $content);
        fclose($f);
        return $content;
    }
    
    function delete_file(){
        if(!$this->file_exists) return false;
        unlink($this->filepath);
    }

    function clear_cache(){
        rcl_remove_dir(RCL_UPLOAD_PATH.'cache/');
    }
}

add_action('rcl_cron_daily','rcl_clear_cache',20);
function rcl_clear_cache(){
    $rcl_cache = new Rcl_Cache();
    $rcl_cache->clear_cache();
}

function rcl_delete_file_cache($string){
    $rcl_cache = new Rcl_Cache();       
    $rcl_cache->get_file($string);
    $rcl_cache->delete_file();
}



