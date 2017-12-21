<?php

class Rcl_Feed_List extends Rcl_Query{
    
    public $load = 'ajax';
    public $content = 'posts';
    public $filters = 1;
    public $user_feed;
    public $paged;
    public $add_uri;

    function __construct($args = array()){
        global $user_ID;
        
        if(!$args) $args = array();
        
        if(!isset($args['user_feed']))
            $args['user_feed'] = $user_ID;
        
        if(isset($_GET['feed-filter'])) 
            $args['content'] = $_GET['feed-filter'];
        
        $args = apply_filters('rcl_feed_args', $args);

        $this->init_properties($args);

        $this->add_uri['feed-filter'] = $this->content;
        
        do_action('rcl_init_feed_'.$this->content.'_content');

        $default_types = array('posts','comments','answers');

        if(in_array($this->content,$default_types)){
            add_filter('rcl_feed_query',array($this,'setup_'.$this->content.'_query'),10,2);
            add_filter('rcl_feed_data',array($this,'setup_'.$this->content.'_data'),10,2);
        }

        $this->setup_feed_query($args);
    }
    
    function setup_feed_query($args){
        $this->query = apply_filters('rcl_feed_query',$this->query, $args);
    }

    function init_properties($args){
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
    }
    
    function count_feed(){
        return $this->count();
    }
    
    function get_feed(){
        return $this->get_data();
    }

    function remove_data(){
        remove_all_filters('rcl_feed_query');
        remove_all_filters('rcl_feed_data');
    }

    function setup_data($data){
        global $rcl_feed;
        
        $array_feed = array(
            'feed_ID',
            'feed_content',
            'feed_author',
            'feed_title',
            'feed_date',
            'feed_parent',
            'post_type',
            'feed_excerpt',
            'feed_permalink',
            'is_options'
        );

        $array_feed = apply_filters('rcl_feed_data',$array_feed,$data);
        
        $array_feed['feed_type'] = $this->content;

        $rcl_feed = (object)$array_feed;
        
        return $rcl_feed;
    }
    
    function setup_posts_data($array_feed,$data){
        
        $array_feed = array(
            'feed_ID'=>$data->ID,
            'feed_content'=>$data->post_content,
            'feed_author'=>$data->post_author,
            'feed_title'=>$data->post_title,
            'feed_date'=>$data->post_date,
            'feed_parent'=>0,
            'post_type'=>$data->post_type,
            'feed_excerpt'=>$data->post_excerpt,
            'feed_permalink'=>get_permalink($data->ID),
            'is_options'=>1
        );
        
        return $array_feed;
    }
    
    function setup_feed_comments_data($array_feed,$data){
        
        $array_feed = array(
            'feed_ID'=>$data->comment_ID,
            'feed_content'=>$data->comment_content,
            'feed_author'=>$data->user_id,
            'feed_title'=>'',
            'feed_date'=>$data->comment_date,
            'feed_parent'=> ($this->content == 'answers')? $data->comment_parent: $data->comment_post_ID,
            'post_type'=>'',
            'feed_excerpt'=>'',
            'feed_permalink'=>''
        );
        
        return $array_feed;
    }

    function setup_comments_data($array_feed,$data){
        
        return $this->setup_feed_comments_data($array_feed,$data);
        
    }
    
    function setup_answers_data($array_feed,$data){
        
        return $this->setup_feed_comments_data($array_feed,$data);
        
    }
    
