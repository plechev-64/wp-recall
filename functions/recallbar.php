<?php

add_action('wp_footer','rcl_recallbar_menu');
function rcl_recallbar_menu(){
    global $rcl_options;
    if(!isset($rcl_options['view_recallbar'])||$rcl_options['view_recallbar']!=1) return false;
    rcl_include_template('recallbar.php');
}

function rcl_recallbar_rightside(){
    $right_li='';
    echo apply_filters('recallbar_right_content',$right_li);
}

