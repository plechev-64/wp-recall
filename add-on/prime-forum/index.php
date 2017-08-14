<?php

global $PrimeQuery,$PrimeGroup,$PrimeForum,$PrimeTopic,$PrimePost;

require_once 'classes/class-prime-roles.php';
require_once 'classes/class-prime-user.php';
require_once 'classes/class-prime-visits.php';
require_once 'classes/class-prime-groups.php';
require_once 'classes/class-prime-forums.php';
require_once 'classes/class-prime-topics.php';
require_once 'classes/class-prime-posts.php';
require_once 'classes/class-prime-meta.php';
require_once 'classes/class-prime-query.php';
require_once 'classes/class-prime-form.php';
require_once 'classes/class-prime-page-navi.php';

require_once 'functions-actions.php';
require_once 'functions-capabilities.php';
require_once 'functions-compatibility.php';
require_once 'functions-database.php';
require_once 'functions-forms.php';
require_once 'functions-groups.php';
require_once 'functions-forums.php';
require_once 'functions-topics.php';
require_once 'functions-posts.php';
require_once 'functions-post-content.php';
require_once 'functions-query.php';
require_once 'functions-templates.php';
require_once 'functions-permalink.php';
require_once 'functions-seo.php';
require_once 'functions-shortcodes.php';

if (is_admin())
    require_once 'admin/index.php';

if (!is_admin()):
    add_action('rcl_enqueue_scripts','pfm_scripts',10);
endif;

function pfm_scripts(){
    rcl_enqueue_style('pfm-style', rcl_addon_url('style.css', __FILE__));
    rcl_enqueue_script('pfm-scripts', rcl_addon_url('js/scripts.js', __FILE__));
}

add_action('init','pfm_init_tab',10);
function pfm_init_tab(){

    rcl_tab(
        array(
            'id'=>'prime-forum',
            'supports' => array('ajax'),
            'name'=>__('Forum','wp-recall'),
            'public'=>0,
            'icon'=>'fa-sitemap',
            'output'=>'menu',
            'content'=>array(
                array(
                    'id'=>'my-topics',
                    'icon' => 'fa-folder',
                    'name'=>__('Started topics','wp-recall'),
                    'callback' => array(
                        'name'=>'pfm_get_user_topics_list'
                    )
                ),
                array(
                    'id'=>'my-posts',
                    'icon' => 'fa-folder',
                    'name'=>__('Messages in topics created by other users','wp-recall'),
                    'callback' => array(
                        'name'=>'pfm_user_posts_other_topics'
                    )
                )
            )
        )
    );
    
}

function pfm_user_posts_other_topics($master_id){
    global $PrimeTopic,$PrimeQuery;
    
    $PrimeQuery = new PrimeQuery();
    
    $TopicsQuery = $PrimeQuery->topics_query;
    $PostsQuery = $PrimeQuery->posts_query;
    
    $args = array(
        'user_id__not_in' => array($master_id),
        'join_query' => array(
            array(
                'table' => $PostsQuery->query['table'],
                'on_topic_id' => 'topic_id',
                'fields' => false,
                'user_id' => $master_id
            )
        )
    );
    
    $countTopics = $TopicsQuery->count($args);
    
    if(!$countTopics)
        return pfm_get_notice(__('There are no messages in topics created by other users.','wp-recall'));
    
    $pageNavi = new Rcl_PageNavi('forum',$countTopics,array('in_page'=>20));
    
    $args['groupby'] = $TopicsQuery->query['table']['as'].'.topic_id';
    $args['offset'] = $pageNavi->offset;
    $args['number'] = $pageNavi->in_page;
    
    $TopicsQuery->reset_query();
    $TopicsQuery->set_query($args);
            
    $TopicsQuery->query['select'] = array(
        "pfm_topics.*",
        "MAX(pfm_posts.post_date) AS last_post_date"
    );

    $TopicsQuery->query['orderby'] = "MAX(pfm_posts.post_date)";
    
    $topics = $TopicsQuery->get_data('get_results');

    $PrimeQuery->last['posts'] = $PrimeQuery->get_topics_last_post($topics);
    
    $theme = pfm_get_current_theme();
    
    $content = '<h3>'.__('Messaged in topics on the forum created by other users','wp-recall').'</h3>';

    $content .= '<div id="prime-forum">';
    
    $content .= $pageNavi->pagenavi();
    
    $content .= '<div class="prime-topics-list prime-loop-list">';
    foreach(wp_unslash($topics) as $PrimeTopic){
        $content .= rcl_get_include_template('pfm-single-topic.php',$theme['path']);
    }
    $content .= '</div>';
    
    $content .= $pageNavi->pagenavi();
    
    $content .= '</div>';
    
    return $content;
}

