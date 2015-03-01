<?php
class Rcl_Blocks {
    public $id;
    public $class;
    public $title;
    public $place;
    public $callback;
    public $public;
    public $gallery;
    function __construct($place,$callback,$args=false){
        $this->place = $place;
        $this->callback = $callback;
        if(isset($args['title'])) $this->title = $args['title'];
        if(isset($args['gallery'])) $this->gallery = $args['gallery'];
        
        if(isset($args['public'])) $this->public = $args['public'];
        else $this->public = 1;
        
        if(isset($args['id'])) $this->id = $args['id'];
        if(isset($args['class'])) $this->class = $args['class'];
        
        if($this->gallery) $this->class .= ' gallery-lk';
        if( !has_filter('rcl_'.$place.'_lk', array(&$this,'add_block')) ) 
                add_filter('rcl_'.$place.'_lk',array(&$this,'add_block'),$args['order'],2);
    }
    
    function add_block($content,$user_lk){
        global $user_ID;
        switch($this->public){          
            case 0: if(!$user_ID||$user_ID!=$user_lk) return $content; break; //только хозяину ЛК
            case -1: if(!$user_ID||$user_ID==$user_lk) return $content; break; //всем зарегистрированным кроме хозяина ЛК
            case -2: if($user_ID&&$user_ID==$user_lk) return $content; break; //всем посетителям кроме хозяина
        }
        
        
        $cl_content = call_user_func($this->callback,$user_lk);
        if(!$cl_content) return $content;
        
        
        $content .= '<div';
        if($this->id) $content .= ' id="'.$this->id.'"';
        $content .= ' class="'.$this->place.'-block-rcl block-rcl';
        if($this->class) $content .= ' '.$this->class;
        $content .= '">';
        if($this->title) $content .= '<h4>'.$this->title.'</h4>';
        $content .= $cl_content;
        if($this->gallery) $content .= '<script>jQuery("#'.$this->gallery.'").bxSlider({
            pager:false,
            minSlides: 1,
            maxSlides: 20,
            slideWidth: 50,
            infiniteLoop:true,
            slideMargin: 5,
            moveSlides:1
            });</script>';
        $content .= '</div>';
        
        return $content;
    }
}
