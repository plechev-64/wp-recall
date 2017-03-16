<?php

add_filter('admin_options_wprecall','rcl_profile_options');
function rcl_profile_options($content){

    $opt = new Rcl_Options(__FILE__);

    $content .= $opt->options(
        __('Profile and account settings','wp-recall'),
        $opt->option_block(
            array(
                $opt->title(__('Profile and account','wp-recall')),

                $opt->label(__('Allow users to delete their account?','wp-recall')),
                $opt->option('select',array(
                    'name'=>'delete_user_account',
                    'options'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                )),

                $opt->label(__('The maximum size of the avatar, Mb','wp-recall')),
                $opt->option('number',array('name'=>'avatar_weight')),
                $opt->notice(__('To limit the size uploading of avatars images, the size in megabytes by default is set at 2MB','wp-recall'))
            )
        )
    );

    return $content;
}

