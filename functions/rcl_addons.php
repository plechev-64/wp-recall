<?php
//активация указанного дополнения
function rcl_activate_addon($addon){
    $active_addons = get_site_option('rcl_active_addons');
    $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

    foreach($paths as $k=>$path){
        if ( false !== strpos($path, '\\') ) $path = str_replace('\\','/',$path);
        $index_src = $path.'/'.$addon.'/index.php';
        if(!is_readable($index_src)) continue;

        $active_addons[$addon]['path'] = $path.'/'.$addon;
        $active_addons[$addon]['priority'] = (!$k)? 1: 0;
        $install_src = $path.'/'.$addon.'/activate.php';

        if(file_exists($install_src)){
            include($install_src);
            break;
        }
    }

    update_site_option('rcl_active_addons',$active_addons);

    do_action('rcl_activate_'.$addon,$active_addons[$addon]);
}
//деактивация указанного дополнения
function rcl_deactivate_addon($addon){
    $active_addons = get_site_option('rcl_active_addons');
    $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

    foreach($paths as $path){
        if(is_readable($path.'/'.$addon.'/deactivate.php')){
            include($path.'/'.$addon.'/deactivate.php');
            break;
        }
    }

    unset($active_addons[$addon]);

    update_site_option('rcl_active_addons',$active_addons);

    do_action('rcl_deactivate_'.$addon);
}
//удаление дополнения
function rcl_delete_addon($addon){
    $active_addons = get_site_option('rcl_active_addons');
    $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on');

    foreach($paths as $path){
        if(is_readable($path.'/'.$addon.'/delete.php')) include($path.'/'.$addon.'/deactivate.php');
        rcl_remove_dir($path.'/'.$addon);
    }

    unset($active_addons[$addon]);

    update_site_option('rcl_active_addons',$active_addons);

    do_action('rcl_delete_'.$addon);
}
//обновление файлов header-scripts.js и footer-scripts.js
function rcl_update_scripts(){
    global $rcl_options;

    $path = RCL_UPLOAD_PATH.'scripts';

    wp_mkdir_p($path);

    $filename = 'footer-scripts.js';
    $file_src = $path.'/'.$filename;
    $f = fopen($file_src, 'w');

    $scripts = '';
    $scripts = apply_filters('file_footer_scripts_rcl',$scripts);
    if(!isset($scripts)) return false;
    if($scripts) $scripts = "jQuery(function($){".$scripts."});";
    $scripts = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $scripts);
    $scripts =  preg_replace('/ {2,}/',' ',$scripts);
    fwrite($f, $scripts);
    fclose($f);


    $opt_slider = "''";
    if(isset($rcl_options['slide-pause'])&&$rcl_options['slide-pause']){
        $pause = $rcl_options['slide-pause']*1000;
        $opt_slider = "{auto:true,pause:$pause}";
    }

    $filename = 'header-scripts.js';
    $file_src = $path.'/'.$filename;
    $f = fopen($file_src, 'w');

    $scripts = "var SliderOptions = ".$opt_slider.";"
            . "jQuery(function(){";
    $scripts = apply_filters('file_scripts_rcl',$scripts);
    $scripts .= "});";
    $scripts = apply_filters('rcl_functions_js',$scripts);
    $scripts = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $scripts);
    $scripts =  preg_replace('/ {2,}/',' ',$scripts);
    fwrite($f, $scripts);
    fclose($f);
}
//парсим содержимое файла info.txt дополнения
function rcl_parse_addon_info($info){
    $addon_data = array();
    $cnt = count($info);

    if($cnt==1) $info = explode(';',$info[0]);

    foreach((array)$info as $string){

        if($cnt>1) $string = str_replace(';','',$string);

        if ( false !== strpos($string, 'Name:') ){
                preg_match_all('/(?<=Name\:)[A-zА-я0-9\-\_\:\/\.\,\?\=\&\@\s\(\)]*/iu', $string, $string_value);
                $addon_data['name'] = trim($string_value[0][0]);
                continue;
        }
        if ( false !== strpos($string, 'Version:') ){
                preg_match_all('/(?<=Version\:)[A-zА-я0-9\-\_\:\/\.\,\?\=\&\@\s]*/iu', $string, $version_value);
                $addon_data['version'] = trim($version_value[0][0]);
                continue;
        }
        if ( false !== strpos($string, 'Support Core:') ){
                preg_match_all('/(?<=Support Core\:)[A-zА-я0-9\-\_\:\/\.\,\?\=\&\@\s]*/iu', $string, $version_value);
                $addon_data['support-core'] = trim($version_value[0][0]);
                continue;
        }
        if ( false !== strpos($string, 'Description:') ){
                preg_match_all('/(?<=Description\:)[A-zА-я0-9\-\_\:\/\.\,\?\=\&\@\s\(\)]*/iu', $string, $desc_value);
                $addon_data['description'] = trim($desc_value[0][0]);
                continue;
        }
        if ( false !== strpos($string, 'Author:') ){
                preg_match_all('/(?<=Author\:)[A-zА-я0-9\-\_\:\/\.\,\?\=\&\@\s]*/iu', $string, $author_value);
                $addon_data['author'] = trim($author_value[0][0]);
                continue;
        }
        if ( false !== strpos($string, 'Url:') ){
                preg_match_all('/(?<=Url\:)[A-zА-я0-9\-\_\:\/\.\?\=\&\@\s]*/iu', $string, $url_value);
                $addon_data['url'] = trim($url_value[0][0]);
                continue;
        }
        if ( false !== strpos($string, 'Add-on URI:') ){
                preg_match_all('/(?<=Add-on URI\:)[A-zА-я0-9\-\_\:\/\.\?\=\&\@\s]*/iu', $string, $url_value);
                $addon_data['add-on-uri'] = trim($url_value[0][0]);
                continue;
        }
        if ( false !== strpos($string, 'Author URI:') ){
                preg_match_all('/(?<=Author URI\:)[A-zА-я0-9\-\_\:\/\.\?\=\&\@\s]*/iu', $string, $url_value);
                $addon_data['author-uri'] = trim($url_value[0][0]);
                continue;
        }
    }

    return $addon_data;
}

require_once("rcl_update.php");