<?php

/*$atts
array(
 * 'ID',
 * 'group_status',
 * 'user_id',
 * 'admin_id',
 * 'search_name',
 * 'filters',
 * 'include',
 * 'include_admin_id',
 * 'exclude',
 * 'exclude_admin_id',
 * 'number',
 * 'per_page',
 * 'offset',
 * 'orderby',
 * 'order'
 * ) */

add_shortcode('grouplist','rcl_get_grouplist');
function rcl_get_grouplist($atts){

    include_once 'classes/class-rcl-groups-list.php';
    $list = new Rcl_Groups_List($atts);

    $count = $list->count();

    if(!isset($atts['number'])){

        $rclnavi = new Rcl_PageNavi('rcl-groups',$count,array('in_page'=>$list->query['number']));
        $list->query['offset'] = $rclnavi->offset;
        
    }

    $groupsdata = $list->get_data();
    
    $content = $list->get_filters($count);

    if(!$groupsdata){
        $content .= '<p align="center">'.__('Groups not found','wp-recall').'</p>';
        return $content;
    }

    $content .= '<div class="rcl-grouplist">';

    foreach($groupsdata as $rcl_group){ $list->setup_groupdata($rcl_group);
        $content .= rcl_get_include_template('group-list.php',__FILE__);
    }

    $content .= '</div>';

    if(!isset($atts['number']) && $rclnavi->in_page)
        $content .= $rclnavi->pagenavi();

    $list->remove_data();

    return $content;
}

add_shortcode('rcl-group','rcl_get_single_group');
function rcl_get_single_group(){
    global $rcl_group;
    
    rcl_dialog_scripts();
    
    if(rcl_is_group_can('admin')){
        rcl_fileupload_scripts();
        rcl_enqueue_script( 'groups-image-uploader', rcl_addon_url('js/groups-image-uploader.js', __FILE__),false,true);
    }

    $admin = (rcl_is_group_can('admin')||rcl_check_access_console())? 1: 0;

    $class = ($admin)? 'class="admin-view"': '';

    $content = '<div id="rcl-group" data-group="'.$rcl_group->term_id.'" '.$class.'>';

    if($admin)
        $content .= rcl_group_admin_panel();

    $content .= '<div id="group-popup"></div>';

    $content .= rcl_get_include_template('single-group.php',__FILE__);

    $content .= '</div>';
    
    return $content;
}

