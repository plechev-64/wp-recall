<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rcl_Custom_Field_Abstract
 *
 * @author Андрей
 */
class Rcl_Field_Abstract {

    public $id;
    public $slug;
    public $type;
    public $icon;
    public $title;
    public $value = null;
    public $default = null;
    public $notice;
    public $input_id;
    public $input_name;
    public $parent;
    public $rand;
    public $class;
    public $required;
    public $maxlength;
    public $childrens;
    public $unique_id = false;
    public $value_in_key = null;
    public $must_delete = true;

    public $_new;

    function __construct($args) {

        if(!isset($args['slug'])) return false;

        $this->id = $args['slug'];

        $this->init_properties($args);

    }

    function get_options(){
        return array();
    }

    function init_properties($args){

        /*$properties = get_class_vars(get_class($this));

        foreach ($properties as $name => $val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }*/

        foreach($args as $key => $val){
            $this->$key = $val;
        }

        if(!isset($this->value) && isset($this->default)){
            $this->value = $this->default;
        }

    }

    function get_prop($propName){
        return $this->isset_prop($propName)? $this->$propName: false;
    }

    function isset_prop($propName){
        return isset($this->$propName);
    }

    function set_prop($propName, $value){
        $this->$propName = $value;
    }

    function get_title(){

        if(!$this->title) return false;

        return '<span class="rcl-field-title">'
                    . $this->title .($this->required? ' <span class="required">*</span>': '')
                . '</span>';
    }

    function get_icon(){

        if(!$this->icon) return false;

        $content = '<span class="rcl-field-icon">';
        $content .= '<i class="rcli '.$this->icon.'" aria-hidden="true"></i> ';
        $content .= '</span>';

        return $content;

    }

    function get_notice(){

        if(!$this->notice) return false;

        return '<span class="rcl-field-notice">'
                . '<i class="rcli fa-info" aria-hidden="true"></i>'
                    . $this->notice
                . '</span>';

    }

    function is_new(){
        return $this->_new;
    }

    function get_field_input(){

        if(!$this->type) return false;

        $this->rand = rand(0,1000);

        if(!$this->input_name)
            $this->input_name = $this->id;

        if(!$this->input_id)
            $this->input_id = $this->id;

        if($this->unique_id){
            $this->input_id .= $this->rand;
        }

        $classes = array('rcl-field-input', 'type-'.$this->type.'-input', 'rcl-field-'.$this->id);

        $callback = 'get_input_'.$this->type;

        $inputField = $this->get_input();

        if($this->icon){
            //$inputField .= '<i class="rcli '.$this->icon.' field-icon"></i>';
            //$classes[] = 'have-icon';
        }

        if(!$this->title && $this->required){
            $inputField .= '<span class="required">*</span>';
        }

        if($this->maxlength){
            $inputField .= '<script>rcl_init_field_maxlength("'.$this->input_id.'");</script>';
        }

        $content = '<div class="'.implode(' ', $classes).'">'
                    . '<div class="rcl-field-core">'
                        . $inputField
                    . '</div>'
                    . $this->get_notice()
                . '</div>';

        return $content;

    }

    function get_field_html($args = false){

        $classes = array('rcl-field', 'rcl-custom-field', 'type-'.$this->type.'-field');

        if(isset($args['classes'])){
            $classes = array_merge($classes, $args['classes']);
        }

        if($this->childrens){
            $classes[] = 'rcl-parent-field';
        }

        if($this->parent){
            $classes[] = 'rcl-children-field';
        }

        $content = '<div class="'.implode(' ', $classes).'" '.($this->parent? 'data-parent="'.$this->parent['id'].'" data-parent-value="'.$this->parent['value'].'"': '').'>';

        $content .= $this->get_title();

        $content .= $this->get_field_input();

        $content .= '</div>';

        return $content;

    }

    function get_childrens(){
        return $this->childrens;
    }

    function isset_childrens(){
        return $this->childrens? true: false;
    }

    protected function get_required(){
        return $this->required? 'required="required"': '';
    }

    protected function get_placeholder(){
        return $this->placeholder? 'placeholder="'.$this->placeholder.'"': '';
    }

    protected function get_maxlength(){
        return $this->maxlength? 'maxlength="'.$this->maxlength.'"': '';
    }

    protected function get_pattern(){
        return $this->pattern? 'pattern="'.$this->pattern.'"': '';
    }

    protected function get_min(){
        return $this->value_min !== ''? 'min="'.$this->value_min.'"': '';
    }

    protected function get_max(){
        return $this->value_max !== ''? 'max="'.$this->value_max.'"': '';
    }

    protected function get_input_id(){
        return $this->input_id? 'id="'.$this->input_id.'"': '';
    }

    function get_class(){

        $class = array($this->type.'-field');

        if($this->class)
            $class[] = $this->class;

        return 'class="'.implode(' ',$class).'"';
    }

    function get_value(){

        if(!$this->value) return false;

        return $value;

    }

    function get_field_value($title = false){

        $value = $this->get_value();

        if(!$value || !$this->type) return false;

        $content = '<div class="rcl-field">';

        $content .= $this->get_icon();

        if($title)
            $content .= $this->get_title().': ';

        $content .= '<span class="rcl-field-value type-'.$this->type.'-value">';

        $content .= $value;

        $content .= '</span>';

        $content .= '</div>';

        return $content;
    }
}
