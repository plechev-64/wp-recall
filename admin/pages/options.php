<?php

require_once RCL_PATH.'classes/class-rcl-options.php';

require_once RCL_PATH.'admin/classes/class-rcl-options-manager.php';

global $rcl_options;

rcl_font_awesome_style();

wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui-dialog');
wp_enqueue_style('wp-jquery-ui-dialog');

$options = new Rcl_Options_Manager(array(
    'option_name' => 'rcl_global_options',
    'page_options' => 'rcl-options',
    'extends' => true
));

$options->add_box('primary', array(
    'title' => __('General settings', 'rcl'),
    'icon' => 'fa-cogs'
))->add_group('office', array(
    'title' => __('Personal cabinet', 'wp-recall'),
    'extend' => true
))->add_options(array(
    array(
        'type'=>'select',
        'slug'=>'view_user_lk_rcl',
        'title'=>__('Personal Cabinet output','wp-recall'),
        'values'=>array(
            __('On the author’s archive page','wp-recall'),
            __('Using shortcode [wp-recall]','wp-recall')),
        'help'=>__('Attention! Changing this parameter is not required. Detailed instructions on personal account output using author.php file can be received here <a href="https://codeseller.ru/ustanovka-plagina-wp-recall-na-sajt/" target="_blank">here</a>','wp-recall'),
        'notice'=>__('If author archive page is selected, the template author.php should contain the code if(function_exists(\'wp_recall\')) wp_recall();','wp-recall'),
        'childrens' => array(
            'lk_page_rcl', 'link_user_lk_rcl'
        )
    ),
    array(
        'parent' => array(
            'id' => 'view_user_lk_rcl',
            'value' => 1
        ),
        'type' => 'custom',
        'slug'=>'lk_page_rcl',
        'title' => __('Shortcode host page','wp-recall'),
        'content' => wp_dropdown_pages( array(
                'selected'   => $rcl_options['lk_page_rcl'],
                'name'       => 'rcl_global_options[lk_page_rcl]',
                'show_option_none' => '<span style="color:red">'.__('Not selected','wp-recall').'</span>',
                'echo'       => 0
            )
        )
    ),
    array(
        'parent' => array(
            'id' => 'view_user_lk_rcl',
            'value' => 1
        ),
        'type' => 'text',
        'slug' => 'link_user_lk_rcl',
        'title' => __('Link format to personal account','wp-recall'),
        'help' => __('The link is formed according to principle "/slug_page/?get=ID". The parameter "get" can be set here. By default user','wp-recall')
    ),
    array(
        'type' => 'runner',
        'slug'=>'timeout',
        'value_min' => 1,
        'value_max' => 20,
        'default' => 10,
        'help'=>__('This value sets the maximum time a user is considered "online" in the absence of activity','wp-recall'),
        'title'=>__('Inactivity timeout','wp-recall'),
        'notice'=>__('Specify the time in minutes after which the user will be considered offline if you did not show activity on the website. The default is 10 minutes.','wp-recall')
    )
));

$options->box('primary')->add_group('access_console', array(
    'title' => __('Access to the console','wp-recall'),
))->add_options(array(
    array(
        'type' => 'select',
        'default' => 7,
        'slug' => 'consol_access_rcl',
        'title' => __('Access to the console is allowed','wp-recall'),
        'values' => array(
            10  => __('only Administrators','wp-recall'),
            7   => __('Editors and higher','wp-recall'),
            2   => __('Authors and higher','wp-recall'),
            1   => __('Participants and higher','wp-recall'),
            0   => __('All users','wp-recall')
        )
    )
));

$options->box('primary')->add_group('logging', array(
    'title' => __('Logging mode','wp-recall'),
))->add_options(array(
    array(
        'type' => 'select',
        'slug'=>'rcl-log',
        'title'=>__('Write background events and errors to the log-file','wp-recall'),
        'values'=> array(
            __('Disabled','wp-recall'),
            __('Enabled','wp-recall')
        )
    )
));

