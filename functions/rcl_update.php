<?php

add_action('wp','rcl_hand_addon_update');
function rcl_hand_addon_update(){
    if(!isset($_GET['rcl-addon-update'])||$_GET['rcl-addon-update']!='now') return false;
    rcl_check_addon_update();
}

add_action('rcl_cron_twicedaily','rcl_check_addon_update',10);
function rcl_check_addon_update(){
    global $active_addons;
    
    $paths = array(RCL_TAKEPATH.'add-on',RCL_PATH.'add-on') ;

    foreach($paths as $path){
        if(file_exists($path)){
            $addons = scandir($path,1);
            $a=0;
            foreach((array)$addons as $namedir){
                $addon_dir = $path.'/'.$namedir;
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

    $url = RCL_UPDATE_HOST."/products-files/api/update.php"
            . "?rcl-addon-action=version-check-list";
    
    $data = array(
        'rcl-version' => VER_RCL,
        'addons' => base64_encode($addonlist),
        'host' => $_SERVER['SERVER_NAME']
    );
    
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );
    
    $context  = stream_context_create($options);
    $xml_array = file_get_contents($url, false, $context);
    
    if(!$xml_array){
        $log['error'] = __('Unable to retrieve the file from the server!','wp-recall');
        echo json_encode($log); exit;
    }
    
    $xml_array = json_decode($xml_array, true);

    if(isset($xml_array['error'])){
        echo json_encode($xml_array); exit;
    }
    
    $ver = 0;    
    foreach($xml_array as $addondata){
        if(!$addondata) continue;
        
        $key = $addondata['slug'];
        
        $ver = version_compare($addondata['version'],$addons_data[$key]['version']); 
        if($ver>0){  
            
            $addons_data[$key]['new-version'] = $addondata['version'];
            $need_update[$key] = $addons_data[$key];
        }
    }
    
    update_option('rcl_addons_need_update',$need_update);

}

add_action('wp_ajax_rcl_update_addon','rcl_update_addon');
function rcl_update_addon(){

    $addon = $_POST['addon'];
    $need_update = get_option('rcl_addons_need_update');
    if(!isset($need_update[$addon])) return false;

    $activeaddons = get_site_option('rcl_active_addons');

    $url = RCL_UPDATE_HOST.'/products-files/api/update.php'
            . '?rcl-addon-action=update';

    $data = array(
        'addon' => $addon,
        'rcl-key' => get_option('rcl-key'),
        'rcl-version' => VER_RCL,
        'addon-version' => $need_update[$addon]['version'],
        'host' => $_SERVER['SERVER_NAME']
    );

    $pathdir = RCL_TAKEPATH.'update/';
    $new_addon = $pathdir.$addon.'.zip';

    if(!file_exists($pathdir)){
        mkdir($pathdir);
        chmod($pathdir, 0755);
    }

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );
    $context  = stream_context_create($options);
    $archive = file_get_contents($url, false, $context);
    
    //print_r($archive);

    if(!$archive){
        $log['error'] = __('Unable to retrieve the file from the server!','wp-recall');
        echo json_encode($log); exit;
    }

    $result = json_decode($archive, true);

    if(is_array($result)&&isset($result['error'])){
        echo json_encode($result); exit;
    }

    file_put_contents($new_addon, $archive);

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
            $log['error'] = __('Update does not have the correct title!','wp-recall');
            echo json_encode($log);
            exit;
        }

        if(file_exists(RCL_TAKEPATH.'add-on'.'/')){

            if(isset($activeaddons[$addon]))
                rcl_deactivate_addon($addon);
            
            rcl_delete_addon($addon);

            $rs = $zip->extractTo(RCL_TAKEPATH.'add-on'.'/');

            if(isset($activeaddons[$addon]))
                rcl_activate_addon($addon);

        }

        $zip->close();
        unlink($new_addon);
        
        unset($need_update[$addon]);
        update_option('rcl_addons_need_update',$need_update);

        $log['success'] = $addon;
        echo json_encode($log);
        exit;

    }else{
        $log['error'] = __('Unable to open archive!','wp-recall');
        echo json_encode($log);
        exit;
    }
}

