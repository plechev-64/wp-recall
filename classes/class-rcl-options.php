<?php
class Rcl_Options extends Rcl_Custom_Fields{
    
    public $key;
    public $type;
    public $nameArray;

    function __construct($key = false, $nameArray = 'global'){
        if($key)
            $this->key = rcl_key_addon(pathinfo($key));
        else
            $this->key = false;
        
        $this->nameArray = $nameArray;
    }

    function options($title,$conts){
        
        $return = '';
        
        if($title){
        
            $return = '<span ';

            if($this->key) 
                $return .= 'id="title-'.$this->key.'" data-addon="'.$this->key.'" data-url="'.admin_url('admin.php?page='.$_GET['page'].'&rcl-addon-options='.$this->key).'" ';
            else
                $return .= 'data-url="'.admin_url('admin.php?page=manage-wprecall').'" ';

            $return .= 'class="title-option"><span class="wp-menu-image dashicons-before dashicons-admin-generic"></span> '.$title.'</span>';
            
        
        }
        
        $return .= '<div ';

        if($this->key) 
            $return .= 'id="options-'.$this->key.'" ';

        $return .= 'class="wrap-recall-options">';
        
        if(is_array($conts)){
            foreach($conts as $content){
                $return .= $content;
            }
        }else{
            $return .= $conts;
        }
            $return .= '</div>';
        return $return;
    }

    function option_block($conts){
        $return = '<div class="option-block">';
        foreach($conts as $content){
            $return .= $content;
        }
        $return .= '</div>';
        return $return;
    }

    function child($args,$conts){
        
        $childClass = array('child-select',$args['name']);
        
        if(is_array($args['value'])){
            foreach($args['value'] as $val){
                $childClass[] = $args['name'].'-'.$val;
            }
        }else{
            $childClass[] = $args['name'].'-'.$args['value'];
        }
        
        $return = '<div class="'.implode(' ', $childClass).'">';
        foreach($conts as $content){
            $return .= $content;
        }
        $return .= '</div>';
        return $return;
    }

    function title($title){
        if(!$title) return false;
        return '<h3>'.$title.'</h3>';
    }

    function label($label){
        return '<label class="option-title">'.$label.'</label>';
    }
    
    function help($content){
        return '<span class="help-option" onclick="return rcl_get_option_help(this);"><i class="dashicons dashicons-editor-help"></i><span class="help-content">'.$content.'</span></span>';
    }

    function notice($notice){
        return '<small>'.$notice.'</small>';
    }
    
    function extend($content){
        
        $extends = isset($_COOKIE['rcl_extends'])? $_COOKIE['rcl_extends']: 0;
        $classes = array('extend-options');
        $classes[] = $extends? 'show-option': 'hidden-option';
        
        if(is_array($content)){
            $return = '';
            foreach($content as $cont){
                $return .= $cont;
            }
            return '<div class="'.implode(' ',$classes).'">'.$return.'</div>';
        }
        return '<div class="'.implode(' ',$classes).'">'.$content.'</div>';
    }
    
    function attr_name($args){
        if(isset($args['group'])){
            $name = $this->type.'['.$args['group'].']['.$args['name'].']';
        }else{
            $name = $this->type.'['.$args['name'].']';
        }
        return $name;
    }

    function option($typefield,$atts){
        global $rcl_options;

        $optiondata = apply_filters('rcl_option_data',array($typefield,$atts));
        
        $type = $optiondata[0];
        $args = $optiondata[1];
        $content = '';
        
        $value = $this->get_value($args);
        
        $this->type = (isset($args['type']))? $args['type']: 'global';
        
        if(isset($args['label'])&&$args['label']){
            $content .= $this->label($args['label']);
        }
        
        $methodName = 'get_type_'.$type;
        
        $field = array(
            'type' => $type,
            'slug' => $args['name'],
            'classes' => (isset($args['parent']))? 'parent-select': '',
            'name' => $this->attr_name($args),
            'values' => isset($args['options'])? $args['options']: array()
        );
        
        $this->value = $value;
        $this->slug = $args['name'];
        $this->field_id = $this->slug;
        
        $content .= $this->$methodName($field);
        
        if(isset($args['help'])&&$args['help']){
            $content .= $this->help($args['help']);
        }
        
        if(isset($args['notice'])&&$args['notice']){
            $content .= $this->notice($args['notice']);
        }
        
        $classes = array('rcl-option');
        
        if(isset($args['extend'])&&$args['extend']){
            $classes[] = 'extend-option';
        }

        $content = '<span class="'.implode(' ',$classes).' rcl-custom-field">'.$content.'</span>';
        
        return $content;
    }

