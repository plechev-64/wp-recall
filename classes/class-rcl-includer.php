<?php

class Rcl_Includer{
    
    public $cache = 0;
    public $cache_time = 3600;
    public $place;
    public $files = array();
    public $minify_dir;
    public $is_minify;
    public $deregister_styles = array();
    public $deregister_scripts = array();
    
    function __construct(){ 
        global $rcl_styles;
        $this->place = (!isset($rcl_styles['header']))? 'header': 'footer';
    }

    function include_styles(){
        global $rcl_styles;
        
        $this->is_minify = rcl_get_option('minify_css');
        
        $this->minify_dir = RCL_UPLOAD_PATH.'css';
        
        $this->init_dir();

        //Если место подключения header
        if($this->place=='header'){
            if(!$rcl_styles) $rcl_styles = array();
            $rcl_styles = $this->regroup($rcl_styles);
        }
        
        $rcl_styles = $this->dequeue(apply_filters('rcl_pre_include_styles', $rcl_styles));
        
        if(!isset($rcl_styles[$this->place])) return false;
        
        $styles = array();
        foreach($rcl_styles[$this->place] as $key => $url) {
            
            //Если минификация не используется, то подключаем файлы как обычно
            if(!$this->is_minify){
                wp_enqueue_style( $key, $url );
                continue;
            }

            $this->files['css'][$key]['path'] = rcl_path_by_url($url);
            $this->files['css'][$key]['url'] = $url;
        }

        if(!isset($this->files['css'])||!$this->files['css']) return false;

        foreach($this->files['css'] as $id=>$file){
            $ids[] = $id.':'.filemtime($file['path']);
        }

        $filename = md5(implode(',',$ids)).'.css';
        $filepath = RCL_UPLOAD_PATH.'css/'.$filename;
        
        if(!file_exists(wp_normalize_path($filepath))){
            $this->create_file($filename,'css');
        }
        
        wp_enqueue_style( 'rcl-'.$this->place, RCL_UPLOAD_URL.'css/'.$filename);

    }
    
    function include_scripts(){
        global $rcl_scripts;
        
        $this->is_minify = rcl_get_option('minify_js');
        
        $this->minify_dir = RCL_UPLOAD_PATH.'js';
        
        $this->init_dir();

        //Если место подключения header
        if($this->place=='header'){
            if(!$rcl_scripts) $rcl_scripts = array();
            $rcl_scripts = $this->regroup($rcl_scripts); 
        }
        
        $rcl_scripts = $this->dequeue(apply_filters('rcl_pre_include_scripts', $rcl_scripts));
        
        if(!isset($rcl_scripts[$this->place])) return false;
        
        $in_footer = ($this->place=='footer')? true: false;

        foreach($rcl_scripts[$this->place] as $key => $url) {
            
            //Если минификация не используется, то подключаем файлы как обычно
            if(!$this->is_minify){ 
                $parents = (isset($rcl_scripts['parents'][$key]))? $parents = array_merge($rcl_scripts['parents'][$key],array('jquery')): array('jquery');
                wp_enqueue_script( $key, $url, $parents, VER_RCL, $in_footer );
                continue;
            }

            $this->files['js'][$key]['path'] = rcl_path_by_url($url);
            $this->files['js'][$key]['url'] = $url;
        }

        if(!isset($this->files['js'])||!$this->files['js']) return false;
        
        $parents = array('jquery');
        foreach($this->files['js'] as $key=>$file){
            $ids[] = $key.':'.filemtime($file['path']);
            if((isset($rcl_scripts['parents'][$key]))){
                $parents = array_merge($rcl_scripts['parents'][$key],$parents);
            }
        }

        $filename = md5(implode(',',$ids)).'.js';
        $filepath = RCL_UPLOAD_PATH.'js/'.$filename;
        
        if(!file_exists($filepath)){
            $this->create_file($filename,'js');
        }
        
        wp_enqueue_script( 'rcl-'.$this->place.'-scripts', RCL_UPLOAD_URL.'js/'.$filename,$parents,VER_RCL,$in_footer);

    }
    
    function init_dir(){
        if($this->is_minify){
            if(!is_dir($this->minify_dir)){
                mkdir($this->minify_dir);
                chmod($this->minify_dir, 0755);
            }
        }else{
            if(is_dir($this->minify_dir))
                rcl_remove_dir($this->minify_dir);
        }
    }
    
