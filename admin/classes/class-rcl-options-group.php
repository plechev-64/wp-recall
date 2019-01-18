<?php
class Rcl_Options_Group{

    public $group_id;
    public $title;
    public $options;
    public $extend;
    public $option_values = array();

    function __construct($group_id, $args = false, $option_name) {

        $this->group_id = $group_id;

        $this->option_name = $option_name;

        $this->option_values = get_site_option($this->option_name);

        if($args)
            $this->init_properties($args);
    }

    function init_properties($args){

        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }

    }

    function get_value($option, $default = false){

        if(isset($this->option_values[$option])){
            if($this->option_values[$option] || is_numeric($this->option_values[$option])){
                return $this->option_values[$option];
            }

        }

        return $default;
    }

    function add_options($options){

        $optionValues = get_site_option($this->option_name);

        foreach($options as $option){

            $option_id = $option['slug'];

            if(!isset($option['value']))
                $option['value'] = $this->get_value($option_id, isset($option['default'])? $option['default']: null);

            $option['input_name'] = $this->option_name.'['.$option_id.']';

            $this->options[$option_id] =  Rcl_Option::setup_option($option);

            if(isset($option['childrens'])){
                foreach($option['childrens'] as $parentValue => $childFields){

                    if(!is_array($childFields)) continue;

                    foreach($childFields as $childField){

                        $child_id = $childField['slug'];

                        if(!isset($childField['value']))
                            $childField['value'] = $this->get_value($child_id, isset($childField['default'])? $childField['default']: null);
                        $childField['input_name'] = $this->option_name.'['.$child_id.']';

                        $childField['parent'] = array(
                            'id' => $option_id,
                            'value' => $parentValue
                        );

                        $this->options[$child_id] =  Rcl_Option::setup_option($childField);

                    }
                }
            }

        }

    }

    function get_content(){

        if(!$this->options) return false;

        $content = '<div id="'.$this->group_id.'-options-group" class="options-group '.($this->extend? 'extend-options': '').'" data-group="'.$this->group_id.'">';

        if($this->title)
            $content .= '<span class="group-title">'.$this->title.'</span>';

        foreach($this->options as $option){

            $args = array('classes' => array('rcl-option'));

            if($option->extend){
                $args['classes'][] = 'extend-options';
            }

            $content .= $option->get_field_html($args);

        }

        $content .= '</div>';

        return $content;

    }

}
