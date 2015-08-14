<?php

add_action('wp', 'rcl_activation_daily_addon_update');
function rcl_activation_daily_addon_update() {
	//wp_clear_scheduled_hook('rcl_daily_addon_update');
	if ( !wp_next_scheduled( 'rcl_daily_addon_update' ) ) {
		$start_date = strtotime(current_time('mysql'));
		wp_schedule_event( $start_date, 'daily', 'rcl_daily_addon_update');
	}
}

add_action('rcl_daily_addon_update','rcl_daily_addon_update');
function rcl_daily_addon_update(){
    $paths = array(RCL_PATH.'add-on',TEMPLATEPATH.'/wp-recall/add-on') ;

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
                            $addons_data[$namedir] = $rcl_addons->get_parse_addon_info($info[0]);
                            $addons_data[$namedir]['src'] = $index_src;
                            $a++;
                            flush();
                    }
            }
        }
    }

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

