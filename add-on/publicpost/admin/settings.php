<?php
add_filter('admin_options_wprecall','rcl_get_publics_options_page');
function rcl_get_publics_options_page($content){
    global $rcl_options,$_wp_additional_image_sizes;

    $opt = new Rcl_Options(__FILE__);

    $args = array(
        'selected'   => $rcl_options['public_form_page_rcl'],
        'name'       => 'global[public_form_page_rcl]',
        'show_option_none' => '<span style="color:red">'.__('Not selected','wp-recall').'</span>',
        'echo'             => 0
    );

    $guest_redirect = (isset($rcl_options['guest_post_redirect']))? $rcl_options['guest_post_redirect']: false;

    $_wp_additional_image_sizes['thumbnail'] = 1;
    $_wp_additional_image_sizes['medium'] = 1;
    $_wp_additional_image_sizes['large'] = 1;
    foreach($_wp_additional_image_sizes as $name=>$size){
        $sh_name = $name;
        if($size!=1) $sh_name .= ' ('.$size['width'].'*'.$size['height'].')';
        $d_sizes[$name] = $sh_name;
    }

    $roles = array(
        10=>__('only Administrators','wp-recall'),
        7=>__('Editors and higher','wp-recall'),
        2=>__('Authors and higher','wp-recall'),
        0=>__('Guests and users','wp-recall'));

    $content .= $opt->options(
        __('Publication settings','wp-recall'),array(
        $opt->option_block(
            array(
                $opt->title(__('General settings','wp-recall')),

                $opt->label(__('Publishing and editing','wp-recall')),
                wp_dropdown_pages( $args ),
                $opt->notice(__('You are required to publish a links to managing publications, you must specify the page with the shortcode [public-form]','wp-recall')),

                $opt->label(__('Display information about the author','wp-recall')),
                $opt->option('select',array(
                    'name'=>'info_author_recall',
                    'options'=>array(__('Disabled','wp-recall'),__('Enabled','wp-recall'))
                )),

                $opt->label(__('Use preview','wp-recall')),
                $opt->option('select',array(
                    'name'=>'public_preview',
                    'options'=>array(__('Yes','wp-recall'),__('No','wp-recall'))
                )),

                $opt->label(__('List of publications tab','wp-recall')),
                $opt->option('select',array(
                    'name'=>'publics_block_rcl',
                    'parent'=>true,
                    'options'=>array(__('Disabled','wp-recall'),__('Enabled','wp-recall'))
                )),
                $opt->child(
                      array('name'=>'publics_block_rcl','value'=>1),
                      array(
                            $opt->label(__('List of publications of the user','wp-recall')),
                            $opt->option('select',array(
                                'name'=>'view_publics_block_rcl',
                                'options'=>array(__('Only owner of the account','wp-recall'),__('Show everyone including guests','wp-recall'))
                            ))
                      )
                )
            )
        ),

        $opt->option_block(
            array(
                $opt->title(__('Form of publication','wp-recall')),

                $opt->label(__('Number of images in Wp-Recall gallery','wp-recall')),
                $opt->option('number',array('name'=>'count_image_gallery','default'=>10)),

                $opt->label(__('The maximum image size, Mb','wp-recall')),
                $opt->option('number',array('name'=>'public_gallery_weight','default'=>2)),
                $opt->notice(__('Maximum image size in megabytes. By default, 2MB','wp-recall')),

                $opt->label(__('The image size in editor by default','wp-recall')),
                $opt->option('select',array(
                    'name'=>'default_size_thumb',
                    'options'=>$d_sizes
                )),
                $opt->notice(__('Select image size for the visual editor during publishing','wp-recall')),

                $opt->label(__('Form of publication output in the personal cabinet','wp-recall')),
                $opt->option('select',array(
                    'name'=>'output_public_form_rcl',
                    'default'=>1,
                    'parent'=>1,
                    'options'=>array(__('Do not display','wp-recall'),__('Output','wp-recall'))
                )),
                $opt->child(
                    array('name'=>'output_public_form_rcl','value'=>1),
                    array(

                        $opt->label(__('The form ID','wp-recall')),
                        $opt->option('number',array('name'=>'form-lk')),
                        $opt->notice(__('Enter the form ID according to the personal Cabinet. The default is 1','wp-recall'))

                    )
                )
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Publication of records','wp-recall')),

                $opt->label(__('Publication is allowed','wp-recall')),
                $opt->option('select',array(
                    'name'=>'user_public_access_recall',
                    'parent'=>1,
                    'options'=>$roles
                )),
                $opt->child(
                    array('name'=>'user_public_access_recall','value'=>0),
                    array(
                        $opt->label(__('Redirect to','wp-recall')),
                        wp_dropdown_pages( array(
                            'selected'   => $guest_redirect,
                            'name'       => 'global[guest_post_redirect]',
                            'show_option_none' => __('Not selected','wp-recall'),
                            'echo'             => 0 )
                        ),
                        $opt->notice(__('Select the page to which the visitors will be redirected after a successful publication, if email authorization is included in the registration precess','wp-recall'))
                    )
                ),

                $opt->label(__('Moderation of publications','wp-recall')),
                $opt->option('select',array(
                    'name'=>'moderation_public_post',
                    'options'=>array(__('Publish now','wp-recall'),__('Send for moderation','wp-recall'))
                )),
                $opt->notice(__('If subject to moderation: To allow the user to see their publication before moderation has been completed, the user should be classifies as Author or higher','wp-recall'))
            )
        ),

	$opt->option_block(
            array(
                $opt->extend(array(
                    $opt->title(__('Editing','wp-recall')),

                    $opt->label(__('Frontend editing','wp-recall')),
                    $opt->option('checkbox',array(
                        'name'=>'front_editing',
                        'options'=>array(
                                10=>__('Administrators','wp-recall'),
                                7=>__('Editors','wp-recall'),
                                2=>__('Authors','wp-recall')
                        )
                    )),
                    $opt->label(__('Ограничение времени редактирования','wp-recall')),
                    $opt->option('number',array('name'=>'time_editing')),
                    $opt->notice(__('Limit editing time of publication in hours, by default: unlimited','wp-recall'))
                ))
            )
        ),

        $opt->option_block(
            array(
                $opt->title(__('Custom fields','wp-recall')),
                $opt->notice(__('Settings only for fields created using the form of the publication wp-recall','wp-recall')),

                $opt->label(__('Automatic output','wp-recall')),

                $opt->option('select',array(
                    'name'=>'pm_rcl',
                    'parent'=>true,
                    'options'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                )),
                $opt->child(
                      array('name'=>'pm_rcl','value'=>1),
                      array(
                            $opt->label(__('Output fields location','wp-recall')),
                            $opt->option('select',array(
                                'name'=>'pm_place',
                                'options'=>array(__('Above publication content','wp-recall'),__('On content recording','wp-recall'))
                            ))
                      )
                )
            )
        ))
    );

    return $content;
}