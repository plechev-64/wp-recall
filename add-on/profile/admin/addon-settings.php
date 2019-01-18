<?php

add_filter('rcl_options','rcl_profile_options');
function rcl_profile_options($options){

    $options->add_box('profile', array(
        'title' => __('Настройки профиля','wp-recall'),
        'icon' => 'fa-user'
    ))->add_group('general')->add_options(array(
        array(
            'type' => 'select',
            'slug' => 'delete_user_account',
            'title' => __('Allow users to delete their account?','wp-recall'),
            'values' => array(__('No','wp-recall'),__('Yes','wp-recall'))
        )
    ));

    return $options;
}

