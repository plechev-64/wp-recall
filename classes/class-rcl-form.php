<?php

class Rcl_Form extends Rcl_Custom_Fields{
    
    public $action = '';
    public $method = 'post';
    public $submit;
    public $nonce_name = '';
    public $onclick;
    public $fields = array();
    public $values = array();

    function __construct($args = false) {
        
        $this->init_properties($args);

    }
    
    function init_properties($args){
        
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
        
    }

    function get_form($args = false){

        $content = '<div class="rcl-form preloader-parent">';
            
            $content .= '<form method="'.$this->method.'" action="'.$this->action.'">';

                foreach($this->fields as $field){

                    $value = (isset($this->values[$field['slug']]))? $this->values[$field['slug']]: false;

                    $required = (isset($field['required']) && $field['required'] == 1)? '<span class="required">*</span>': '';

                    $content .= '<div id="field-'.$field['slug'].'" class="form-field field-type-'.$field['type'].' rcl-option">';

                        if(isset($field['title'])){
                            $content .= '<span class="field-title">';
                            $content .= $this->get_title($field).' '.$required;
                            $content .= '</span>';
                        }

                        $content .= $this->get_input($field,$value);

                    $content .= '</div>';

                }

                $content .= '<div class="submit-box">';
                
                if($this->onclick){
                    $content .= '<a href="#" title="'.$this->submit.'" class="recall-button" onclick=\''.$this->onclick.'\'>';
                    $content .= '<i class="rcli fa-check-circle" aria-hidden="true"></i> '.$this->submit;
                    $content .= '</a>';
                }else{
                    $content .= '<input type="submit" class="recall-button" value="'.$this->submit.'"/>';
                }

                $content .= '</div>';
                
                if($this->nonce_name)
                    $content .= wp_nonce_field($this->nonce_name,'_wpnonce',true,false);

            $content .= '</form>';
            
        $content .= '</div>';
        
        return $content;
        
    }
    
}

