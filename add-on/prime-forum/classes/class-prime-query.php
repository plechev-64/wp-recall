<?php

class PrimeQuery{
    
    public $vars = array();
    public $object;
    public $is_frontpage = false;
    public $is_group = false;
    public $is_forum = false;
    public $is_topic = false;
    public $is_search = false;
    public $is_page = false;
    public $groups;
    public $forums;
    public $topics;
    public $posts;
    public $authors;
    public $canonical = '';
    public $errors = array();
    public $groups_query;
    public $forums_query;
    public $topics_query;
    public $posts_query;
    public $offset = 0;
    public $number = 20;
    public $all_items = 0;
    public $current_page = 1;
    public $parent_groups = array();
    public $last = array(
        'topics' => array(),
        'posts' => array()
    );
    public $next = array(
        'group' => 0,
        'forum' => 0,
        'topic' => 0,
        'post' => 0
    );
    
    function __construct() {
        $this->init_table_query();
    }
    
    function init_query(){
        
        $this->init_vars();

        $this->init_conditions();
        
        if(!$this->is_frontpage && !$this->is_search){
            
            $this->init_queried_object();
            
            if($this->is_group && !$this->object->group_id){

                $this->errors['notice'][] = __('Группа не найдена');

            }else if($this->is_forum && !$this->object->forum_id){

                $this->errors['notice'][] = __('Форум не найден');

            }else if($this->is_topic && !$this->object->topic_id){

                $this->errors['notice'][] = __('Тема не найдена');

            }
            
            if($this->errors){
                status_header(404);
            }
            
        }

        $errors = apply_filters('pfm_check_forum_errors',$this->errors,$this);
        
        if($errors && is_array($errors)){

            $this->errors = $errors;
            
            return;
            
        }else{
            
            add_action('pfm_query_init',array($this,'add_forums_data_in_home'),10);
            add_action('pfm_query_init',array($this,'add_child_forums'),10);
            add_action('pfm_query_init',array($this,'init_canonical_url'),10);
            
            add_action('pfm_query_init',array($this,'setup_last_items'),15);
            
            $this->setup_page_data();
            
            $this->init_child_items();
            
            do_action('pfm_query_init',$this);
            
        }

    }
    
    function init_vars(){
        
        $this->vars = array(
            'pfm-group' => get_query_var('pfm-group'),
            'pfm-forum' => get_query_var('pfm-forum'),
            'pfm-topic' => get_query_var('pfm-topic'),
            'pfm-page' => get_query_var('pfm-page'),
            'search_vars' => isset($_GET['fs'])? $_GET['fs']: ''
        );

    }
    
    function init_table_query(){
        
        $this->groups_query = new PrimeGroups();
        $this->forums_query = new PrimeForums();
        $this->topics_query = new PrimeTopics();
        $this->posts_query = new PrimePosts();
        
    }
    
    function init_conditions(){

        if($this->vars['search_vars']){
            
            $this->is_search = true;
            
        }else if($this->vars['pfm-group']){
            
            $this->is_group = true;
        
        }else if($this->vars['pfm-topic']){
            
            $this->is_topic = true;

        }else if($this->vars['pfm-forum']){
            
            $this->is_forum = true;
            
        }else{
            
            $this->is_frontpage = true;
            
        }
        
        if($this->vars['pfm-page']){
            
            $this->is_page = true;

            $this->current_page = $this->vars['pfm-page'];

        }
        
    }
    
    function get_args_object(){
        
        $args = array();
        
        if($this->is_group){
            
            if ( '' != get_option('permalink_structure') ) {
                $args = array(
                    'group_slug' => $this->vars['pfm-group']
                );
            }else{
                $args = array(
                    'group_id' => $this->vars['pfm-group']
                );
            }
        
        }else if($this->is_forum){
            
            $args = array(
                'join_query' => array(
                    array(
                        'table' => $this->groups_query->query['table'],
                        'on_group_id' => 'group_id'
                    )
                )
            );
            
            if ( '' != get_option('permalink_structure') ) {
                $args['forum_slug'] = $this->vars['pfm-forum'];
            }else{
                $args['forum_id'] = $this->vars['pfm-forum'];
            }
            
        }else if($this->is_topic){
            
            $args = array(
                'join_query' => array(
                    array(
                        'table' => $this->forums_query->query['table'],
                        'on_forum_id' => 'forum_id',
                        'join_query' => array(
                            array(
                                'table' => $this->groups_query->query['table'],
                                'on_group_id' => 'group_id'
                            )
                        )
                    ),
                    array(
                        'table' => $this->posts_query->query['table'],
                        'on_topic_id' => 'topic_id',
                        'fields' => false
                    )
                )
            );
            
            if ( '' != get_option('permalink_structure') ) {
                $args['topic_slug'] = $this->vars['pfm-topic'];
                $args['join_query'][0]['forum_slug'] = $this->vars['pfm-forum'];
            }else{
                $args['topic_id'] = $this->vars['pfm-topic'];
                $args['join_query'][0]['forum_id'] = $this->vars['pfm-forum'];
            }
            
        }
        
        return apply_filters('pfm_pre_get_object',$args,$this);
        
    }
    
