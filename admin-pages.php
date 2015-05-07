<?php
function rcl_options_panel(){
	add_menu_page(__('WP-RECALL','rcl'), __('WP-RECALL','rcl'), 'manage_options', 'manage-wprecall', 'rcl_global_options');
		add_submenu_page( 'manage-wprecall', __('SETTINGS','rcl'), __('SETTINGS','rcl'), 'manage_options', 'manage-wprecall', 'rcl_global_options');
		add_submenu_page( 'manage-wprecall', __('Documentation','rcl'), __('Documentation','rcl'), 'manage_options', 'manage-doc-recall', 'rcl_doc_manage');
}

function rcl_doc_manage(){
	echo '<h2>'.__('Documentation for the plugin WP-RECALL').'</h2>
		<ol>
                    <li><a href="http://wppost.ru/ustanovka-plagina-wp-recall-na-sajt/" target="_blank">'.__('Plugin installation','rcl').'</a></li>
                    <li><a href="http://wppost.ru/obnovlenie-plagina-wp-recall-i-ego-dopolnenij/" target="_blank">'.__('Update the plugin and its extensions','rcl').'</a></li>
                    <li><a href="http://wppost.ru/nastrojki-plagina-wp-recall/" target="_blank">'.__('The plugin settings','rcl').'</a></li>
                    <li><a href="http://wppost.ru/shortkody-wp-recall/" target="_blank">'.__('Used shortcodes Wp-Recall','rcl').'</a></li>
                    <li><a href="http://wppost.ru/obshhie-svedeniya-o-dopolneniyax-wp-recall/" target="_blank">'.__('General information about plugins Wp-Recall','rcl').'</a></li>
                    <li><a href="http://wppost.ru/dopolneniya-wp-recall/" target="_blank">'.__('Basic add-ons Wp-Recall','rcl').'</a></li>
                    <li><a href="http://wppost.ru/downloads-files/" target="_blank">'.__('Paid add-ons Wp-Recall','rcl').'</a></li>
                    <li><a title="Произвольные поля Wp-Recall" href="http://wppost.ru/proizvolnye-polya-wp-recall/" target="_blank">'.__('Custom fields profile Wp-Recall','rcl').'</a></li>
                    <li><a title="Произвольные поля формы публикации Wp-Recall" href="http://wppost.ru/proizvolnye-polya-formy-publikacii-wp-recall/" target="_blank">'.__('Custom fields form publishing Wp-Recall','rcl').'</a></li>
                    <li><a href="http://wppost.ru/sozdaem-svoe-dopolnenie-dlya-wp-recall-vyvodim-svoyu-vkladku-v-lichnom-kabinete/" target="_blank">'.__('An example of additions Wp-Recall','rcl').'</a></li>
                    <li><a href="http://wppost.ru/xuki-i-filtry-wp-recall/" target="_blank">'.__('Functions and hooks Wp-Recall for the development','rcl').'</a></li>
                    <li><a href="http://wppost.ru/category/novosti/obnovleniya/" target="_blank">'.__('Update history Wp-Recall','rcl').'</a></li>
                    <li><a title="Используемые библиотеки и ресурсы" href="http://wppost.ru/ispolzuemye-biblioteki-i-resursy/">'.__('Used libraries and resources','rcl').'</a></li>
                    <li><a href="http://wppost.ru/faq/" target="_blank">'.__('FAQ','rcl').'</a></li>
            </ol>';
}

if (is_admin()) add_action('admin_init', 'rcl_postmeta_post');
function rcl_postmeta_post() {
    add_meta_box( 'recall_meta', __('Settings Wp-Recall','rcl'), 'rcl_options_box', 'post', 'normal', 'high'  );
    add_meta_box( 'recall_meta', __('Settings Wp-Recall','rcl'), 'rcl_options_box', 'page', 'normal', 'high'  );
}

