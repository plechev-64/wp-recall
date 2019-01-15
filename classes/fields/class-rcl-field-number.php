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
class Rcl_Field_Number extends Rcl_Field_Abstract{

    public $required;
    public $placeholder;
    public $value_max;
    public $value_min;
    public $class;

    function __construct($args) {
        parent::__construct($args);
    }

    function get_options(){

        return array(
            array(
                'slug' => 'icon',
                'default' => 'fa-file-text-o',
                'placeholder' => 'fa-file-text-o',
                'class' => 'rcl-iconpicker',
                'type' => 'text',
                'title'=>__('Icon class of  font-awesome', 'wp-recall'),
                'notice'=>__('Source', 'wp-recall').' <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">http://fontawesome.io/</a>'
            ),
            array(
                'slug' => 'placeholder',
                'default' => $this->placeholder,
                'type' => 'text',
                'title' => __('Placeholder', 'wp-recall')
            ),
            array(
                'slug' => 'value_min',
                'default' => $this->value_min,
                'type' => 'number',
                'title' => __('Min', 'wp-recall'),
            ),
            array(
                'slug' => 'value_max',
                'default' => $this->value_max,
                'type' => 'number',
                'title' => __('Max', 'wp-recall'),
            ),
        );

    }

    function get_input(){
        return '<input type="'.$this->type.'" '.$this->get_min().' '.$this->get_max().' '.$this->get_required().' '.$this->get_placeholder().' '.$this->get_class().' name="'.$this->input_name.'" id="'.$this->input_id.'" value=\''.$this->value.'\'/>';
    }

}
