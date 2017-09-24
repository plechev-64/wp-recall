<?php

add_filter('page_rewrite_rules', 'pfm_set_rewrite_rules');
function pfm_set_rewrite_rules($rules) {
    global $wp_rewrite;
    
    if(!pfm_get_option('home-page')) return $rules;
    
    $page = get_post(pfm_get_option('home-page'));
    $slugmatch = $page->post_name;
    if ($wp_rewrite->using_index_permalinks() && $wp_rewrite->root == 'index.php/') $slugmatch = 'index.php/'.$slugmatch;

    $rules[$slugmatch.'/forum-group/([^/]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-group=$matches[1]';
    $rules[$slugmatch.'/forum-group/([^/]+)/page/([0-9]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-group=$matches[1]&pfm-page=$matches[2]';
    
    $rules[$slugmatch.'/([^/]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-forum=$matches[1]';
    $rules[$slugmatch.'/([^/]+)/([^/]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-forum=$matches[1]&pfm-topic=$matches[2]';

    $rules[$slugmatch.'/([^/]+)/page/([0-9]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-forum=$matches[1]&pfm-page=$matches[2]';
    $rules[$slugmatch.'/([^/]+)/([^/]+)/page/([0-9]+)/?$'] = 'index.php?pagename='.$page->post_name.'&pfm-forum=$matches[1]&pfm-topic=$matches[2]&pfm-page=$matches[3]';
    
    return $rules;
}

add_filter('query_vars', 'pfm_set_query_vars');
function pfm_set_query_vars($vars) {
	
    $vars[] = 'pfm-group';
    $vars[] = 'pfm-forum';
    $vars[] = 'pfm-topic';
    $vars[] = 'pfm-page';

    $vars = apply_filters('pfm_query_vars', $vars);

    return $vars;
}

add_action('pfm_init', 'pfm_init_noindex_meta_tag', 10);
function pfm_init_noindex_meta_tag(){
    
    if(pfm_is_search() || pfm_is_author()){
        add_action('wp_head', 'pfm_print_noindex_meta_tag', 10);
    }
    
}

function pfm_print_noindex_meta_tag(){
    echo '<meta name="robots" content="noindex, nofollow" />'."\n";
}

add_action('pfm_init','pfm_add_seo_filters',10);
function pfm_add_seo_filters(){
    
    add_filter('the_title','pfm_setup_page_title',30);
    add_filter('document_title_parts','pfm_replace_title',30);
    add_filter('wp_title','pfm_replace_title',30);

    add_filter('get_canonical_url', 'pfm_replace_canonical_url',30);
    add_filter('get_shortlink', 'pfm_replace_shortlink',30);
    
    add_filter('aioseop_canonical_url', 'pfm_replace_canonical_url',30);
    add_filter('aioseop_description', 'pfm_replace_description',30);
    add_filter('aioseop_title_page', 'pfm_replace_title',30);
    
    add_filter('wpseo_title','pfm_replace_title',30);
    add_filter('wpseo_canonical','pfm_replace_canonical_url',30);
    add_filter('wpseo_opengraph_url','pfm_replace_canonical_url',30);
    add_filter('wpseo_metadesc','pfm_replace_description',30);
      
}


function pfm_replace_title($title){
    
    $pfmTitle = pfm_get_title_tag();
    
    if($pfmTitle){
        
        if(is_array($title)){
            $title = array('title' => $pfmTitle);
        }else{
            $title = $pfmTitle;
        }
    }
    
    return $title;
}

function pfm_setup_page_title($title){
    
    $pfmTitle = pfm_get_title_page();
    
    if($pfmTitle)
        $title = $pfmTitle;
    
    return $title;
}

function pfm_replace_shortlink($url){
    global $PrimeQuery;
    
    return false;
    
    if($PrimeQuery->is_frontpage) return $url;
    
    if($PrimeQuery->is_page) return false;
    
    if($PrimeQuery->is_group){
            
        $object_id = $PrimeQuery->object->group_id;
        $object_type = 'group';

    }else if($PrimeQuery->is_forum){

        $object_id = $PrimeQuery->object->forum_id;
        $object_type = 'forum';

    }else if($PrimeQuery->is_topic){

        $object_id = $PrimeQuery->object->topic_id;
        $object_type = 'topic';

    }
    
    $pfmUrl = pfm_get_shortlink($object_id, $object_type);
    
    if($pfmUrl)
        $url = $pfmUrl;
    
    return $url;
}

function pfm_replace_canonical_url($url){
    
    $pfmUrl = pfm_get_canonical_url();
    
    if($pfmUrl)
        $url = $pfmUrl;
    
    return $url;
}

function pfm_replace_description($aioseo_descr) {
    global $PrimeQuery;
    
    if($PrimeQuery->is_group){
            
        $aioseo_descr = $PrimeQuery->object->group_desc;

    }else if($PrimeQuery->is_forum){

        $aioseo_descr = $PrimeQuery->object->forum_desc;

    }else if($PrimeQuery->is_topic){

        $aioseo_descr = wp_trim_words($PrimeQuery->posts[0]->post_content,50);

    }
    
    return $aioseo_descr;
}