    function create_file($filename,$type){

        $filepath = $this->minify_dir.'/'.$filename;
        
        $f = fopen($filepath, 'w');

        $string = '';
        foreach($this->files[$type] as $id=>$file){
            
            $file_string = file_get_contents($file['path']);
            
            if($type=='css'){
                $urls = array();
                preg_match_all('/(?<=url\()[A-zА-я0-9\-\_\/\"\'\.\?\s]*(?=\))/iu', $file_string, $urls);
                $addon = (rcl_addon_path($file['path']))? true: false;

                if($urls[0]){
                    
                    foreach($urls[0] as $u){
                        $imgs[] = ($addon)? rcl_addon_url(trim($u,'\',\"'),$file['path']): RCL_URL.'css/'.trim($u,'\',\"');
                        $us[] = $u;
                    }
                    
                    $file_string = str_replace($us, $imgs, $file_string);
                }
            }
            
            $string .= $file_string;
            
        }
        
        if($type=='js'){
            // удаляем строки начинающиеся с //
            $string = preg_replace('#//.*#','',$string);
        }
        
        // удаляем многострочные комментарии /* */
        $string = preg_replace('#/\*(?:[^*]*(?:\*(?!/))*)*\*/#','',$string);
        // удаляем пробелы, переносы, табуляцию
        $string = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $string);
        $string =  preg_replace('/ {2,}/',' ',$string);

        fwrite($f, $string);
        fclose($f);
        
        return $filepath;
    }
    
    function regroup($array){
        $new_array = array();
        
        if(isset($array['dequeue'])){
            $new_array['dequeue'] = $array['dequeue'];
            unset($array['dequeue']);
        }

        $new_array[$this->place] = $array;

        if(isset($new_array[$this->place]['footer'])){
            $new_array['footer'] = $new_array[$this->place]['footer'];
            unset($new_array[$this->place]['footer']);
        }

        $array = $new_array;
        
        return $array;
    }
    
    function dequeue($included){
        
        if(isset($included['dequeue'])){
            
            foreach($included['dequeue'] as $key){
                
                if(isset($included['header'][$key])){
                    unset($included['header'][$key]);
                }else if(isset($included['footer'][$key])){
                    unset($included['footer'][$key]);
                }
                
            }
            
        }
        
        return $included;
    }
    
    function get_ajax_includes(){
        
        $content = '';
        
        $styles = $this->get_ajax_styles();
        
        if($styles)
            $content .= $styles;
        
        $scripts = $this->get_ajax_scripts();
        
        if($scripts)
            $content .= $scripts;
        
        return $content;
        
    }
    
    function get_ajax_scripts(){

        $wp_scripts = wp_scripts();
    
        $remove = array(
            'jquery'
        );

        $scriptsArray = array();
        foreach($wp_scripts->queue as $k => $script_id){

            if(in_array($script_id, $remove)) continue;

            if(strpos($script_id, 'admin') !== false) continue;

            $scriptsArray[] = $script_id;

        }

        if(!$scriptsArray) return false;
        
        ob_start();

        $wp_scripts->do_items($scriptsArray);

        $scripts = ob_get_contents();

        ob_end_clean();
        
        return $scripts;
        
    }
    
    function get_ajax_styles(){
        
        $wp_scripts = wp_styles();

        $scriptsArray = array();
        foreach($wp_scripts->queue as $k => $script_id){

            if(strpos($script_id, 'admin') !== false) continue;

            $scriptsArray[] = $script_id;

        }

        if(!$scriptsArray) return false;

        ob_start();

        $wp_scripts->do_items($scriptsArray);

        $scripts = ob_get_contents();

        ob_end_clean();
        
        return $scripts;
        
    }
    
    function get_ajax_src_list_includes(){

        $styles = $this->get_ajax_src_list_styles();

        $scripts = $this->get_ajax_src_list_scripts();

        return array_merge($styles,$scripts);
        
    }
    
    function get_ajax_src_list_scripts(){
        
        $wp_scripts = wp_scripts();
    
        $remove = array(
            'jquery'
        );

        $scriptsArray = array();
        
        foreach($wp_scripts->queue as $k => $script_id){

            if(in_array($script_id, $remove)) continue;

            if(strpos($script_id, 'admin') !== false) continue;

            $obj = $wp_scripts->registered[$script_id];

            $scriptsArray[] = $obj->src;

        }
        
        return $scriptsArray;
        
    }
    
    function get_ajax_src_list_styles(){
        
        $wp_scripts = wp_styles();

        $scriptsArray = array();
        foreach($wp_scripts->queue as $k => $script_id){

            if(strpos($script_id, 'admin') !== false) continue;

            $obj = $wp_scripts->registered[$script_id];

            $scriptsArray[] = $obj->src;

        }
        
        return $scriptsArray;
        
    }
}

