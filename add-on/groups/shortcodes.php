<?php
add_shortcode('grouplist','rcl_get_grouplist');
function rcl_get_grouplist($atts){

    include_once 'classes/rcl-groups.php';
    $list = new Rcl_Groups($atts);

    $count = false;

    if(!$list->number){

        $rqst = $list->search_request();

        $search_string = ($rqst)? '&'.implode('&',$rqst): '';

        $count = $list->count_groups();

        $rclnavi = new RCL_navi($list->inpage,$count,$search_string,$list->paged);
        $list->offset = $rclnavi->offset;
        $list->number = $rclnavi->inpage;
    }

    $groupsdata = $list->get_groups();

    $content = $list->get_filters($count);

    if(!$groupsdata){
        $content .= '<p align="center">'.__('Groups not found','rcl').'</p>';
        return $content;
    }

    $content .= '<div class="rcl-grouplist">';

    foreach($groupsdata as $rcl_group){ $list->setup_groupdata($rcl_group);
        $content .= rcl_get_include_template('group-list.php',__FILE__);
    }

    $content .= '</div>';

    if($rclnavi->inpage)
        $content .= $rclnavi->navi();

    $list->remove_data();

    return $content;
}

