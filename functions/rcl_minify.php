<?php
class Rcl_Minify {
    public $id;
    public $path;
    
    function __construct($id,$path){
        $this->id = $id;
        $this->path = $path;
        if (!is_admin()) add_action('wp_enqueue_scripts', array(&$this,'output_style'));
        if (is_admin()) add_filter('csspath_array_rcl', array(&$this,'minify_css'));
    }
    
    function output_style(){
            global $rcl_options;	
            if(isset($rcl_options['minify_css'])&&$rcl_options['minify_css']==1) return;
            wp_enqueue_style( $this->id, addon_url('style.css', $this->path) );	
    }
    
    function minify_css($array){
            global $rcl_options;	
            if($rcl_options['minify_css']!=1) return;
            $path = pathinfo($this->path);
            $array[] = $path['dirname'].'/style.css';
            return $array;
    }
}