    function init_queried_object(){
        global $PrimeGroup,$PrimeForum,$PrimeTopic;
        
        $args = $this->get_args_object();
        
        if(!$args) return false;

        if($this->is_group){
            
            $this->object = $this->groups_query->get_row($args);
            
            $PrimeGroup = $this->object;
        
        }else if($this->is_forum){
            
            $object = $this->forums_query->get_results($args);
            
            $this->object = $object[0];
            
            $PrimeForum = $this->object;
            
        }else if($this->is_topic){
            
            $this->topics_query->reset_query();
            
            $this->topics_query->set_query($args);
            
            $this->topics_query->query['select'][] = "MAX(pfm_posts.post_date) AS last_post_date";
            
            $object = $this->topics_query->get_data('get_results');
            
            $this->object = $object[0];
            
            $PrimeTopic = $this->object;
            
        }
        
    }
    
    function get_args_child_items(){
        
        if($this->is_search){
            
            $args = array(
                'number' => $this->number,
                'offset' => $this->offset,
                'join_query' => array(
                    array(
                        'table' => $this->posts_query->query['table'],
                        'on_topic_id' => 'topic_id',
                        'fields' => false
                    )
                ),
                'groupby' => $this->topics_query->query['table']['as'].'.topic_id'
            );
            
            if($this->vars['pfm-forum']){
                $args['forum_id'] = $this->vars['pfm-forum'];
            }
            
            if($this->vars['pfm-group']){
                $args['join_query'][] = array(
                    'table' => $this->forums_query->query['table'],
                    'on_forum_id' => 'forum_id',
                    'fields' => false,
                    'group_id' => $this->vars['pfm-group']
                );
            }
            
        }else if($this->is_frontpage){
            
            $args = array(
                'number' => $this->number,
                'offset' => $this->offset,
                'order' => 'ASC',
                'orderby' => 'group_seq',
                'join_query' => array(
                    array(
                        'table' => $this->forums_query->query['table'],
                        'on_group_id' => 'group_id',
                        'fields' => false,
                        'join' => 'LEFT'
                    )
                ),
                'groupby' => $this->groups_query->query['table']['as'].'.group_id'
            );

        }else if($this->is_group && $this->object){
            
            $args = array(
                'group_id' => $this->object->group_id,
                'parent_id' => 0,
                'number' => $this->number,
                'offset' => $this->offset,
                'order' => 'ASC',
                'orderby' => 'forum_seq',
                'join_query' => array(
                    array(
                        'table' => array(
                            'name' => $this->forums_query->query['table']['name'],
                            'as' => $this->forums_query->query['table']['as'].'2',
                            'cols' => $this->forums_query->query['table']['cols']
                        ),
                        'on_forum_id' => 'parent_id',
                        'fields' => false,
                        'join' => 'LEFT'
                    )
                ),
                'groupby' => $this->forums_query->query['table']['as'].'.forum_id'
            );
            
        }else if($this->is_forum && $this->object){
            
            $args = array(
                'forum_id' => $this->object->forum_id,
                'join_query' => array(
                    array(
                        'table' => $this->posts_query->query['table'],
                        'on_topic_id' => 'topic_id',
                        'fields' => false
                    )
                ),
                'offset' => $this->offset,
                'number' => $this->number,
                'groupby' => $this->topics_query->query['table']['as'].'.topic_id'
            );
            
        }else if($this->is_topic && $this->object){
            global $wpdb;
            
            $args = array(
                'topic_id' => $this->object->topic_id,
                'number' => $this->number,
                'offset' => $this->offset,
                'order' => 'ASC',
                'orderby' => 'post_date',
                'join_query' => array(
                    array(
                        'table' => array(
                            'name' => $wpdb->users,
                            'as' => 'wp_users',
                            'cols' => array(
                                'ID',
                                'display_name',
                                'user_registered'
                            )
                        ),
                        'join' => 'LEFT',
                        'on_user_id' => 'ID',
                        'fields' => array(
                            'display_name',
                            'user_registered'
                        )
                    )
                )
            );
            
        }
        
        return apply_filters('pfm_pre_get_child_items', $args, $this);
        
    }
    
