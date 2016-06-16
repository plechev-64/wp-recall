<?php
add_shortcode('grouplist','rcl_get_grouplist');
function rcl_get_grouplist($atts){

    include_once 'classes/rcl-groups.php';
    $list = new Rcl_Groups($atts);

    $count = false;

    if(!$list->number){

        $count = $list->count_groups();

        $rclnavi = new Rcl_PageNavi('rcl-groups',$count,array('in_page'=>$list->inpage,'current_page'=>$list->paged));
        $list->offset = $rclnavi->offset;
        $list->number = $rclnavi->in_page;
    }

    $groupsdata = $list->get_groups();

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

    if($rclnavi->in_page)
        $content .= $rclnavi->pagenavi();

    $list->remove_data();

    return $content;
}

