<?php
add_filter('admin_options_wprecall','rcl_get_publics_options_page');
function rcl_get_publics_options_page($content){
    global $rcl_options,$_wp_additional_image_sizes;

    $opt = new Rcl_Options(__FILE__);

    $args = array(
        'selected'   => $rcl_options['public_form_page_rcl'],
        'name'       => 'public_form_page_rcl',
        'show_option_none' => '<span style="color:red">'.__('Not selected','rcl').'</span>',
        'echo'             => 0
    );

    $_wp_additional_image_sizes['thumbnail'] = 1;
    $_wp_additional_image_sizes['medium'] = 1;
    $_wp_additional_image_sizes['large'] = 1;
    foreach($_wp_additional_image_sizes as $name=>$size){
        $sh_name = $name;
        if($size!=1) $sh_name .= ' ('.$size['width'].'*'.$size['height'].')';
        $d_sizes[$name] = $sh_name;
    }

    $roles = array(
        10=>__('only Administrators','rcl'),
        7=>__('Editors and older','rcl'),
        2=>__('Authors and older','rcl'),
        0=>__('Guests and users','rcl'));

    $content .= $opt->options(
        __('The publish settings','rcl'),array(
        $opt->option_block(
            array(
                $opt->title(__('General settings','rcl')),

                $opt->label(__('Page publishing and editing records','rcl')),
                wp_dropdown_pages( $args ),
                $opt->notice(__('Required for proper formation of links to edit records, you must specify the page where the shortcode is [public-form] no matter where displayed the very form of publication this page will be used to edit the entry','rcl')),

                $opt->label(__('Display information about the author','rcl')),
                $opt->option('select',array(
                    'name'=>'info_author_recall',
                    'options'=>array(__('Disabled','rcl'),__('Included','rcl'))
                )),

                $opt->label(__('Tab list of publications','rcl')),
                $opt->option('select',array(
                    'name'=>'publics_block_rcl',
                    'parent'=>true,
                    'options'=>array(__('Disabled','rcl'),__('Included','rcl'))
                )),
                $opt->child(
                      array('name'=>'publics_block_rcl','value'=>1),
                      array(
                            $opt->label(__('List of publications of the user','rcl')),
                            $opt->option('select',array(
                                'name'=>'view_publics_block_rcl',
                                'options'=>array(__('Only the owner of the account','rcl'),__('Show everyone including guests','rcl'))
                            ))
                      )
                )
            )
        ),

        $opt->option_block(
            array(
                $opt->title(__('Category','rcl')),

                $opt->label(__('Authorized headings','rcl')),
                $opt->option('text',array('name'=>'id_parent_category')),
                $opt->notice(__('ID columns in which permitted the publication should be separated by commas. '
                        . 'This setting is common to all forms of publication, but it is possible '
                        . 'to specify the desired category in shortcode forms, for example: [public-form cats="72,149"] '
                        . 'or for each form separately on the page generate custom fields','rcl')),
                $opt->notice(__('It is better to specify the parent category, then their child will be withdrawn automatically.','rcl')),

                $opt->label(__('Number of columns to select','rcl')),
                $opt->option('select',array(
                    'name'=>'count_category_post',
                    'default'=>1,
                    'options'=>array(1=>1,2=>2,3=>3,4=>4,5=>5)
                ))
            )
        ),
        /*$opt->option_block(
            array(
                $opt->title(__('Media','rcl')),

                $opt->label(__('Load the media files to the publications','rcl')),
                $opt->option('select',array(
                    'name'=>'media_downloader_recall',
                    'parent'=>true,
                    'options'=>array(__('Download Wp-Recall','rcl'),__('The Wordpress Media Library','rcl'))
                )),
                $opt->child(
                    array('name'=>'media_downloader_recall','value'=>0),
                    array(
                        $opt->notice(__('<b>note:</b> Using the ability to upload media to Wp-Recall you disable the ability to use the media library site, the downloaded files will form the gallery of images above the content you publish.','rcl')),
                        $opt->label(__('Number of images in the gallery Wp-Recall','rcl')),
                        $opt->option('select',array(
                            'name'=>'count_image_gallery',
                            'options'=>$count_img
                        )),

                        $opt->label(__('The maximum image size, Mb','rcl')),
                        $opt->option('number',array('name'=>'public_gallery_weight')),
                        $opt->notice(__('To limit image uploads to publish this value in megabytes. By default, 2MB','rcl')),

                        $opt->label(__('The size in the editor by default','rcl')),
                        $opt->option('select',array(
                            'name'=>'default_size_thumb',
                            'options'=>$d_sizes
                        )),
                        $opt->notice(__('Select the picture size in the silence of their use in the visual editor during the publishing process','rcl'))
                    )
                )
            )
        ),*/
        $opt->option_block(
            array(
                $opt->title(__('Form of publication','rcl')),

                $opt->label(__('Text editor','rcl')),
                $opt->option('select',array(
                    'name'=>'type_text_editor',
					'parent'=>true,
                    'options'=>array(
                        __('WP-RECALL editor','rcl'),
                        __('WordPress editor','rcl')
                    )
                )),
				$opt->child(
                    array('name'=>'type_text_editor','value'=>0),
                    array(
                        $opt->label(__('Images sizes','rcl')),
                        $opt->option('text',array(
                                'name'=>'max_sizes_attachment',
                                'default'=>'800,600'
                        )),
                        $opt->notice(__('Default: 800,600','rcl')),

			$opt->label(__('Available buttons Editor','rcl')),
                        $opt->option('checkbox',array(
                            'name'=>'rcl_editor_buttons',
                            'options'=>array(
                                'header'=>__('Subheader','rcl'),
                                'text'=>__('Text','rcl'),
                                'image'=>__('Upload images','rcl'),
                                'html'=>__('HTML','rcl')
                               )
                        ))
                    )
                ),
				$opt->child(
                    array('name'=>'type_text_editor','value'=>1),
                    array(
                        $opt->label(__('View editor WP','rcl')),
                        $opt->option('checkbox',array(
							'name'=>'wp_editor',
							'options'=>array(1=>__('Visual Editor','rcl'),2=>__('HTML-Editor','rcl'))
						)),
                        $opt->label(__('Number of images in the gallery Wp-Recall','rcl')),
                        $opt->option('number',array('name'=>'count_image_gallery','default'=>10)),

                        $opt->label(__('The maximum image size, Mb','rcl')),
                        $opt->option('number',array('name'=>'public_gallery_weight','default'=>2)),
                        $opt->notice(__('To limit image uploads to publish this value in megabytes. By default, 2MB','rcl')),

                        $opt->label(__('The size in the editor by default','rcl')),
                        $opt->option('select',array(
                            'name'=>'default_size_thumb',
                            'options'=>$d_sizes
                        )),
                        $opt->notice(__('Select the picture size in the silence of their use in the visual editor during the publishing process','rcl'))
                    )
                ),

                $opt->label(__('The output form of publication','rcl')),
                $opt->option('select',array(
                    'name'=>'output_public_form_rcl',
                    'default'=>1,
                    'parent'=>1,
                    'options'=>array(__('Do not display','rcl'),__('Output','rcl'))
                )),
                $opt->child(
                    array('name'=>'output_public_form_rcl','value'=>1),
                    array(
                        $opt->label(__('The form ID','rcl')),
                        $opt->option('number',array('name'=>'form-lk')),
                        $opt->notice(__('Enter the form ID to the conclusion in the personal Cabinet. The default is 1','rcl'))
                    )
                )
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Publication of records','rcl')),

                $opt->label(__('Republishing is allowed','rcl')),
                $opt->option('select',array(
                    'name'=>'user_public_access_recall',
					'parent'=>1,
                    'options'=>$roles
                )),
                $opt->child(
                    array('name'=>'user_public_access_recall','value'=>0),
                    array(
                        $opt->label(__('Redirect page','rcl')),
                        wp_dropdown_pages( array(
							'selected'   => $rcl_options['guest_post_redirect'],
							'name'       => 'guest_post_redirect',
							'show_option_none' => __('Not selected','rcl'),
							'echo'             => 0 )
						),
                        $opt->notice(__('Select the page to which visitors will be redirected after a successful publication , if the site is included in the registration confirmation email','rcl'))
                    )
                ),

                $opt->label(__('Moderation of publications','rcl')),
                $opt->option('select',array(
                    'name'=>'moderation_public_post',
                    'options'=>array(__('To publish immediately','rcl'),__('Send for moderation','rcl'))
                )),
                $opt->notice(__('If used in moderation: To allow the user to see their publication before it is moderated, it is necessary to have on the website right below the Author','rcl'))
            )
        ),

		$opt->option_block(
            array(
                $opt->title(__('Editing','rcl')),

                $opt->label(__('Frontend editing','rcl')),
                $opt->option('checkbox',array(
                    'name'=>'front_editing',
                    'options'=>array(
						10=>__('Administrators','rcl'),
						7=>__('Editors','rcl'),
						2=>__('Authors','rcl')
					)
                )),
				$opt->label(__('Time limit editing','rcl')),
                $opt->option('number',array('name'=>'time_editing')),
				$opt->notice(__('Limit editing time of publication in hours, by default: unlimited','rcl'))
            )
        ),

		$opt->option_block(
            array(
                $opt->title(__('Tags','rcl')),

                $opt->label(__('Displaying a list of tags','rcl')),
                $opt->option('select',array(
                    'name'=>'display_tags',
                    'parent'=>1,
                    'options'=>array(__('Do not display','rcl'),__('Output','rcl'))
                )),
                $opt->child(
                    array('name'=>'display_tags','value'=>1),
                    array(
                        $opt->label(__('Limit the output','rcl')),
                        $opt->option('text',array('name'=>'limit_tags')),
                        $opt->notice(__('IDs of tags separated by commas, default displays all','rcl'))
                    )
                )
            )
        ),
        $opt->option_block(
            array(
                $opt->title(__('Custom fields','rcl')),
                $opt->notice(__('Settings only for fields created using the form of the publication wp-recall','rcl')),

                $opt->label(__('Automatic withdrawal','rcl')),

                $opt->option('select',array(
                    'name'=>'pm_rcl',
                    'parent'=>true,
                    'options'=>array(__('No','rcl'),__('Yes','rcl'))
                )),
                $opt->child(
                      array('name'=>'pm_rcl','value'=>1),
                      array(
                            $opt->label(__('Place output fields','rcl')),
                            $opt->option('select',array(
                                'name'=>'pm_place',
                                'options'=>array(__('After the content recording','rcl'),__('On content recording','rcl'))
                            ))
                      )
                )
            )
        ))
    );

    return $content;
}