<?php
function rcl_options_panel(){
    $need_update = get_option('rcl_addons_need_update');
    $cnt = $need_update? count($need_update): 0;
    $notice = ($cnt)? ' <span class="update-plugins count-'.$cnt.'"><span class="plugin-count">'.$cnt.'</span></span>': '';
    add_menu_page(__('WP-RECALL','wp-recall').$notice, __('WP-RECALL','wp-recall').$notice, 'manage_options', 'manage-wprecall', 'rcl_global_options');
    add_submenu_page( 'manage-wprecall', __('SETTINGS','wp-recall'), __('SETTINGS','wp-recall'), 'manage_options', 'manage-wprecall', 'rcl_global_options');
    $hook = add_submenu_page( 'manage-wprecall', __('Add-ons','wp-recall').$notice, __('Add-ons','wp-recall').$notice, 'manage_options', 'manage-addon-recall', 'rcl_render_addons_manager');
    add_action( "load-$hook", 'rcl_add_options_addons_manager' );
    $hook = add_submenu_page( 'manage-wprecall', __('Templates','wp-recall'), __('Templates','wp-recall').$notice, 'manage_options', 'manage-templates-recall', 'rcl_render_templates_manager');
    add_action( "load-$hook", 'rcl_add_options_templates_manager' );
    add_submenu_page( 'manage-wprecall', __('Repository','wp-recall'), __('Repository','wp-recall'), 'manage_options', 'rcl-repository', 'rcl_repository_page');
    add_submenu_page( 'manage-wprecall', __('Documentation','wp-recall'), __('Documentation','wp-recall'), 'manage_options', 'manage-doc-recall', 'rcl_doc_manage');
    add_submenu_page( 'manage-wprecall', __('Custom tabs','wp-recall'), __('Custom tabs','wp-recall'), 'manage_options', 'manage-custom-tabs', 'rcl_custom_tabs_manage');
}

function rcl_doc_manage(){
    echo '<h2>'.__('Documentation for the plugin WP-RECALL','wp-recall').'</h2>
    <ol>
	<li><a href="https://codeseller.ru/ustanovka-plagina-wp-recall-na-sajt/" target="_blank">Установка плагина </a></li>
	<li><a href="https://codeseller.ru/obnovlenie-plagina-wp-recall-i-ego-dopolnenij/" target="_blank">Обновление плагина и его дополнений</a></li>
	<li><a href="https://codeseller.ru/nastrojki-plagina-wp-recall/" target="_blank">Настройки плагина</a></li>
	<li><a href="https://codeseller.ru/shortkody-wp-recall/" target="_blank">Используемые шорткоды Wp-Recall</a></li>
	<li><a href="https://codeseller.ru/obshhie-svedeniya-o-dopolneniyax-wp-recall/" target="_blank">Общие сведения о дополнениях Wp-Recall</a></li>
	<li><a href="https://codeseller.ru/post-group/poryadok-dobavleniya-funkcionala-grupp-s-pomoshhyu-plagina-wp-recall/">Порядок добавления функционала групп</a></li>
	<li><a href="https://codeseller.ru/prodcat/dopolneniya-wp-recall/" target="_blank">Все дополнения Wp-Recall</a></li>
	<li><a title="Произвольные поля Wp-Recall" href="https://codeseller.ru/proizvolnye-polya-wp-recall/" target="_blank">Произвольные поля профиля Wp-Recall</a></li>
	<li><a title="Произвольные поля формы публикации Wp-Recall" href="https://codeseller.ru/proizvolnye-polya-formy-publikacii-wp-recall/" target="_blank">Произвольные поля формы публикации Wp-Recall</a></li>
	<li><a href="https://codeseller.ru/post-group/sozdaem-svoe-dopolnenie-dlya-wp-recall-vyvodim-svoyu-vkladku-v-lichnom-kabinete/" target="_blank">Пример создания своего дополнения Wp-Recall</a></li>
	<li><a href="https://codeseller.ru/xuki-i-filtry-wp-recall/" target="_blank">Функции и хуки Wp-Recall для разработки</a></li>
	<li><a href="https://codeseller.ru/api-rcl/" target="_blank">API WP-Recall</a></li>
	<li><a href="https://codeseller.ru/groups/obnovleniya/" target="_blank">История обновлений Wp-Recall</a></li>
	<li><a title="Используемые библиотеки и ресурсы" href="https://codeseller.ru/ispolzuemye-biblioteki-i-resursy/">Используемые библиотеки и ресурсы</a></li>
	<li><a href="https://codeseller.ru/forum/problemi-i-reshenia-na-localnom-servere/">Проблемы и решения на локальном сервере</a></li>
	<li><a href="https://codeseller.ru/faq/" target="_blank">FAQ</a></li>
    </ol>';
}

