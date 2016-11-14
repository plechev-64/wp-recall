<?php
add_filter('admin_options_wprecall','rcl_admin_groups_page_content');
function rcl_admin_groups_page_content($content){

        $opt = new Rcl_Options(__FILE__);

        $content .= $opt->options(
            __('Group settings','wp-recall'),
            $opt->option_block(
                array(
                    $opt->title(__('Groups','wp-recall')),
                    $opt->label(__('Group creation allowed','wp-recall')),
                    $opt->option('select',array(
                        'name'=>'public_group_access_recall',
                        'options'=>array(
                            10=>__('only Administrators','wp-recall'),
                            7=>__('Editors and higher','wp-recall'),
                            2=>__('Authors and higher','wp-recall'),
                            1=>__('Participants and higher','wp-recall'))
                    )),
                    
                    $opt->label(__('Group publication moderation','wp-recall')),
                    $opt->option('select',array(
                        'name'=>'moderation_public_group',
                        'options'=>array(
                            __('Publish now','wp-recall'),
                            __('Send for moderation','wp-recall'))
                    )),
                    $opt->notice(__('If subject to moderation: To allow the user to see their publication before moderation has been completed, the user should be classifies as Author or higher','wp-recall')),
                    
                    $opt->label(__('Group contents widget','wp-recall')),
                    $opt->option('select',array(
                        'name'=>'groups_posts_widget',
                        'options'=>array(
                            __('Disabled','wp-recall'),
                            __('Enabled','wp-recall'))
                    )),
                    $opt->notice(__('enable if publication loop within the group has been removed from the template','wp-recall'))
                )
            )
        );
	return $content;
}

