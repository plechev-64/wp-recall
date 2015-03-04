<?php
function wp_recall_options_panel(){
	add_menu_page(__('WP-RECALL','rcl'), __('WP-RECALL','rcl'), 'manage_options', 'manage-wprecall', 'global_recall_options');
		add_submenu_page( 'manage-wprecall', __('НАСТРОЙКИ','rcl'), __('НАСТРОЙКИ','rcl'), 'manage_options', 'manage-wprecall', 'global_recall_options');
		add_submenu_page( 'manage-wprecall', __('Документация','rcl'), __('Документация','rcl'), 'manage_options', 'manage-doc-recall', 'recall_doc_manage');
}

function recall_doc_manage(){
	echo '<h2>Документация по плагину WP-RECALL</h2>
		<ol>
                    <li><a href="http://wppost.ru/ustanovka-plagina-wp-recall-na-sajt/" target="_blank">Установка плагина </a></li>
                    <li><a href="http://wppost.ru/obnovlenie-plagina-wp-recall-i-ego-dopolnenij/" target="_blank">Обновление плагина и его дополнений</a></li>
                    <li><a href="http://wppost.ru/nastrojki-plagina-wp-recall/" target="_blank">Настройки плагина</a></li>
                    <li><a href="http://wppost.ru/shortkody-wp-recall/" target="_blank">Используемые шорткоды Wp-Recall</a></li>
                    <li><a href="http://wppost.ru/obshhie-svedeniya-o-dopolneniyax-wp-recall/" target="_blank">Общие сведения о дополнениях Wp-Recall</a></li>
                    <li><a href="http://wppost.ru/dopolneniya-wp-recall/" target="_blank">Базовые дополнения Wp-Recall</a></li>
                    <li><a href="http://wppost.ru/downloads-files/" target="_blank">Платные дополнения Wp-Recall</a></li>
                    <li><a title="Произвольные поля Wp-Recall" href="http://wppost.ru/proizvolnye-polya-wp-recall/" target="_blank">Произвольные поля профиля Wp-Recall</a></li>
                    <li><a title="Произвольные поля формы публикации Wp-Recall" href="http://wppost.ru/proizvolnye-polya-formy-publikacii-wp-recall/" target="_blank">Произвольные поля формы публикации Wp-Recall</a></li>
                    <li><a href="http://wppost.ru/sozdaem-svoe-dopolnenie-dlya-wp-recall-vyvodim-svoyu-vkladku-v-lichnom-kabinete/" target="_blank">Пример создания своего дополнения Wp-Recall</a></li>
                    <li><a href="http://wppost.ru/xuki-i-filtry-wp-recall/" target="_blank">Функции и хуки Wp-Recall для разработки</a></li>
                    <li><a href="http://wppost.ru/category/novosti/obnovleniya/" target="_blank">История обновлений Wp-Recall</a></li>
                    <li><a title="Используемые библиотеки и ресурсы" href="http://wppost.ru/ispolzuemye-biblioteki-i-resursy/">Используемые библиотеки и ресурсы</a></li>
                    <li><a href="http://wppost.ru/forum/problemi-i-reshenia-na-localnom-servere/">Проблемы и решения на локальном сервере</a></li>
                    <li><a href="http://wppost.ru/faq/" target="_blank">FAQ</a></li>
            </ol>';
}

if (is_admin()) add_action('admin_init', 'recall_postmeta_post');
function recall_postmeta_post() {
    add_meta_box( 'recall_meta', __('Настройки Wp-Recall','rcl'), 'options_box_rcl', 'post', 'normal', 'high'  );
    add_meta_box( 'recall_meta', __('Настройки Wp-Recall','rcl'), 'options_box_rcl', 'page', 'normal', 'high'  );
}

add_filter('post_options_rcl','post_gallery_options',10,2);
function post_gallery_options($options,$post){
    $mark_v = get_post_meta($post->ID, 'recall_slider', 1);
    $options .= '<p>'.__('Использовать для изображений записи вывод в галерее Wp-Recall?','rcl').':
        <label><input type="radio" name="wprecall[recall_slider]" value="" '.checked( $mark_v, '',false ).' />'.__('Нет','rcl').'</label>
        <label><input type="radio" name="wprecall[recall_slider]" value="1" '.checked( $mark_v, '1',false ).' />'.__('Да','rcl').'</label>
    </p>';
    return $options;
}

function options_box_rcl( $post ){
        $content = '';
	echo apply_filters('post_options_rcl',$content,$post); ?>
	<input type="hidden" name="rcl_fields_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>" />
	<?php
}

function recall_postmeta_update( $post_id ){
    if(!isset($_POST['rcl_fields_nonce'])) return false;
    if ( !wp_verify_nonce($_POST['rcl_fields_nonce'], __FILE__) ) return false;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return false;
    if ( !current_user_can('edit_post', $post_id) ) return false;

    if( !isset($_POST['wprecall']) ) return false;	

    $_POST['wprecall'] = array_map('trim', (array)$_POST['wprecall']);
    foreach((array) $_POST['wprecall'] as $key=>$value ){
            if($value=='') delete_post_meta($post_id, $key);
            else update_post_meta($post_id, $key, $value);
    }
    return $post_id;
}