    function setup_posts_query($query, $args){
        global $wpdb;

        $feeds = new Rcl_Feed_Query();
        
        //получаем игнорируемых авторов
        $authors_ignor = $feeds->get_col(array(
            'feed_type' => 'author',
            'user_id' => $this->user_feed,
            'feed_status' => 0,
            'fields' => array('object_id')
        ));
        
        $authors_ignor[] = $this->user_feed;
        
        $authors_feed = array();
        
        $usersFeed1 = $feeds->get_col(array(
            'feed_type' => 'author',
            'user_id' => $this->user_feed,
            'feed_status' => 1,
            'fields' => array('object_id')
        ));
        
        if($usersFeed1){
        
            $usersFeed2 = $feeds->get_col(array(
                'feed_type' => 'author',
                'user_id__in' => $usersFeed1,
                'feed_status' => 1,
                'fields' => array('object_id')
            ));
            
            $authors_feed = array_unique(array_merge($usersFeed1,$usersFeed2));
        
        }
        
        $defaults = array(
            'table' => array(
                'name' => $wpdb->posts,
                'as' => 'wp_posts',
                'cols' => array(
                    'ID',
                    'post_author',
                    'post_date',
                    'post_content',
                    'post_title',
                    'post_excerpt',
                    'post_parent',
                    'post_status',
                    'post_type'
                )
            ),
            'post_status' => 'publish',
            'post_parent' => 0,
            'post_author__not_in' => $authors_ignor,
            'post_author__in' => $authors_feed,
            'post_type__not_in' => array(
                'page',
                'nav_menu_item',
                'oembed_cache',
                'customize_changeset',
                'custom_css'
            ),
            'fields' => array(
                'ID',
                'post_title',
                'post_author',
                'post_date',
                'post_excerpt',
                'post_type',
                'post_content'
            )
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        $args = apply_filters('rcl_feed_posts_args',$args,$this->user_feed);

        $this->set_query($args);
        
        return apply_filters('rcl_feed_posts_query',$this->query,$this->user_feed);

    }
    
    function get_comments_table(){
        global $wpdb;
        
        return array(
            'name' => $wpdb->comments,
            'as' => 'wp_comments',
            'cols' => array(
                'comment_ID',
                'comment_post_ID',
                'comment_author',
                'comment_date',
                'comment_content',
                'comment_parent',
                'comment_approved',
                'user_id'
            )
        );
    }

    function setup_comments_query($query, $args){
        global $wpdb;
        
        $defaults = array(
            'table' => $this->get_comments_table(),
            'comment_approved' => 1,
            'user_id__not_in' => $this->user_feed,
            'fields' => array(
                'comment_ID',
                'comment_post_ID',
                'comment_author',
                'comment_date',
                'comment_content',
                'comment_parent',
                'user_id'
            )
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        $args = apply_filters('rcl_feed_comments_args',$args,$this->user_feed);

        $this->set_query($args);
        
        $this->query['join'][] = "INNER JOIN ".RCL_PREF."feeds AS rcl_feeds ON wp_comments.user_id=rcl_feeds.object_id"; 
        $this->query['where'][] = "rcl_feeds.feed_type = 'author'";
        $this->query['where'][] = "rcl_feeds.user_id = '$this->user_feed'";
        $this->query['where'][] = "rcl_feeds.feed_status = '1'";
        $this->query['where'][] = "wp_comments.comment_ID NOT IN (SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = '_wp_trash_meta_status' AND meta_value ='1')";

        return apply_filters('rcl_feed_comments_query',$this->query,$this->user_feed);

    }

    function setup_answers_query($query, $args){
        global $wpdb;
        
        $defaults = array(
            'table' => $this->get_comments_table(),
            'comment_approved' => 1,
            'user_id__not_in' => $this->user_feed,
            'comment_parent__not_in' => 0,
            'fields' => array(
                'comment_ID',
                'comment_post_ID',
                'comment_author',
                'comment_date',
                'comment_content',
                'comment_parent',
                'user_id'
            )
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        $args = apply_filters('rcl_feed_answers_args',$args,$this->user_feed);

        $this->set_query($args);
        
        $this->query['join'][] = "INNER JOIN $wpdb->comments AS wp_comments2 ON wp_comments.comment_parent = wp_comments2.comment_ID";
        $this->query['where'][] = "wp_comments2.user_id='$this->user_feed'";
        
        return apply_filters('rcl_feed_answers_query',$this->query,$this->user_feed);

    }

    function search_request(){
        global $user_LK;

        $rqst = '';

        if(isset($_GET['search-groups'])||$user_LK){
            $rqst = array();
            foreach($_GET as $k=>$v){
                if($k=='rcl-page'||$k=='feed-filter') continue;
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

    function get_filters(){
        global $post,$active_addons,$user_LK;

        if(!$this->filters) return false;

        $content = '';

        if(isset($this->add_uri['feed-filter'])) unset($this->add_uri['feed-filter']);

        $s_array = $this->search_request();

        $rqst = ($s_array)? implode('&',$s_array).'&' :'';
        
        if($user_LK){
            $url = (isset($_POST['tab_url']))? $_POST['tab_url']: get_author_posts_url($user_LK);
        }else{
            $url = get_permalink($post->ID);
        }

        $perm = rcl_format_url($url).$rqst;

        $filters = array(
            'posts'       => __('News','wp-recall'),
            'comments'    => __('Comments','wp-recall'),
            'answers'     => __('Answers in comments','wp-recall')
        );

        $filters = apply_filters('rcl_feed_filter',$filters);

        $content .= '<div class="rcl-data-filters">';

        foreach($filters as $key=>$name){
            $content .= '<a class="data-filter recall-button '.rcl_a_active($this->content,$key).'" href="'.$perm.'feed-filter='.$key.'">'.$name.'</a> ';
        }

        $content .= '</div>';

        return $content;

    }
}

