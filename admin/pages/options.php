<?php

global $rcl_options;

rcl_font_awesome_style();
    
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui-dialog');
wp_enqueue_style('wp-jquery-ui-dialog');

require_once RCL_PATH.'classes/class-rcl-options.php';

$fields = new Rcl_Options(__FILE__);

$rcl_options = get_option('rcl_global_options');

$extends = isset($_COOKIE['rcl_extends'])? $_COOKIE['rcl_extends']: 0;

$content = '<h2>'.__('Configure Wp-Recall plugin and add-ons','wp-recall').'</h2>
    <div id="recall" class="left-sidebar wrap">
    <span class="shift-extend-options">
        <label><input type="checkbox" name="extend_options" '.checked($extends,1,false).' onclick="return rcl_enable_extend_options(this);" value="1"> '.__('Advanced settings').'</label>
    </span>
    <form method="post" id="rcl-options-form" onsubmit="rcl_update_options();return false;" action="">
    '.wp_nonce_field('update-options-rcl','_wpnonce',true,false).'
    <span id="title-primary" data-addon="primary" data-url="'.admin_url('admin.php?page='.$_GET['page'].'&rcl-addon-options=primary').'" class="title-option active"><span class="wp-menu-image dashicons-before dashicons-admin-generic"></span> '.__('General settings','wp-recall').'</span>
    <div class="wrap-recall-options" id="options-primary" style="display:block;">';

        $content .= $fields->extend(array(
            $fields->options_box(
                __('Personal cabinet','wp-recall'),
                array(
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
                            1 => array(
                                array(
                                    'type' => 'custom',
                                    'title' => __('Shortcode host page','wp-recall'),
                                    'content' => wp_dropdown_pages( array(
                                            'selected'   => $rcl_options['lk_page_rcl'],
                                            'name'       => 'global[lk_page_rcl]',
                                            'show_option_none' => '<span style="color:red">'.__('Not selected','wp-recall').'</span>',
                                            'echo'       => 0
                                        ) 
                                    )
                                ),
                                array(
                                    'type' => 'text',
                                    'slug' => 'link_user_lk_rcl',
                                    'title' => __('Link format to personal account','wp-recall'),
                                    'help' => __('The link is formed according to principle "/slug_page/?get=ID". The parameter "get" can be set here. By default user','wp-recall')
                                )
                            )
                        )
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
                )
            )
        ));

        $content .= $fields->extend(array(
            $fields->options_box(
                __('Access to the console','wp-recall'),
                array(
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
                )
            )
        ));

        $content .= $fields->extend(array(
            $fields->options_box(
                __('Logging mode','wp-recall'),
                array(
                    array(
                        'type' => 'select',
                        'slug'=>'rcl-log',
                        'title'=>__('Write background events and errors to the log-file','wp-recall'),
                        'values'=> array(
                            __('Disabled','wp-recall'),
                            __('Enabled','wp-recall')
                        )
                    )
                )
            )
        ));

        $content .= $fields->options_box(
            __('Design','wp-recall'),
            array(
                array(
                    'type' => 'color',
                    'slug'=>'primary-color',
                    'title'=>__('Primary color','wp-recall'),
                    'default'=>'#4C8CBD'
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
                    'extend' => true,
                    'type' => 'runner',
                    'slug'=>'slide-pause',
                    'title'=>__('Pause Slider','wp-recall'),
                    'help'=>__('Only applied for slider via shortcode publications <a href="https://codeseller.ru/api-rcl/slider-rcl/" target="_blank">[slider-rcl]</a>','wp-recall'),
                    'notice'=>__('The value of the pause between slide transitions in seconds. Default value is 0 - the slide show is not made','wp-recall')
                )
            )
        );

        $content .= $fields->extend(array(
            $fields->options_box(
                __('Caching','wp-recall'),
                array(
                    array(
                        'type' => 'select',
                        'slug'=>'use_cache',
                        'title'=>__('Cache','wp-recall'),
                        'help'=>__('Use the functionality of the caching WP-Recall plugin. <a href="https://codeseller.ru/post-group/funkcional-keshirovaniya-plagina-wp-recall/" target="_blank">read More</a>','wp-recall'),
                        'values'=>array(
                            __('Disabled','wp-recall'),
                            __('Enabled','wp-recall')),
                        'childrens' => array(
                            1 => array(
                                array(
                                    'type' => 'number',
                                    'slug'=>'cache_time',
                                    'default'=>3600,
                                    'latitlebel'=>__('Time cache (seconds)','wp-recall'),
                                    'notice'=>__('Default','wp-recall').': 3600'
                                ),
                                array(
                                    'type' => 'select',
                                    'slug'=>'cache_output',
                                    'title'=>__('Cache output','wp-recall'),
                                    'values'=>array(
                                        __('All users','wp-recall'),
                                        __('Only guests','wp-recall'))
                                )
                            )
                        )
                    ),
                    array(
                        'type'=>'select',
                        'slug'=>'minify_css',
                        'title'=>__('Minimization of file styles','wp-recall'),
                        'values'=>array(
                            __('Disabled','wp-recall'),
                            __('Enabled','wp-recall')),
                        'notice'=>__('Minimization of file styles only works in correlation with Wp-Recall style files and add-ons that support this feature','wp-recall')
                    ),
                    array(
                        'type'=>'select',
                        'slug'=>'minify_js',
                        'title'=>__('Minimization of scripts','wp-recall'),
                        'values'=>array(
                            __('Disabled','wp-recall'),
                            __('Enabled','wp-recall'))
                    )
                )
            )
        ));

        $content .= $fields->options_box(
            __('Login and register','wp-recall'),
            array(
                array(
                    'type' => 'select',
                    'slug'=>'login_form_recall',
                    'title'=>__('Output procedure','wp-recall'),
                    'values'=>array(
                        __('Floating form','wp-recall'),
                        __('On a separate page','wp-recall'),
                        __('Wordpress Forms','wp-recall'),
                        __('Widget form','wp-recall')),
                    'childrens' => array(
                        1 => array(
                            array(
                                'type' => 'custom',
                                'title' => __('ID of the shortcode page [loginform]','wp-recall'),
                                'content' => wp_dropdown_pages( array(
                                    'selected'   => isset($rcl_options['page_login_form_recall'])? $rcl_options['page_login_form_recall']: '',
                                    'name'       => 'global[page_login_form_recall]',
                                    'show_option_none' => __('Not selected','wp-recall'),
                                    'echo'             => 0 )
                                )
                            )
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
                        2 => array(
                            array(
                                'extend' => true,
                                'type' => 'text',
                                'slug'=>'custom_authorize_page',
                                'title'=>__('URL','wp-recall'),
                                'notice'=>__('Enter your URL below, if you select an arbitrary URL after login','wp-recall')
                            )
                        )
                    )
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
            )
        );

        $content .= $fields->options_box(
            __('Recallbar','wp-recall'),
            array(
                array(
                    'type' => 'select',
                    'slug'=>'view_recallbar',
                    'title'=>__('Output of recallbar panel','wp-recall'),
                    'help'=>__('Recallbar – is he top panel WP-Recall plugin through which the plugin and its add-ons can output their data and the administrator can make his menu, forming it on <a href="/wp-admin/nav-menus.php" target="_blank">page management menu of the website</a>','wp-recall'),
                    'values'=>array(__('Disabled','wp-recall'),__('Enabled','wp-recall')),
                    'childrens' => array(
                        1 => array(
                            array(
                                'type' => 'select',
                                'slug'=>'rcb_color',
                                'title'=>__('Color','wp-recall'),
                                'values'=>array(__('Default','wp-recall'),__('Primary colors of WP-Recall','wp-recall'))
                            )
                        )
                    )
                )
            )
        );

$content .= '</div>';

$content = apply_filters('admin_options_wprecall',$content);

$content .= '<div class="submit-block">
<input type="submit" class="rcl-save-button" name="rcl_global_options" value="'.__('Save settings','wp-recall').'" />
</div></form></div>';

echo $content;

