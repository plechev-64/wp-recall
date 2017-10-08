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

$sort = isset($_GET['sort'])? $_GET['sort']: 'update';

$type = isset($_GET['type'])? $_GET['type']: 'term';

$s = isset($_GET['s'])? $_GET['s']: '';

$page = (isset($_GET['paged']))? $_GET['paged']: 1;

$url = RCL_SERVICE_HOST.'/products-files/api/add-ons.php'
        . '?rcl-addon-info=get-add-ons&page='.$page;

if($sort){
    $url .= '&sort='.$sort;
}

if($type){
    $url .= '&type='.$type;
}

if($s){
    $url .= '&s='.$s;
}

$data = array(
    'rcl-key' => get_option('rcl-key'),
    'rcl-version' => VER_RCL,
    'host' => $_SERVER['SERVER_NAME']
);

$result = wp_remote_post( $url, array('body' => $data) );

if ( is_wp_error( $result ) ) {
   $error_message = $result->get_error_message();
   echo __('Error').': '.$error_message; exit;
}

$result =  json_decode($result['body']);

if(!$result){
    echo '<h2>'.__('Failed to get data','wp-recall').'.</h2>'; exit;
}

if(!$result->count || !$result->addons){
    
}

if(is_array($result)&&isset($result['error'])){
    echo '<h2>'.__('Error','wp-recall').'! '.$result['error'].'</h2>'; exit;
}

$navi = new Rcl_PageNavi('rcl-addons',$result->count,array('key'=>'paged','in_page'=>$result->number));

$content = '<h2>'.__('Repository for WP-Recall add-ons','wp-recall').'</h2>';

$content .= '<div class="wp-filter">
    <ul class="filter-links">
        <li class="plugin-install-featured"><a href="'.admin_url('admin.php?').$navi->get_string(array('type','s','page')).'&sort=update" class="'.($sort == 'update'? 'current': '').'">По обновлению</a></li>
        <li class="plugin-install-popular"><a href="'.admin_url('admin.php?').$navi->get_string(array('type','s','page')).'&sort=active-installs" class="'.($sort == 'active-installs'? 'current': '').'">По популярности</a></li>
    </ul>

    <form class="search-form search-plugins" method="get">
        <input type="hidden" name="page" value="rcl-repository">
        <input type="hidden" name="sort" value="'.$sort.'">
        <label class="screen-reader-text" for="typeselector">Критерий поиска:</label>
        <select name="type" id="typeselector">
            <option value="term" '.selected($type,'term',false).'>Слово</option>
            <option value="author" '.selected($type,'author',false).'>Автор</option>
            <option value="tag" '.selected($type,'tag',false).'>Метка</option>
        </select>
        <label><span class="screen-reader-text">Поиск дополнений</span>
            <input type="search" name="s" value="'.($s? $s: '').'" class="wp-filter-search" placeholder="Поиск дополнений..." aria-describedby="live-search-desc">
        </label>
        <input type="submit" id="search-submit" class="button hide-if-js" value="Поиск дополнений">	
    </form>
</div>';

if($result->count && $result->addons){
    
    $content .= '<p class="rcl-search-results">'.__('Results found','wp-recall').': '.$result->count.'</p>';

    $content .= $navi->pagenavi();

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

}else{
    $content .= '<h3>'.__('Nothing found','wp-recall').'</h3>';
}

echo $content;
