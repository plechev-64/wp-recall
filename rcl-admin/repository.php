<?php

function rcl_repository_page(){
    global $addon,$active_addons;

    $paths = array(RCL_PATH.'add-on',RCL_TAKEPATH.'add-on') ;

    foreach($paths as $path){
        if(file_exists($path)){
            $installs = scandir($path,1);
            $a=0;
            foreach($installs as $namedir){
               $install_addons[$namedir] = 1;
            }
        }
    }
    
    $page = (isset($_GET['paged']))? $_GET['paged']: 1;

     $url = RCL_SERVICE_HOST.'/products-files/api/add-ons.php'
            . '?rcl-addon-info=get-add-ons&page='.$page;

     $data = array(
        'rcl-key' => get_option('rcl-key'),
        'rcl-version' => VER_RCL,
        'host' => $_SERVER['SERVER_NAME']
    );

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        )
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $result =  json_decode($result);

    if(!$result){
        echo '<h2>Не удалось получить данные.</h2>'; exit;
    }

    if(is_array($result)&&isset($result['error'])){
        echo '<h2>Ошибка! '.$result['error'].'</h2>'; exit;
    }
    
    $pagenavi = rcl_navi_admin($result->number,$result->count,$result->page,'rcl-repository','');
    
    //$content = $pagenavi;

    $content = '<div class="wp-list-table widefat plugin-install">
	<div id="the-list">';
    foreach($result->addons as $add){
        if(!$add) continue;
        (object)$addon;
        foreach($add as $k=>$v){
            $key = str_replace('-','_',$k);
            $v = (isset($v))? $v: '';
            $addon->$key = $v;
        }

        $content .= rcl_get_include_template('add-on-card.php');
    }
    $content .= '</div>'
    .'</div>';
    
    $content .= $pagenavi;

    echo '<h1>Репозиторий дополнений Wp-Recall</h1>';
    //echo '<p>На этой странице отображаются доступные на данный момент дополнения, но не установленные на вашем сайте.</p>';
    echo $content;
    
}

