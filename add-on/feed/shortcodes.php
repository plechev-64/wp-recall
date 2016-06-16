<?php

add_shortcode('feed','rcl_feed_shortcode');
function rcl_feed_shortcode($atts){
    global $wpdb,$user_ID,$rcl_feed;

    if(!$user_ID){
        return '<p class="aligncenter">'
                .__('Login or register to view the latest publications and comments from users on which you will you subscribed.','wp-recall')
                .'</p>';
    }

    include_once 'classes/class-rcl-feed.php';
    $list = new Rcl_Feed($atts);

    $count = false;

    if(!$list->number){

        //$rqst = $list->search_request();

        //$search_string = ($rqst)? '&'.implode('&',$rqst): '';

        $count = $list->count_feed_posts();

        $rclnavi = new Rcl_PageNavi('rcl-feed',$count,array('in_page'=>$list->inpage,'current_page'=>$list->paged));
        $list->offset = $rclnavi->offset;
        $list->number = $rclnavi->in_page;
    }

    $feedsdata = $list->get_feed();

    $content = $list->get_filters($count);

    if(!$feedsdata){
        $content .= '<p align="center">'.__('News not found','wp-recall').'</p>';
        return $content;
    }

    $load = ($rclnavi->inpage)? 'data-load="'.$list->load.'"': '';

    $content .= '<div id="rcl-feed" data-feed="'.$list->content.'" '.$load.'>';

    foreach($feedsdata as $rcl_feed){ $list->setup_data($rcl_feed);
        $content .= '<div id="feed-'.$rcl_feed->feed_type.'-'.$rcl_feed->feed_ID.'" class="feed-box feed-user-'.$rcl_feed->feed_author.' feed-'.$rcl_feed->feed_type.'">';
        $content .= rcl_get_include_template('feed-post.php',__FILE__);
        $content .= '</div>';
    }

    if($list->load=='ajax'&&$rclnavi->inpage)
        $content .= '<div id="feed-preloader"><div></div></div>'
            . '<div id="feed-bottom"></div>';

    $content .= '</div>';

    if($list->load=='pagenavi'&&$rclnavi->in_page)
        $content .= $rclnavi->pagenavi();

    $list->remove_data();

    return $content;

}