add_filter('rcl_post_options','rcl_gallery_options',10,2);
function rcl_gallery_options($options,$post){
    $mark_v = get_post_meta($post->ID, 'recall_slider', 1);
    $options .= '<p>'.__('Pictures record the withdrawal in the gallery Wp-Recall?').':
        <label><input type="radio" name="wprecall[recall_slider]" value="" '.checked( $mark_v, '',false ).' />'.__('No','rcl').'</label>
        <label><input type="radio" name="wprecall[recall_slider]" value="1" '.checked( $mark_v, '1',false ).' />'.__('Yes','rcl').'</label>
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
function rcl_data_type_profile_field(){

	//$type = $_POST['type'];
	$slug = sanitize_title($_POST['slug']);

	$content = '<textarea rows="1" name="field_select_'.$slug.'"></textarea>';

	$data['result']=100;
	$data['content']= $content;
	echo json_encode($data);

    exit;
}
add_action('wp_ajax_rcl_data_type_profile_field', 'rcl_data_type_profile_field');
//add_action('wp_ajax_nopriv_rcl_data_type_profile_field', 'rcl_data_type_profile_field');

//Настройки плагина в админке
function rcl_global_options(){
    global $rcl_options;

    include_once RCL_PATH.'functions/rcl_options.php';
    $fields = new Rcl_Options();

    $rcl_options = get_option('primary-rcl-options');

    $content = '<h2>'.__('Configure the plugin Wp-Recall and additions','rcl').'</h2>
        <div id="recall" class="left-sidebar wrap">
	<form method="post" action="">
	'.wp_nonce_field('update-options-rcl','_wpnonce',true,false).'
	<span class="title-option active">'.__('General settings','rcl').'</span>
	<div class="wrap-recall-options" style="display:block;">';

                $args = array(
                    'selected'   => $rcl_options['lk_page_rcl'],
                    'name'       => 'lk_page_rcl',
                    'show_option_none' => '<span style="color:red">'.__('Not selected','rcl').'</span>',
                    'echo'       => 0
                );

                $content .= $fields->option_block(array(
                    $fields->title(__('Personal account','rcl')),
                    $fields->label(__('The order of withdrawal of the personal Cabinet','rcl')),
                    $fields->option('select',array(
                            'name'=>'view_user_lk_rcl',
                            'parent'=>true,
                            'options'=>array(
                                __('On the archive page of the author','rcl'),
                                __('Using the shortcode [wp-recall]','rcl'))
                        )),
                    $fields->child(
                        array(
                            'name'=>'view_user_lk_rcl',
                            'value'=>1
                        ),
                        array(
                            $fields->label(__('The host page the shortcode','rcl')),
                            wp_dropdown_pages( $args ),
                            $fields->label(__('The formation of links to personal account','rcl')),
                            $fields->option('text',array('name'=>'link_user_lk_rcl')),
                            $fields->notice(__('The link is formed by a principle "/slug_page/?get=ID". The parameter "get" can be set here. By default user','rcl'))
                        )
                    ),
                    $fields->label(__('Download tabs','rcl')),
                    $fields->option('select',array(
                        'name'=>'tab_newpage',
                        'options'=>array(
                            __('Downloads all','rcl'),
                            __('On a separate page','rcl'),
                            __('Ajax loading','rcl'))
                    )),
                    $fields->label(__('Inactivity timeout','rcl')),
                    $fields->option('number',array('name'=>'timeout')),
                    $fields->notice(__('Specify the time in minutes after which the user will be considered offline if you did not show activity on the website. The default is 10 minutes.','rcl'))
                ));


                $roles = array(
                    10=>__('only Administrators','rcl'),
                    7=>__('Editors and older','rcl'),
                    2=>__('Authors and older','rcl'),
                    1=>__('Participants and older','rcl'),
                    0=>__('All users','rcl'));

                $content .= $fields->option_block(array(
                    $fields->title(__('Access to the console','rcl')),
                    $fields->label(__('Access to the site is permitted console','rcl')),
                    $fields->option('select',array(
                            'default'=>7,
                            'name'=>'consol_access_rcl',
                            'options'=>$roles
                    )),
                    $fields->notice(__('If the selected archive page of the author, in the right place template author.php paste the code if(function_exists(\'wp_recall\')) wp_recall();','rcl')),

                ));

		$filecss = (file_exists(TEMP_PATH.'css/minify.css'))? '<a href="'.RCL_URL.'css/getcss.php">'.__('Download the current style file for editing','rcl').'</a>':'';
                $content .= $fields->option_block(
                    array(
			$fields->title(__('Making','rcl')),

			$fields->label(__('The placement of the buttons sections','rcl')),
                        $fields->option('select',array(
                            'name'=>'buttons_place',
                            'options'=>array(
                                __('Top','rcl'),
                                __('Left','rcl'))
                        )),

			rcl_theme_list(),

                        $fields->label(__('Pause Slider','rcl')),
                        $fields->option('number',array('name'=>'slide-pause')),
                        $fields->notice(__('The value of the pause between slide transitions in seconds. Default value is 0 - the slide show is not made','rcl')),

                        $fields->label(__('Minimization of style files','rcl')),
                        $fields->option('select',array(
                            'name'=>'minify_css',
                            'parent'=>true,
                            'options'=>array(
                                __('Disabled','rcl'),
                                __('Included','rcl'))
                        )),
                        $fields->notice(__('Minimization of style files only works against the style files Wp-Recall and additions that support this feature','rcl')),
			$fields->child(
                             array(
                                 'name'=>'minify_css',
                                 'value'=>1
                             ),
                             array(
                                 $fields->label(__('Your stylesheet(CSS)','rcl')),
                                 $fields->option('text',array('name'=>'custom_scc_file_recall')),
                                 $fields->notice(__('File replaces the minified stylesheet, if enabled minimization','rcl')),
                                 $filecss
                             )
                        )
                    )
                );

                $content .= $fields->option_block(
                    array(
                        $fields->title(__('Login and register','rcl')),
                        $fields->label(__('The order','rcl')),
                        $fields->option('select',array(
                            'name'=>'login_form_recall',
                            'parent'=>true,
                            'options'=>array(
                                __('Floating form','rcl'),
                                __('On a separate page','rcl'),
                                __('Form Wordpress','rcl'),
                                __('The form in the widget','rcl'))
                        )),
                        $fields->child(
                            array(
                              'name' => 'login_form_recall',
                              'value' => 1
                            ),
                            array(
                                $fields->label(__('ID of the page with the shortcode [loginform]','rcl')),
                                $fields->option('text',array('name'=>'page_login_form_recall','rcl')),
                                $fields->notice(__('<b>note:</b> If selected, the order of the login form and registration on a separate page, create the page, to arrange its contents shortcode [loginform] and specify the ID of this page in the box above.','rcl'))
                            )
                        ),
                        $fields->label(__('A registration confirmation by the user','rcl')),
                        $fields->option('select',array(
                            'name'=>'confirm_register_recall',
                            'options'=>array(
                                __('Not used','rcl'),
                                __('Used','rcl'))
                        )),
                        $fields->label(__('Redirect user after login','rcl')),
                        $fields->option('select',array(
                            'name'=>'authorize_page',
                            'parent'=>1,
                            'options'=>array(
                                __('The user profile','rcl'),
                                __('Current page','rcl'),
                                __('Arbitrary URL','rcl'))
                        )),
                        $fields->child(
                            array(
                              'name' => 'authorize_page',
                              'value' => 2
                            ),
                            array(
                                $fields->label(__('URL','rcl')),
                                $fields->option('text',array('name'=>'custom_authorize_page')),
                                $fields->notice(__('Enter your URL below, if you select an arbitrary URL after login','rcl'))
                            )
                        ),
                        $fields->label(__('Field repeat password','rcl')),
                        $fields->option('select',array(
                            'name'=>'repeat_pass',
                            'options'=>array(__('Disabled','rcl'),__('Displayed','rcl'))
                        )),
                        $fields->label(__('Indicator password complexity','rcl')),
                        $fields->option('select',array(
                            'name'=>'difficulty_parole',
                            'options'=>array(__('Disabled','rcl'),__('Displayed','rcl'))
                        ))
                    )
                );

                $content .= $fields->option_block(
                    array(
                        $fields->title(__('Recallbar','rcl')),
                        $fields->label(__('Conclusion the panel recallbar','rcl')),
                        $fields->option('select',array(
                            'name'=>'view_recallbar',
                            'options'=>array(__('Disabled','rcl'),__('Included','rcl'))
                        ))
                    )
                );

                $content .= $fields->option_block(
                    array(
                        $fields->title(__('Your gratitude','rcl')),
                        $fields->label(__('To display a link to the developer`s site (Thank you, if you decide to show)','rcl')),
                        $fields->option('select',array(
                               'name'=>'footer_url_recall',
                               'options'=>array(__('No','rcl'),__('Yes','rcl'))
                        ))
                    )
                );

    $content .= '</div>';

    $content = apply_filters('admin_options_wprecall',$content);

    $content .= '<div class="submit-block">
    <p><input type="submit" class="button button-primary button-large right" name="primary-rcl-options" value="'.__('Save settings','rcl').'" /></p>
    </div></form></div>';

    echo $content;
}

function rcl_theme_list(){
    global $rcl_options;

    if(!isset($rcl_options['color_theme'])) $color_theme = 1;
    else $color_theme = $rcl_options['color_theme'];
    $dirs   = array(RCL_PATH.'css/themes',TEMPLATEPATH.'/wp-recall/themes');
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
            $content = '<label>'.__('Used template','rcl').'</label>';
            $content .= '<select name="color_theme" size="1">
                <option value="">'.__('Not connected','rcl').'</option>
                    '.$t_list.'
            </select>';

        return $content;
    }
    return false;
}
/*no found*/
function rcl_url_theme(){
    $dirs   = array(TEMPLATEPATH.'/wp-recall/themes',RCL_PATH.'css/themes');
    foreach($dirs as $dir){
        if(!file_exists($dir.'/'.$rcl_options['color_theme'].'.css')) continue;
        wp_enqueue_theme_rcl(rcl_path_to_url($dir.'/'.$rcl_options['color_theme'].'.css'));
        break;
    }
}
function wp_enqueue_theme_rcl($url){
    wp_enqueue_style( 'theme_rcl', $url );
}