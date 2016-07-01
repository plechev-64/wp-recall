<?php

class Rcl_Includer{
    
    public $cache = 0;
    public $cache_time = 3600;
    public $place;
    public $files = array();
    public $minify_dir;
    public $is_minify;
    
    function __construct(){ 
        global $rcl_styles;
        $this->place = (!isset($rcl_styles['header']))? 'header': 'footer';
    }
    
    function include_styles(){
        global $rcl_styles,$rcl_options,$user_ID;
        
        $this->is_minify = (isset($rcl_options['minify_css']))? $rcl_options['minify_css']: 0;
        
        $this->minify_dir = RCL_UPLOAD_PATH.'css';
        
        $this->init_dir();

        //Если место подключения header
        if($this->place=='header'){
            
            if(!$rcl_styles) $rcl_styles = array();

            $css_dir = RCL_URL.'css/';

            $primary = array(
                'rcl-primary'           =>  $css_dir.'style.css',
                'rcl-slider'            =>  $css_dir.'slider.css',
                'rcl-users-list'        =>  $css_dir.'users.css',
                'rcl-register-form'     =>  $css_dir.'regform.css'
            );
            
            //если используем recallbar, то подключаем его стили
            if(isset($rcl_options['view_recallbar'])&&$rcl_options['view_recallbar']){
                $primary['rcl-bar'] = $css_dir.'recallbar.css';
            }

            $rcl_styles = array_merge($primary, $rcl_styles);
            
            $rcl_styles = $this->regroup($rcl_styles);
        }
        
        if(!isset($rcl_styles[$this->place])) return false;
        
        $styles = array();
        foreach($rcl_styles[$this->place] as $key => $value) {
            
            //Если минификация не используется, то подключаем файлы как обычно
            if(!$this->is_minify){
                wp_enqueue_style( $key, rcl_path_to_url($value) );
                continue;
            }

            $this->files['css'][$key] = $value;
        }

        if(!isset($this->files['css'])||!$this->files['css']) return false;

        foreach($this->files['css'] as $id=>$url){
            $ids[] = $id;
        }

        $filename = md5(implode(',',$ids)).'.css';
        $filepath = RCL_UPLOAD_PATH.'css/'.$filename;
        
        if(!file_exists($filepath)){
            $this->create_file($filename,'css');
        }
        
        wp_enqueue_style( 'rcl-'.$this->place, RCL_UPLOAD_URL.'css/'.$filename);

    }
    
    function include_scripts(){
        global $rcl_scripts,$rcl_options,$user_ID;
        
        $this->is_minify = (isset($rcl_options['minify_js']))? $rcl_options['minify_js']: 0;
        
        $this->minify_dir = RCL_UPLOAD_PATH.'js';
        
        $this->init_dir();

        //Если место подключения header
        if($this->place=='header'){
            if(!$rcl_scripts) $rcl_scripts = array();
            $rcl_scripts = $this->regroup($rcl_scripts);
            
        }
        
        if(!isset($rcl_scripts[$this->place])) return false;
        
        $in_footer = ($this->place=='footer')? true: false;
        
        $scripts = array();
        foreach($rcl_scripts[$this->place] as $key => $url) {
            
            //Если минификация не используется, то подключаем файлы как обычно
            if(!$this->is_minify){ 
                $parents = (isset($rcl_scripts['parents'][$key]))? $parents = array_merge($rcl_scripts['parents'][$key],array('jquery')): array('jquery');
                wp_enqueue_script( $key, rcl_path_to_url($url),$parents,VER_RCL,$in_footer );
                continue;
            }

            $this->files['js'][$key] = $url;
        }

        if(!isset($this->files['js'])||!$this->files['js']) return false;
        
        $parents = array('jquery');
        foreach($this->files['js'] as $key=>$url){
            $ids[] = $key;
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
        foreach($this->files[$type] as $id=>$url){
            
            $file_string = file_get_contents(rcl_path_by_url($url));
            
            if($type=='css'){
                $urls = '';
                preg_match_all('/(?<=url\()[A-zА-я0-9\-\_\/\"\'\.\?\s]*(?=\))/iu', $file_string, $urls);
                $addon = (rcl_addon_path($url))? true: false;

                if($urls[0]){
                    foreach($urls[0] as $u){
                        $imgs[] = ($addon)? rcl_addon_url(trim($u,'\',\"'),$url): RCL_URL.'css/'.trim($u,'\',\"');
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

        $new_array[$this->place] = $array;

        if(isset($new_array[$this->place]['footer'])){
            $new_array['footer'] = $new_array[$this->place]['footer'];
            unset($new_array[$this->place]['footer']);
        }

        $array = $new_array;
        
        return $array;
    }
}

//подключаем стилевой файл дополнения
function rcl_enqueue_style($id,$url,$footer=false){
    global $rcl_styles;
    
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

function rcl_enqueue_script($id,$url,$parents=array(),$footer=false){
    global $rcl_scripts;
    
    //если скрипт выводим в футере
    if($footer||isset($rcl_scripts['header'])){
        //если не обнаружен дубль скрипта в хедере
        if(!isset($rcl_scripts['header'][$id]))
            $rcl_scripts['footer'][$id] = $url;
    }else{
        $rcl_scripts[$id] = $url; 
    }
    
    if($parents) 
        $rcl_scripts['parents'][$id] = $parents;
}

add_action('wp_enqueue_scripts','rcl_include_scripts',10);
add_action('wp_footer','rcl_include_scripts',10);
function rcl_include_scripts(){  
    
    do_action('rcl_enqueue_scripts');
    
    $Rcl_Include = new Rcl_Includer();
    $Rcl_Include->include_styles();
    $Rcl_Include->include_scripts();
}