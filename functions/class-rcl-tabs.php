<?php

add_filter( 'rcl_custom_tab_content', 'do_shortcode', 11 );
add_filter( 'rcl_custom_tab_content', 'wpautop', 10 );

class Rcl_Tabs{
    public $id;
    public $callback;
    public $name;
    public $tab_active = 0;
    public $tab_upload = 0;
    public $args = array(
                'ajax-load'=>0,
                'output'=>'menu',
                'cache'=>0,
                'content'=>'',
                'order'=>10,
                'public'=>0,
                'class'=>'fa-cog',
                'first'=>0
            );
    
    function __construct($args){
        global $rcl_options;
        
        $this->init_properties($args);
        
        $type_upload = (isset($rcl_options['tab_newpage']))? $rcl_options['tab_newpage']: 0;
        $this->tab_active = $this->is_view_tab();
        $this->tab_upload = (!$type_upload||$this->tab_active)? true: false;
        $this->args['cache'] = ($this->args['cache']&&isset($rcl_options['use_cache'])&&$rcl_options['use_cache'])? $this->args['cache']: false;
        
        do_action('rcl_construct_'.$this->id.'_tab');
    }
    
    function init_properties($args){

        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            
            if(!isset($args[$name])) continue;
            
            if($name=='args'){
                $this->args = wp_parse_args( $args[$name], $this->args  );
                continue;
            }
            
            $this->$name = $args[$name];
     
        }
    }

    function add_tab(){
        add_action('rcl_area_tabs',array($this,'print_tab'),$this->args['order']);
        add_action('rcl_area_'.$this->args['output'],array($this,'print_tab_button'),$this->args['order']);
    }
    
    function print_tab(){
        global $user_LK;
        echo $this->get_tab($user_LK);
    }
    
    function print_tab_button(){
        global $user_LK;
        echo $this->get_tab_button($user_LK);
    }
    
    function is_view_tab(){
        global $rcl_options;
        
        $view = false;
 
        if(isset($_GET['tab'])){
            $view = ($_GET['tab']==$this->id)? true: false;
        }else{
            if($this->args['first']){
                $view = true;
            }
        }
        
        return $view;
        
    }
    
    function get_callback_content($author_lk){
        
        $callback = $this->callback;
        
        if(is_array($callback)){
            $object = new $callback[0];
            $method = $callback[1];
            $content = $object->$method($author_lk);
        }else{
            $content = $callback($author_lk);
        }

        $content = apply_filters('rcl_tab_'.$this->id,$content);

        return $content;

    }
    
    function get_tab_content($author_lk){
        if($this->callback){
            $content = $this->get_callback_content($author_lk);
        }else if($this->args['content']){
            $content = apply_filters('rcl_custom_tab_content',stripslashes_deep($this->args['content']));
        }
        return $content;
    }
    
    function get_class_button(){
        global $rcl_options;

        $class = false;
        $tb = (isset($rcl_options['tab_newpage']))? $rcl_options['tab_newpage']:false;
        if(!$tb) $class = 'block_button';
        if($tb==2&&$this->args['ajax-load']){
            $class = 'rcl-ajax';
        }
        if($this->tab_active) $class .= ' active';
        return $class;
    }
    
    function get_tab_button($author_lk){
        global $user_ID;
        
        switch($this->args['public']){
            case 0: if(!$user_ID||$user_ID!=$author_lk) return false; break;
            case -1: if(!$user_ID||$user_ID==$author_lk) return false; break;
            case -2: if($user_ID&&$user_ID==$author_lk) return false; break;
        }

        $link = rcl_format_url(get_author_posts_url($author_lk),$this->id);
        
        $datapost = array(
            'callback'=>'rcl_ajax_tab',
            'tab_id'=>$this->id,
            'user_LK'=>$author_lk
        );
        
        $name = (isset($this->args['counter']))? sprintf('%s <span class="rcl-menu-notice">%s</span>',$this->name,$this->args['counter']): $this->name;
        
        $html_button = rcl_get_button(
                $name,$link,
                array(
                    'class'=>$this->get_class_button(),
                    'icon'=> ($this->args['class'])? $this->args['class']:'fa-cog',
                    'attr'=>'data-post='.rcl_encode_post($datapost)
                )
        );
        
	return sprintf('<span class="rcl-tab-button" data-tab="%s" id="tab-button-%s">%s</span>',$this->id,$this->id,$html_button);

    }
    
    function get_tab($author_lk){
        global $user_ID,$rcl_options;
        
        switch($this->args['public']){
            case 0: if(!$user_ID||$user_ID!=$author_lk) return false; break;
            case -1: if(!$user_ID||$user_ID==$author_lk) return false; break;
            case -2: if($user_ID&&$user_ID==$author_lk) return false; break;
        }
        
        if(!$this->tab_upload) return false;
        
        $status = ($this->tab_active) ? 'active':'';
        
        $content = '';
        
        if($this->args['cache']){
                                   
            $rcl_cache = new Rcl_Cache();
            
            $protocol  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://':  'https://';
            
            if(!$rcl_options['tab_newpage']){ //если загружаются все вкладки               
                $string = (isset($_GET['tab'])&&$_GET['tab']==$this->id)? $protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']: rcl_format_url(get_author_posts_url($author_lk),$this->id);               
            }else{
            
                if(defined( 'DOING_AJAX' ) && DOING_AJAX){
                    $string = rcl_format_url(get_author_posts_url($author_lk),$this->id);
                }else{                   
                    $string = $protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
                }
            
            }
            
            $file = $rcl_cache->get_file($string);

            if($file->need_update){

                $content = $this->get_tab_content($author_lk);

                $rcl_cache->update_cache($content);
            
            }else{

                $content = $rcl_cache->get_cache();
            
            }

        }else{

            $content = $this->get_tab_content($author_lk);
            
            if(!$content) return false;
        
        }
        
        return sprintf('<div id="tab-%s" class="%s_block recall_content_block %s">%s</div>',$this->id,$this->id,$status,$content);

    }

}