<?php

rcl_sortable_scripts();

include_once RCL_PATH.'functions/class-rcl-editfields.php';

$f_edit = new Rcl_EditFields('custom_tabs',
        array(
            'meta-key'=>false,
            'select-type'=>false,
            'placeholder'=>false,
            'sortable'=>false
            )
        );

if($f_edit->verify()) $fields = $f_edit->update_fields(false);

$content = '<h2>'.__('Custom tabs of the personal account','wp-recall').'</h2>';

$content .= $f_edit->edit_form(array(
    $f_edit->option('text',array(
        'name'=>'slug',
        'label'=>__('Tab ID','wp-recall'),
        'placeholder'=>__('Latin alphabet and numbers','wp-recall')
    )),
    $f_edit->option('text',array(
        'name'=>'icon',
        'label'=>__('Icon class of  font-awesome','wp-recall'),
        'placeholder'=>__('Example , fa-user','wp-recall'),
        'notice'=>__('Источник <a href="http://fontawesome.io/icons/" target="_blank">http://fontawesome.io/</a>','wp-recall')
    )),
    $f_edit->option('select',array(
        'name'=>'public',
        'notice'=>__('Public tab','wp-recall'),
        'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
    )),
    $f_edit->option('select',array(
        'name'=>'ajax',
        'notice'=>__('ajax-loading support','wp-recall'),
        'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
    )),
    $f_edit->option('select',array(
        'name'=>'cache',
        'notice'=>__('caching support','wp-recall'),
        'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
    )),
    $f_edit->option('textarea',array(
        'name'=>'content',
        'label'=>__('Content tab','wp-recall'),
        'notice'=>__('supported shortcodes and HTML-code','wp-recall')
    ))
));

echo $content;