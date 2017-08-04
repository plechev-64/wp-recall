<?php

global $rcl_options;
    
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

            $args = array(
                'selected'   => $rcl_options['lk_page_rcl'],
                'name'       => 'global[lk_page_rcl]',
                'show_option_none' => '<span style="color:red">'.__('Not selected','wp-recall').'</span>',
                'echo'       => 0
            );

            $roles = array(
                10=>__('only Administrators','wp-recall'),
                7=>__('Editors and higher','wp-recall'),
                2=>__('Authors and higher','wp-recall'),
                1=>__('Participants and higher','wp-recall'),
                0=>__('All users','wp-recall')
            );

            $content .= $fields->option_block(array(
                $fields->extend(array(
                    $fields->title(__('Personal cabinet','wp-recall')),

                    $fields->option('select',array(
                        'name'=>'view_user_lk_rcl',
                        'label'=>__('Personal Cabinet output','wp-recall'),
                        'parent'=>true,
                        'options'=>array(
                            __('On the author’s archive page','wp-recall'),
                            __('Using shortcode [wp-recall]','wp-recall')),
                        'help'=>__('Attention! Changing this parameter is not required. Detailed instructions on personal account output using author.php file can be received here <a href="https://codeseller.ru/ustanovka-plagina-wp-recall-na-sajt/" target="_blank">here</a>','wp-recall'),
                        'notice'=>__('If author archive page is selected, the template author.php should contain the code if(function_exists(\'wp_recall\')) wp_recall();','wp-recall')
                    )),
                    $fields->child(
                        array(
                            'name'=>'view_user_lk_rcl',
                            'value'=>1
                        ),
                        array(
                            $fields->label(__('Shortcode host page','wp-recall')),
                            wp_dropdown_pages( $args ),
                            $fields->option('text',array(
                                'name'=>'link_user_lk_rcl',
                                'label'=>__('Link format to personal account','wp-recall'),
                                'help'=>__('The link is formed according to principle "/slug_page/?get=ID". The parameter "get" can be set here. By default user','wp-recall')
                            ))
                        )
                    ),
                    $fields->option('number',array(
                        'name'=>'timeout',
                        'help'=>__('This value sets the maximum time a user is considered "online" in the absence of activity','wp-recall'),
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
                        'label'=>__('Access to the console is allowed','wp-recall'),
                        'options'=>$roles
                    ))
            ))));
            
            $content .= $fields->option_block(array(
                $fields->extend(array(
                    $fields->title(__('Logging mode','wp-recall')),
                    $fields->option('select',array(
                        'name'=>'rcl-log',
                        'label'=>__('Write background events and errors to the log-file','wp-recall'),
                        'options'=> array(
                            __('Disabled','wp-recall'),
                            __('Enabled','wp-recall')
                        )
                    ))
            ))));

            $content .= $fields->option_block(
                array(
                    $fields->title(__('Design','wp-recall')),                 
                    $fields->option('text',array(
                        'name'=>'primary-color',
                        'label'=>__('Primary color','wp-recall'),
                        'default'=>'#4C8CBD'
                    )),
                    $fields->option('select',array(
                        'name'=>'buttons_place',
                        'label'=>__('The location of the section buttons','wp-recall'),
                        'options'=>array(
                            __('Top','wp-recall'),
                            __('Left','wp-recall'))
                    )),
                    $fields->extend(array(
                        $fields->option('number',array(
                            'name'=>'slide-pause',
                            'label'=>__('Pause Slider','wp-recall'),
                            'help'=>__('Only applied for slider via shortcode publications <a href="https://codeseller.ru/api-rcl/slider-rcl/" target="_blank">[slider-rcl]</a>','wp-recall'),
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
                            'help'=>__('Use the functionality of the caching WP-Recall plugin. <a href="https://"codeseller.ru/post-group/funkcional-keshirovaniya-plagina-wp-recall/" target="_blank">read More</a>','wp-recall'),
                            'parent'=>true,
                            'options'=>array(
                                __('Disabled','wp-recall'),
                                __('Enabled','wp-recall'))
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
                            'label'=>__('Minimization of file styles','wp-recall'),
                            'options'=>array(
                                __('Disabled','wp-recall'),
                                __('Enabled','wp-recall')),
                                'notice'=>__('Minimization of file styles only works in correlation with Wp-Recall style files and add-ons that support this feature','wp-recall')
                        )),
                        $fields->option('select',array(
                            'name'=>'minify_js',
                            'label'=>__('Minimization of scripts','wp-recall'),
                            'options'=>array(
                                __('Disabled','wp-recall'),
                                __('Enabled','wp-recall'))
                        ))
                    ))
                )
            );

            $page_lg_form = isset($rcl_options['page_login_form_recall'])? $rcl_options['page_login_form_recall']: '';

            $content .= $fields->option_block(
                array(
                    $fields->title(__('Login and register','wp-recall')),
                    $fields->option('select',array(
                        'name'=>'login_form_recall',
                        'label'=>__('Output procedure','wp-recall'),
                        'parent'=>true,
                        'options'=>array(
                            __('Floating form','wp-recall'),
                            __('On a separate page','wp-recall'),
                            __('Wordpress Forms','wp-recall'),
                            __('Widget form','wp-recall'))
                    )),
                    $fields->child(
                        array(
                          'name' => 'login_form_recall',
                          'value' => 1
                        ),
                        array(
                            $fields->label(__('ID of the shortcode page [loginform]','wp-recall')),
                            wp_dropdown_pages( array(
                                'selected'   => $page_lg_form,
                                'name'       => 'global[page_login_form_recall]',
                                'show_option_none' => __('Not selected','wp-recall'),
                                'echo'             => 0 )
                            )
                        )
                    ),
                    $fields->extend(array(
                        $fields->option('select',array(
                            'name'=>'confirm_register_recall',
                            'help'=>__('If you are using the registration confirmation, after registration, the user will need to confirm your email by clicking on the link in the sent email','wp-recall'),
                            'label'=>__('Registration confirmation by the user','wp-recall'),
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
                            'label'=>__('repeat password field','wp-recall'),
                            'options'=>array(__('Disabled','wp-recall'),__('Displaye','wp-recall'))
                        )),
                        $fields->option('select',array(
                            'name'=>'difficulty_parole',
                            'label'=>__('Indicator of password complexity','wp-recall'),
                            'options'=>array(__('Disabled','wp-recall'),__('Displaye','wp-recall'))
                        ))
                    ))
                )
            );

            $content .= $fields->option_block(
                array(
                    $fields->title(__('Recallbar','wp-recall')),
                    $fields->option('select',array(
                        'name'=>'view_recallbar',
                        'label'=>__('Output of recallbar panel','wp-recall'),
                        'help'=>__('Recallbar – is he top panel WP-Recall plugin through which the plugin and its add-ons can output their data and the administrator can make his menu, forming it on <a href="/wp-admin/nav-menus.php" target="_blank">page management menu of the website</a>','wp-recall'),
                        'parent'=>true,
                        'options'=>array(__('Disabled','wp-recall'),__('Enabled','wp-recall'))
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
                                'options'=>array(__('Default','wp-recall'),__('Primary colors of WP-Recall','wp-recall'))
                            ))
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