    function setup_page_data(){
        
        if($this->is_topic){
            
            $this->number = $this->posts_query->number;
            $this->all_items = $this->object->post_count;
            
        }else if($this->is_forum){
            
            $this->number = $this->topics_query->number;
            $this->all_items = $this->object->topic_count;
            
        }else if($this->is_group){
            
            $this->number = $this->forums_query->number;
            $this->all_items = $this->forums_query->count(array(
                'group_id' => $this->object->group_id
            ));
            
        }
        
        $this->offset = ($this->current_page-1) * $this->number;
        
    }
    
    function init_child_items(){

        $args = $this->get_args_child_items();
        
        if($this->is_search){
            
            $this->topics_query->reset_query();
            
            $this->topics_query->set_query($args);
            
            $this->topics_query->query['where'][] = "(pfm_topics.topic_name LIKE '%".$this->vars['search_vars']."%' "
                . "OR pfm_posts.post_content LIKE '%".$this->vars['search_vars']."%')";
            
            $this->all_items = $this->topics_query->count();
            
            $this->topics_query->query['select'] = array(
                "pfm_topics.*",
                "MAX(pfm_posts.post_date) AS last_post_date"
            );
            
            $this->topics = $this->topics_query->get_data('get_results');
            
        }else if($this->is_frontpage){
            
            $this->groups_query->reset_query();
            
            $this->groups_query->set_query($args);
            
            $this->groups_query->query['select'] = array(
                "pfm_groups.*",
                "COUNT(pfm_forums.forum_id) AS forum_count"
            );
            
            $this->groups = $this->groups_query->get_data('get_results');

        }else if($this->is_group){
            
            $this->forums_query->reset_query();
            
            $this->forums_query->set_query($args);
            
            $this->forums_query->query['select'] = array(
                "pfm_forums.*",
                "COUNT(DISTINCT pfm_forums2.forum_id) AS subforum_count"
            );
            
            $this->forums = $this->forums_query->get_data('get_results');
            
        }else if($this->object && $this->is_forum){
            
            $this->topics_query->reset_query();
            
            $this->topics_query->set_query($args);
            
            $this->topics_query->query['select'] = array(
                "pfm_topics.*",
                "MAX(pfm_posts.post_date) AS last_post_date"
            );
            
            $this->topics_query->query['orderby'] = "topic_fix DESC, MAX(pfm_posts.post_date)";
            
            $this->topics = $this->topics_query->get_data('get_results');
            
        }else if($this->object && $this->is_topic){

            $this->posts = $this->posts_query->get_results($args);
            
        }
        
    }

    function add_forums_data_in_home(){

        if(!pfm_get_option('view-forums-home')) return false;
        
        if(!$this->is_frontpage || !$this->groups) return false;
        
        $groups = (pfm_get_option('forums-home-list'))? array_map('trim',explode(',',pfm_get_option('forums-home-list'))): false;

        if(!$groups){
            $groups = array();
            foreach($this->groups as $group){
                $groups[] = $group->group_id;
            }
        }
        
        $this->parent_groups = $groups;

        $args = array(
            'group_id__in' => $groups,
            'parent_id' => 0,
            'number' => -1,
            'order' => 'ASC',
            'join_query' => array(
                array(
                    'table' => $this->groups_query->query['table'],
                    'on_group_id' => 'group_id',
                    'fields' => false,
                    'join' => 'LEFT'
                ),
                array(
                    'table' => array(
                        'name' => $this->forums_query->query['table']['name'],
                        'as' => $this->forums_query->query['table']['as'].'2',
                        'cols' => $this->forums_query->query['table']['cols']
                    ),
                    'on_forum_id' => 'parent_id',
                    'fields' => false,
                    'join' => 'LEFT'
                )
            ),
            'groupby' => $this->forums_query->query['table']['as'].'.forum_id'
        );
        
        $this->forums_query->reset_query();

        $this->forums_query->set_query($args);

        $this->forums_query->query['orderby'] = "pfm_groups.group_seq ASC, pfm_forums.forum_seq";
        $this->forums_query->query['select'] = array(
            "pfm_forums.*",
            "COUNT(DISTINCT pfm_forums2.forum_id) AS subforum_count"
        );

        $this->forums = $this->forums_query->get_data('get_results');

    }
    
    function add_child_forums(){

        if(!$this->is_forum) return false;

        $args = array(
            'group_id' => $this->object->group_id,
            'parent_id' => $this->object->forum_id,
            'number' => -1,
            'order' => 'ASC',
            'orderby' => 'forum_seq',
            'join_query' => array(
                array(
                    'table' => array(
                        'name' => $this->forums_query->query['table']['name'],
                        'as' => $this->forums_query->query['table']['as'].'2',
                        'cols' => $this->forums_query->query['table']['cols']
                    ),
                    'on_forum_id' => 'parent_id',
                    'fields' => false,
                    'join' => 'LEFT'
                )
            ),
            'groupby' => $this->forums_query->query['table']['as'].'.forum_id'
        );
        
        $this->forums_query->reset_query();

        $this->forums_query->set_query($args);
            
        $this->forums_query->query['select'] = array(
            "pfm_forums.*",
            "COUNT(DISTINCT pfm_forums2.forum_id) AS subforum_count"
        );

        $this->forums = $this->forums_query->get_data('get_results');

    }
    
