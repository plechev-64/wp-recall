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
class Rcl_Field_Uploader extends Rcl_Field_Abstract{

    public $required;
    public $file_types = 'jpg, jpeg, png';
    public $max_size = 512;
    public $max_files = 5;
    public $multiple = 0;
    public $dropzone = 0;

    function __construct($args) {
        parent::__construct($args);
    }

    function get_options(){

        $options = array(
            array(
                'slug' => 'icon',
                'default' => 'fa-file',
                'placeholder' => 'fa-file',
                'type' => 'text',
                'title'=>__('Icon class of  font-awesome', 'wp-recall'),
                'notice'=>__('Source', 'wp-recall').' <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">http://fontawesome.io/</a>'
            ),
            array(
                'slug' => 'max_size',
                'default' => $this->max_size,
                'type' => 'runner',
                'unit' => 'Kb',
                'value_min' => 256,
                'value_max' => 5120,
                'value_step' => 256,
                'title' => __('File size', 'wp-recall'),
                'notice' => __('maximum size of uploaded file, Kb (Default - 512)', 'wp-recall')
            ),
            array(
                'slug' => 'file_types',
                'default' => $this->file_types,
                'type' => 'text',
                'title' => __('Allowed file types', 'wp-recall'),
                'notice' => __('allowed types of files are divided by comma, for example: pdf, zip, jpg', 'wp-recall')
            ),
            array(
                'slug' => 'max_files',
                'default' => $this->max_files,
                'type' => 'runner',
                'value_min' => 1,
                'value_max' => 100,
                'value_step' => 1,
                'title' => __('Макс. количество файлов', 'wp-recall'),
            ),
            array(
                'slug' => 'multiple',
                'default' => $this->multiple,
                'type' => 'radio',
                'values' => array(
                    __('Отключено', 'wp-recall'),
                    __('Включено', 'wp-recall')
                ),
                'title' => __('Множественная загрузка', 'wp-recall'),
            ),
            array(
                'slug' => 'dropzone',
                'default' => $this->dropzone,
                'type' => 'radio',
                'values' => array(
                    __('Отключено', 'wp-recall'),
                    __('Включено', 'wp-recall')
                ),
                'title' => __('Dropzone', 'wp-recall'),
            )
        );

        return $options;

    }

    function get_input(){
        global $user_ID;

        $uploader = new Rcl_Uploader('field_'.$this->id, array(
            //'temp_media' => true,
            'required' => intval($this->required),
            'user_id' => $user_ID,
            'min_width' => 200,
            'min_height' => 200,
            //'resize' => array(500, 500),
            'dropzone' => $this->dropzone,
            'multiple' => $this->multiple,
            'max_size' => $this->max_size,
            'auto_upload' => $this->multiple? true: false,
            'file_types' => array_map('trim',explode(',',$this->file_types)),
            'max_files' => $this->max_files,
            'crop' => $this->multiple? false: true,
            'input_attach' => $this->id
            /*'image_sizes' => array(
                array(
                    'width' => 150,
                    'height' => 150,
                    'crop' => 1
                )
            )*/
        ));

        $content = $uploader->get_gallery($this->value, true);

        $content .= $uploader->get_uploader();

        return $content;
    }

    function get_value() {

        if(!$this->value) return false;

        $width = 100;

        $galArgs = array(
            'id' => 'rcl-gallery-'.$this->id,
            'attach_ids' => $this->value,
            //'center_align' => true,
            //'width' => (count($this->value) < 7)? count($this->value) * 73: 500,
            'height' => $width,
            'slides' => array(
                'slide' => array($width,$width),
                'full' => 'large'
            ),
            'options' => array(
                '$SlideWidth' => $width,
                '$SlideSpacing' => 3
            )
        );

        if(count($attach_ids) >= 7){
            $galArgs['navigator'] = array(
                'arrows' => true
            );
        }

        return rcl_get_image_gallery($galArgs);

    }

}
