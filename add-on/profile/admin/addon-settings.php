<?php

add_filter('admin_options_wprecall','rcl_profile_options');
function rcl_profile_options($content){

    $opt = new Rcl_Options(__FILE__);

    $content .= $opt->options(
        __('Profile and account settings','wp-recall'),
        $opt->options_box(
            __('Profile and account','wp-recall'),
            array(
                array(
                    'type' => 'select',
                    'slug' => 'delete_user_account',
                    'title' => __('Allow users to delete their account?','wp-recall'),
                    'values' => array(__('No','wp-recall'),__('Yes','wp-recall'))
                ),
                array(
                    'type' => 'runner',
                    'value_min' => 1,
                    'value_max' => 5,
                    'value_step' => 1,
                    'default' => 2,
                    'slug' => 'avatar_weight',
                    'title' => __('The maximum size of the avatar, Mb','wp-recall'),
                    'notice' => __('To limit the size uploading of avatars images, the size in megabytes by default is set at 2MB','wp-recall')
                )
            )
        )
    );

    return $content;
}

