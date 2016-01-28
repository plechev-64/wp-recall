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
        global $rcl_options,$user_ID;
        $this->inc_cache = (isset($rcl_options['use_cache']))? $rcl_options['use_cache']: 0;
        $this->only_guest = $only_guest;
        if(!$this->only_guest) $this->only_guest = (isset($rcl_options['cache_output']))? $rcl_options['cache_output']: 0;
        $this->is_cache = ($this->inc_cache&&(!$this->only_guest||$this->only_guest&&!$user_ID))? 1: 0;
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
        $file['need_update'] = ($endcache<current_time('timestamp',1))? 1: 0;
        
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

add_shortcode('rcl-cache','rcl_cache_shortcode');
function rcl_cache_shortcode($atts,$content = null){
    global $post;

    extract(shortcode_atts(array(
	'key' => '',
        'only_guest' => false,
        'time' => false
	),
    $atts));
    
    if($post->post_status=='publish'){
    
        $key .= '-cache-'.$post->ID;

        $rcl_cache = new Rcl_Cache($time,$only_guest);

        if($rcl_cache->is_cache){

            $file = $rcl_cache->get_file($key);

            if(!$file->need_update){
                return $rcl_cache->get_cache();
            }

        }
    
    }
    
    $content = do_shortcode( shortcode_unautop( $content ) );
    if ( '</p>' == substr( $content, 0, 4 )
    and '<p>' == substr( $content, strlen( $content ) - 3 ) )
    $content = substr( $content, 4, strlen( $content ) - 7 );
    
    if($post->post_status=='publish'){

        if($rcl_cache->is_cache){
            $rcl_cache->update_cache($content);
        }
    
    }
    
    return $content;
}