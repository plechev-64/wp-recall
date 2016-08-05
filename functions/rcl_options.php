<?php
class Rcl_Options {
    public $key;
    public $type;

    function __construct($key=false){
        $this->key=rcl_key_addon(pathinfo($key));
    }

    function options($title,$conts){
        $return = '<span class="title-option"><span class="wp-menu-image dashicons-before dashicons-admin-generic"></span> '.$title.'</span>
	<div ';
        if($this->key) $return .= 'id="options-'.$this->key.'" ';
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
        $return = '<div class="child-select '.$args['name'].'" id="'.$args['name'].'-'.$args['value'].'">';
        foreach($conts as $content){
            $return .= $content;
        }
        $return .= '</div>';
        return $return;
    }

    function title($title){
        return '<h3>'.$title.'</h3>';
    }

    function label($label){
        return '<label>'.$label.'</label>';
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
        
        $content = '';
        
        $optiondata = apply_filters('rcl_option_data',array($typefield,$atts));
        
        $type = $optiondata[0];
        $args = $optiondata[1];
        
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
        
        $this->type = (isset($args['type']))? $args['type']: 'global';
        
        if(isset($args['label'])&&$args['label']){
            $content .= $this->label($args['label']);
        }
        
        $content .= $this->$type($args,$value);
        
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

        $content = '<span class="'.implode(' ',$classes).'">'.$content.'</span>';
        
        return $content;
    }

    function select($args,$value){
        global $rcl_options;
        
        if(!isset($args['options'])) return false;

        $content = '<select id="'.$args['name'].'"';
        if(isset($args['parent'])) $content .= 'class="parent-select" ';
        $content .= 'name="'.$this->attr_name($args).'" size="1">';
            foreach($args['options'] as $val=>$name){
               $content .= '<option value="'.$val.'" '.selected($value,$val,false).'>'
                       . $name
                       .'</option>';
            }
        $content .= '</select>';
        return $content;
    }

    function checkbox($args,$value){
        global $rcl_options;

        $a = 0;
        $key = false;
        $content = '';

        foreach($args['options'] as $val=>$name){
           $a++;
           if($value&&is_array($value)){
                foreach($value as $v){
                    if($val!=$v) continue;
                        $key = $v;
                        break;
                }
           }

           $content .= '<label for="'.$args['name'].'_'.$a.'">';
           $content .= '<input id="'.$args['name'].'_'.$a.'" type="checkbox" name="'.$this->attr_name($args).'[]" value="'.trim($val).'" '.checked($key,$val,false).'> '.$name;
           $content .= '</label>';
        }

        return $content;
    }

    function text($args,$value){
        return '<input type="text" name="'.$this->attr_name($args).'" value="'.$value.'" size="60">';
    }

    function password($args,$value){
        return '<input type="password" name="'.$this->attr_name($args).'" value="'.$value.'" size="60">';
    }

    function number($args,$value){
        return '<input type="number" name="'.$this->attr_name($args).'" value="'.$value.'" size="60">';
    }

    function email($args,$value){
        return '<input type="email" name="'.$this->attr_name($args).'" value="'.$value.'" size="60">';
    }

    function url($args,$value){
        return '<input type="url" name="'.$this->attr_name($args).'" value="'.$value.'" size="60">';
    }

    function textarea($args,$value){
        return '<textarea name="'.$this->attr_name($args).'">'.$value.'</textarea>';
    }

    function get_value($args){
        global $rcl_options;
        $val = (isset($rcl_options[$args['name']]))?$rcl_options[$args['name']]:'';
        if(!$val&&isset($args['default'])) $val = $args['default'];
        return $val;
    }

}