function pfm_get_user_topics_list($master_id, $navi = true){
    global $PrimeTopic,$PrimeQuery;
    
    $PrimeQuery = new PrimeQuery();
    
    $TopicsQuery = $PrimeQuery->topics_query;
    $PostsQuery = $PrimeQuery->posts_query;
    
    $countTopics = $TopicsQuery->count(array(
        'user_id' => $master_id
    ));
    
    if(!$countTopics)
        return pfm_get_notice(__('There are no started topics on the forum yet.','wp-recall'));
    
    if($navi)
        $pageNavi = new Rcl_PageNavi('forum',$countTopics,array('in_page'=>20));
    
    $args = array(
        'user_id' => $master_id,
        'offset' => $pageNavi->offset,
        'number' => $pageNavi->in_page,
        'join_query' => array(
            array(
                'table' => $PostsQuery->query['table'],
                'on_topic_id' => 'topic_id',
                'fields' => false
            )
        ),
        'groupby' => $TopicsQuery->query['table']['as'].'.topic_id'
    );
    
    $TopicsQuery->set_query($args);
            
    $TopicsQuery->query['select'] = array(
        "pfm_topics.*",
        "MAX(pfm_posts.post_date) AS last_post_date"
    );

    $TopicsQuery->query['orderby'] = "MAX(pfm_posts.post_date)";

    $topics = $TopicsQuery->get_data('get_results');
    
    $PrimeQuery->last['posts'] = $PrimeQuery->get_topics_last_post($topics);

    $theme = pfm_get_current_theme();
    
    $content = '<h3>'.__('Started topics on the forum','wp-recall').'</h3>';

    $content .= '<div id="prime-forum">';
    
    if($navi)
        $content .= $pageNavi->pagenavi();
    
    $content .= '<div class="prime-topics-list prime-loop-list">';
    foreach(wp_unslash($topics) as $PrimeTopic){
        $content .= rcl_get_include_template('pfm-single-topic.php',$theme['path']);
    }
    $content .= '</div>';

    if($navi)
        $content .= $pageNavi->pagenavi();
    
    $content .= '</div>';
    
    return $content;
    
}

add_action('pre_get_posts','pfm_init_query',10);
function pfm_init_query($wp_query){
    global $PrimeQuery,$PrimeGroup,$PrimeForum,$PrimeTopic,$PrimePost,$PrimeUser;
    
    if(!$wp_query->is_main_query()) return;
    
    if(isset($wp_query->queried_object) && $wp_query->queried_object->ID != pfm_get_option('home-page')) return;

    $PrimeUser = new PrimeUser();
    
    $PrimeQuery = new PrimeQuery();
    
    $PrimeQuery->init_query();

    do_action('pfm_init');

}

add_action('pfm_init','pfm_redirect_short_url');
function pfm_redirect_short_url(){
    global $PrimeQuery;
    
    if ( '' == get_option('permalink_structure') ) return false;
    
    if($PrimeQuery->is_search) return false;
    
    if($PrimeQuery->is_group && isset($_GET['pfm-group']) ){
        if($group_id = pfm_get_group_field($PrimeQuery->vars['pfm-group'], 'group_id')){
            wp_redirect(pfm_get_group_permalink($group_id));exit;
        }
    }
    
    if($PrimeQuery->is_forum && isset($_GET['pfm-forum']) ){
        if($forum_id = pfm_get_forum_field($PrimeQuery->vars['pfm-forum'], 'forum_id')){
            wp_redirect(pfm_get_forum_permalink($forum_id));exit;
        }
    }
    
    if($PrimeQuery->is_topic && isset($_GET['pfm-topic']) ){
        if($topic_id = pfm_get_topic_field($PrimeQuery->vars['pfm-topic'], 'topic_id')){
            wp_redirect(pfm_get_topic_permalink($topic_id));exit;
        }
    }
    
}