if (is_admin()) add_action('admin_init', 'rcl_postmeta_post');
function rcl_postmeta_post() {
    add_meta_box( 'recall_meta', __('Settings Wp-Recall','wp-recall'), 'rcl_options_box', 'post', 'normal', 'high'  );
    add_meta_box( 'recall_meta', __('Settings Wp-Recall','wp-recall'), 'rcl_options_box', 'page', 'normal', 'high'  );
}

add_filter('rcl_post_options','rcl_gallery_options',10,2);
function rcl_gallery_options($options,$post){
    $mark_v = get_post_meta($post->ID, 'recall_slider', 1);
    $options .= '<p>'.__('Pictures record the withdrawal in the gallery Wp-Recall?','wp-recall').':
        <label><input type="radio" name="wprecall[recall_slider]" value="" '.checked( $mark_v, '',false ).' />'.__('No','wp-recall').'</label>
        <label><input type="radio" name="wprecall[recall_slider]" value="1" '.checked( $mark_v, '1',false ).' />'.__('Yes','wp-recall').'</label>
    </p>';
    return $options;
}

function rcl_options_box( $post ){
    $content = '';
	echo apply_filters('rcl_post_options',$content,$post); ?>
	<input type="hidden" name="rcl_fields_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>" />
	<?php
}

function rcl_postmeta_update( $post_id ){
    if(!isset($_POST['rcl_fields_nonce'])) return false;
    if ( !wp_verify_nonce($_POST['rcl_fields_nonce'], __FILE__) ) return false;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return false;
    if ( !current_user_can('edit_post', $post_id) ) return false;

    if( !isset($_POST['wprecall']) ) return false;

    $POST = $_POST['wprecall'];
    
    foreach($POST as $key=>$value ){
        if(!is_array($value)) $value = trim($value);
        if($value=='') delete_post_meta($post_id, $key);
        else update_post_meta($post_id, $key, $value);
    }
    return $post_id;
}