    function get_value($args){
        global $rcl_options;
        
        $value = '';
        
        if(isset($args['group'])){
            if(isset($args['type'])&&$args['type']=='local'){
                $value = get_option($args['group']);
                $value = $value[$args['name']];
            }else if(isset($rcl_options[$args['group']][$args['name']])){
                $value = $rcl_options[$args['group']][$args['name']];
            }else if(isset($args['default'])){
                $value = $args['default'];
            }
        }else{
            if(isset($args['type'])&&$args['type']=='local') 
                $value = get_option($args['name']);
            else if(isset($args['default'])&&!isset($rcl_options[$args['name']]))
                $value = $args['default'];
            else 
                $value = isset($rcl_options[$args['name']])? $rcl_options[$args['name']]: '';
        }
        
        return $value;
        
    }
    
    function field_value($args){
        global $rcl_options;
        
        if(isset($args['group'])){
            
            if(isset($rcl_options[$args['group']][$args['slug']])){
                return $rcl_options[$args['group']][$args['slug']];
            }
            
            return isset($args['default'])? $args['default']: '';
            
        }
        
        $value = rcl_get_option($args['slug'], $args['default']);
        
        return $value;
        
    }
    
    function field_name($field){
        
        if(isset($field['group'])){
            $name = $this->nameArray.'['.$field['group'].']['.$field['slug'].']';
        }else{
            $name = isset($field['slug'])? $this->nameArray.'['.$field['slug'].']': $this->nameArray.'[]';
        }
        
        return $name;
    }
    
    function options_box($titleBox, $fields){
        
        $content = '<div class="option-block rcl-custom-fields-box">';
        
        $content .= $this->title($titleBox);

        $content .= $this->options_loop($fields);
        
        $content .= '</div>';
        
        return $content;
        
    }
    
    function options_loop($fields){

        $content = '';
        
        foreach($fields as $field){
        
            $value = $this->field_value(array(
                'slug' => isset($field['slug'])? $field['slug']: '',
                'group' => isset($field['group'])? $field['group']: null,
                'default' => isset($field['default'])? $field['default']: null
            ));
            
            $classes = array('rcl-option rcl-custom-field');

            if(isset($field['child']) || isset($field['childrens'])) $classes[] = 'parent-option';
            
            if(isset($field['parent']) && is_array($field['parent'])){
                
                $classes[] = 'children-option';

                //$classes[] = 'option-hide';
                
                foreach($field['parent'] as $parent => $val){
                    
                    $classes[] = 'parent-'.$parent;
                    
                    if(is_array($val)){
                        foreach($val as $v){
                            $classes[] = $parent.'-'.$v;
                        }
                    }else{
                        $classes[] = $parent.'-'.$val;
                    }
 
                }
                
            }
            
            $contentField = '<div class="'.implode(' ', $classes).'">';
            
            if(isset($field['title']))
                $contentField .= $this->label($field['title']);
            
            if(isset($field['help']))
                $contentField .= $this->help($field['help']);
            
            $field['name'] = $this->field_name($field);
 
            $contentField .= $this->get_input($field, $value);
            
            $contentField .= '</div>';
            
            if(isset($field['childrens'])){
                
                foreach($field['childrens'] as $parentValue => $childFields){
                    $contentField .= '<div class="'.implode(' ', array('rcl-option', 'children-option', 'parent-'.$field['slug'], $field['slug'].'-'.$parentValue)).'">';
                    $contentField .= $this->options_loop($childFields);
                    $contentField .= '</div>';
                }
                
                
            }
            
            if(isset($field['extend']))
                $contentField = $this->extend($contentField);
            
            $content .= $contentField;
        }
        
        return $content;
        
    }

}
