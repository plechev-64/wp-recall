<?php

global $wpdb;

rcl_sortable_scripts();

$f_edit = new Rcl_Custom_Fields_Manager(
        'products-variations',
        array(
            //'sortable'=>false,
            //'meta-key'=>false,
            'custom-slug'=>1,
            'types' => array(
                'select',
                'checkbox',
                'radio'
            )
        ));

$content = '<h2>'.__('Products variations management','wp-recall').'</h2>';

$content .= $f_edit->manager_form(array(
                array(
                    'type' => 'textarea',
                    'slug'=>'notice',
                    'title'=>__('field description','wp-recall')
                )
            ));

echo $content;

