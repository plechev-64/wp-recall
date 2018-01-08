<?php

add_action('wp','rcl_hand_addon_update');
function rcl_hand_addon_update(){
    if(!isset($_GET['rcl-addon-update'])||$_GET['rcl-addon-update']!='now') return false;
    rcl_check_addon_update();
}

add_action('rcl_cron_twicedaily','rcl_check_addon_update',10);
function rcl_check_addon_update(){
    global $active_addons;
    
    $paths = rcl_get_addon_paths();

    foreach($paths as $path){
        if(file_exists($path)){
            $addons = scandir($path,1);
            $a=0;
            foreach((array)$addons as $namedir){
                $addon_dir = $path.'/'.$namedir;
                if(!is_dir($addon_dir)) continue;
                $index_src = $addon_dir.'/index.php';
                if(!file_exists($index_src)) continue;
                $info_src = $addon_dir.'/info.txt';
                if(file_exists($info_src)){
                    $info = file($info_src);
                    $addons_data[$namedir] = rcl_parse_addon_info($info);
                    $addons_data[$namedir]['src'] = $index_src;
                    $a++;
                    flush();
                }
            }
        }
    }
    
    if(!$addons_data) return false;
    
    rcl_add_log(__('Sending request to the update server to get the latest versions of the installed add-ons','wp-recall'));

    $url = RCL_SERVICE_HOST."/products-files/info/light-info.xml";

    $xml_array = @simplexml_load_file($url);
    
    if(!$xml_array){
        rcl_add_log(
            __('Failed to open file with add-ons data to check for updates','wp-recall'),
            $url
        ); 
        exit;
    }

    $need_update = array(); $ver = 0;
    
    foreach($xml_array as $xml_data){
        
        if(!$xml_data) continue;
        
        $key = (string)$xml_data->slug;
        
        if(!isset($addons_data[$key])) continue;
        
        $last_ver = (string)$xml_data->version;
        
        $ver = version_compare($last_ver,$addons_data[$key]['version']);
        
        if($ver>0){
            $addons_data[$key]['new-version'] = $last_ver;
            $need_update[$key] = $addons_data[$key];
        }
    }
    
    update_option('rcl_addons_need_update',$need_update);

}

add_action('rcl_cron_daily','rcl_send_addons_data',10);
function rcl_send_addons_data(){
    global $active_addons;
    
    $paths = rcl_get_addon_paths();

    foreach($paths as $path){
        if(file_exists($path)){
            $addons = scandir($path,1);
            $a=0;
            foreach((array)$addons as $namedir){
                $addon_dir = $path.'/'.$namedir;
                if(!is_dir($addon_dir)) continue;
                $index_src = $addon_dir.'/index.php';
                if(!file_exists($index_src)) continue;
                $info_src = $addon_dir.'/info.txt';
                if(file_exists($info_src)){
                    $info = file($info_src);
                    $addons_data[$namedir] = rcl_parse_addon_info($info);
                    $addons_data[$namedir]['src'] = $index_src;
                    $a++;
                    flush();
                }
            }
        }
    }
    
    if(!$addons_data) return false;

    $need_update = array();    
    $get = array();

    foreach($addons_data as $key=>$addon){
        $status = (isset($active_addons[$key]))?1:0;
        $get[] = $key.':'.$addon['version'].':'.$status;
    }
    
    $addonlist = implode(';',$get);

    $url = RCL_SERVICE_HOST."/products-files/api/update.php"
            . "?rcl-addon-action=version-check-list&compress=1&noreply=1";
    
    $addonlist = gzencode($addonlist);
    $addonlist = strtr(base64_encode($addonlist), '+/=', '-_,');		
    
    $data = array(
        'rcl-version' => VER_RCL,
        'addons' => $addonlist,
        'host' => $_SERVER['SERVER_NAME']
    );
    
    wp_remote_post( $url, array('body' => $data) );

}