/*************************************************
Добавляем textarea в поле профиля для внесения настроек
*************************************************/
function get_data_type_profile_field_recall(){

	//$type = $_POST['type'];	
	$slug = $_POST['slug'];		
	
	$content = '<textarea rows="1" name="field_select_'.$slug.'"></textarea>';
	
	$data['result']=100;
	$data['content']= $content;
	echo json_encode($data);

    exit;
}
add_action('wp_ajax_get_data_type_profile_field_recall', 'get_data_type_profile_field_recall');
//add_action('wp_ajax_nopriv_get_data_type_profile_field_recall', 'get_data_type_profile_field_recall');

//Настройки плагина в админке
function global_recall_options(){
    global $rcl_options;

    include_once RCL_PATH.'functions/rcl_options.php';
    $fields = new Rcl_Options();
        
    $rcl_options = get_option('primary-rcl-options');

    $content = '<h2>'.__('Настройки плагина Wp-Recall и его дополнений','rcl').'</h2>
        <div id="recall" class="left-sidebar wrap">
	<form method="post" action="">
	'.wp_nonce_field('update-options-rcl','_wpnonce',true,false).'
	<h2 class="active">'.__('Общие настройки','rcl').'</h2>	
	<div class="wrap-recall-options" style="display:block;">';
    
                $args = array(    
                    'selected'   => $rcl_options['lk_page_rcl'],   
                    'name'       => 'lk_page_rcl',
                    'show_option_none' => '<span style="color:red">Не выбрано</span>',
                    'echo'       => 0  
                );
                
                $content .= $fields->option_block(array(
                    $fields->title('Личный кабинет'),
                    $fields->label('Порядок вывода личного кабинета пользователя'),
                    $fields->option('select',array(
                            'name'=>'view_user_lk_rcl',
                            'parent'=>true,
                            'options'=>array('На странице архива автора','Через шорткод [wp-recall]')
                        )),
                    $fields->child(
                        array(
                            'name'=>'view_user_lk_rcl',
                            'value'=>1
                        ),
                        array( 
                            $fields->label('Страница размещения шорткода ЛК'),
                            wp_dropdown_pages( $args ),
                            $fields->label('Формирование ссылки на личный кабинет'),
                            $fields->option('text',array('name'=>'link_user_lk_rcl')),
                            $fields->notice('Ссылка формируется по принципу "/slug_page/?get=ID". Параметр "get" можно задать тут. По-умолчанию user')
                        )
                    ),
                    $fields->label('Загрузка вкладок ЛК'),
                    $fields->option('select',array(
                        'name'=>'tab_newpage',
                        'options'=>array('Загружаются все','На отдельной странице','Ajax-загрузка')
                    )),
                    $fields->label('Таймаут активности'),
                    $fields->option('text',array('name'=>'timeout')),			
                    $fields->notice('Укажите время в минутах, по истечении которого, пользователь будет считаться offline, если не проявлял активности на сайте. По-умолчанию 10 минут.')
                )); 
                
                
                $roles = array(10=>'только Администраторам',7=>'Редакторам и старше',2=>'Авторам и старше',1=>'Участникам и старше',0=>'Всем пользователям');
                $content .= $fields->option_block(array(
                    $fields->title('Доступ в консоль'),
                    $fields->label('Доступ в консоль сайт разрешена'),
                    $fields->option('select',array(
                            'default'=>7,
                            'name'=>'consol_access_rcl',
                            'options'=>$roles
                    )),
                    $fields->notice('Если выбрана страница архива автора, то в нужном месте шаблона author.php вставить код if(function_exists(\'wp_recall\')) wp_recall();'),

                ));               
                
		$filecss = (file_exists(TEMP_PATH.'css/minify.css'))? '<a href="'.RCL_URL.'css/getcss.php">Скачать текущий стилевой файл для правки</a>':'';
                $content .= $fields->option_block(
                    array(
			$fields->title('Оформление'),	
                        
			$fields->label('Размещение кнопок разделов в ЛК'),						
                        $fields->option('select',array(
                            'name'=>'buttons_place',
                            'options'=>array('Сверху','Слева')
                        )),

			get_theme_list(),
                        
                        $fields->label('Пауза Слайдера'),
                        $fields->option('text',array('name'=>'slide-pause')),
                        $fields->notice('Значение паузы между сменой слайдов в секундах. По-умолчанию 0 - смены слайдов не производится'),                       
                        
                        $fields->label('Минимизация стилевых файлов'),				
                        $fields->option('select',array(
                            'name'=>'minify_css',
                            'parent'=>true,
                            'options'=>array('Отключено','Включено')
                        )),
                        $fields->notice('Минимизация стилевых файлов работает только по отношению к стилевым файлам Wp-Recall и его дополнений, которые поддерживают эту функцию'),
			$fields->child(
                             array(
                                 'name'=>'minify_css',
                                 'value'=>1
                             ),
                             array(
                                 $fields->label('Cвой файл стилей(CSS)'),
                                 $fields->option('text',array('name'=>'custom_scc_file_recall')),
                                 $fields->notice('Файл заменяет минимизированный файл стилей, если включена минимизация'),
                                 $filecss
                             )
                        )
                    )
                );

                $content .= $fields->option_block(
                    array(
                        $fields->title('Вход и регистрация'),
                        $fields->label('Порядок вывода'), 
                        $fields->option('select',array(
                            'name'=>'login_form_recall',
                            'parent'=>true,
                            'options'=>array('Плавающая форма','На отдельной странице','Форма Wordpress','Форма в виджете')
                        )),
                        $fields->child(
                            array(
                              'name' => 'login_form_recall',
                              'value' => 1
                            ),
                            array(
                                $fields->label('ID страницы с шорткодом [loginform]'),
                                $fields->option('text',array('name'=>'page_login_form_recall')),
                                $fields->notice('<b>Примечание:</b> Если выбран порядок вывода формы входа и регистрации на отдельной странице, то необходимо создать страницу, расположить в ее содержимом шорткод [loginform] и указать ID этой страницы в поле выше.')
                            )
                        ),
                        $fields->label('Подтверждение регистрации пользователем'),
                        $fields->option('select',array(
                            'name'=>'confirm_register_recall',
                            'options'=>array('Не используется','Используется')
                        )),
                        $fields->label('Перенаправление пользователя после авторизации'),
                        $fields->option('select',array(
                            'name'=>'authorize_page',
                            'parent'=>1,
                            'options'=>array('Профиль пользователя','Текущая страница','Произвольный URL')
                        )),
                        $fields->child(
                            array(
                              'name' => 'authorize_page',
                              'value' => 2
                            ),
                            array(
                                $fields->label('URL'),
                                $fields->option('text',array('name'=>'custom_authorize_page')),
                                $fields->notice('Впишите свой URL ниже, если выбран произвольный URL после авторизации')
                            )
                        ),
                        $fields->label('Поле повтора пароля'),                       
                        $fields->option('select',array(
                            'name'=>'repeat_pass',
                            'options'=>array('Отключено','Отображается')
                        )),
                        $fields->label('Индикатор сложности пароля'),                       
                        $fields->option('select',array(
                            'name'=>'difficulty_parole',
                            'options'=>array('Отключен','Отображается')
                        ))
                    )
                );   

                $content .= $fields->option_block(
                    array(
                        $fields->title('Recallbar'),
                        $fields->label('Вывод панели recallbar'), 
                        $fields->option('select',array(
                            'name'=>'view_recallbar',
                            'options'=>array('Отключено','Включено')
                        ))
                    )
                );

                $content .= $fields->option_block(
                    array(
                        $fields->title('Ваша благодарность'),
                        $fields->label('Отображать ссылку на сайт разработчика (Спасибо, если решили показать)'), 
                        $fields->option('select',array(
                               'name'=>'footer_url_recall',
                               'options'=>array('Нет','Да')
                        ))
                    )
                );  
                
    $content .= '</div>';
		
    $content = apply_filters('admin_options_wprecall',$content);

    $content .= '<div class="submit-block">
    <p><input type="submit" class="button button-primary button-large right" name="primary-rcl-options" value="'.__('Сохранить настройки','rcl').'" /></p>
    </div></form></div>';

    echo $content;
}

