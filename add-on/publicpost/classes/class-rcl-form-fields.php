<?php

/*deprecated*/
class Rcl_Form_Fields{

    public $type;
    public $placeholder;
    public $label;
    public $name;
    public $id;
    public $class;
    public $value;
    public $maxlength;
    public $checked;
    public $required;

    function get_field($args){
        $this->type = (isset($args['type']))? $args['type']: 'text';
        $this->id = (isset($args['id']))? $args['id']: false;
        $this->class = (isset($args['class']))? $args['class']: false;
        $this->placeholder = (isset($args['placeholder']))? $args['placeholder']: false;
        $this->label = (isset($args['label']))? $args['label']: false;
        $this->name = (isset($args['name']))? $args['name']: false;
        $this->value = (isset($args['value']))? $args['value']: false;
        $this->maxlength = (isset($args['maxlength']))? $args['maxlength']: false;
        $this->checked = (isset($args['checked']))? $args['checked']: false;
        $this->required = (isset($args['required'])&&$args['required'])? true: false;

        return $this->get_type_field();
    }

    function add_label($field){

        switch($this->type){
            case 'radio': 
                $content = '<span class="rcl-'.$this->type.'-box">';
                $content .= sprintf('%s<label for="%s" class="block-label">%s</label>',$field,$this->id,$this->label);
                $content .= '</span>';
                break;
            case 'checkbox': 
                $content = '<span class="rcl-'.$this->type.'-box">';
                $content .= sprintf('%s<label for="%s" class="block-label">%s</label>',$field,$this->id,$this->label); 
                $content .= '</span>';
                break;
            default: $content = sprintf('<label class="block-label">%s</label>%s',$this->label,$field);
        }

        return $content;
    }

    function get_type_field(){

        switch($this->type){
            case 'textarea': $field = sprintf('<textarea name="%s" placeholder="%s" '.$this->required().' %s>%s</textarea>',$this->name,$this->placeholder,$this->id,$this->value); break;
            default: $field = sprintf('<input type="%s" name="%s" value="%s" placeholder="%s" maxlength="%s" '.$this->get_class().' '.$this->selected().' '.$this->required().' id="%s">',$this->type,$this->name,$this->value,$this->placeholder,$this->maxlength,$this->id);
        }

        if($this->label) $field = $this->add_label($field);

        return $field;

    }

    function get_class(){

        if($this->class)
            return 'class="'.$this->class.'"';

    }

    function selected(){
        if(!$this->checked) return false;
        switch($this->type){
            case 'radio': return 'checked=checked'; break;
            case 'checkbox': return 'checked=checked'; break;
        }
    }

    function required(){
        if(!$this->required) return false;
        return 'required=required';
    }
}