//Настройки плагина в админке
function rcl_global_options(){
    global $rcl_options;
    
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery-ui-dialog' );
    wp_enqueue_style('wp-jquery-ui-dialog');
    
    include_once RCL_PATH.'functions/rcl_options.php';
    $fields = new Rcl_Options();

    $rcl_options = get_option('rcl_global_options');
    
    $extends = isset($_COOKIE['rcl_extends'])? $_COOKIE['rcl_extends']: 0;

    $content = '<h2>'.__('Configure the plugin Wp-Recall and additions','wp-recall').'</h2>
        <div id="recall" class="left-sidebar wrap">
        <span class="shift-extend-options">
            <label><input type="checkbox" name="extend_options" '.checked($extends,1,false).' onclick="return rcl_enable_extend_options(this);" value="1"> Расширенные настройки</label>
	</span>
        <form method="post" id="rcl-options-form" onsubmit="rcl_update_options();return false;" action="">
	'.wp_nonce_field('update-options-rcl','_wpnonce',true,false).'
	<span class="title-option active"><span class="wp-menu-image dashicons-before dashicons-admin-generic"></span> '.__('General settings','wp-recall').'</span>
	<div class="wrap-recall-options" style="display:block;">';

                $args = array(
                    'selected'   => $rcl_options['lk_page_rcl'],
                    'name'       => 'global[lk_page_rcl]',
                    'show_option_none' => '<span style="color:red">'.__('Not selected','wp-recall').'</span>',
                    'echo'       => 0
                );
                
                $roles = array(
                    10=>__('only Administrators','wp-recall'),
                    7=>__('Editors and older','wp-recall'),
                    2=>__('Authors and older','wp-recall'),
                    1=>__('Participants and older','wp-recall'),
                    0=>__('All users','wp-recall')
                );

                $content .= $fields->option_block(array(
                    $fields->extend(array(
                        $fields->title(__('Personal office','wp-recall')),
                    
                        $fields->option('select',array(
                            'name'=>'view_user_lk_rcl',
                            'label'=>__('The order of withdrawal of the personal Cabinet','wp-recall'),
                            'parent'=>true,
                            'options'=>array(
                                __('On the archive page of the author','wp-recall'),
                                __('Using the shortcode [wp-recall]','wp-recall')),
                            'help'=>__('Attention! To change this parameter is not required. Detailed instructions on withdrawal personal account using the file author.php described <a href="https://codeseller.ru/ustanovka-plagina-wp-recall-na-sajt/" target="_blank">here</a>','wp-recall'),
                            'notice'=>__('If the selected archive page of the author, in the right place template author.php paste the code if(function_exists(\'wp_recall\')) wp_recall();','wp-recall')
                        )),
                        $fields->child(
                            array(
                                'name'=>'view_user_lk_rcl',
                                'value'=>1
                            ),
                            array(
                                $fields->label(__('The host page the shortcode','wp-recall')),
                                wp_dropdown_pages( $args ),
                                $fields->option('text',array(
                                    'name'=>'link_user_lk_rcl',
                                    'label'=>__('The formation of links to personal account','wp-recall'),
                                    'help'=>__('The link is formed by a principle "/slug_page/?get=ID". The parameter "get" can be set here. By default user','wp-recall')
                                ))
                            )
                        ),
                        $fields->option('select',array(
                            'name'=>'tab_newpage',
                            'label'=>__('Download tabs','wp-recall'),
                            'help'=>__('Depending on the setting, it will load the content of tabs personal account. It is recommended to install a separate download or tabs ajax loading','wp-recall'),
                            'options'=>array(
                                __('Downloads all','wp-recall'),
                                __('On a separate page','wp-recall'),
                                __('Ajax loading','wp-recall'))
                        )),
                        $fields->option('number',array(
                            'name'=>'timeout',
                            'help'=>__('This value sets the maximum time a user is considered "online" in the absence of active','wp-recall'),
                            'label'=>__('Inactivity timeout','wp-recall'),
                            'notice'=>__('Specify the time in minutes after which the user will be considered offline if you did not show activity on the website. The default is 10 minutes.','wp-recall')
                        ))
                ))));
                
                $content .= $fields->option_block(array(
                    $fields->extend(array(
                        $fields->title(__('Access to the console','wp-recall')),
                        $fields->option('select',array(
                            'default'=>7,
                            'name'=>'consol_access_rcl',
                            'label'=>__('Access to the site is permitted console','wp-recall'),
                            'options'=>$roles
                        ))
                ))));
                
                $templates = rcl_get_install_templates();
                $templates = ($templates)? $templates: array(__('Not found','wp-recall'));

                $content .= $fields->option_block(
                    array(
			$fields->title(__('Making','wp-recall')),                 
                        $fields->option('text',array(
                            'name'=>'primary-color',
                            'label'=>__('Primary color','wp-recall'),
                            'default'=>'#4C8CBD'
                        )),
                        $fields->option('select',array(
                            'name'=>'buttons_place',
                            'label'=>__('The placement of the buttons sections','wp-recall'),
                            'options'=>array(
                                __('Top','wp-recall'),
                                __('Left','wp-recall'))
                        )),
                        $fields->extend(array(
                            $fields->option('number',array(
                                'name'=>'slide-pause',
                                'label'=>__('Pause Slider','wp-recall'),
                                'help'=>__('Only used in the derivation of the slider via shortcode publications <a href="https://codeseller.ru/api-rcl/slider-rcl/" target="_blank">[slider-rcl]</a>','wp-recall'),
                                'notice'=>__('The value of the pause between slide transitions in seconds. Default value is 0 - the slide show is not made','wp-recall')
                            ))
                        ))
                    )
                );
                
                
                $content .= $fields->option_block(
                    array(
                        $fields->extend(array(
                            $fields->title(__('Caching','wp-recall')),

                            $fields->option('select',array(
                                'name'=>'use_cache',
                                'label'=>__('Cache','wp-recall'),
                                'help'=>__('Use the functionality of the caching plugin WP-Recall. <a href="https://codeseller.ru/post-group/funkcional-keshirovaniya-plagina-wp-recall/" target="_blank">read More</a>','wp-recall'),
                                'parent'=>true,
                                'options'=>array(
                                    __('Disabled','wp-recall'),
                                    __('Included','wp-recall'))
                            )),
                            $fields->child(
                                 array(
                                     'name'=>'use_cache',
                                     'value'=>1
                                 ),
                                 array(
                                     $fields->option('number',array(
                                         'name'=>'cache_time',
                                         'default'=>3600,
                                         'label'=>__('Time cache (seconds)','wp-recall'),
                                         'notice'=>__('Default','wp-recall').': 3600'
                                         )),

                                     $fields->option('select',array(
                                        'name'=>'cache_output',
                                        'label'=>__('Cache output','wp-recall'),
                                        'options'=>array(
                                            __('All users','wp-recall'),
                                            __('Only guests','wp-recall'))
                                    ))
                                 )
                            ),
                            $fields->option('select',array(
                                'name'=>'minify_css',
                                'label'=>__('Minimization of style files','wp-recall'),
                                'options'=>array(
                                    __('Disabled','wp-recall'),
                                    __('Included','wp-recall')),
                                    'notice'=>__('Minimization of style files only works against the style files Wp-Recall and additions that support this feature','wp-recall')
                            )),
                            $fields->option('select',array(
                                'name'=>'minify_js',
                                'label'=>__('Minimization of scripts','wp-recall'),
                                'options'=>array(
                                    __('Disabled','wp-recall'),
                                    __('Included','wp-recall'))
                            ))
                        ))
                    )
                );

                $content .= $fields->option_block(
                    array(
                        $fields->title(__('Login and register','wp-recall')),
                        $fields->option('select',array(
                            'name'=>'login_form_recall',
                            'label'=>__('The order','wp-recall'),
                            'parent'=>true,
                            'options'=>array(
                                __('Floating form','wp-recall'),
                                __('On a separate page','wp-recall'),
                                __('Form Wordpress','wp-recall'),
                                __('The form in the widget','wp-recall'))
                        )),
                        $fields->child(
                            array(
                              'name' => 'login_form_recall',
                              'value' => 1
                            ),
                            array(
                                $fields->label(__('ID of the page with the shortcode [loginform]','wp-recall')),
                                wp_dropdown_pages( array(
                                    'selected'   => $rcl_options['page_login_form_recall'],
                                    'name'       => 'global[page_login_form_recall]',
                                    'show_option_none' => __('Not selected','wp-recall'),
                                    'echo'             => 0 )
                                )
                            )
                        ),
                        $fields->extend(array(
                            $fields->option('select',array(
                                'name'=>'confirm_register_recall',
                                'help'=>__('If you are using the confirmation of registration, after registration, the user will need to confirm your email by clicking on the link in the sent email','wp-recall'),
                                'label'=>__('A registration confirmation by the user','wp-recall'),
                                'options'=>array(
                                    __('Not used','wp-recall'),
                                    __('Used','wp-recall'))
                            )),
                            $fields->option('select',array(
                                'name'=>'authorize_page',
                                'label'=>__('Redirect user after login','wp-recall'),
                                'parent'=>1,
                                'options'=>array(
                                    __('The user profile','wp-recall'),
                                    __('Current page','wp-recall'),
                                    __('Arbitrary URL','wp-recall'))
                            )),
                            $fields->child(
                                array(
                                  'name' => 'authorize_page',
                                  'value' => 2
                                ),
                                array(
                                    $fields->option('text',array(
                                        'name'=>'custom_authorize_page',
                                        'label'=>__('URL','wp-recall'),
                                        'notice'=>__('Enter your URL below, if you select an arbitrary URL after login','wp-recall')
                                    ))
                                )
                            ),
                            $fields->option('select',array(
                                'name'=>'repeat_pass',
                                'label'=>__('Field repeat password','wp-recall'),
                                'options'=>array(__('Disabled','wp-recall'),__('Displayed','wp-recall'))
                            )),
                            $fields->option('select',array(
                                'name'=>'difficulty_parole',
                                'label'=>__('Indicator password complexity','wp-recall'),
                                'options'=>array(__('Disabled','wp-recall'),__('Displayed','wp-recall'))
                            ))
                        ))
                    )
                );

                $content .= $fields->option_block(
                    array(
                        $fields->title(__('Recallbar','wp-recall')),
                        $fields->option('select',array(
                            'name'=>'view_recallbar',
                            'label'=>__('Conclusion the panel recallbar','wp-recall'),
                            'help'=>__('Recallbar - top panel from the plugin WP-Recall using that plugin and its add-ons can withdraw their data and the administrator can place an arbitrary menu, forming him on <a href="/wp-admin/nav-menus.php" target="_blank">page management menu of the website</a>','wp-recall'),
                            'parent'=>true,
                            'options'=>array(__('Disabled','wp-recall'),__('Included','wp-recall'))
                        )),
                        $fields->child(
                            array(
                                'name'=>'view_recallbar',
                                'value'=>1
                            ),
                            array(
                                $fields->option('select',array(
                                    'name'=>'rcb_color',
                                    'label'=>__('Color','wp-recall'),
                                    'options'=>array(__('Default','wp-recall'),__('Primary colors WP-Recall','wp-recall'))
                                ))
                            )
                        )
                    )
                );

                $content .= $fields->option_block(
                    array(
                        $fields->extend(array(
                            $fields->title(__('Your gratitude','wp-recall')),
                            $fields->option('select',array(
                                'name'=>'rcl_footer_link',
                                'label'=>__('To display a link to the developer`s site (Thank you, if you decide to show)','wp-recall'),
                                'type'=>'local',
                                'options'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                            ))
                        ))
                    )
                );

    $content .= '</div>';

    $content = apply_filters('admin_options_wprecall',$content);

    $content .= '<div class="submit-block">
    <p><input type="submit" class="button button-primary button-large right" name="rcl_global_options" value="'.__('Save settings','wp-recall').'" /></p>
    </div></form></div>';

    echo $content;
}

