<?php

add_filter('admin_options_wprecall','rcl_get_tablist_options');
function rcl_get_tablist_options($content){
    global $rcl_tabs,$rcl_order_tabs;

    rcl_sortable_scripts();
    
    $rcl_order_tabs = get_option('rcl_order_tabs');

    $opt = new Rcl_Options('tabs');

    if(!$rcl_tabs) {
        $content .= $opt->options(__('Setting tabs','wp-recall'),__('Neither one tab personal account not found','wp-recall'));
        return $content;
    }

    $tabs = '<p>'.__('Sort your tabs by dragging them to the desired position','wp-recall').'</p>'
            . '<ul id="tabs-list-rcl" class="sortable">';

    if($rcl_order_tabs){
        foreach($rcl_order_tabs['order'] as $order=>$key){
            if(!isset($rcl_tabs[$key])) continue;
            $tabs .= rcl_get_tab_option($key);
            $keys[$key] = 1;
        }
        foreach($rcl_tabs as $key=>$tab){
            if(isset($keys[$key])) continue;
            $tabs .= rcl_get_tab_option($key,$tab);
        }
    }else{

        foreach($rcl_tabs as $key=>$tab){
            if(!isset($tab['args']['order'])) continue;
            $order = $tab['args']['order'];
            if (isset($order)) {
                if (!isset($otabs[$order])) {
                    $otabs[$order][$key] = $tab;
                }else {
                    for($a=$order;1==1;$a++){
                        if(!isset($otabs[$a])){
                            $otabs[$a][$key] = $tab;
                            break;
                        }
                    }
                }
            }
        }

        foreach($rcl_tabs as $key=>$tab){
            if (!isset($tab['args']['order'])) {
                $otabs[][$key] = $tab;
            }
        }

        ksort($otabs);

        foreach($otabs as $order=>$vals){
            foreach($vals as $key=>$val){
                $tabs .= rcl_get_tab_option($key,$val);
            }
        }
    }
    $tabs .= '</ul>';

    $tabs .= '<script>jQuery(function(){jQuery(".sortable").sortable();return false;});</script>';

    $content .= $opt->options(__('Setting tabs','wp-recall'),$opt->option_block(array($tabs)));

    return $content;
}

function rcl_get_tab_option($key,$tab=false){
    global $rcl_order_tabs;

    $name = (isset($rcl_order_tabs)&&isset($rcl_order_tabs['name'][$key])) ?$rcl_order_tabs['name'][$key] :  $tab['name'];
    return '<li>'
            . __('Name tab','wp-recall').': <input type="text" name="local[rcl_order_tabs][name]['.$key.']" value="'.$name.'">'
            . '<input type="hidden" name="local[rcl_order_tabs][order][]" value="'.$key.'">'
            . '</li>';
}