//подключаем стилевой файл дополнения
function rcl_enqueue_style($id, $url, $footer = false){
    global $rcl_styles;
    
    if(is_admin() || doing_action('login_enqueue_scripts')){
        
        wp_enqueue_style( $id, $url);
        
        return;
        
    }
    
    $search = str_replace('\\','/',ABSPATH);
    $url = str_replace('\\','/',$url);
    
    //если определили, что указан абсолютный путь, то получаем URL до файла style.css
    if(stristr($url,$search)){
        $url = rcl_addon_url('style.css',$url);
    }
    
    //если скрипт выводим в футере
    if($footer||isset($rcl_styles['header'])){
        //если не обнаружен дубль скрипта в хедере
        if(!isset($rcl_styles['header'][$id]))
            $rcl_styles['footer'][$id] = $url;
    }else{
        $rcl_styles[$id] = $url;
    }  
}

function rcl_enqueue_script($id, $url, $parents = array(), $in_footer=false){
    global $rcl_scripts;
    
    if(is_admin() || doing_action('login_enqueue_scripts')){
        
        wp_enqueue_script( $id, $url, $parents, false, $in_footer);
        
        return;
        
    }
    
    //если скрипт выводим в футере
    if($in_footer||isset($rcl_scripts['header'])){
        //если не обнаружен дубль скрипта в хедере
        if(!isset($rcl_scripts['header'][$id]))
            $rcl_scripts['footer'][$id] = $url;
    }else{
        $rcl_scripts[$id] = $url; 
    }
    
    if($parents) 
        $rcl_scripts['parents'][$id] = $parents;
}

function rcl_dequeue_style($style){
    global $rcl_styles;
    
    if(!isset($rcl_styles['dequeue']))
        $rcl_styles['dequeue'] = array();
    
    if(is_array($style)){
        $rcl_styles['dequeue'] = array_merge($rcl_styles['dequeue'], $style);
    }else{
        $rcl_styles['dequeue'][] = $style;
    }

}

function rcl_dequeue_script($script){
    global $rcl_scripts;
    
    if(!isset($rcl_scripts['dequeue']))
        $rcl_scripts['dequeue'] = array();
    
    if(is_array($style)){
        $rcl_scripts['dequeue'] = array_merge($rcl_scripts['dequeue'], $script);
    }else{
        $rcl_scripts['dequeue'][] = $script;
    }

}

add_action('wp_enqueue_scripts','rcl_include_scripts',10);
add_action('wp_footer','rcl_include_scripts',10);
function rcl_include_scripts(){  
    
    do_action('rcl_enqueue_scripts');
    
    $Rcl_Include = new Rcl_Includer();
    $Rcl_Include->include_styles();
    $Rcl_Include->include_scripts();
}

//сбрасываем массивы зарегистрированных скриптов и стилей при вызове вкладки через ajax
add_action('rcl_init_ajax_tab','rcl_reset_wp_dependencies', 10);
function rcl_reset_wp_dependencies(){
    global $wp_scripts, $wp_styles;

    $wp_scripts->queue = array();
    $wp_styles->queue = array();
    
}

//цепляем код подключения скриптов и стилей вызванных внутри вкладки
add_filter('rcl_ajax_tab_content','rcl_add_registered_scripts');
function rcl_add_registered_scripts($content){
    
    $Rcl_Include = new Rcl_Includer();
    
    add_filter('script_loader_src','rcl_ajax_edit_version_scripts');
    
    $content = $Rcl_Include->get_ajax_includes() . $content;
    
    return $content;
    
}

//добавление массива подключаемых скриптов к возвращаемому результату вызова вкладки через ajax
//для их подключения через функцию getScripts
//add_filter('rcl_ajax_tab_result','rcl_add_src_list_includes');
function rcl_add_src_list_includes($result){
    $Rcl_Include = new Rcl_Includer();
    $result['includes'] = $Rcl_Include->get_ajax_src_list_includes();
    return $result;
}

//генерируем свою версию подключаемых скриптов при ajax-вызове вкладки
function rcl_ajax_edit_version_scripts($src){
    
    if(strpos($src,'wp-includes/js/jquery/jquery.js')) return false;
    
    $srcData = explode('?',$src);
    
    if(isset($srcData[1])){
        
        $str = 'ver='.md5(current_time('mysql'));
        
        $src = str_replace($srcData[1], $str, $src);
        
    }

    return $src;
}