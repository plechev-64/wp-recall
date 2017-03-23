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

$content = '<h2>'.__('Custom tabs of the personal account','wp-recall').'</h2>';

$content .= $f_edit->edit_form(array(
    
    array(
        'type' => 'text',
        'slug'=>'slug',
        'title'=>__('Tab ID','wp-recall'),
        'placeholder'=>__('Latin alphabet and numbers','wp-recall')
    ),
    
    array(
        'type' => 'text',
        'slug'=>'icon',
        'title'=>__('Icon class of  font-awesome','wp-recall'),
        'placeholder'=>__('Example , fa-user','wp-recall'),
        'notice'=>__('Источник <a href="http://fontawesome.io/icons/" target="_blank">http://fontawesome.io/</a>','wp-recall')
    ),
    
    array(
        'type' => 'checkbox',
        'slug'=>'options-tab',
        'title'=>__('Options tab','wp-recall'),
        'values'=>array(
            'public' => __('public tab','wp-recall'),
            'ajax' => __('ajax-loading','wp-recall'),
            'cache' => __('caching support','wp-recall')
        )
    ),
    
    array(
        'type' => 'textarea',
        'slug'=>'content',
        'title'=>__('Content tab','wp-recall'),
        'notice'=>__('supported shortcodes and HTML-code','wp-recall')
    )
    
));

echo $content;