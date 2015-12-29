<?php

class Rcl_Feed{
    public $number = false;
    public $inpage = 20;
    public $load = 'ajax';
    public $offset = 0;
    public $paged = 0;
    public $orderby = 'ID';
    public $order = 'DESC';
    public $content = 'posts';
    public $filters = 1;
    public $query_count = false;
    public $user_id;
    public $relation = 'AND';

    function __construct($args){

        $this->init_properties($args);

        if(isset($_GET['feed-filter'])&&$this->filters) $this->content = $_GET['feed-filter'];

        $this->add_uri['feed-filter'] = $this->content;

        if($this->paged){
            $this->offset = $this->paged*$this->inpage - $this->inpage;
        }

        add_filter('rcl_feed_query',array($this,'add_query_'.$this->content));

    }

    function init_properties($args){
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
    }

    function remove_data(){
        remove_all_filters('rcl_feed_query');
        remove_all_filters('rcl_feed');
    }

    function setup_data($data){
        global $rcl_feed;

        switch($this->content){
            case 'posts':
                $array_feed = array(
                    'feed_ID'=>$data->ID,
                    'feed_content'=>$data->post_content,
                    'feed_author'=>$data->post_author,
                    'feed_title'=>$data->post_title,
                    'feed_date'=>$data->post_date,
                    'feed_parent'=>0,
                    'post_type'=>$data->post_type,
                    'feed_excerpt'=>$data->post_excerpt
                );
            break;
            case 'comments':
                $array_feed = array(
                    'feed_ID'=>$data->comment_ID,
                    'feed_content'=>$data->comment_content,
                    'feed_author'=>$data->user_id,
                    'feed_title'=>'',
                    'feed_date'=>$data->comment_date,
                    'feed_parent'=>$data->comment_post_ID,
                    'post_type'=>'',
                    'feed_excerpt'=>''
                );
            break;
            case 'answers':
                $array_feed = array(
                    'feed_ID'=>$data->comment_ID,
                    'feed_content'=>$data->comment_content,
                    'feed_author'=>$data->user_id,
                    'feed_title'=>'',
                    'feed_date'=>$data->comment_date,
                    'feed_parent'=>$data->comment_parent,
                    'post_type'=>'',
                    'feed_excerpt'=>''
                );
            break;
        }

        $array_feed['feed_type'] = $this->content;

        $rcl_feed = (object)$array_feed;

        return $rcl_feed;
    }

    function get_feed($args = false){
        global $wpdb;

        if($args) $this->init_properties($args);

        $feeds = $wpdb->get_results( $this->query() );

        $feeds = apply_filters('rcl_feed',$feeds);

        return $feeds;
    }

    function count_feed_posts(){
        global $wpdb;
        if($this->number){
            $feed = $this->get_feed();
            return count($feed);
        }else{
            return $wpdb->get_var( $this->query('count') );
        }
    }

    function query($count=false){
        global $wpdb,$rcl_options,$user_ID;

        if($count) $this->query_count = true;

        $query = array(
            'select'    => array(),
            'from'      => "$wpdb->posts AS posts",
            'join'      => array(),
            'where'     => array(),
            'relation'  => $this->relation,
            'exclude'     => array(),
            'group'     => '',
            'orderby'   => ''
        );

        $query = apply_filters('rcl_feed_query',$query);

        if($query['exclude']){
            foreach($query['exclude'] as $field=>$data){
                $query['where'][] = "posts.$field NOT IN (".implode(',',$data).")";
                break;
            }
        }

        $query_string = "SELECT "
            . implode(", ",$query['select'])." "
            . "FROM ".$query['from']." "
            . implode(" ",$query['join'])." ";

        if($query['where']) $query_string .= "WHERE ".implode(' '.$query['relation'].' ',$query['where'])." ";
        if($query['group']) $query_string .= "GROUP BY ".$query['group']." ";

        if(!$this->query_count){
            if(!$query['orderby']) $query['orderby'] = "posts.".$this->orderby;
            $query_string .= "ORDER BY ".$query['orderby']." $this->order ";
            $query_string .= "LIMIT $this->offset,$this->number";
        }

        //if(!$count) echo $query_string;

        if($this->query_count)
            $this->query_count = false;

        return $query_string;

    }

