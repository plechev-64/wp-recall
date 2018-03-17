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
                )
            )
        )
    );

    return $content;
}