rcl_ajax_action('rcl_get_details_addon', false);
function rcl_get_details_addon(){
    
    $slug = $_POST['slug'];
    
    $url = RCL_SERVICE_HOST.'/products-files/api/add-ons.php'
            . '?rcl-addon-info=get-details';

    $data = array(
        'addon' => $slug,
        'rcl-key' => get_option('rcl-key'),
        'rcl-version' => VER_RCL,
        'host' => $_SERVER['SERVER_NAME']
    );
    
    $response = wp_remote_post( $url, array('body' => $data) );

    if ( is_wp_error( $response ) ) {
       $error_message = $response->get_error_message();
       echo __('Error').': '.$error_message; exit;
    }

    $result = json_decode($response['body'], true);

    if(is_array($result)&&isset($result['error'])){
        wp_send_json($result);
    }
    
    $content = wpautop(
        links_add_target($result['content'])
    );

    wp_send_json(array(
        'title' => $result['title'],
        'content' => $content
    ));
    
}

rcl_ajax_action('rcl_update_addon', false);
function rcl_update_addon(){

    $addon = $_POST['addon'];
    $need_update = get_option('rcl_addons_need_update');
    if(!isset($need_update[$addon])){
        wp_send_json(array('error'=>__('Дополнение не требует обновления.','wp-recall')));
    }
    
    if(!class_exists('ZipArchive')){
        wp_send_json(array('error'=>__('Обновление невозможно! Не определен класс ZipArchive.','wp-recall')));
    }

    $activeaddons = get_site_option('rcl_active_addons');

    $pathdir = RCL_TAKEPATH.'update/';
    $new_addon = $pathdir.$addon.'.zip';

    if(!file_exists($pathdir)){
        mkdir($pathdir);
        chmod($pathdir, 0755);
    }
    
    $url = RCL_SERVICE_HOST.'/products-files/api/update.php'
            . '?rcl-addon-action=update';

    $data = array(
        'addon' => $addon,
        'rcl-key' => get_option('rcl-key'),
        'rcl-version' => VER_RCL,
        'addon-version' => $need_update[$addon]['version'],
        'host' => $_SERVER['SERVER_NAME']
    );
    
    $response = wp_remote_post( $url, array('body' => $data) );

    if ( is_wp_error( $response ) ) {
       $error_message = $response->get_error_message();
       echo __('Error').': '.$error_message; exit;
    }

    $result = json_decode($response['body'], true);

    if(is_array($result)&&isset($result['error'])){
        wp_send_json($result);
    }

    $put = file_put_contents($new_addon, $response['body']);
    
    if($put === false){
        wp_send_json(array('error'=>__('The files failed to be uploaded!','wp-recall')));
    }

    $zip = new ZipArchive;

    $res = $zip->open($new_addon);

    if($res === TRUE){

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if($i==0) $dirzip = $zip->getNameIndex($i);
            if($zip->getNameIndex($i)==$dirzip.'info.txt'){
                    $info = true; break;
            }
        }

        if(!$info){
            $zip->close();
            wp_send_json(array('error'=>__('Update has incorrect title!','wp-recall')));
        }
        
        $paths = rcl_get_addon_paths();
        
        foreach($paths as $path){
            if(file_exists($path.'/'.$addon.'/')){
                $dirpath = $path;
                break;
            }
        }

        if(file_exists($dirpath.'/')){

            if(isset($activeaddons[$addon]))
                rcl_deactivate_addon($addon);
            
            rcl_delete_addon($addon,false);

            $rs = $zip->extractTo($dirpath.'/');

            if(isset($activeaddons[$addon]))
                rcl_activate_addon($addon,true,$dirpath);

        }

        $zip->close();
        unlink($new_addon);
        
        unset($need_update[$addon]);
        update_option('rcl_addons_need_update',$need_update);

        wp_send_json(array(
            'success' => $addon
        ));

    }else{

        wp_send_json(array(
            'error' => __('Unable to open update archive!','wp-recall')
        ));

    }
}

