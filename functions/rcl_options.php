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

    function notice($notice){
        return '<small>'.$notice.'</small>';
    }

    function option($typefield,$atts){
        global $rcl_options;
        
        $optiondata = apply_filters('rcl_option_data',array($typefield,$atts));
        
        $type = $optiondata[0];
        $args = $optiondata[1];
        
        if(isset($args['type'])&&$args['type']=='local') 
            $value = get_option($args['name']);
        else if(isset($args['default'])&&!isset($rcl_options[$args['name']]))
            $value = $args['default'];
        else 
            $value = $rcl_options[$args['name']];
        
        $this->type = (isset($args['type']))? $args['type']: 'global';
        
        $content = $this->$type($args,$value);
        
        $content = apply_filters('rcl_option_content',$content,array($type,$args));
        
        return $content;
    }

    function select($args,$value){
        global $rcl_options;

        $content = '<select id="'.$args['name'].'"';
        if(isset($args['parent'])) $content .= 'class="parent-select" ';
        $content .= 'name="'.$this->type.'['.$args['name'].']" size="1">';
            foreach($args['options'] as $val=>$name){
               //$key = (isset($rcl_options[$args['name']]))? $rcl_options[$args['name']]:'';
               $content .= '<option value="'.$val.'" '.selected($value,$val,false).'>'
                       . $name
                       .'</option>';
            }
        $content .= '</select>';
        return $content;
    }

    function checkbox($args){
        global $rcl_options;

        $a = 0;
        $key = false;
        $content = '';

        foreach($args['options'] as $val=>$name){
           $a++;
           if(isset($rcl_options[$args['name']])&&is_array($rcl_options[$args['name']])){
                foreach($rcl_options[$args['name']] as $v){
                    if($val!=$v) continue;
                        $key = $v;
                        break;
                }
           }

           $content .= '<label for="'.$args['name'].'_'.$a.'">';
           $content .= '<input id="'.$args['name'].'_'.$a.'" type="checkbox" name="'.$this->type.'['.$args['name'].'][]" value="'.trim($val).'" '.checked($key,$val,false).'> '.$name;
           $content .= '</label>';
        }

        return $content;
    }

    function text($args){
        global $rcl_options;
        $val = $this->get_value($args);
        return '<input type="text" name="'.$this->type.'['.$args['name'].']" value="'.$val.'" size="60">';
    }

    function password($args){
        global $rcl_options;
        $val = $this->get_value($args);
        return '<input type="password" name="'.$this->type.'['.$args['name'].']" value="'.$val.'" size="60">';
    }

    function number($args){
        global $rcl_options;
        $val = $this->get_value($args);
        return '<input type="number" name="'.$this->type.'['.$args['name'].']" value="'.$val.'" size="60">';
    }

    function email($args){
        global $rcl_options;
        $val = $this->get_value($args);
        return '<input type="email" name="'.$this->type.'['.$args['name'].']" value="'.$val.'" size="60">';
    }

    function url($args){
        global $rcl_options;
        $val = $this->get_value($args);
        return '<input type="url" name="'.$this->type.'['.$args['name'].']" value="'.$val.'" size="60">';
    }

    function textarea($args){
        global $rcl_options;
        $val = $this->get_value($args);
        return '<textarea name="'.$this->type.'['.$args['name'].']">'.$val.'</textarea>';
    }

	function get_value($args){
		global $rcl_options;
		$val = (isset($rcl_options[$args['name']]))?$rcl_options[$args['name']]:'';
		if(!$val&&isset($args['default'])) $val = $args['default'];
		return $val;
	}

}
