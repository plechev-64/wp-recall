<?php

class PrimePageNavi extends Rcl_PageNavi{
    
    public $type = 'global';
    
    function __construct($args = array()) {
        global $PrimeQuery;
        
        if($args)
            $this->init_properties($args);
        
        $currentPage = 1;
        
        if($this->type == 'global'){
            $itemsAmount = $PrimeQuery->all_items;
            $inPage = $PrimeQuery->number;
            $currentPage = $PrimeQuery->current_page;
        }else if($this->type == 'topic'){
            global $PrimeTopic;
            $itemsAmount = $PrimeTopic->post_count;
            $inPage = $PrimeQuery->posts_query->number;
        }else if($this->type == 'forum'){
            global $PrimeForum;
            $itemsAmount = $PrimeForum->topic_count;
            $inPage = $PrimeQuery->topics_query->number;
        }
        
        parent::__construct(false,$itemsAmount,
            array(
                'in_page' => $inPage,
                'current_page' => $currentPage
            )
        );
    }
    
    function init_properties($args){
        
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
        
    }
    
    function get_url($page_id){ 
        global $PrimeQuery;
    
        if($this->type == 'global'){
            if($PrimeQuery->is_topic){
                $url = pfm_get_topic_permalink($PrimeQuery->object->topic_id);
            }else if($PrimeQuery->is_forum){
                $url = pfm_get_forum_permalink($PrimeQuery->object->forum_id);
            }else if($PrimeQuery->is_group){
                $url = pfm_get_group_permalink($PrimeQuery->object->group_id);
            }else if($PrimeQuery->is_search){
                $url = untrailingslashit(pfm_get_home_url()).'/search/';
            }
        }else if($this->type == 'topic'){
            global $PrimeTopic;
            $url = pfm_get_topic_permalink($PrimeTopic->topic_id);
        }else if($this->type == 'forum'){
            global $PrimeForum;
            $url = pfm_get_forum_permalink($PrimeForum->forum_id);
        }
        
        if($page_id != 1){
            $url = pfm_add_number_page($url,$page_id);
        }
        
        if($PrimeQuery->is_search){
            $url = add_query_arg(array('pfm-page' => $page_id, 'fs' => $PrimeQuery->vars['search_vars']));
        }

        return $url;
    }
    
}