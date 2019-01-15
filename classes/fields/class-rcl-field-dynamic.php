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
class Rcl_Field_Dynamic extends Rcl_Field_Abstract{

    public $required;
    public $placeholder;

    function __construct($args) {
        parent::__construct($args);
    }

    function get_options(){

        return array(
             array(
                'slug' => 'icon',
                'default' => 'fa-bars',
                'placeholder' => 'fa-bars',
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
            )
        );

    }

    function get_input(){

        $content = '<span class="dynamic-values">';

        if($this->value && is_array($this->value)){
            $cnt = count($this->value);
            foreach($this->value as $k => $val){
                $content .= '<span class="dynamic-value">';
                $content .= '<input type="text" '.$this->get_required().' '.$this->get_placeholder().' name="'.$this->input_name.'[]" value="'.$val.'"/>';
                if($cnt == ++$k){
                    $content .= '<a href="#" onclick="rcl_add_dynamic_field(this);return false;"><i class="rcli fa-plus" aria-hidden="true"></i></a>';
                }else{
                    $content .= '<a href="#" onclick="rcl_remove_dynamic_field(this);return false;"><i class="rcli fa-minus" aria-hidden="true"></i></a>';
                }
                $content .= '</span>';
            }
        }else{
            $content .= '<span class="dynamic-value">';
            $content .= '<input type="text" '.$this->get_required().' '.$this->get_placeholder.' name="'.$this->input_name.'[]" value=""/>';
            $content .= '<a href="#" onclick="rcl_add_dynamic_field(this);return false;"><i class="rcli fa-plus" aria-hidden="true"></i></a>';
            $content .= '</span>';
        }

        $content .= '</span>';

        return $content;
    }

    function get_value(){

        if(!$this->value) return false;

        return implode(', ',$this->value);

    }

}
