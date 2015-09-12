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

     $url = 'http://wppost.ru/products-files/api/add-ons.php'
            . '?rcl-addon-info=get-add-ons&number=20';

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
    //print_r($result);exit;

    if(!$result){
        echo '<h2>Не удалось получить данные.</h2>'; exit;
    }

    if(is_array($result)&&isset($result['error'])){
        echo '<h2>Ошибка! '.$result['error'].'</h2>'; exit;
    }

    $content = '<div class="wp-list-table widefat plugin-install">
	<div id="the-list">';
    foreach($result as $add){
        if(!$add||isset($install_addons[$add->slug])) continue;
        (object)$addon;
        foreach($add as $k=>$v){
            $key = str_replace('-','_',$k);
            $addon->$key = $v;
        }

        $content .= rcl_get_include_template('add-on-card.php');
    }
    $content .= '</div>'
    .'</div>';

    echo '<h1>Репозиторий дополнений Wp-Recall</h1>';
    echo '<p>На этой странице отображаются доступные на данный момент дополнения, но не установленные на вашем сайте.</p>';
    echo $content;
}

