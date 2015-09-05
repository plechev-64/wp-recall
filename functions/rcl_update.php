<?php

add_action('wp', 'rcl_activation_daily_addon_update');
function rcl_activation_daily_addon_update() {
	//wp_clear_scheduled_hook('rcl_daily_addon_update');
	if ( !wp_next_scheduled( 'rcl_daily_addon_update' ) ) {
		$start_date = strtotime(current_time('mysql'));
		wp_schedule_event( $start_date, 'twicedaily', 'rcl_daily_addon_update');
	}
}

add_action('wp','rcl_hand_addon_update');
function rcl_hand_addon_update(){
    if(!isset($_GET['rcl-addon-update'])||$_GET['rcl-addon-update']!='now') return false;
    rcl_daily_addon_update();
}

add_action('rcl_daily_addon_update','rcl_daily_addon_update');
function rcl_daily_addon_update(){
    $paths = array(RCL_TAKEPATH.'add-on') ;

    $rcl_addons = new Rcl_Addons();

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
                            $addons_data[$namedir] = $rcl_addons->get_parse_addon_info($info);
                            $addons_data[$namedir]['src'] = $index_src;
                            $a++;
                            flush();
                    }
            }
        }
    }

    //print_r($addons_data);exit;

    $need_update = array();
    foreach((array)$addons_data as $key=>$addon){
        $ver = $rcl_addons->get_actual_version($key,$addon['version']);
        if($ver){
            $addon['new-version'] = $ver;
            $need_update[$key] = $addon;
        }
    }

    update_option('rcl_addons_need_update',$need_update);

}

add_action('wp_ajax_rcl_update_addon','rcl_update_addon');
function rcl_update_addon(){
    $addon = $_POST['addon'];
    $need_update = get_option('rcl_addons_need_update');
    if(!isset($need_update[$addon])) return false;

    $url = 'http://wppost.ru/products-files/api/update.php'
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
    //print_r($archive);exit;
    if(!$archive){
        $log['error'] = __('Unable to retrieve the file from the server!','rcl');
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
            $log['error'] = __('Update does not have the correct title!','rcl');
            echo json_encode($log);
            exit;
        }

        if(file_exists(RCL_TAKEPATH.'add-on'.'/')){
            rcl_deactivate_addon($addon);
            $rs = $zip->extractTo(RCL_TAKEPATH.'add-on'.'/');
            rcl_activate_addon($addon);
        }

        $zip->close();
        unlink($new_addon);

        $log['success'] = $addon;
        echo json_encode($log);
        exit;

    }else{
        $log['error'] = __('Unable to open archive!','rcl');
        echo json_encode($log);
        exit;
    }
}

