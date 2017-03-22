<?php

global $wpdb;

rcl_sortable_scripts();

if ( ! class_exists( 'Rcl_EditFields' ) ) 
    include_once RCL_PATH.'functions/class-rcl-editfields.php';

$f_edit = new Rcl_EditFields('orderform');

$content = '<h2>'.__('Order Form Field Management','wp-recall').'</h2>

'.$f_edit->edit_form(array(
    $f_edit->option('select',array(
        'name'=>'required',
        'notice'=>__('required field','wp-recall'),
        'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
    ))
));

echo $content;