$options->box('primary')->add_group('design', array(
    'title' => __('Design','wp-recall'),
))->add_options(array(
    array(
        'type' => 'color',
        'slug'=>'primary-color',
        'title'=>__('Primary color','wp-recall'),
        'default'=>'#4C8CBD'
    ),
    array(
        'type' => 'color',
        'slug'=>'rcl-button-text-color',
        'title'=>__('Button text color','wp-recall'),
        'default'=>'#fff'
    ),
    array(
        'type' => 'runner',
        'value_min' => 12,
        'value_max' => 20,
        'value_step' => 1,
        'default' => 14,
        'slug' => 'rcl-button-font-size',
        'title' => __('Button font size','wp-recall').', px'
    ),
    array(
        'type' => 'radio',
        'slug'=>'font-awesome',
        'title'=>__('Подключение шрифта Font Awesome','wp-recall'),
        'default'=>1,
        'values' => array(
            __('No', 'wp-recall'),
            __('Yes', 'wp-recall')
        ),
        'notice' => __('Подключение необходимо для отображения иконок в дополнениях, использующих шрифт Font Awesome 4.7 и ниже. При отсутствии такой необходимости можно отключить.', 'wp-recall')
    ),
    array(
        'type' => 'select',
        'slug'=>'buttons_place',
        'title'=>__('The location of the section buttons','wp-recall'),
        'values'=>array(
            __('Top','wp-recall'),
            __('Left','wp-recall'))
    ),
    array(
        'type' => 'runner',
        'value_min' => 0,
        'value_max' => 5120,
        'value_step' => 256,
        'default' => 1024,
        'slug' => 'avatar_weight',
        'title' => __('Max weight of avatars','wp-recall').', Kb',
        'notice' => __('Set the image upload limit in kb, by default','wp-recall'). ' 1024Kb' .
        '. ' . __('If 0 is specified, download is disallowed.','wp-recall')
    ),
    array(
        'type' => 'runner',
        'value_min' => 0,
        'value_max' => 5120,
        'value_step' => 256,
        'default' => 1024,
        'slug' => 'cover_weight',
        'title' => __('Max weight of cover','wp-recall').', Kb',
        'notice' => __('Set the image upload limit in kb, by default','wp-recall'). ' 1024Kb' .
        '. ' . __('If 0 is specified, download is disallowed.','wp-recall')
    )
));

$options->box('primary')->add_group('caching', array(
    'title' => __('Caching','wp-recall'),
    'extend' => true
))->add_options(array(
    array(
        'type' => 'select',
        'slug'=>'use_cache',
        'title'=>__('Cache','wp-recall'),
        'help'=>__('Use the functionality of the caching WP-Recall plugin. <a href="https://codeseller.ru/post-group/funkcional-keshirovaniya-plagina-wp-recall/" target="_blank">read More</a>','wp-recall'),
        'values'=>array(
            __('Disabled','wp-recall'),
            __('Enabled','wp-recall')),
        'childrens' => array(
            'cache_time', 'cache_output'
        )
    ),
    array(
        'parent' => array(
            'id' => 'use_cache',
            'value' => 1
        ),
        'type' => 'number',
        'slug'=>'cache_time',
        'default'=>3600,
        'latitlebel'=>__('Time cache (seconds)','wp-recall'),
        'notice'=>__('Default','wp-recall').': 3600'
    ),
    array(
        'parent' => array(
            'id' => 'use_cache',
            'value' => 1
        ),
        'type' => 'select',
        'slug'=>'cache_output',
        'title'=>__('Cache output','wp-recall'),
        'values'=>array(
            __('All users','wp-recall'),
            __('Only guests','wp-recall'))
    ),
    array(
        'type'=>'select',
        'slug'=>'minify_css',
        'title'=>__('Minimization of file styles','wp-recall'),
        'values'=>array(
            __('Disabled','wp-recall'),
            __('Enabled','wp-recall')),
        'notice'=>__('Minimization of file styles only works in correlation with WP-Recall style files and add-ons that support this feature','wp-recall')
    ),
    array(
        'type'=>'select',
        'slug'=>'minify_js',
        'title'=>__('Minimization of scripts','wp-recall'),
        'values'=>array(
            __('Disabled','wp-recall'),
            __('Enabled','wp-recall'))
    )
));

