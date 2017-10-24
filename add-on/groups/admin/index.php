<?php

add_filter('admin_options_wprecall','rcl_admin_groups_page_content');
function rcl_admin_groups_page_content($content){
    
    $opt = new Rcl_Options(__FILE__);

    $content .= $opt->options(
        __('Group settings','wp-recall'),
        $opt->options_box(
            __('Groups','wp-recall'),    
            array(
                array(
                    'type' => 'select',
                    'title'=>__('Group output','wp-recall'),
                    'slug'=>'group-output',
                    'values'=>array(
                        __('On the archive page of post-groups entries','wp-recall'),
                        __('On an arbitrary page of the website','wp-recall')
                    ),
                    'childrens' => array(
                        0 => array(
                            array(
                                'type' => 'select',
                                'title'=>__('Group contents widget','wp-recall'),
                                'slug'=>'groups_posts_widget',
                                'values'=>array(
                                    __('Disabled','wp-recall'),
                                    __('Enabled','wp-recall')
                                ),
                                'notice' => __('enable if publication loop within the group has been removed from the template','wp-recall')
                            )
                        ),
                        1 => array(
                            array(
                                'type' => 'custom',
                                'title'=>__('Shortcode host page','wp-recall'),
                                'slug'=>'groups-host-page',
                                'content'=> wp_dropdown_pages(array(
                                    'selected'   => rcl_get_option('group-page'),
                                    'name'       => 'global[group-page]',
                                    'show_option_none' => '<span style="color:red">'.__('Not selected','wp-recall').'</span>',
                                    'echo'       => 0
                                )),
                                'notice' => __('please specify the page where the [grouplist] shortcode is placed','wp-recall')
                            )
                        )
                    )
                ),
                array(
                    'type' => 'select',
                    'title'=> __('Group creation allowed','wp-recall'),
                    'slug'=>'public_group_access_recall',
                    'values'=>array(
                        10  => __('only Administrators','wp-recall'),
                        7   =>__('Editors and higher','wp-recall'),
                        2   =>  __('Authors and higher','wp-recall'),
                        1   => __('Participants and higher','wp-recall')
                    )
                ),
                array(
                    'type' => 'select',
                    'title'=> __('Group publication moderation','wp-recall'),
                    'slug'=>'moderation_public_group',
                    'values'=>array(
                        __('Publish now','wp-recall'),
                        __('Send for moderation','wp-recall')
                    ),
                    'notice' => __('If subject to moderation: To allow the user to see their publication before moderation has been completed, the user should be classifies as Author or higher','wp-recall')
                )
            )
        )
    );
    
    return $content;
    
}

function rcl_groups_admin_create($term_id){
   global $user_ID,$wpdb;
   
   $term = get_term( $term_id, 'groups' );
   
   if($term->parent) return false;
   
   $result = $wpdb->insert(
        RCL_PREF.'groups',
        array(
            'ID'=>$term_id,
            'admin_id'=>$user_ID,
            'group_status'=>'open',
            'group_date'=>current_time('mysql')
        )
    );

    if(!$result) return false;

    rcl_update_group_option($term_id,'can_register',1);
    rcl_update_group_option($term_id,'default_role','author');

    do_action('rcl_create_group',$term_id);
}

