<?php

global $wpdb;

rcl_sortable_scripts();

if ( ! class_exists( 'Rcl_EditFields' ) ) 
    include_once RCL_PATH.'functions/class-rcl-editfields.php';

$f_edit = new Rcl_EditFields('orderform');

$content = '<h2>'.__('Order Form Field Management','wp-recall').'</h2>';

$content .= $f_edit->edit_form(array(
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