function get_theme_list(){
    global $rcl_options;
    
    if(!isset($rcl_options['color_theme'])) $color_theme = 1;
    else $color_theme = $rcl_options['color_theme'];
    $dirs   = array(RCL_PATH.'css/themes',TEMPLATEPATH.'/recall/themes');
    $t_list = '';
    foreach($dirs as $dir){
        //echo $dir;
        if(!file_exists($dir)) continue;
        $ts = scandir($dir,1);
        
        foreach((array)$ts as $t){
                if ( false == strpos($t, '.css') ) continue;
                $name = str_replace('.css','',$t);
                $t_list .= '<option value="'.$name.'" '.selected($color_theme,$name,false).'>'.$name.'</option>';	
        }
    }
    if($t_list){
            $content = '<label>Используемый шаблон</label>';
            $content .= '<select name="color_theme" size="1">
                <option value="">Не подключен</option>
                    '.$t_list.'				
            </select>';
            
        return $content;
    }
    return false;
}

function get_url_theme_rcl(){
    $dirs   = array(TEMPLATEPATH.'/recall/themes',RCL_PATH.'css/themes');
    foreach($dirs as $dir){
        if(!file_exists($dir.'/'.$rcl_options['color_theme'].'.css')) continue;
        wp_enqueue_theme_rcl(path_to_url_rcl($dir.'/'.$rcl_options['color_theme'].'.css'));
        break;
    }
}
function wp_enqueue_theme_rcl($url){
    wp_enqueue_style( 'theme_rcl', $url );
}