    function add_query_posts($query){
        global $wpdb,$user_ID;

        $posts = array();
        $ignored = array();

        $query['from'] = "$wpdb->posts AS posts";
        $query['where'][] = "posts.post_status='publish'";

        $feeds = $wpdb->get_col("SELECT object_id FROM ".RCL_PREF."feeds WHERE user_id='$user_ID' AND feed_type='author' AND feed_status='1'");

        $ignored = $wpdb->get_col("SELECT object_id FROM ".RCL_PREF."feeds WHERE user_id='$user_ID' AND feed_type='author' AND feed_status='0'");

        if($feeds){

            $sec_feeds = $wpdb->get_col("SELECT object_id FROM ".RCL_PREF."feeds WHERE user_id IN (".implode(',',$feeds).") AND feed_type='author' AND feed_status='1'");

            if($sec_feeds) $feeds = array_unique(array_merge($feeds,$sec_feeds));

            $posts = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_status='publish' AND post_parent='0' AND post_author IN (".implode(',',$feeds).") AND post_author NOT IN ($user_ID)");
        }

        $posts = apply_filters('rcl_feed_posts_array',$posts);

        if($posts) $query['where'][] = "posts.ID IN (".implode(',',$posts).")";

        $query['where'][] = "posts.post_type NOT IN ('page','nav_menu_item')";

        $ignored = ($ignored)? array_unique(array_merge($ignored,array($user_ID))): array($user_ID);

        $query['exclude']['post_author'] = $ignored;

        if(!$this->query_count){
            $query['select'][] = "posts.*";
            $query['orderby'] = "posts.ID";
        }else{
            $query['select'][] = "COUNT(posts.ID)";
        }

        return $query;
    }

    function add_query_comments($query){
        global $wpdb,$user_ID;

        $query['from'] = "$wpdb->comments AS comments";

        $query['where'][] = "feeds.feed_type='author'";
        $query['where'][] = "feeds.user_id='$user_ID'";
        $query['where'][] = "feeds.feed_status!='0'";
        $query['where'][] = "comments.comment_ID NOT IN (SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = '_wp_trash_meta_status' AND meta_value ='1')";

        $query['join'][] = "INNER JOIN ".RCL_PREF."feeds AS feeds ON comments.user_id=feeds.object_id";

        $query['orderby'] = "comments.comment_ID";

        if(!$this->query_count){
            $query['select'][] = "comments.*";
        }else{
            $query['select'][] = "COUNT(comments.comment_ID)";
        }

        return $query;
    }

    function add_query_answers($query){
        global $wpdb,$user_ID;

        $query['from'] = "$wpdb->comments AS comments1";

        $query['where'][] = "comments1.user_id='$user_ID'";

        $query['join'][] = "INNER JOIN $wpdb->comments AS comments2 ON comments1.comment_ID=comments2.comment_parent";

        $query['orderby'] = "comments2.comment_ID";

        if(!$this->query_count){
            $query['select'][] = "comments2.*";
        }else{
            $query['select'][] = "COUNT(comments2.comment_ID)";
        }

        return $query;
    }

    function search_request(){
        global $user_LK;

        $rqst = '';

        if(isset($_GET['search-groups'])||$user_LK){
            $rqst = array();
            foreach($_GET as $k=>$v){
                if($k=='navi'||$k=='feed-filter') continue;
                $rqst[$k] = $k.'='.$v;
            }

        }

        if($this->add_uri){
            foreach($this->add_uri as $k=>$v){
                $rqst[$k] = $k.'='.$v;
            }
        }

        $rqst = apply_filters('rcl_feed_uri',$rqst);

        return $rqst;
    }

    function get_filters($count_groups = false){
        global $post,$active_addons,$user_LK;

        if(!$this->filters) return false;

        $content = '';

        $count_groups = (false!==$count_groups)? $count_groups: $this->count_feed_posts();

        if(isset($this->add_uri['feed-filter'])) unset($this->add_uri['feed-filter']);

        $s_array = $this->search_request();

        $rqst = ($s_array)? implode('&',$s_array).'&' :'';

        $url = ($user_LK)? get_author_posts_url($user_LK): get_permalink($post->ID);

        $perm = rcl_format_url($url).$rqst;

        $filters = array(
            'posts'       => __('News','wp-recall'),
            'comments'    => __('Comments','wp-recall'),
            'answers'     => __('Answers','wp-recall')
        );

        $filters = apply_filters('rcl_feed_filter',$filters);

        $content .= '<div class="rcl-data-filters">';

        foreach($filters as $key=>$name){
            $content .= $this->get_filter($key,$name,$perm);
        }

        $content .= '</div>';

        return $content;

    }

    function get_filter($key,$name,$perm){
        return '<a class="data-filter '.rcl_a_active($this->content,$key).'" href="'.$perm.'feed-filter='.$key.'">'.$name.'</a> ';
    }
}

