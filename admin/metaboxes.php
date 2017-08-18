<?php

add_action('rcl_add_dashboard_metabox', 'rcl_add_default_metabox');
function rcl_add_default_metabox($screen){
    add_meta_box( 'rcl-stats-metabox', __('WP-Recall statistics','wp-recall'), 'rcl_stats_metabox', $screen->id, 'normal' );
    add_meta_box( 'rcl-news-metabox', __('WP-Recall news','wp-recall'), 'rcl_news_metabox', $screen->id, 'normal' );
    add_meta_box( 'rcl-docs-metabox', __('Documentation for WP-Recall','wp-recall'), 'rcl_docs_metabox', $screen->id, 'normal' );
}

function rcl_stats_metabox(){
    global $active_addons,$rcl_template;
    
    $paths = array(RCL_PATH.'add-on',RCL_TAKEPATH.'add-on') ;
        
    $countAddons = 0;
    foreach($paths as $path){
        $path = wp_normalize_path($path);
        if(file_exists($path)){
            foreach(scandir($path,1) as $namedir){
                $addon_dir = $path.'/'.$namedir;
                $index_src = $addon_dir.'/index.php';
                if(!is_dir($addon_dir)||!file_exists($index_src)) continue;
                $info_src = $addon_dir.'/info.txt';
                if(!file_exists($info_src)) continue;
                $countAddons ++;
            }
        }
    }
    
    $data = array(
        array(
            'name' => __('Total addons','wp-recall'),
            'content' => $countAddons
        ),
        array(
            'name' => __('Active addons','wp-recall'),
            'content' => count($active_addons).' (<a href="'.admin_url('admin.php?page=manage-addon-recall').'">'.__('Go to addons manager','wp-recall').'</a>)'
        ),
        array(
            'name' => __('Active template','wp-recall'),
            'content' => $active_addons[$rcl_template]['name'].' (<a href="'.admin_url('admin.php?page=manage-templates-recall').'">'.__('Go to templates manager','wp-recall').'</a>)'
        )
    );
    
    foreach($data as $d){
        echo '<p><b>'.$d['name'].':</b> '.$d['content'].'</p>';
    }
    
}

function rcl_news_metabox(){
    
    $url = RCL_SERVICE_HOST."/dashboard-posts/rcl-news.xml";

    $xmlData = @simplexml_load_file($url);
    
    if(!$xmlData){
        echo __('Unable to retrieve news','wp-recall'); return;
    }
    
    echo '<ul>';
    foreach($xmlData as $post){
        echo '<li><h4><a href="'.$post->post_url.'" target="_blank">'.$post->post_title.'</a></h4></li>';
    }
    echo '</ul>';
    
}

function rcl_docs_metabox(){
    
    echo '<ol>
	<li><a href="https://codeseller.ru/ustanovka-plagina-wp-recall-na-sajt/" target="_blank">Установка плагина</a></li>
	<li><a href="https://codeseller.ru/obnovlenie-plagina-wp-recall-i-ego-dopolnenij/" target="_blank">Обновление плагина и его дополнений</a></li>
	<li><a href="https://codeseller.ru/nastrojki-plagina-wp-recall/" target="_blank">Настройки плагина</a></li>
	<li><a href="https://codeseller.ru/shortkody-wp-recall/" target="_blank">Используемые шорткоды Wp-Recall</a></li>
	<li><a href="https://codeseller.ru/obshhie-svedeniya-o-dopolneniyax-wp-recall/" target="_blank">Общие сведения о дополнениях Wp-Recall</a></li>
	<li><a href="https://codeseller.ru/post-group/poryadok-dobavleniya-funkcionala-grupp-s-pomoshhyu-plagina-wp-recall/">Порядок добавления функционала групп</a></li>
	<li><a href="https://codeseller.ru/prodcat/dopolneniya-wp-recall/" target="_blank">Все дополнения Wp-Recall</a></li>
	<li><a href="https://codeseller.ru/proizvolnye-polya-wp-recall/" target="_blank">Произвольные поля профиля Wp-Recall</a></li>
	<li><a href="https://codeseller.ru/proizvolnye-polya-formy-publikacii-wp-recall/" target="_blank">Произвольные поля формы публикации Wp-Recall</a></li>
	<li><a href="https://codeseller.ru/post-group/sozdaem-svoe-dopolnenie-dlya-wp-recall-vyvodim-svoyu-vkladku-v-lichnom-kabinete/" target="_blank">Пример создания своего дополнения Wp-Recall</a></li>
	<li><a href="https://codeseller.ru/xuki-i-filtry-wp-recall/" target="_blank">Функции и хуки Wp-Recall для разработки</a></li>
	<li><a href="https://codeseller.ru/api-rcl/" target="_blank">API WP-Recall</a></li>
	<li><a href="https://codeseller.ru/groups/obnovleniya/" target="_blank">История обновлений Wp-Recall</a></li>
	<li><a href="https://codeseller.ru/ispolzuemye-biblioteki-i-resursy/">Используемые библиотеки и ресурсы</a></li>
	<li><a href="https://codeseller.ru/forum/problemi-i-reshenia-na-localnom-servere/">Проблемы и решения на локальном сервере</a></li>
	<li><a href="https://codeseller.ru/faq/" target="_blank">FAQ</a></li>
    </ol>';
    
}