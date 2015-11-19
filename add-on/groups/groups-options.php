<?php
add_filter('admin_options_wprecall','rcl_admin_groups_page_content');
function rcl_admin_groups_page_content($content){

        $opt = new Rcl_Options(__FILE__);

        $content .= $opt->options(
            __('Group settings','rcl'),
            $opt->option_block(
                array(
                    $opt->title(__('Groups','rcl')),
                    $opt->label(__('Creating groups is allowed','rcl')),
                    $opt->option('select',array(
                        'name'=>'public_group_access_recall',
                        'options'=>array(
                            10=>__('only Administrators','rcl'),
                            7=>__('Editors and older','rcl'),
                            2=>__('Authors and older','rcl'),
                            1=>__('Participants and older','rcl'))
                    )),
                    
                    $opt->label(__('Moderation of publications in the group','rcl')),
                    $opt->option('select',array(
                        'name'=>'moderation_public_group',
                        'options'=>array(
                            __('To publish immediately','rcl'),
                            __('Send for moderation','rcl'))
                    )),
                    $opt->notice(__('If used in moderation: To allow the user to see their publication before it is moderated, it is necessary to have on the website right below the Author','rcl')),
                )
            )
        );
	return $content;
}

