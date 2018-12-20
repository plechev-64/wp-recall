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
class Rcl_Field_Range extends Rcl_Field_Abstract{

    public $value_min = 0;
    public $value_max = 100;
    public $value_step = 1;
    public $unit;

    function __construct($args) {
        parent::__construct($args);
    }

    function get_options(){

        $options = array(
            array(
                'slug' => 'icon',
                'default' => 'fa-arrows-h',
                'placeholder' => 'fa-arrows-h',
                'type' => 'text',
                'title'=>__('Icon class of  font-awesome', 'wp-recall'),
                'notice'=>__('Source', 'wp-recall').' <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">http://fontawesome.io/</a>'
            ),
            array(
                'slug' => 'unit',
                'default' => $this->unit,
                'placeholder' => __('Например: км. или шт.', 'wp-recall'),
                'type' => 'text',
                'title'=>__('Единица измерения', 'wp-recall')
            ),
            array(
                'slug' => 'value_min',
                'value' => $this->value_min,
                'type' => 'number',
                'title' => __('Min', 'wp-recall'),
                'default' => 0
            ),
            array(
                'slug' => 'value_max',
                'value' => $this->value_max,
                'type' => 'number',
                'title' => __('Max', 'wp-recall'),
                'default' => 100
            ),
            array(
                'slug' => 'value_step',
                'value' => $this->value_step,
                'type' => 'number',
                'title' => __('Step', 'wp-recall'),
                'default' => 1
            )
        );

        return $options;

    }

    function get_input(){

        rcl_slider_scripts();

        $content = '<div id="rcl-range-'.$this->rand.'" class="rcl-range">';

            $content .= '<span class="rcl-range-value"><span>'.(implode(' - ', array($this->value_min, $this->value_max))).'</span>';
            if($this->unit)
                $content .= ' '.$this->unit;
            $content .= '</span>';

            $content .= '<div class="rcl-range-box"></div>';
            $content .= '<input type="hidden" class="rcl-range-min" name="'.$this->input_name.'[]" value="'.$this->value_min.'">';
            $content .= '<input type="hidden" class="rcl-range-max" name="'.$this->input_name.'[]" value="'.$this->value_max.'">';
        $content .= '</div>';

        $init = 'rcl_init_range('.json_encode(array(
                'id' => $this->rand,
                'values' => $this->value? $this->value: array($this->value_min,$this->value_max),
                'min' => $this->value_min,
                'max' => $this->value_max,
                'step' => $this->value_step,
            )).');';

        if(!rcl_is_ajax()){
            $content .= '<script>jQuery(window).on("load", function() {' . $init . '});</script>';
        }else{
            $content .= '<script>' . $init . '</script>';
        }

        return $content;
    }

    function get_value(){

        if(!$this->value) return false;

        $minValue = $this->value[0];
        $maxValue = $this->value[1];

        if($this->unit){
            $minValue .= $this->unit;
            $maxValue .= $this->unit;
        }

        return __('from','wp-recall').' '.$minValue.' '.__('for','wp-recall').' '.$maxValue;

    }

}
