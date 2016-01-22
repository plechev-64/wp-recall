<?php
add_filter('admin_options_wprecall','rcl_admin_groups_page_content');
function rcl_admin_groups_page_content($content){

        $opt = new Rcl_Options(__FILE__);

        $content .= $opt->options(
            __('Group settings','wp-recall'),
            $opt->option_block(
                array(
                    $opt->title(__('Groups','wp-recall')),
                    $opt->label(__('Creating groups is allowed','wp-recall')),
                    $opt->option('select',array(
                        'name'=>'public_group_access_recall',
                        'options'=>array(
                            10=>__('only Administrators','wp-recall'),
                            7=>__('Editors and older','wp-recall'),
                            2=>__('Authors and older','wp-recall'),
                            1=>__('Participants and older','wp-recall'))
                    )),
                    
                    $opt->label(__('Moderation of publications in the group','wp-recall')),
                    $opt->option('select',array(
                        'name'=>'moderation_public_group',
                        'options'=>array(
                            __('To publish immediately','wp-recall'),
                            __('Send for moderation','wp-recall'))
                    )),
                    $opt->notice(__('If used in moderation: To allow the user to see their publication before it is moderated, it is necessary to have on the website right below the Author','wp-recall')),
                    
                    $opt->label(__('Widget Content Groups','wp-recall')),
                    $opt->option('select',array(
                        'name'=>'groups_posts_widget',
                        'options'=>array(
                            __('Disabled','wp-recall'),
                            __('Included','wp-recall'))
                    )),
                    $opt->notice(__('Include if the loop publications in the group was removed from the template','wp-recall'))
                )
            )
        );
	return $content;
}

