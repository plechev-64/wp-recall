<?php

/*array(
    'user_id'=>$user_id,
    'object_id'=>$object_id,
    'feed_type'=>'author',
    'feed_status'=>1
)*/

//добавляем новую подписку по переданному массиву значений
function rcl_insert_feed_data($args){
    global $wpdb;

    $wpdb->insert(
        RCL_PREF."feeds",
        $args
    );

    $feed_id = $wpdb->insert_id;

    do_action('rcl_insert_feed_data',$feed_id,$args);

    return $feed_id;
}

//Обновляем данные фида по переданному массиву значений
function rcl_update_feed_data($args){
    global $wpdb;

    if(!isset($args['feed_id'])) return false;

    $feed_id = $args['feed_id'];
    unset($args['feed_id']);

    $result = $wpdb->update(
        RCL_PREF."feeds",
        $args,
        array('feed_id'=>$feed_id)
    );

    if(!$result) return false;

    do_action('rcl_update_feed_data',$feed_id,$args);

    return $result;
}

//добавляем подписку текущему пользователю на указанного пользователя
function rcl_add_feed_author($author_id){
    global $user_ID;

    $result = rcl_insert_feed_data(array(
        'user_id'=>$user_ID,
        'object_id'=>$author_id,
        'feed_type'=>'author',
        'feed_status'=>1
    ));

    return $result;
}

//удаляем подписку текущему пользователю на указанного пользователя
function rcl_remove_feed_author($author_id){
    global $user_ID,$wpdb;

    $feed_id = rcl_get_feed_author_current_user($author_id);

    return rcl_remove_feed($feed_id);
}