add_action('wp_ajax_rcl_update_options', 'rcl_update_options');
function rcl_update_options(){
    global $rcl_options;
    
    if( !wp_verify_nonce( $_POST['_wpnonce'], 'update-options-rcl' ) ){
        $result['result'] = 0;
        $result['notice'] = __('Error','wp-recall');
        echo json_encode($result);
        exit;
    }

    $POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    
    array_walk_recursive(
    $POST, function(&$v, $k) {
      $v = trim($v);
    });
    
    //print_r($POST);exit;

    if($POST['global']['login_form_recall']==1&&!isset($POST['global']['page_login_form_recall'])){
            $POST['global']['page_login_form_recall'] = wp_insert_post(array('post_title'=>__('Login and register','wp-recall'),'post_content'=>'[loginform]','post_status'=>'publish','post_author'=>1,'post_type'=>'page','post_name'=>'login-form'));
    }

    foreach((array)$POST['global'] as $key => $value){
        $value = apply_filters('rcl_global_option_value',$value,$key);
        $options[$key] = $value;
    }

    if(isset($rcl_options['users_page_rcl'])) 
        $options['users_page_rcl'] = $rcl_options['users_page_rcl'];

    update_option('rcl_global_options',$options);

    if(isset($POST['local'])){
        foreach((array)$POST['local'] as $key => $value){
            $value = apply_filters('rcl_local_option_value',$value,$key);
            if($value=='') delete_option($key);
            else update_option($key,$value);
        }
    }

    $rcl_options = $options;

    if( current_user_can('edit_plugins') ){
        rcl_update_scripts();
        //rcl_minify_style();
    }

    $result['result'] = 1;
    $result['notice'] = __('Options saved!','wp-recall');

    echo json_encode($result);
    exit;

}

