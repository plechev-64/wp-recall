<?php

/*15.0.0*/
class RCL_navi{

    public $inpage;
    public $navi;
    public $cnt_data;
    public $num_page;
    public $get;
    public $page;
    public $offset;
    public $g_name;

    function __construct($inpage,$cnt_data,$get=false,$page=false,$getname='navi'){
        $this->navi=1;
        $this->g_name=$getname;
        if(isset($_GET[$this->g_name])) $this->navi = $_GET[$this->g_name];
        if($page) $this->navi = $page;
        $this->inpage = $inpage;
        $this->cnt_data = $cnt_data;
        $this->get = $get;
        $this->offset = ($this->navi-1)*$this->inpage;
        $this->limit();
    }

    function limit(){
        $limit_us = $this->offset.','.$this->inpage;
        if($this->inpage) $this->num_page = ceil($this->cnt_data/$this->inpage);
        else $this->num_page = 1;
        return $limit_us;
    }

    function navi(){
        global $post,$group_id,$user_LK;
        $class = 'rcl-navi';
        $page_navi = '';

        if($group_id){
                $prm = get_term_link((int)$group_id,'groups' );
                if($_GET['group-page']) $prm = rcl_format_url($prm).'group-page='.$_GET['group-page'];
        }else if($user_LK){
            $prm = get_author_posts_url($user_LK);
        }else{
            if(isset($post))$prm = get_permalink($post->ID);
        }

        if($this->inpage&&$this->cnt_data>$this->inpage){

            if(isset($prm))$redirect_url = rcl_format_url($prm);
            else $redirect_url = '#';

            if($redirect_url=='#'||$group_id) $class .= ' ajax-navi';

            $page_navi = '<div class="'.$class.'">';
            $next = $this->navi + 3;
            $prev = $this->navi - 4;
            if($prev==1) $page_navi .= '<a href="'.$redirect_url.$this->g_name.'=1'.$this->get.'">1</a>';
            for($a=1;$a<=$this->num_page;$a++){
                if($a==1&&$a<=$prev&&$prev!=1) $page_navi .= '<a href="'.$redirect_url.$this->g_name.'=1'.$this->get.'">1</a> ... ';
                if($prev<$a&&$a<=$next){
                    if($this->navi==$a) $page_navi .= '<span>'.$a.'</span>';
                    else $page_navi .= '<a href="'.$redirect_url.$this->g_name.'='.$a.''.$this->get.'">'.$a.'</a>';
                }
            }
            if($next<$this->num_page&&$this->num_page!=$next+1) $page_navi .= ' ... <a href="'.$redirect_url.'navi='.$this->num_page.''.$this->get.'">'.$this->num_page.'</a>';
            if($this->num_page==$next+1) $page_navi .= '<a href="'.$redirect_url.$this->g_name.'='.$this->num_page.''.$this->get.'">'.$this->num_page.'</a>';
            $page_navi .= '</div>';
        }

        return $page_navi;
    }
}

/*15.0.0*/
function rcl_update_dinamic_files(){
    rcl_update_scripts();
    //rcl_minify_style();
}

/*перед удалением удалить все применения данной функции в ядре*/
function rcl_update_scripts(){
    global $rcl_options;

    $path = RCL_UPLOAD_PATH.'scripts';
    
    rcl_remove_dir($path);

    wp_mkdir_p($path);
    
    $footer_scripts = apply_filters('file_footer_scripts_rcl','');
    
    if($footer_scripts){
        $filename = 'footer-scripts.js';
        $file_src = $path.'/'.$filename;
        $f = fopen($file_src, 'w');

        $scripts = "jQuery(function($){".$footer_scripts."});";
        $scripts = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $scripts);
        $scripts =  preg_replace('/ {2,}/',' ',$scripts);
        fwrite($f, $scripts);
        fclose($f);
    }

    $header_scripts = apply_filters('file_scripts_rcl','');
    
    if($header_scripts){
        
        $opt_slider = "''";
        if(isset($rcl_options['slide-pause'])&&$rcl_options['slide-pause']){
            $pause = $rcl_options['slide-pause']*1000;
            $opt_slider = "{auto:true,pause:$pause}";
        }
        
        $filename = 'header-scripts.js';
        $file_src = $path.'/'.$filename;
        $f = fopen($file_src, 'w');
        
        $scripts = "var SliderOptions = ".$opt_slider.";"
                . "jQuery(function(){"
                . $header_scripts
                . "});";
        $scripts = apply_filters('rcl_functions_js',$scripts);
        $scripts = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $scripts);
        $scripts =  preg_replace('/ {2,}/',' ',$scripts);
        fwrite($f, $scripts);
        fclose($f);
    }
}

add_action('rcl_area_actions','rcl_area_header',10);
function rcl_area_header(){
    do_action('rcl_area_header');
}

add_action('rcl_area_counters','rcl_area_sidebar',10);
function rcl_area_sidebar(){
    do_action('rcl_area_sidebar');
}

add_action('rcl_area_extra','rcl_area_footer',10);
function rcl_area_footer(){
    do_action('rcl_area_footer');
}

add_action('rcl_area_details','rcl_area_content',10);
function rcl_area_content(){
    do_action('rcl_area_content');
}

add_action('rcl_area_before','rcl_before',10);
function rcl_before(){
    global $user_LK;
    echo apply_filters( 'rcl_before_lk', '', $user_LK );
}

add_action('rcl_area_after','rcl_after',10);
function rcl_after(){
    global $user_LK;
    echo apply_filters( 'rcl_after_lk', '', $user_LK );
}

add_action('rcl_area_actions','rcl_header',10);
function rcl_header(){
    global $user_LK;
    echo apply_filters('rcl_header_lk','',$user_LK);
}

add_action('rcl_area_counters','rcl_sidebar',10);
function rcl_sidebar(){
    global $user_LK;
    echo apply_filters('rcl_sidebar_lk','',$user_LK);
}

add_action('rcl_area_details','rcl_content',10);
function rcl_content(){
    global $user_LK;
    echo apply_filters('rcl_content_lk','',$user_LK);
}

add_action('rcl_area_extra','rcl_footer',10);
function rcl_footer(){
    global $user_LK;
    echo apply_filters('rcl_footer_lk','',$user_LK);
}