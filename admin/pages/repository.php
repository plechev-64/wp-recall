<?php

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

$result = wp_remote_post( $url, array('body' => $data) );

if ( is_wp_error( $response ) ) {
   $error_message = $response->get_error_message();
   echo __('Error').': '.$error_message; exit;
}

$result =  json_decode($result['body']);

if(!$result){
    echo '<h2>'.__('Failed to get data','wp-recall').'.</h2>'; exit;
}

if(is_array($result)&&isset($result['error'])){
    echo '<h2>'.__('Error','wp-recall').'! '.$result['error'].'</h2>'; exit;
}

$navi = new Rcl_PageNavi('rcl-addons',$result->count,array('key'=>'paged','in_page'=>$result->number));

$content = $navi->pagenavi();

$content .= '<div class="wp-list-table widefat plugin-install">
    <div id="the-list">';
foreach($result->addons as $add){
    if(!$add) continue;
    $addon = array();
    foreach($add as $k=>$v){
        $key = str_replace('-','_',$k);
        $v = (isset($v))? $v: '';
        $addon[$key] = $v;            
    }
    $addon = (object)$addon;
    $content .= rcl_get_include_template('add-on-card.php');
}
$content .= '</div>'
.'</div>';

$content .= $navi->pagenavi();

echo '<h2>'.__('Repository for WP-Recall add-ons','wp-recall').'</h2>';
//echo '<p>На этой странице отображаются доступные на данный момент дополнения, но не установленные на вашем сайте.</p>';
echo $content;
