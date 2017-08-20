<?php

class PrimeLastPosts{
    
    public $number = 5;
    public $name_length = 30;
    public $post_length = 120;
    public $avatar_size = 40;
    public $topics = array();
    public $posts = array();
    
    function __construct($args) {
        
        $this->init_properties($args);
        
        $this->topics = $this->get_topics();
        $this->posts = $this->get_posts();
        
    }
    
    function init_properties($args){
        
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
        
    }
    
    function get_topics(){
        global $wpdb;
        
        $cachekey = 'pfm_last_topics';
        $cache = wp_cache_get( $cachekey );
        if ( $cache )
            return $cache;
        
        $topics = $wpdb->get_results(
            "SELECT "
                . "ptopics.* "
            . "FROM ".RCL_PREF."pforum_topics AS ptopics "
            . "INNER JOIN ".RCL_PREF."pforum_posts AS pposts ON ptopics.topic_id = pposts.topic_id "
            . "GROUP BY ptopics.topic_id "
            . "ORDER BY MAX(pposts.post_date) DESC "
            . "LIMIT $this->number"
        );
        
        $topics = wp_unslash($topics);
        
        wp_cache_add( $cachekey, $topics );
        
        if(!$topics) return false;
        
        return $topics;
        
    }
    
    function get_posts(){
        global $wpdb;
        
        if(!$this->topics) return false;
        
        $tIDs = array();
        foreach($this->topics as $topic){
            $tIDs[] = $topic->topic_id;
        }

        $posts = $wpdb->get_results(
            "SELECT "
                . "posts.topic_id,"
                . "posts.post_id,"
                . "posts.post_content,"
                . "posts.user_id "
            . "FROM "
            . RCL_PREF."pforum_posts AS posts "
            . "WHERE "
                . "posts.topic_id IN (".implode(",",$tIDs).") "
            . "ORDER BY posts.post_date DESC"
        );
        
        if(!$posts) return false;
        
        return wp_unslash($posts);
    }
    
    function string_trim($string,$length){
        
        if( iconv_strlen($string = strip_tags($string), 'utf-8') > $length ) {
            $string = iconv_substr($string, 0, $length, 'utf-8');
            $string = preg_replace('@(.*)\s[^\s]*$@s', '\\1', $string).'...';
        }
        
        return $string;
        
    }
    
    function get_post_by_topic($topic_id){
        
        if(!$this->posts) return false;
        
        foreach($this->posts as $post){
            if($post->topic_id == $topic_id) return $post;
        }
        
        return false;
        
    }
    
    function get_content(){
        
        if(!$this->topics) return false;

        $content = '<div class="prime-last-posts">';
            $content .= '<ul class="last-post-list">';

            foreach ($this->topics as $topic) {
                
                $post = $this->get_post_by_topic($topic->topic_id);
                
                $url = pfm_get_post_permalink($post->post_id);

                $content .= '<li class="last-post-box">';
                
                    if($this->avatar_size){
                        $content .= '<div class="last-post-author-avatar">
                            <a href="'.$url.'">'.get_avatar( $post->user_id, $this->avatar_size ).'</a>
                        </div>';
                    }
                    
                    if($this->name_length){
                        $content .= '<div class="last-post-title">
                            <a href="'.$url.'">
                                '.($topic->topic_closed? '<i class="fa fa-lock"></i>':'').' '.$this->string_trim($topic->topic_name,$this->name_length).'
                            </a>
                        </div>';
                    }

                    if($this->post_length){
                        $content .= '<div class="last-post-content">
                            '.$this->string_trim($post->post_content,$this->post_length).' '
                            . '<a class="last-post-more" href='.$url.'> '.__('Read more','wp-recall').'</a>
                        </div>';
                    }
                    
                $content .= '</li>';
            }

            $content .= '</ul>';
        $content .= '</div>';

        return $content;
    }
    
}