function rcl_custom_tabs_manage(){
    
    rcl_sortable_scripts();

    include_once RCL_PATH.'functions/class-rcl-editfields.php';
    
    $f_edit = new Rcl_EditFields('custom_tabs',
            array(
                'meta-key'=>false,
                'select-type'=>false,
                'placeholder'=>false,
                'sortable'=>false
                )
            );
    
    if($f_edit->verify()) $fields = $f_edit->update_fields(false);
    
    $content = '<h2>'.__('Custom tabs personal account','wp-recall').'</h2>';

    $content .= $f_edit->edit_form(array(
        $f_edit->option('text',array(
            'name'=>'slug',
            'label'=>__('ID tab','wp-recall'),
            'placeholder'=>__('Latin alphabet and numbers','wp-recall')
        )),
        $f_edit->option('text',array(
            'name'=>'icon',
            'label'=>__('Class icon font-awesome','wp-recall'),
            'placeholder'=>__('Example , fa-user','wp-recall'),
            'notice'=>__('Источник <a href="http://fontawesome.io/icons/" target="_blank">http://fontawesome.io/</a>','wp-recall')
        )),
        $f_edit->option('select',array(
            'name'=>'public',
            'notice'=>__('Public tab','wp-recall'),
            'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
        )),
        $f_edit->option('select',array(
            'name'=>'ajax',
            'notice'=>__('ajax-loading support','wp-recall'),
            'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
        )),
        $f_edit->option('select',array(
            'name'=>'cache',
            'notice'=>__('caching support','wp-recall'),
            'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
        )),
        $f_edit->option('textarea',array(
            'name'=>'content',
            'label'=>__('Content tab','wp-recall'),
            'notice'=>__('supported shortcodes and HTML-code','wp-recall')
        ))
    ));

    echo $content;
}

function wp_enqueue_theme_rcl($url){
    wp_enqueue_style( 'theme_rcl', $url );
}

add_action('admin_notices', 'my_plugin_admin_notices');
function my_plugin_admin_notices() {
    if(isset($_GET['page'])&&(
            $_GET['page']=='manage-wprecall'||
            $_GET['page']=='rcl-repository'||
            $_GET['page']=='manage-doc-recall'||
            $_GET['page']=='manage-addon-recall'
    ))
      echo "<div class='updated is-dismissible notice'><p>Понравился плагин WP-Recall? Поддержите развитие плагина, оставив положительный отзыв на его странице в <a target='_blank' href='https://wordpress.org/plugins/wp-recall/'>репозитории</a>!</p></div>";
}

include 'repository.php';