add_action('pfm_init','pfm_update_current_visitor',10);
function pfm_update_current_visitor(){
    global $user_ID,$PrimeQuery;
    
    if(!$user_ID) return false;
    
    $args = array(
        'user_id' => $user_ID
    );
    
    if($PrimeQuery->is_group){
        $args['group_id'] = $PrimeQuery->object->group_id;
    }else if($PrimeQuery->is_forum){
        $args['group_id'] = $PrimeQuery->object->group_id;
        $args['forum_id'] = $PrimeQuery->object->forum_id;
    }else if($PrimeQuery->is_topic){
        $args['group_id'] = $PrimeQuery->object->group_id;
        $args['forum_id'] = $PrimeQuery->object->forum_id;
        $args['topic_id'] = $PrimeQuery->object->topic_id;
    }
    
    pfm_update_visit($args);
    
}

add_filter('rcl_init_js_variables','pfm_init_js_variables',10);
function pfm_init_js_variables($data){
    global $PrimeQuery;
    
    if(!$PrimeQuery) return $data;
    
    if(!$PrimeQuery->is_forum && !$PrimeQuery->is_topic) return $data;
    
    $tags = array(
        array('pfm_pre', __('pre','wp-recall'), '<pre>', '</pre>', 'h', __('Multiline code','wp-recall'), 100),
        array('pfm_spoiler', __('Spoiler','wp-recall'), '[spoiler]', '[/spoiler]', 'h', __('Spoiler','wp-recall'), 120),
        array('pfm_offtop', __('Off-topic','wp-recall'), '[offtop]', '[/offtop]', 'h', __('Off-topic','wp-recall'), 110),
    );
    
    $tags = apply_filters('pfm_gtags',$tags);
    
    if(!$tags) return $data;

    $data['QTags'] = $tags;

    return $data;
}

function pfm_get_option($name, $default = false){
    
    $PfmOptions = get_option('rcl_pforum_options');
    
    if(!isset($PfmOptions[$name]) || $PfmOptions[$name] == ''){
        return $default;
    }
    
    return $PfmOptions[$name];
}

function pfm_get_title_tag(){
    global $PrimeQuery;
    
    if(!$PrimeQuery) return false;
        
    $object = $PrimeQuery->object;

    if(!$object) return false;
    
    if($PrimeQuery->is_topic){
        $title = pfm_replace_mask_title(pfm_get_option('mask-tag-topic', $object->topic_name.' | '.__('Forum','wp-recall').' '.$object->forum_name));
    }else if($PrimeQuery->is_forum){
        $title = pfm_replace_mask_title(pfm_get_option('mask-tag-forum', __('Forum','wp-recall').' '.$object->forum_name));
    }else if($PrimeQuery->is_group){
        $title = pfm_replace_mask_title(pfm_get_option('mask-tag-group', __('Group of forums','wp-recall').' '.$object->group_name));
    }

    if($PrimeQuery->is_page){
        $title .= ' | '.__('Page','wp-recall').' '.$PrimeQuery->current_page;
    }

    return $title;

}

function pfm_get_title_page(){
    global $PrimeQuery;
    
    if(!$PrimeQuery || !in_the_loop())return false;
        
    $object = $PrimeQuery->object;

    if(!$object) return false;

    if($PrimeQuery->is_topic){
        $title = pfm_replace_mask_title(pfm_get_option('mask-page-topic', $object->topic_name));
    }else if($PrimeQuery->is_forum){
        $title = pfm_replace_mask_title(pfm_get_option('mask-page-forum', __('Forum','wp-recall').' '.$object->forum_name));
    }else if($PrimeQuery->is_group){
        $title = pfm_replace_mask_title(pfm_get_option('mask-page-group', __('Group of forums','wp-recall').' '.$object->group_name));
    }

    if($PrimeQuery->is_page){
        $title .= ' | '.__('Page','wp-recall').' '.$PrimeQuery->current_page;
    }
        
    return $title;
}

function pfm_replace_mask_title($string){
    global $PrimeQuery;
    
    $object = $PrimeQuery->object;
    
    $mask = array();
    $replace = array();
    
    if(isset($object->group_name)){
        $mask[] = '%GROUPNAME%';
        $replace[] = $object->group_name;
    }
    
    if(isset($object->forum_name)){
        $mask[] = '%FORUMNAME%';
        $replace[] = $object->forum_name;
    }
    
    if(isset($object->topic_name)){
        $mask[] = '%TOPICNAME%';
        $replace[] = $object->topic_name;
    }
    
    if(!$mask || !$replace) return $string;
    
    return str_replace($mask, $replace, $string);
    
}

function pfm_get_current_theme(){
    return rcl_get_addon(get_option('rcl_pforum_template'));
}
