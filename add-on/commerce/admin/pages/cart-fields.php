<?php

$Manager = new Rcl_Fields_Manager('orderform', array(
    'option_name' => 'rcl_cart_fields',
    'empty_field' => true,
    'field_options' => array(
        array(
            'type' => 'textarea',
            'slug' => 'notice',
            'title' => __('field description','wp-recall')
        ),
        array(
            'type' => 'radio',
            'slug'=>'required',
            'title'=>__('required field','wp-recall'),
            'values'=>array(
                __('No','wp-recall'),
                __('Yes','wp-recall'
            )
        ))
    )
));

$content = '<h2>'.__('Order Form Field Management','wp-recall').'</h2>';

$content .= $Manager->get_manager();

echo $content;