$options->box('primary')->add_group('sign', array(
    'title' => __('Login and register','wp-recall')
))->add_options(array(
    array(
        'type' => 'select',
        'slug'=>'login_form_recall',
        'title'=>__('Порядок вывода формы входа и регистрации','wp-recall'),
        'values'=>array(
            __('Floating form','wp-recall'),
            __('On a separate page','wp-recall'),
            __('Wordpress Forms','wp-recall'),
            __('Widget form','wp-recall')),
        'notice' => __('Форма входа и регистрации плагина может быть выведена через виджет "Панель управления", шорткод [loginform], а можете использовать стандартные формы WordPress', 'wp-recall'),
        'childrens' => array(
            'page_login_form_recall'
        ),
        array(
            'parent' => array(
                'id' => 'login_form_recall',
                'value' => 1
            ),
            'type' => 'custom',
            'slug' => 'page_login_form_recall',
            'title' => __('ID of the shortcode page [loginform]','wp-recall'),
            'content' => wp_dropdown_pages( array(
                'selected'   => isset($rcl_options['page_login_form_recall'])? $rcl_options['page_login_form_recall']: '',
                'name'       => 'rcl_global_options[page_login_form_recall]',
                'show_option_none' => __('Not selected','wp-recall'),
                'echo'             => 0 )
            )
        )
    ),
    array(
        'extend' => true,
        'type' => 'select',
        'slug'=>'confirm_register_recall',
        'help'=>__('If you are using the registration confirmation, after registration, the user will need to confirm your email by clicking on the link in the sent email','wp-recall'),
        'title'=>__('Registration confirmation by the user','wp-recall'),
        'values'=>array(
            __('Not used','wp-recall'),
            __('Used','wp-recall'))
    ),
    array(
        'extend' => true,
        'type' => 'select',
        'slug'=>'authorize_page',
        'title'=>__('Redirect user after login','wp-recall'),
        'values'=>array(
            __('The user profile','wp-recall'),
            __('Current page','wp-recall'),
            __('Arbitrary URL','wp-recall')),
        'childrens' => array(
            'custom_authorize_page'
        )
    ),
    array(
        'parent' => array(
            'id' => 'authorize_page',
            'value' => 2
        ),
        'extend' => true,
        'type' => 'text',
        'slug'=>'custom_authorize_page',
        'title'=>__('URL','wp-recall'),
        'notice'=>__('Enter your URL below, if you select an arbitrary URL after login','wp-recall')
    ),
    array(
        'extend' => true,
        'type' => 'select',
        'slug'=>'repeat_pass',
        'title'=>__('repeat password field','wp-recall'),
        'values'=>array(__('Disabled','wp-recall'),__('Displaye','wp-recall'))
    ),
    array(
        'extend' => true,
        'type' => 'select',
        'slug'=>'difficulty_parole',
        'title'=>__('Indicator of password complexity','wp-recall'),
        'values'=>array(__('Disabled','wp-recall'),__('Displaye','wp-recall'))
    )
));

$options->box('primary')->add_group('recallbar', array(
    'title' => __('Recallbar','wp-recall')
))->add_options(array(
    array(
        'type' => 'select',
        'slug'=>'view_recallbar',
        'title'=>__('Output of recallbar panel','wp-recall'),
        'help'=>__('Recallbar – is he top panel WP-Recall plugin through which the plugin and its add-ons can output their data and the administrator can make his menu, forming it on <a href="/wp-admin/nav-menus.php" target="_blank">page management menu of the website</a>','wp-recall'),
        'values'=>array(__('Disabled','wp-recall'),__('Enabled','wp-recall')),
        'childrens' => array(
            'rcb_color'
        )
    ),
    array(
        'parent' => array(
            'id' => 'view_recallbar',
            'value' => 1
        ),
        'type' => 'select',
        'slug'=>'rcb_color',
        'title'=>__('Color','wp-recall'),
        'values'=>array(__('Default','wp-recall'),__('Primary colors of WP-Recall','wp-recall'))
    )
));

/* support old options */
global $rclOldOptionData;

apply_filters('admin_options_wprecall', '');

if($rclOldOptionData){

    foreach($rclOldOptionData as $box_id => $box){

        if(!$box['groups']) continue;

        $options->add_box($box_id, array(
            'title' => $box['title']
        ));

        foreach($box['groups'] as $k => $group){

            $options->box($box_id)->add_group($k, array(
                'title' => $group['title']
            ))->add_options($group['options']);

        }

    }

}

unset($rclOldOptionData);
/****/

$options = apply_filters('rcl_options', $options);

$content = '<h2>'.__('Configure WP-Recall plugin and add-ons', 'wp-recall').'</h2>';

$content .= $options->get_content();

echo $content;

