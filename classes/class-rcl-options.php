<?php
class Rcl_Options extends Rcl_Custom_Fields{
    
    public $key;
    public $type;

    function __construct($key=false){
        if($key)
            $this->key = rcl_key_addon(pathinfo($key));
        else
            $this->key = false;
    }

    function options($title,$conts){
        $return = '<span ';
        
        if($this->key) 
            $return .= 'id="title-'.$this->key.'" data-addon="'.$this->key.'" data-url="'.admin_url('admin.php?page='.$_GET['page'].'&rcl-addon-options='.$this->key).'" ';
        else
            $return .= 'data-url="'.admin_url('admin.php?page=manage-wprecall').'" ';
        
        $return .= 'class="title-option"><span class="wp-menu-image dashicons-before dashicons-admin-generic"></span> '.$title.'</span>
	<div ';
        
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

        $optiondata = apply_filters('rcl_option_data',array($typefield,$atts));
        
        $type = $optiondata[0];
        $args = $optiondata[1];
        $value = '';
        $content = '';
        
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
        
        $methodName = 'get_type_'.$type;
        
        $field = array(
            'type' => $type,
            'slug' => $args['name'],
            'classes' => (isset($args['parent']))? 'parent-select': '',
            'name' => $this->attr_name($args),
            'values' => $args['options']
        );
        
        $this->value = $value;
        $this->slug = $args['name'];
        
        $content .= $this->$methodName($field,$value);
        
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

    function get_value($args){
        global $rcl_options;
        $val = (isset($rcl_options[$args['name']]))?$rcl_options[$args['name']]:'';
        if(!$val&&isset($args['default'])) $val = $args['default'];
        return $val;
    }

}
