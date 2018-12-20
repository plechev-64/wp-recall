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
class Rcl_Field_MultiSelect extends Rcl_Field_Abstract{

    public $required;
    public $values;
    public $value_in_key;

    function __construct($args) {
        parent::__construct($args);
    }

    function get_options(){

        return array(
            array(
                'slug' => 'icon',
                'default' => 'fa-list',
                'placeholder' => 'fa-list',
                'type' => 'text',
                'title'=>__('Icon class of  font-awesome', 'wp-recall'),
                'notice'=>__('Source', 'wp-recall').' <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">http://fontawesome.io/</a>'
            ),
            array(
                'slug' => 'values',
                'default' => $this->values,
                'type' => 'dynamic',
                'title' => __('Specify options', 'wp-recall'),
                'notice' => __('specify each option in a separate field', 'wp-recall')
            )
        );

    }

    function get_input(){

        if(!$this->values) return false;

        rcl_multiselect_scripts();

        $this->value = ($this->value)? $this->value: array();

        if(!is_array($this->value))
            $this->value = array($this->value);

        $content = '<select '.$this->get_required().' name="'.$this->input_name.'[]" id="'.$this->input_id.'" '.$this->get_class().' multiple>';

        foreach($this->values as $k => $value){

            if($this->value_in_key) $k = $value;

            $content .= '<option '.selected(in_array($k,$this->value),true,false).' value="'.trim($k).'">'.$value.'</option>';
        }

        $content .= '</select>';

        $init = 'jQuery("#'.$this->input_id.'").fSelect();';

        if(!rcl_is_ajax()){
            $content .= '<script>jQuery(window).on("load", function() {' . $init . '});</script>';
        }else{
            $content .= '<script>' . $init . '</script>';
        }

        return $content;
    }

    function get_value(){

        if(!$this->value) return false;

        return implode(', ',$this->value);

    }

}
