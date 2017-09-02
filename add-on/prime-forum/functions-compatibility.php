<?php

add_action('init','pfm_register_rating_type');
function pfm_register_rating_type(){
    
    if(!rcl_exist_addon('rating-system')) return false;
    
    rcl_register_rating_type(array(
        'rating_type'=>'forum-post',
        'type_name'=>__('Forum','wp-recall'),
        'style'=>true,
        'icon'=>'fa-weixin'
    ));
}

add_filter('rcl_feed_filter','pfm_add_feed_filter');
function pfm_add_feed_filter($filter){

    $filter['pfm_forum'] = __('The answers on the forum','wp-recall');
    
    return $filter;
}

add_action('rcl_init_feed_pfm_forum_content','pfm_init_feed');
function pfm_init_feed(){

    class PrimeFeed extends Rcl_Feed_List{
    
        function __construct(){
            add_filter('rcl_feed_query',array($this,'setup_forum_query'),10);
            add_filter('rcl_feed_data',array($this,'setup_forum_data'),10,2);
        }

        function setup_forum_data($array_feed,$data){

            $array_feed = array(
                'feed_ID'=>$data->post_id,
                'feed_content'=>$data->post_content,
                'feed_author'=>$data->user_id,
                'feed_title'=>$data->topic_name,
                'feed_date'=>$data->post_date,
                'feed_parent'=>0,
                'post_type'=>'',
                'feed_excerpt'=>'',
                'feed_permalink'=>  pfm_get_post_permalink($data->post_id)
            );

            return $array_feed;
        }

        function setup_forum_query($query){
            global $wpdb,$user_ID;
            
            $PostsQuery = new PrimePosts();

            $this->set_query(array(
                'table' => $PostsQuery->query['table'],
                'user_id__not_in' => $user_ID
            ));

            $this->query['join'][] = "INNER JOIN ".RCL_PREF."pforum_topics AS pfm_topics ON pfm_posts.topic_id=pfm_topics.topic_id";
            $this->query['join'][] = "INNER JOIN ".RCL_PREF."pforums AS pfm_forums ON pfm_topics.forum_id=pfm_forums.forum_id";
            $this->query['where'][] = "pfm_topics.user_id = '$user_ID'";

            $this->query['select'][] = "pfm_topics.topic_name,"
                                    . "pfm_topics.topic_slug,"
                                    . "pfm_forums.forum_slug";
            
            $this->query['orderby'] = "pfm_posts.post_id";
            
            return $this->query;

        }

    }
    
    $PrimeFeed = new PrimeFeed();
    
}