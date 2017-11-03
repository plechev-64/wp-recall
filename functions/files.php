<?php

//получение абсолютного пути до указанного файла шаблона
function rcl_get_template_path($temp_name,$path=false){
    
    if(file_exists(RCL_TAKEPATH.'templates/'.$temp_name)) 
            return RCL_TAKEPATH.'templates/'.$temp_name;
    
    $path = ($path)? rcl_addon_path($path).'templates/': RCL_PATH.'templates/';
    
    $filepath = $path.$temp_name;
    
    $filepath = apply_filters('rcl_template_path',$filepath,$temp_name);
    
    if(!file_exists($filepath)) return false;

    return $filepath;
}

//подключение указанного файла шаблона с выводом
function rcl_include_template($temp_name, $path = false, $data = false){
    
    if ( ! empty( $data ) && is_array( $data ) ) {
        extract( $data );
    }
    
    $pathfile = rcl_get_template_path($temp_name, $path);
    
    if(!$pathfile) 
        return false;
    
    do_action('rcl_include_template_before', $temp_name, $path);
    
    include $pathfile;
    
    do_action('rcl_include_template_after', $temp_name, $path);
    
}

//подключение указанного файла шаблона без вывода
function rcl_get_include_template($temp_name, $path=false, $data = false){
    ob_start();
    rcl_include_template($temp_name, $path, $data);
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

//форматирование абсолютного пути в урл
function rcl_path_to_url($path){

    $DirTail = stristr($path,basename(content_url()));
    
    $url = untrailingslashit(dirname(WP_CONTENT_URL).'/'.$DirTail);
    
    return $url;
}

//получение абсолютного пути из указанного урла
function rcl_path_by_url($url){

    if(function_exists('wp_normalize_path')) 
        $url = wp_normalize_path($url);
    
    $string = stristr($url,basename(content_url()));

    $path = untrailingslashit(dirname(WP_CONTENT_DIR).'/'.$string);
    
    return $path;
}

function rcl_format_url($url, $tab_id = false, $subtab_id = false){
    $ar_perm = explode('?',$url);
    $cnt = count($ar_perm);
    if($cnt>1) $a = '&';
    else $a = '?';
    $url = $url.$a;
    if($tab_id) $url .= 'tab='.$tab_id;
    if($subtab_id) $url .= '&subtab='.$subtab_id;
    return $url;
}

function rcl_check_jpeg($f, $fix=false ){
# [070203]
# check for jpeg file header and footer - also try to fix it
    if ( false !== (@$fd = fopen($f, 'r+b' )) ){
        if ( fread($fd,2)==chr(255).chr(216) ){
            fseek ( $fd, -2, SEEK_END );
            if ( fread($fd,2)==chr(255).chr(217) ){
                fclose($fd);
                return true;
            }else{
                if ( $fix && fwrite($fd,chr(255).chr(217)) ){return true;}
                fclose($fd);
                return false;
            }
        }else{fclose($fd); return false;}
    }else{
        return false;
    }
}

function rcl_get_mime_type_by_ext($file_ext){
    
    if(!$file_ext) return false;
    
    $mimes = get_allowed_mime_types();
    
    foreach ($mimes as $type => $mime) {
        if (strpos($type, $file_ext) !== false) {
            return $mime;
        }
    }
    
    return false;
}

function rcl_get_mime_types($ext_array){
    
    if(!$ext_array) return false;
    
    $mTypes = array();
    
    foreach($ext_array as $ext){
        if(!$ext) continue;
        $mTypes[] = rcl_get_mime_type_by_ext($ext);
    }
    
    return $mTypes;
}

/*22-06-2015 Удаление папки с содержимым*/
function rcl_remove_dir($dir){
    $dir = untrailingslashit($dir);
    if(!is_dir($dir)) return false;
    if ($objs = glob($dir."/*")) {
       foreach($objs as $obj) {
             is_dir($obj) ? rcl_remove_dir($obj) : unlink($obj);
       }
    }
    rmdir($dir);
}