    function setup_last_items(){
        
        if(!$this->is_topic){
            
            if($this->forums){

                $this->last['topics'] = $this->get_forums_last_topic($this->forums);

                $this->last['posts'] = $this->get_forums_last_post($this->forums);
                
            }
            
            if($this->topics){
                
                $posts = $this->get_topics_last_post($this->topics);

                $this->last['posts'] = $this->last['posts']? array_merge($this->last['posts'],$posts): $posts;

            }
            
            $this->last = wp_unslash($this->last);
            
        }
        
    }
    
    function get_forums_last_post($forums){
        global $wpdb;
        
        $forumIDs = array();
                
        foreach($forums as $forum){
            $forumIDs[] = $forum->forum_id;
        }

        $sql = "SELECT "
                . "posts.post_id,"
                . "posts.post_date,"
                . "posts.topic_id,"
                . "posts.user_id, "
                . "posts.forum_id "
                . "FROM ("
                    . "SELECT p.*,t.forum_id FROM ".RCL_PREF."pforum_posts AS p "
                . "INNER JOIN  ".RCL_PREF."pforum_topics AS t ON p.topic_id=t.topic_id "
                    . "WHERE t.forum_id IN (".implode(',',$forumIDs).")"
                    . "ORDER BY p.post_id DESC "
                . ") as posts "
                . "GROUP BY posts.forum_id ";

        $posts = $wpdb->get_results($sql);
        
        return $posts;
    }
    
    function get_topics_last_post($topics){
        global $wpdb;
        
        $topicIDs = array();

        foreach($topics as $topic){
            $topicIDs[] = $topic->topic_id;
        }

        $sql = "SELECT "
                . "posts.post_id,"
                . "posts.post_date,"
                . "posts.topic_id,"
                . "posts.user_id "
                . "FROM ("
                    . "SELECT * FROM ".RCL_PREF."pforum_posts "
                    . "WHERE topic_id IN (".implode(',',$topicIDs).")"
                    . "ORDER BY post_id DESC "
                . ") as posts "
                . "GROUP BY posts.topic_id ";

        $posts = $wpdb->get_results($sql);

        return $posts;
    }
    
    function get_forums_last_topic($forums){
        global $wpdb;
        
        $forumIDs = array();
                
        foreach($forums as $forum){
            $forumIDs[] = $forum->forum_id;
        }

        $sql = "SELECT "
                . "topics.topic_id,"
                . "topics.topic_name,"
                . "topics.forum_id,"
                . "topics.user_id "
                . "FROM ("
                    . "SELECT * FROM ".RCL_PREF."pforum_topics "
                    . "WHERE forum_id IN (".implode(',',$forumIDs).")"
                    . "ORDER BY topic_id DESC "
                . ") as topics "
                . "GROUP BY topics.forum_id ";

        $topics = $wpdb->get_results($sql);
        
        return $topics;
        
    }
    
    function search_forum_last_topic($forum_id){
        
        if(!$this->last['topics']) return false;
        
        foreach($this->last['topics'] as $topic){
            if($forum_id == $topic->forum_id) return $topic;
        }

        return false;
    }
    
    function search_forum_last_post($forum_id){
        
        if(!$this->last['posts']) return false;
        
        foreach($this->last['posts'] as $post){
            if(!isset($post->forum_id)) continue;
            if($forum_id == $post->forum_id) return $post;
        }

        return false;
    }
    
    function search_topic_last_post($topic_id){
        
        if(!$this->last['posts']) return false;
        
        foreach($this->last['posts'] as $post){
            if($topic_id == $post->topic_id) return $post;
        }

        return false;
    }

    function init_canonical_url(){
        
        $url = false;

        if($this->is_group){
            
            $url = pfm_get_group_permalink($this->object->group_id);

        }else if($this->is_forum){

            $url = pfm_get_forum_permalink($this->object->forum_id);

        }else if($this->is_topic){

            $url = pfm_get_topic_permalink($this->object->topic_id);

        }
        
        if($url){

            if($this->is_page){
                if ( '' != get_option('permalink_structure') ) {
                    $url = untrailingslashit($url);
                    $url .= '/page/'.$this->current_page;
                    if(preg_match("/\/$/",get_option('permalink_structure'))) $url .= '/';
                }else{
                    $url = add_query_arg(array('pfm-page' => $this->current_page), $url);
                }
            }
            
            $this->canonical = $url;
        
        }

    }
    
}

