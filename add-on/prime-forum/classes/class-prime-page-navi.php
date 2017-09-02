<?php

class PrimePageNavi extends Rcl_PageNavi{
    
    function __construct() {
        
        global $PrimeQuery;
        
        parent::__construct(false,$PrimeQuery->all_items,
            array(
                'in_page' => $PrimeQuery->number,
                'current_page' => $PrimeQuery->current_page
            )
        );
    }
    
    function get_url($page_id){ 
        global $PrimeQuery;
    
        if($PrimeQuery->is_topic){
            $url = pfm_get_topic_permalink($PrimeQuery->object->topic_id);
        }else if($PrimeQuery->is_forum){
            $url = pfm_get_forum_permalink($PrimeQuery->object->forum_id);
        }else if($PrimeQuery->is_group){
            $url = pfm_get_group_permalink($PrimeQuery->object->group_id);
        }else if($PrimeQuery->is_search){
            $url = untrailingslashit(pfm_get_home_url()).'/search/';
        }
        
        if($page_id != 1){
            if ( '' != get_option('permalink_structure') ) {
                $url .= '/page/'.$page_id;
                $url = user_trailingslashit($url);
            }else{
                $url = add_query_arg(array('pfm-page' => $page_id));
            }
        }
        
        if($PrimeQuery->is_search){
            $url = add_query_arg(array('pfm-page' => $page_id, 'fs' => $PrimeQuery->vars['search_vars']));
        }

        return $url;
    }
    
}