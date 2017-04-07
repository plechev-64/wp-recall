<?php
/**
 * Description of Rcl_Group
 *
 * @author Андрей
 */

class Rcl_Group_Widget {

    public $widget;

    function __construct($args=false){

        if(!$args) return false;

        $called = get_called_class();

        if($called==__CLASS__) return false;

        $args['class'] = $called;
        $this->widget = $args;

    }

    function register($widget_class){
        global $rcl_group_widgets;
        if(class_exists($widget_class)){
            $object = new $widget_class();
            $rcl_group_widgets[] = (object)$object->widget;
        }
    }

    function before($object){

        if(!isset($object->widget_type)||!$object->widget_type) $object->widget_type = 'normal';

        $before = sprintf('<div %s class="sidebar-widget '.$object->widget_type.'-widget">','id="'.$object->widget_id.'"');

        $title = (isset($object->widget_options['title']))? $object->widget_options['title']: $object->widget_title;

        if($title)
            $before .= '<h3 class="title-widget">'.$title.'</h3>';

        if($object->widget_type=='hidden')
            $before .= '<a href="#" onclick="rcl_more_view(this); return false;" class="manage-hidden-widget">'
                . '<i class="fa fa-plus-square-o"></i>'.__('Show all','wp-recall')
                . '</a>';

        $before .= '<div class="widget-content">';

        return $before;
    }

    function after($object){
        return '</div></div>';
    }

    function field_name($id_field){
        return 'data[][widget]['.$this->widget['widget_id'].'][options]['.$id_field.']';
    }

    function field_value($field_value){
        return $field_value;
    }