//получаем данные фида по ИД
function rcl_get_feed_data($feed_id){
    global $wpdb;
    
    $cachekey = json_encode(array('rcl_get_feed_data',$feed_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;

    $result = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM ".RCL_PREF."feeds WHERE feed_id='%d'",$feed_id)
    );
    
    wp_cache_add( $cachekey, $result );

    return $result;
}

//удаление фида по ИД
function rcl_remove_feed($feed_id){
    global $wpdb;

    $feed = rcl_get_feed_data($feed_id);

    if(!$feed) return false;

    do_action('rcl_pre_remove_feed',$feed);

    $result = $wpdb->query(
        $wpdb->prepare("DELETE FROM ".RCL_PREF."feeds WHERE feed_id='%d'",$feed_id)
    );

    return $result;
}

function rcl_is_ignored_feed_author($author_id){
    global $user_ID,$wpdb;
    
    $cachekey = json_encode(array('rcl_is_ignored_feed_author',$author_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $feed_id = $wpdb->get_var("SELECT feed_id FROM ".RCL_PREF."feeds WHERE user_id='$user_ID' AND object_id='$author_id' AND feed_type='author' AND feed_status='0'");
    
    wp_cache_add( $cachekey, $feed_id );
    
    return $feed_id;
}

//получаем ИД фида текущего пользователя по ИД автора
function rcl_get_feed_author_current_user($author_id){
    global $user_ID,$wpdb;
    
    $cachekey = json_encode(array('rcl_get_feed_author_current_user',$author_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $result = $wpdb->get_var("SELECT feed_id FROM ".RCL_PREF."feeds WHERE user_id='$user_ID' AND object_id='$author_id' AND feed_type='author' AND feed_status='1'");
    
    wp_cache_add( $cachekey, $result );
    
    return $result;
}

function rcl_get_feed_callback_link($user_id,$name,$callback){
    return '<div class="callback-link user-link-'.$user_id.'">'
            .rcl_get_button($name,'#',array('icon'=>'fa-rss','class'=>'feed-callback','attr'=>'data-feed='.$user_id.' data-callback="'.$callback.'" title="'.$name.'"'))
            .'</div>';
}

//считаем кол-во подписок указанного пользователя
function rcl_feed_count_authors($user_id){
    global $wpdb;
    
    $cachekey = json_encode(array('rcl_feed_count_authors',$user_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $result = $wpdb->get_var("SELECT COUNT(feed_id) FROM ".RCL_PREF."feeds WHERE user_id='$user_id' AND feed_type='author' AND feed_status='1'");

    wp_cache_add( $cachekey, $result );
    
    return $result;
}

//считаем кол-во подписчиков указанного пользователя
function rcl_feed_count_subscribers($user_id){
    global $wpdb;
    
    $cachekey = json_encode(array('rcl_feed_count_subscribers',$user_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $result =  $wpdb->get_var("SELECT COUNT(feed_id) FROM ".RCL_PREF."feeds WHERE object_id='$user_id' AND feed_type='author' AND feed_status='1'");

    wp_cache_add( $cachekey, $result );
    
    return $result;
}

add_action('wp_ajax_rcl_feed_callback','rcl_feed_callback');
function rcl_feed_callback(){
    
    rcl_verify_ajax_nonce();
    
    $data = $_POST['data'];
    $callback = $_POST['callback'];
    $content = $callback($data);
    echo json_encode($content);
    exit;
}

function rcl_feed_content(){
    global $rcl_feed;
    echo apply_filters('rcl_feed_content',$rcl_feed->feed_content);
}

add_filter('rcl_feed_content','rcl_add_feed_content_meta',10);
function rcl_add_feed_content_meta($content){
    global $rcl_feed;

    switch($rcl_feed->feed_type){
        case 'posts':
            return $content;
            break;
        case 'comments':
            $content .= '<div class="feed-content-meta">'.__('For publication','wp-recall').' <a href="'.get_permalink( $rcl_feed->feed_parent ).'">'.get_the_title( $rcl_feed->feed_parent ).'</a></div>';
            break;
        case 'answers': $content .= '<div class="feed-content-meta">'.__('In response to','wp-recall').' <a href="'.get_comment_link( $rcl_feed->feed_parent ).'">'.__('your comment','wp-recall').'</a></div>';
            break;
        default: return $content;
    }

    return $content;
}

add_filter('rcl_feed_content','rcl_get_feed_excerpt',20);
function rcl_get_feed_excerpt($content){
    global $rcl_feed;

    if($rcl_feed->feed_type!='posts') return $content;

    remove_filter('the_content','get_notifi_bkms',20);
    remove_filter('the_content','add_button_bmk_in_content',20);

    /*$rcl_box = strpos($content, '[rcl-box');
    if($rcl_box!==false){
        $content = apply_filters('the_content',$content);
    }*/

    $content = apply_filters('the_content',$content);

    $content = strip_tags($content);

    if(( iconv_strlen($content, 'utf-8') > 500 )) {
        $content = iconv_substr($content, 0, 500, 'utf-8');
        $content = preg_replace('@(.*)\s[^\s]*$@s', '\\1', $content).'...<a href="'.get_permalink( $rcl_feed->feed_ID ).'">'.__('Read more','wp-recall').'</a>';
    }

    $content = nl2br($content);

    $thumb = get_post_meta($rcl_feed->feed_ID,'_thumbnail_id',1);
    if($thumb){
        $src = wp_get_attachment_image_src($thumb,'medium');
        $content = '<img class="aligncenter" src="' . $src[0] . '" alt="" />'.$content;
    }

    return $content;
}

add_filter('rcl_feed_content','rcl_get_feed_attachment',30);
function rcl_get_feed_attachment($content){
    global $rcl_feed;

    if($rcl_feed->feed_type!='posts'||$rcl_feed->post_type!='attachment') return $content;

    $src = wp_get_attachment_image_src($rcl_feed->feed_ID,'medium');
    $content = '<a href="'.get_permalink($rcl_feed->feed_ID).'"><img class="aligncenter" src="' . $src[0] . '" alt="" /></a>'.$content;

    return $content;
}

add_filter('rcl_feed_content','rcl_get_feed_video',40);
function rcl_get_feed_video($content){
    global $rcl_feed,$active_addons;

    if($rcl_feed->feed_type!='posts'||$rcl_feed->post_type!='video') return $content;

    if(isset($active_addons['video-gallery'])){

        $data = explode(':',$rcl_feed->feed_excerpt);
        $video = new Rcl_Video();
        $video->service = $data[0];
        $video->video_id = $data[1];
        $video->height = 300;
        $video->width = 450;
        $content = '<div class="video-iframe aligncenter">'.$video->rcl_get_video_window().'</div>'.$content;

    }

    return $content;
}

function rcl_feed_options(){
    global $rcl_feed;

    $content = '<div class="feed-options">'
            . '<i class="fa fa-times"></i>'
            . '<div class="options-box">'
                . rcl_get_feed_callback_link($rcl_feed->feed_author,__('Ignore the publication','wp-recall').' '.get_the_author_meta('display_name',$rcl_feed->feed_author),'rcl_ignored_feed_author')
            . '</div>'
        . '</div>';

    echo $content;

}

function rcl_get_author_feed_data($author_id){
    global $user_ID,$wpdb;
    
    $cachekey = json_encode(array('rcl_get_author_feed_data',$author_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $result = $wpdb->get_row("SELECT * FROM ".RCL_PREF."feeds WHERE user_id='$user_ID' AND object_id='$author_id' AND feed_type='author'");

    wp_cache_add( $cachekey, $result );
    
    return $result;
}

function rcl_ignored_feed_author($author_id){
    global $user_ID;

    $feed = rcl_get_author_feed_data($author_id);

    $args = array(
        'user_id'=>$user_ID,
        'object_id'=>$author_id,
        'feed_type'=>'author',
        'feed_status'=>0
    );

    if(!$feed){

        $result = rcl_insert_feed_data($args);

    }else{

        if(!$feed->feed_status){
            $args['feed_status'] = 1;
        }

        $args['feed_id'] = $feed->feed_id;

        $result = rcl_update_feed_data($args);

    }

    if($result){
        $data['success'] = __('The subscription status changed','wp-recall');
        $data['all'] = (!$feed||$feed->feed_status)? __('Subscribe','wp-recall'): __('Unsubscribe','wp-recall');
    }else{
        $data['error'] = 'Error';
    }

    $data['return'] = 'notice';

    return $data;
}

function rcl_get_feed_array($user_id,$type_feed='author'){
    global $wpdb;
    
    $cachekey = json_encode(array('rcl_get_feed_array',$user_id,$type_feed));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    $feeds = array();
    
    $feeds = $wpdb->get_col("SELECT object_id FROM ".RCL_PREF."feeds WHERE user_id='$user_id' AND feed_type='$type_feed' AND feed_status='1'");

    if($feeds){

        $sec_feeds = $wpdb->get_col("SELECT object_id FROM ".RCL_PREF."feeds WHERE user_id IN (".implode(',',$feeds).") AND feed_type='$type_feed' AND feed_status='1'");

        if($sec_feeds) $feeds = array_unique(array_merge($feeds,$sec_feeds));
    }
    
    wp_cache_add( $cachekey, $feeds );
    
    return $feeds;
}

