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
            <li><a href="https://codeseller.ru/ustanovka-plagina-wp-recall-na-sajt/" target="_blank" rel="noopener noreferrer">Установка плагина на сайт</a></li>
            <li><a href="https://codeseller.ru/srazu-posle-aktivacii-plagina-wp-recall/" target="_blank" rel="noopener">Сразу после активации</a></li>
            <li>Административная часть:<br/>
                - <a href="https://codeseller.ru/stranica-konsoli-plagina/" target="_blank" rel="noopener">Консоль плагина</a><br/>
                - <a href="https://codeseller.ru/stranica-nastroek-plagina-wp-recall/" target="_blank" rel="noopener">Настройки плагина</a><br/>
                - <a href="https://codeseller.ru/stranica-menedzhera-dopolnenij/" target="_blank" rel="noopener">Менеджер дополнений</a><br/>
                - <a href="https://codeseller.ru/stranica-repozitoriya-dopolnenij/" target="_blank" rel="noopener">Репозиторий дополнений</a><br/>
                - <a href="https://codeseller.ru/stranica-shablonov-lichnogo-kabineta/" target="_blank" rel="noopener">Шаблоны личного кабинета</a><br/>
                - <a href="https://codeseller.ru/proizvolnye-polya-wp-recall/" target="_blank" rel="noopener noreferrer">Произвольные поля профиля WP-Recall</a><br/>
                - <a href="https://codeseller.ru/post-group/proizvolnye-polya-formy-publikacii-wp-recall/" target="_blank" rel="noopener noreferrer">Произвольные поля формы публикации WP-Recall</a></li>
            <li><a href="https://codeseller.ru/post-group/ustanovka-plagina-wp-recall-na-sajt/" target="_blank" rel="noopener noreferrer">Варианты вывода личного кабинета</a></li>
            <li><a href="https://codeseller.ru/obshhie-svedeniya-o-dopolneniyax-wp-recall/" target="_blank" rel="noopener noreferrer">Общие сведения о дополнениях WP-Recall</a></li>
            <li><a href="https://codeseller.ru/prodcat/dopolneniya-wp-recall/" target="_blank" rel="noopener noreferrer">Все дополнения WP-Recall</a></li>
            <li><a href="https://codeseller.ru/post-group/sozdaem-svoe-dopolnenie-dlya-wp-recall-vyvodim-svoyu-vkladku-v-lichnom-kabinete/" target="_blank" rel="noopener noreferrer">Пример создания своего дополнения WP-Recall</a></li>
            <li><a href="https://codeseller.ru/obnovlenie-plagina-wp-recall-i-ego-dopolnenij/" target="_blank" rel="noopener noreferrer">Обновление плагина и его дополнений</a></li>
            <li><a href="https://codeseller.ru/groups/obnovleniya/" target="_blank" rel="noopener noreferrer">История обновлений WP-Recall</a></li>
            <li><a href="https://codeseller.ru/shortkody-wp-recall/" target="_blank" rel="noopener noreferrer">Используемые шорткоды WP-Recall</a></li>
            <li><a href="https://codeseller.ru/post-group/poryadok-dobavleniya-funkcionala-grupp-s-pomoshhyu-plagina-wp-recall/">Порядок добавления функционала групп</a></li>
            <li><a href="https://codeseller.ru/ispolzuemye-biblioteki-i-resursy/">Используемые библиотеки и ресурсы</a></li>
            <li><a href="https://codeseller.ru/type-api/wp-recall/" target="_blank" rel="noopener noreferrer">API WP-Recall</a></li>
        </ol>';

}