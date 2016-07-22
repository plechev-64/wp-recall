<?php

add_filter('admin_options_wprecall','rcl_get_tablist_options',100);
function rcl_get_tablist_options($content){
    global $rcl_tabs,$rcl_order_tabs;

    rcl_sortable_scripts();
    
    $rcl_order_tabs = get_option('rcl_order_tabs');
    
    //удалить позже
    if(isset($rcl_order_tabs['name'])) $rcl_order_tabs = array();
    
    $opt = new Rcl_Options('tabs');

    if(!$rcl_tabs) {
        $content .= $opt->options(__('Setting tabs','wp-recall'),__('Neither one tab personal account not found','wp-recall'));
        return $content;
    }
    
    $areas = array();
    if($rcl_order_tabs){
        
        foreach($rcl_order_tabs as $area_id=>$tabs){
            foreach($tabs as $id_tab=>$tab){
                if(!isset($rcl_tabs[$id_tab])) continue;
                if(isset($rcl_tabs[$id_tab]['args']['output'])&&$rcl_tabs[$id_tab]['args']['output']!=$area_id) continue;
                $areas[$area_id][$id_tab] = $tab;
            }
        }
        
        foreach($rcl_tabs as $id_tab=>$tab){
            $area = isset($tab['args']['output'])? $tab['args']['output']: 'menu';
            if(isset($rcl_order_tabs[$area][$id_tab])) continue;
            $areas[$area][$id_tab] = $tab;
        }
        
    }else{
        
        foreach($rcl_tabs as $id_tab=>$tab){
            $area = isset($tab['args']['output'])? $tab['args']['output']: 'menu';
            $areas[$area][$id_tab] = $tab;
        }
    }
    
    $tab_content = '<p>'.__('Sort your tabs by dragging them to the desired position','wp-recall').'</p>';
    
    foreach($areas as $area_id=>$tabs){
        $tab_content .= '<div class="rcl-area">';
        $tab_content .= '<h3 class="area-name">'.__('Area','wp-recall').' "'.$area_id.'"</h3>';
        $tab_content .= '<ul id="tabs-list-'.$area_id.'" class="tabs-list-rcl sortable">';
            foreach($tabs as $tab_id=>$tab){
                $tab_content .= rcl_get_tab_option($area_id,$tab_id,$tab);
            }
        $tab_content .= '</ul>';
        $tab_content .= '</div>';
    }
    $tab_content .= '<script>jQuery(function(){'
            . 'jQuery(".sortable").sortable({'
            . 'containment: "parent",'
            . 'distance: 5,'
            . 'tolerance: "pointer"'
            . '});return false;});</script>';

    $content .= $opt->options(__('Setting tabs','wp-recall'),$opt->option_block(array($tab_content)));

    return $content;
}

function rcl_get_tab_option($area_id,$tab_id,$tab=false){
    global $rcl_order_tabs;

    $name = (isset($rcl_order_tabs)&&isset($rcl_order_tabs['name'][$tab_id])) ?$rcl_order_tabs['name'][$tab_id] :  $tab['name'];
    return '<li>'
            . __('Name tab','wp-recall').': <input type="text" name="local[rcl_order_tabs]['.$area_id.']['.$tab_id.'][name]" value="'.$name.'">'
            . '</li>';
}