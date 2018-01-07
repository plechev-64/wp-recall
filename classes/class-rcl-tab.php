<?php

class Rcl_Tab{
    
    public $id;//идентификатор вкладки
    public $name;//имя вкладки
    public $icon = 'fa-cog';
    public $public = 0;
    public $order = 10;
    public $first = false;
    public $counter = null;
    public $onclick = false;
    public $output = 'menu';
    public $supports = array();
    public $content = array();
    public $tab_active = 0;//указание активности вкладки
    public $tab_upload = 0;//указание загрузки содержимого вкладки
    public $use_cache;
    public $active_subtab;
    
    function __construct($args){
        
        $args = apply_filters('rcl_pre_output_tab',$args);
        
        $this->init_properties($args);
        
        $this->tab_active = $this->is_view_tab();
        $this->tab_upload = ($this->tab_active)? true: false;
        $this->use_cache = rcl_get_option('use_cache');

        do_action('rcl_construct_'.$this->id.'_tab');

    }
    
    function init_properties($args){

        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(!isset($args[$name])) continue;
            $this->$name = $args[$name];
        }
    }

    function register_tab(){
        add_filter('rcl_content_area_tabs',array($this,'print_tab'),$this->order);
        if($this->output)
            add_filter('rcl_content_area_'.$this->output,array($this,'print_tab_button'),$this->order);
    }
    
    function print_tab($content){
        global $user_LK;
        $content .= $this->get_tab($user_LK);
        return $content;
    }
    
    function print_tab_button($content){
        global $user_LK;
        $content .= $this->get_tab_button($user_LK);
        return $content;
    }
    
    function is_view_tab(){
        
        $view = false;
 
        if(isset($_GET['tab'])){
            $view = ($_GET['tab']==$this->id)? true: false;
        }else{
            if($this->first){
                $view = true;
            }
        }
        
        return $view;
        
    }
    
    function get_tab_content($master_id,$subtab_id = false){
        global $rcl_tab;

        $subtabs = apply_filters('rcl_subtabs',$this->content,$this->id);
        
        require_once 'class-rcl-sub-tabs.php';

        $subtab = new Rcl_Sub_Tabs($subtabs,$this->id);
        
        if($subtab_id)
            $subtab->active_tab = $subtab_id;
        
        if($subtab->active_tab){
            $this->active_subtab = $subtab->active_tab;
        }
        
        $rcl_tab = $this;

        if(count($this->content)>1)
            $content = $subtab->get_sub_content($master_id);
        else
            $content = $subtab->get_subtab($master_id);
        
        $content = apply_filters('rcl_tab_'.$this->id,$content);
        
        return $content;
    }
    
    function get_class_button(){

        $classes = array();
        
        if(in_array('dialog',$this->supports)){
            $classes[] = 'rcl-dialog';
            $classes[] = 'rcl-ajax';
        }else if(in_array('ajax',$this->supports)){
            $classes[] = 'rcl-ajax';
        }

        if($this->tab_active) 
            $classes[] = ' active';
        
        return implode(' ',$classes);
    }
    
    function is_user_access($master_id){
        global $user_ID;
        
        switch($this->public){
            case 0: if(!$user_ID || $user_ID != $master_id) return false; break;
            case -1: if(!$user_ID || $user_ID == $master_id) return false; break;
            case -2: if($user_ID && $user_ID == $master_id) return false; break;
        }
        
        return true;
    }
    
    function get_tab_button($master_id){
        global $user_ID;
        
        if(!$this->is_user_access($master_id)) return false;

        $name = (isset($this->counter))? sprintf('%s <span class="rcl-menu-notice">%s</span>',$this->name,$this->counter): $this->name;

        $icon = ($this->icon)? $this->icon: 'fa-cog';
        
        if($this->onclick){
            
            $html_button = rcl_get_button(
                $name,
                '#',
                array(
                    'class'=>'recall-button',
                    'icon'=> $icon,
                    'attr'=> 'onclick="'.$this->onclick.';return false;"'
                )
            );
            
        }else{
            
            $link = rcl_get_tab_permalink($master_id,$this->id);
            
            $datapost = array(
                'tab_id'=>$this->id,
                'master_id'=>$master_id
            );
            
            $html_button = rcl_get_button(
                $name,
                $link,
                array(
                    'class'=>$this->get_class_button(),
                    'icon'=> $icon,
                    'attr'=>'data-post='.rcl_encode_post($datapost)
                )
            );
            
        }

	return sprintf('<span class="rcl-tab-button" data-tab="%s" id="tab-button-%s">%s</span>',$this->id,$this->id,$html_button);

    }
    
    function get_tab($master_id, $subtab_id = false){
        global $user_ID;
        
        if(!$this->is_user_access($master_id)) return false;
        
        if(!$this->tab_upload) return false;
        
        $status = ($this->tab_active) ? 'active': '';
        
        $content = '';

        if($this->use_cache && in_array('cache',$this->supports)){
                                   
            $rcl_cache = new Rcl_Cache();
            
            $protocol  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://':  'https://';

            if(defined( 'DOING_AJAX' ) && DOING_AJAX){
                $string = rcl_get_tab_permalink($master_id,$this->id,$subtab_id);
            }else{                   
                $string = $protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
            }

            $file = $rcl_cache->get_file($string);

            if($file->need_update){

                $content = $this->get_tab_content($master_id,$subtab_id);

                $rcl_cache->update_cache($content);
            
            }else{

                $content = $rcl_cache->get_cache();
            
            }

        }else{

            $content = $this->get_tab_content($master_id,$subtab_id);
            
            if(!$content) return false;
        
        }
        
        return sprintf('<div id="tab-%s" class="%s_block recall_content_block %s">%s</div>',$this->id,$this->id,$status,$content);

    }

}