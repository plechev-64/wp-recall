<?php

global $wpdb;

rcl_sortable_scripts();

$f_edit = new Rcl_Custom_Fields_Manager('orderform');

$content = '<h2>'.__('Order Form Field Management','wp-recall').'</h2>';

$content .= $f_edit->manager_form(array(
                array(
                    'type' => 'textarea',
                    'slug' => 'notice',
                    'title' => __('field description','wp-recall')
                ),
                array(
                    'type' => 'select',
                    'slug'=>'required',
                    'title'=>__('required field','wp-recall'),
                    'values'=>array(
                        __('No','wp-recall'),
                        __('Yes','wp-recall'
                    )
                ))
            ));

echo $content;

