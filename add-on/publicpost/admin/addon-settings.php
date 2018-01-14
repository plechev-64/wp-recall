<?php
add_filter('admin_options_wprecall','rcl_get_publics_options_page');
function rcl_get_publics_options_page($content){
    global $_wp_additional_image_sizes;

    $opt = new Rcl_Options(__FILE__);

    $_wp_additional_image_sizes['thumbnail'] = 1;
    $_wp_additional_image_sizes['medium'] = 1;
    $_wp_additional_image_sizes['large'] = 1;
    foreach($_wp_additional_image_sizes as $name=>$size){
        $sh_name = $name;
        if($size!=1) $sh_name .= ' ('.$size['width'].'*'.$size['height'].')';
        $d_sizes[$name] = $sh_name;
    }
    
    $post_types = get_post_types(array(
            'public'   => true,
            '_builtin' => false
        ), 'objects');

    $types = array('post' => __('Records','wp-recall'));

    foreach ($post_types  as $post_type ) {
        $types[$post_type->name] = $post_type->label;
    }

    $content .= $opt->options(
        __('Publication settings','wp-recall'),array(
        $opt->options_box(
            __('General settings','wp-recall'),
            array(
                array(
                    'type' => 'custom',
                    'slug' => 'edit-page',
                    'title' => __('Publishing and editing','wp-recall'),
                    'content' => wp_dropdown_pages(array(
                        'selected'   => rcl_get_option('public_form_page_rcl'),
                        'name'       => 'global[public_form_page_rcl]',
                        'show_option_none' => '<span style="color:red">'.__('Not selected','wp-recall').'</span>',
                        'echo'             => 0
                    )),
                    'notice' => __('You are required to publish a links to managing publications, you must specify the page with the shortcode [public-form]','wp-recall')
                ),
                array(
                    'type' => 'select',
                    'slug' => 'info_author_recall',
                    'title' => __('Display information about the author','wp-recall'),
                    'values'=>array(
                        __('Disabled','wp-recall'),
                        __('Enabled','wp-recall')
                    ),
                    'childrens' => array(
                        1 => array(
                            array(
                                'type' => 'checkbox',
                                'slug' => 'post_types_authbox',
                                'title' => __('Типы записей для вывода блока автора','wp-recall'),
                                'values' => $types,
                                'notice' => __('Укажите типы записей, где должен выводится блок автора. Если ничего не указано, то выводится везде.','wp-recall')
                            )
                        )
                    )
                ),
                array(
                    'type' => 'select',
                    'slug' => 'publics_block_rcl',
                    'title' => __('List of publications tab','wp-recall'),
                    'values'=>array(__('Disabled','wp-recall'),__('Enabled','wp-recall')),
                    'childrens' => array(
                        1 => array(
                            array(
                                'type' => 'select',
                                'slug' => 'view_publics_block_rcl',
                                'title' => __('List of publications of the user','wp-recall'),
                                'values' => array(__('Only owner of the account','wp-recall'),__('Show everyone including guests','wp-recall'))
                            )
                        )
                    )
                )
            )
        ), 
        $opt->options_box(
            __('Form of publication','wp-recall'),
            array(
                array(
                    'type' => 'select',
                    'slug' => 'public_preview',
                    'title' => __('Use preview','wp-recall'),
                    'values'=> array(__('No','wp-recall'),__('Yes','wp-recall'))
                ),
                array(
                    'type' => 'select',
                    'slug' => 'public_draft',
                    'title' => __('Use draft','wp-recall'),
                    'values'=> array(__('No','wp-recall'),__('Yes','wp-recall'))
                ),
                array(
                    'type' => 'select',
                    'slug' => 'default_size_thumb',
                    'title' => __('The image size in editor by default','wp-recall'),
                    'values'=> $d_sizes,
                    'notice' => __('Select image size for the visual editor during publishing','wp-recall')
                ),
                array(
                    'type' => 'select',
                    'slug' => 'output_public_form_rcl',
                    'title' => __('Form of publication output in the personal cabinet','wp-recall'),
                    'values'=> array(__('Do not display','wp-recall'),__('Output','wp-recall')),
                    'default' => 1,
                    'childrens' => array(
                        1 => array(
                            array(
                                'type' => 'number',
                                'slug' => 'form-lk',
                                'title' => __('The form ID','wp-recall'),
                                'notice'=> __('Enter the form ID according to the personal Cabinet. The default is 1','wp-recall')
                            )
                        )
                    )
                )
            )
        ),
        $opt->options_box(
            __('Publication of records','wp-recall'),
            array(
                array(
                    'type' => 'select',
                    'slug' => 'user_public_access_recall',
                    'title' => __('Publication is allowed','wp-recall'),
                    'values'=> array(
                        10  => __('only Administrators','wp-recall'),
                        7   => __('Editors and higher','wp-recall'),
                        2   => __('Authors and higher','wp-recall'),
                        0   => __('Guests and users','wp-recall')
                    ),
                    'childrens' => array(
                        array(
                            array(
                                'type' => 'custom',
                                'slug' => 'redirect-to',
                                'title' => __('Redirect to','wp-recall'),
                                'content'=> wp_dropdown_pages( array(
                                    'selected'   => rcl_get_option('guest_post_redirect'),
                                    'name'       => 'global[guest_post_redirect]',
                                    'show_option_none' => __('Not selected','wp-recall'),
                                    'echo'             => 0 )
                                ),
                                'notice' => __('Select the page to which the visitors will be redirected after a successful publication, if email authorization is included in the registration precess','wp-recall')
                            )
                        )
                    )
                ),
                array(
                    'type' => 'select',
                    'slug' => 'moderation_public_post',
                    'title' => __('Moderation of publications','wp-recall'),
                    'values' => array(__('Publish now','wp-recall'),__('Send for moderation','wp-recall')),
                    'notice' => __('If subject to moderation: To allow the user to see their publication before moderation has been completed, the user should be classifies as Author or higher','wp-recall'),
                    'childrens' => array(
                        1 => array(
                            array(
                                'type' => 'checkbox',
                                'slug' => 'post_types_moderation',
                                'title' => __('Тип записи','wp-recall'),
                                'values' => $types,
                                'notice' => __('Укажите типы записей, которые будут отправляться на модерацию. Если ничего не указано, то модерация действует для всех типов.','wp-recall')
                            )
                        )
                    )
                )
            )
        ),
        $opt->extend(array(    
            $opt->options_box(
                __('Editing','wp-recall'),
                array(
                    array(
                        'type' => 'checkbox',
                        'slug' => 'front_editing',
                        'title' => __('Frontend editing','wp-recall'),
                        'values' => array(
                            10  => __('Administrators','wp-recall'),
                            7   => __('Editors','wp-recall'),
                            2   => __('Authors','wp-recall')
                        )
                    ),
                    array(
                        'type' => 'number',
                        'slug' => 'time_editing',
                        'title' => __('The time limit edit','wp-recall'),
                        'notice' => __('Limit editing time of publication in hours, by default: unlimited','wp-recall')
                    )
                )
            )
        )),
        $opt->options_box(
            __('Custom fields','wp-recall'),
            array(
                array(
                    'type' => 'select',
                    'slug' => 'pm_rcl',
                    'title' => __('Automatic output','wp-recall'),
                    'values' => array(__('No','wp-recall'),__('Yes','wp-recall')),
                    'notice' => __('Settings only for fields created using the form of the publication wp-recall','wp-recall'),
                    'childrens' => array(
                        1 => array(
                            array(
                                'type' => 'select',
                                'slug' => 'pm_place',
                                'title' => __('Output fields location','wp-recall'),
                                'values' => array(__('Above publication content','wp-recall'),__('On content recording','wp-recall'))
                            ),
                            array(
                                'type' => 'checkbox',
                                'slug' => 'pm_post_types',
                                'title' => __('Типы записей для вывода произвольных полей','wp-recall'),
                                'values' => $types,
                                'notice' => __('Укажите типы записей, где будут выводится значения произвольных полей. Если ничего не указано, то выводятся везде.','wp-recall')
                            )
                        )
                    )
                ),
                
            )
        ))
    );

    return $content;
}