<?php

class Rcl_Cache{
    
    public $inc_cache;
    public $only_guest;
    public $time_cache;
    public $is_cache;
    public $filepath;
    public $last_update;
    public $file_exists;
    
    function __construct($timecache=0,$only_guest=false){
        global $user_ID;
        
        $this->inc_cache = rcl_get_option('use_cache');
        $this->only_guest = $only_guest;
        
        if(!$this->only_guest) 
            $this->only_guest = rcl_get_option('cache_output');
        
        $this->is_cache = ($this->inc_cache&&(!$this->only_guest||$this->only_guest&&!$user_ID))? 1: 0;
        $this->time_cache = rcl_get_option('cache_time',3600);
        
        if($timecache) 
            $this->time_cache = $timecache;
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
        
        $file = array(
                'filename'=>$filename,
                'filepath'=>$this->filepath
            );
        
        if(!file_exists($this->filepath)){
            $file['need_update'] = 1;
            $file['file_exists'] = 0;
            return (object)$file;           
        }
        
        $this->last_update = filemtime($this->filepath);
        $endcache = $this->last_update+$this->time_cache;

        $this->file_exists = 1;
        
        $file['file_exists'] = 1;
        $file['last_update'] = $this->last_update;
        $file['need_update'] = ($endcache<time())? 1: 0;
        
        return (object)$file;
    }
    
    function get_cache(){
        if(!$this->file_exists) return false;
        return file_get_contents($this->filepath).'<!-- Rcl-cache start:'.date('d.m.Y H:i',$this->last_update).' time:'.$this->time_cache.' -->';
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