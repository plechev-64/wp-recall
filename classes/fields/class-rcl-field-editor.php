<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-rcl-custom-field-text
 *
 * @author Андрей
 */
class Rcl_Field_Editor extends Rcl_Field_Abstract{

    public $tinymce;
    public $html_editor = 1;
    public $editor_id;
    public $quicktags;
    public $media_button;

    function __construct($args) {

        if(isset($args['editor-id']))
            $args['editor_id'] = $args['editor-id'];

        parent::__construct($args);

    }

    function get_options(){

        return array(
            array(
                'slug' => 'icon',
                'default' => 'fa-file-text-o',
                'placeholder' => 'fa-file-text-o',
                'type' => 'text',
                'title'=>__('Icon class of  font-awesome', 'wp-recall'),
                'notice'=>__('Source', 'wp-recall').' <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">http://fontawesome.io/</a>'
            ),
            array(
                'slug' => 'tinymce',
                'type' => 'radio',
                'title' => __('TinyMCE', 'wp-recall'),
                'values' => array(
                    __('Disabled', 'wp-recall'),
                    __('Using TinyMCE', 'wp-recall')
                ),
                'notice' => __('May not load with AJAX', 'wp-recall')
            ),
            array(
                'slug' => 'media_button',
                'type' => 'radio',
                'title' => __('Media loader', 'wp-recall'),
                'values' => array(
                    __('Disabled', 'wp-recall'),
                    __('Enabled', 'wp-recall')
                )
            )
        );

    }

    function get_input(){

        $editor_id = $this->editor_id? $this->editor_id: 'editor-'.$this->rand;

        $data = array( 'wpautop' => 1
            ,'media_buttons' => $this->media_button
            ,'textarea_name' => $this->input_name
            ,'textarea_rows' => 10
            ,'tabindex' => null
            ,'editor_css' => ''
            ,'editor_class' => 'autosave'
            ,'teeny' => 0
            ,'dfw' => 0
            ,'tinymce' => $this->tinymce? true: false
            ,'quicktags' => $this->quicktags? array('buttons'=>$this->quicktags): true
        );

        ob_start();

        wp_editor( $this->value, $editor_id, $data );

        if(rcl_is_ajax()){
            global $wp_scripts, $wp_styles;

            $wp_scripts->do_items(array(
                'quicktags'
            ));

            $wp_styles->do_items(array(
                'buttons'
            ));
        }

        $content = ob_get_contents();

        if(rcl_is_ajax()){
            $content .= '<script>rcl_init_ajax_editor("'.$editor_id.'",'.json_encode(array(
                'tinymce' => $this->tinymce,
                'qt_buttons' => $this->quicktags? $this->quicktags: false
            )).');</script>';
        }

        ob_end_clean();

        return $content;
    }

    function get_value(){

        if(!$this->value) return false;

        return nl2br($this->value);

    }

}
