<?php

function pfm_get_canonical_url() {
    global $PrimeQuery;
    return $PrimeQuery->canonical;
}

function pfm_get_home_url(){

    if(!pfm_get_option('home-page')) return false;

    $url = user_trailingslashit(get_permalink(pfm_get_option('home-page')));
    
    return $url;
}

function pfm_get_shortlink($object_id, $object_type){
    
    if(!pfm_get_option('home-page')) return false;
    
    $url = home_url( '?p=' . pfm_get_option('home-page'). '&pfm-' . $object_type . '=' . $object_id);
    
    return $url;
    
}

function pfm_the_group_permalink(){
    global $PrimeGroup;
    echo pfm_get_group_permalink($PrimeGroup->group_id);
}

function pfm_get_group_permalink($group_id){
    global $PrimeGroup;
    
    $cachekey = json_encode(array('pfm_group_permalink',$group_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;

    if ( '' != get_option('permalink_structure') ) {

        if($PrimeGroup && $PrimeGroup->group_id == $group_id){
            $slug = $PrimeGroup->group_slug;
        }else{
            $slug = pfm_get_group_field($group_id,'group_slug');
        }

        $url = untrailingslashit(pfm_get_home_url()).'/forum-group/'.$slug;
        
        $url = user_trailingslashit($url);

    } else {

        $url = home_url(add_query_arg(array(
            'pfm-group' => $group_id,
            'pfm-forum' => false,
            'pfm-topic' => false,
            'pfm-page' => false
        )));

    }
    
    wp_cache_add( $cachekey, $url );
    
    return $url;

}

function pfm_the_forum_permalink(){
    global $PrimeForum;
    echo pfm_get_forum_permalink($PrimeForum->forum_id);
}

function pfm_get_forum_permalink($forum_id){
    global $PrimeForum;
    
    $cachekey = json_encode(array('pfm_forum_permalink',$forum_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    if ( '' != get_option('permalink_structure') ) {

        if($PrimeForum && $PrimeForum->forum_id == $forum_id){
            $slug = $PrimeForum->forum_slug;
        }else{
            $slug = pfm_get_forum_field($forum_id,'forum_slug');
        }

        $url = untrailingslashit(pfm_get_home_url()).'/'.$slug;
        
        $url = user_trailingslashit($url);

    } else {

        $url = home_url(add_query_arg(array(
            'pfm-group' => false,
            'pfm-topic' => false, 
            'pfm-page' => false, 
            'pfm-forum' => $forum_id
        )));

    }
    
    wp_cache_add( $cachekey, $url );
    
    return $url;

}

function pfm_the_topic_permalink(){
    global $PrimeTopic;
    echo pfm_get_topic_permalink($PrimeTopic->topic_id);
}

function pfm_get_topic_permalink($topic_id){
    global $PrimeTopic,$PrimeForum;
    
    $cachekey = json_encode(array('pfm_topic_permalink',$topic_id));
    $cache = wp_cache_get( $cachekey );
    if ( $cache )
        return $cache;
    
    if ( '' != get_option('permalink_structure') ) {

        if($PrimeTopic && $PrimeTopic->topic_id == $topic_id){
            
            $topic_slug = $PrimeTopic->topic_slug;
            
            if(isset($PrimeTopic->forum_slug)){
                
                $forum_slug = $PrimeTopic->forum_slug;
                
            }else if($PrimeForum){
                
                $forum_slug = $PrimeForum->forum_slug;
                
            }else{
        
                $forum_slug = pfm_get_forum_field($PrimeTopic->forum_id,'forum_slug');
                
            }
            
        }else{
            
            $TopicQuery = new PrimeTopics();
            $ForumQuery = new PrimeForums();

            $slugs = $TopicQuery->get_results(array(
                'topic_id' => $topic_id,
                'fields' => array('topic_slug'),
                'join_query' => array(
                    array(
                        'table' => $ForumQuery->query['table'],
                        'on_forum_id' => 'forum_id',
                        'fields' => array('forum_slug')
                    )
                )
            ));
            
            $topic_slug = $slugs[0]->topic_slug;
            $forum_slug = $slugs[0]->forum_slug;
            
        }

        $url = untrailingslashit(pfm_get_home_url()).'/'.$forum_slug.'/'.$topic_slug;
        
        $url = user_trailingslashit($url);

    } else {
        
        $TopicQuery = new PrimeTopics();
        
        $forum_id = $TopicQuery->get_var(array(
            'topic_id' => $topic_id,
            'fields' => array('forum_id')
        ));

        $url = home_url(add_query_arg(array(
            'pfm-forum' => $forum_id, 
            'pfm-topic' => $topic_id,
            'pfm-page' => false
        )));

    }
    
    wp_cache_add( $cachekey, $url );
    
    return $url;

}

function pfm_get_post_page_number($post_id){
    
    $post = pfm_get_post($post_id);
    
    if(!$post) return false;
    
    $post_count = pfm_get_topic_field($post->topic_id,'post_count');
    
    $PostsQuery = new PrimePosts();
    
    $lastPage = ceil($post_count / $PostsQuery->number);
    
    for($page_id = 1; $page_id <= $lastPage; $page_id++){
        $lastIndex = $PostsQuery->number * $page_id;
        if($post->post_index <= $lastIndex) break;
    }
    
    return $page_id;
    
}

function pfm_get_post_page_permalink($post_id){
    
    $topic_id = pfm_get_post_field($post_id, 'topic_id');
    
    if(!$topic_id) return false;
    
    $page_id = pfm_get_post_page_number($post_id);
    
    if(!$page_id) return false;
    
    $url = untrailingslashit(pfm_get_topic_permalink($topic_id));
    
    if($page_id != 1){
        $url = pfm_add_number_page($url, $page_id);
    }else{
        $url = user_trailingslashit($url);
    }
    
    return $url;
}

function pfm_get_post_permalink($post_id){
    
    $url = pfm_get_post_page_permalink($post_id);
    
    if(!$url) return false;

    $url .= '#topic-post-'.$post_id;

    return $url;
    
}

function pfm_add_number_page($url,$page_id){
    if ( '' != get_option('permalink_structure') ) {
        $url = untrailingslashit($url).'/page/'.$page_id;
        $url = user_trailingslashit($url);
    }else{
        $url = add_query_arg(array('pfm-page' => $page_id));
    }
    return $url;
}