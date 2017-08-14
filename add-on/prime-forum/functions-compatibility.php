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

    $filter['pfm_forum'] = __('The answers on the forum');
    
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

/*add_action('wp','pfm_init_migrate_simplepress_data');
function pfm_init_migrate_simplepress_data(){
    if(isset($_GET['simple-press-migrate']))
        pfm_migrate_simplepress_data($_GET['simple-press-migrate']);
}

function pfm_migrate_simplepress_data($dataname){
    global $wpdb;
    
    switch($dataname){
        case 'groups':
            
            $sgroups = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."sfgroups ORDER BY group_id DESC");
            
            if($sgroups){
                foreach($sgroups as $sgroup){
                    pfm_add_group(array(
                        'group_id' => $sgroup->group_id,
                        'group_name' => $sgroup->group_name,
                        'group_desc' => $sgroup->group_desc,
                        'group_seq' => $sgroup->group_seq
                    ));
                }
            }
            
        break;
        case 'forums':
            
            $sforums = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."sfforums ORDER BY forum_id DESC");
            
            if($sforums){
                foreach($sforums as $sforums){
                    pfm_add_forum(array(
                        'forum_id' => $sforums->forum_id,
                        'forum_name' => $sforums->forum_name,
                        'forum_desc' => $sforums->forum_desc,
                        'forum_slug' => $sforums->forum_slug,
                        'forum_seq' => $sforums->forum_seq,
                        'group_id' => $sforums->group_id,
                        'parent_id' => $sforums->parent,
                        'forum_closed' => 0,
                        'topic_count' => $sforums->topic_count
                    ));
                }
            }
            
        break;
        case 'topics':
            
            $stopics = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."sftopics ORDER BY topic_id DESC");
            
            if($stopics){
                foreach($stopics as $stopic){
                    
                    pfm_add_topic(array(
                        'topic_id' => $stopic->topic_id,
                        'topic_name' => $stopic->topic_name,
                        'topic_slug' => $stopic->topic_slug,
                        'forum_id' => $stopic->forum_id,
                        'user_id' => $stopic->user_id,
                        'topic_fix' => $stopic->topic_pinned,
                        'topic_closed' => $stopic->topic_status,
                        'post_count' => $stopic->post_count
                    ));
                }
            }
            
        break;
        case 'posts':
            
            $page = (isset($_GET['page']))? $_GET['page']: 1;
            $number = 500;
            $offset = ($page-1) * $number;

            $sposts = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."sfposts ORDER BY post_id DESC LIMIT $offset,$number");
            
            if($sposts){
                foreach($sposts as $spost){
                    
                    pfm_add_post(array(
                        'post_id' => $spost->post_id,
                        'post_content' => $spost->post_content,
                        'post_date' => $spost->post_date,
                        'post_edit' => $spost->post_edit,
                        'post_index' => $spost->post_index,
                        'user_id' => $spost->user_id,
                        'guest_name' => $spost->guest_name,
                        'guest_email' => $spost->guest_email,
                        'topic_id' => $spost->topic_id
                    ));
                }
                
                $page++;
                
                wp_redirect('/?simple-press-migrate=posts&page='.$page);exit;
            }
            
            
        break;
    }
    
}*/