    function loop($place='sidebar'){
        global $rcl_group,$rcl_group_widgets,$rcl_group_area;

        $content = '';

        if(!$rcl_group_widgets) return $content;

        $rcl_group_widgets = apply_filters('rcl_group_widgets',$rcl_group_widgets);

        $group_widgets = rcl_get_group_option($rcl_group->term_id,'group_widgets');
        $widgets_options = rcl_get_group_option($rcl_group->term_id,'widgets_options');

        ob_start();

        foreach($rcl_group_area as $zone){

            if($place!=$zone['id']) continue;

            foreach($rcl_group_widgets as $widget){

                if($place!=$widget->widget_place) continue;

                $widget->widget_options = $widgets_options[$widget->widget_id];

                $obj = new $widget->class();
                $method = 'widget';

                $data = array(
                    'before'=>$this->before($widget),
                    'after'=>$this->after($widget)
                );

                $obj->$method($data,$widget->widget_options);
            }

        }

        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    function manage_widgets($group_id){
        global $rcl_group_widgets,$rcl_group_area,$user_ID;

        $rcl_group_widgets = apply_filters('rcl_group_widgets',$rcl_group_widgets);

        $content = '<div id="widgets-options">';
        $content .= '<form method="post">';

        $zones = array();

        if($rcl_group_area[0]['id']!='unuses')
            array_unshift($rcl_group_area,array('id'=>'unuses','name'=>__('Unused','wp-recall')));

        $widgets_options = rcl_get_group_option($group_id,'widgets_options');

        foreach($rcl_group_area as $zone){

            $zones[] = '#'.$zone['id'].'-widgets';

            $content .= '<div id="'.$zone['id'].'-zone" class="widgets-zone">';
            $content .= '<input type="hidden" name="data[][content]" value="'.$zone['id'].'">';
            $content .= '<span class="zone-name">'.$zone['name'].'</span>';
            $content .= '<ul id="'.$zone['id'].'-widgets" class="sortable-connected">';
            foreach($rcl_group_widgets as $widget){

                if($widget->widget_place!=$zone['id']) continue;

                $options = false;

                $obj = new $widget->class();
                $this->widget = $obj->widget;
                $this->widget['options'] = $widgets_options[$widget->widget_id];

                $method = 'options';
                if(method_exists($obj,$method)){
                    ob_start();
                    $obj->$method($this->widget['options']);
                    $options = ob_get_contents();
                    ob_end_clean();
                }

                $content .= '<li id="'.$widget->widget_id.'-widget" class="widget-box">';

                $content .= '<input type="hidden" name="data[][widget]['.$this->widget['widget_id'].'][id]" value="'.$widget->widget_id.'">';

                if($options)
                    $content .= '<span class="widget-name" onclick="rcl_more_view(this); return false;"><i class="fa fa-plus-square-o"></i>'.$widget->widget_title.'</span>';
                else
                    $content .= '<span class="widget-name">'.$widget->widget_title.'</span>';

                if($options)
                    $content .= '<div class="widget-options" style="display:none;">'.$options.'</div>';

                $content .= '</li>';

            }
            $content .= '</ul>';
            $content .= '</div>';
        }

        $content .= '<input type="hidden" name="group-action" value="update-widgets">'
                . wp_nonce_field( 'group-action-' . $user_ID,'_wpnonce',true,false );
        
        $content .= '<input type="submit" class="recall-button" name="group-submit" value="'.__('Save changes','wp-recall').'">';

        $content .= '</form>';

        $content .= '</div>'
                . '<script>
                jQuery(function() {
                  jQuery( "'.implode(',',$zones).'" ).sortable({
                    connectWith: ".sortable-connected",
                    placeholder: "ui-state-highlight",
                    distance: 3,
                    cursor: "move",
                    forceHelperSize: true
                  });
                });
                </script>';

        return $content;
    }
}

function rcl_group_register_widget($child_class){
    global $rcl_group_widgets;
    $widgets = new Rcl_Group_Widget();
    $widgets->register($child_class);
}

function rcl_group_area($place = 'sidebar'){
    global $rcl_group,$rcl_group_widgets;
    
    do_action('rcl_group_'.$place.'_area');
    
    $widgets = new Rcl_Group_Widget();
    echo $widgets->loop($place);
}

function rcl_get_group_widgets($group_id){
    $widgets = new Rcl_Group_Widget();
    return $widgets->manage_widgets($group_id);
}

function rcl_update_group_widgets($group_id,$args){
    global $rcl_group_widgets,$rcl_group_area;

    $zones = array();
    $options = array();
    foreach($args as $widget){
        if(isset($widget['content'])){
            $key = $widget['content'];
            continue;
        }

        foreach($widget['widget'] as $widget_id=>$data){

            if($data['id']){
                $zones[$key][] = $widget_id;
            }

            if(isset($data['options'])){
                $optionsData[$widget_id][] = $data['options'];
            }

        }

    }

    if($optionsData){
        foreach($optionsData as $id_widget=>$opts){
            foreach($opts as $k=>$option){
                foreach($option as $key=>$val){
                    $options[$id_widget][$key] = $val;
                }
            }
        }
    }

    if($zones) rcl_update_group_option($group_id,'group_widgets',$zones);
    else rcl_delete_group_option($group_id,'group_widgets');

    if($options) rcl_update_group_option($group_id,'widgets_options',$options);
    else rcl_delete_group_option($group_id,'widgets_options');
}

add_filter('rcl_group_widgets','rcl_edit_group_widgets');
function rcl_edit_group_widgets($widgets){
    global $rcl_group,$rcl_group_area,$rcl_group_widgets;

    $group_widgets = rcl_get_group_option($rcl_group->term_id,'group_widgets');

    if(!$group_widgets) return $widgets;
    
    //удаляем данные о виджетах в незарегистрированных областях
    foreach($group_widgets as $area_id=>$ws){
        if(!rcl_is_group_area($area_id)){
            unset($group_widgets[$area_id]);
        }
    }

    array_unshift($rcl_group_area,array('id'=>'unuses','name'=>__('Unused','wp-recall')));
    
    foreach($rcl_group_area as $zone){

        if(!isset($group_widgets[$zone['id']])) continue;

        foreach($widgets as $k=>$widget){

            $key = array_search($widget->widget_id,$group_widgets[$zone['id']]);

            if($key!==false){
                $widget->widget_place = $zone['id'];
                $NewWidgets[$zone['id']][$key] = $widget;
            }else{

            }
        }

    }

    foreach($widgets as $k=>$widget){
        $used=false;
        foreach($group_widgets as $content=>$data){
            $key = array_search($widget->widget_id,$group_widgets[$content]);
            if($key!==false) $used = true;
        }
        if($used==false){
            $widget->widget_place = 'unuses';
            $NewWidgets['unuses'][] = $widget;
        }
    }

    foreach($NewWidgets as $z=>$Widgets){
        ksort($Widgets);
        $NewWidgets[$z] = $Widgets;
    }

    $widgets = array();
    foreach($NewWidgets as $zone=>$wdgts){
        foreach($wdgts as $widget){
            $widgets[] = $widget;
        }
    }

    return $widgets;
}
