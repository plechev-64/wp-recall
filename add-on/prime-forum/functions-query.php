<?php

function pfm_is_search(){
    global $PrimeQuery;
    return ($PrimeQuery->is_search)? true: false;
}

function pfm_is_home(){
    global $PrimeQuery;
    return ($PrimeQuery->is_frontpage)? true: false;
}

function pfm_is_forum(){
    global $PrimeQuery;
    return ($PrimeQuery->is_forum)? true: false;
}

function pfm_is_group(){
    global $PrimeQuery;
    return ($PrimeQuery->is_group)? true: false;
}

function pfm_is_topic(){
    global $PrimeQuery;
    return ($PrimeQuery->is_topic)? true: false;
}

function pfm_have_groups(){
    global $PrimeQuery;    
    return (!$PrimeQuery->groups || $PrimeQuery->errors)? false: true;
}

function pfm_have_forums(){
    global $PrimeQuery;
    return (!$PrimeQuery->forums || $PrimeQuery->errors)? false: true;
}

function pfm_have_topics(){
    global $PrimeQuery;
    return (!$PrimeQuery->topics || $PrimeQuery->errors)? false: true;
}

function pfm_have_posts(){
    global $PrimeQuery;
    return (!$PrimeQuery->posts || $PrimeQuery->errors)? false: true;
}

function pfm_reset_forumdata(){
    global $PrimeQuery,$PrimeForum;
    $PrimeForum = $PrimeQuery->object;
}

function pfm_have_errors($errors = false){
    global $PrimeQuery;
    
    if(!$errors){
        $errors = $PrimeQuery->errors;
    }
    
    if(!$errors || !is_array($errors)) return false;
    
    return true;
    
}

function pfm_get_next($type){
    global $PrimeQuery,$PrimeGroup,$PrimeForum,$PrimeTopic,$PrimePost;
    
    $nextID = $PrimeQuery->next[$type];
    
    switch($type){
        case 'group':

            if(isset($PrimeQuery->groups[$nextID])){
                
                $PrimeGroup = $PrimeQuery->groups[$nextID];
                
                $PrimeQuery->next[$type] += 1;
                
                return $PrimeGroup;
                
            }
            
        break;
        case 'forum':
            
            if(isset($PrimeQuery->forums[$nextID])){
                
                if($PrimeQuery->forums[$nextID]->group_id == $PrimeQuery->object->group_id || $PrimeQuery->forums[$nextID]->group_id == $PrimeGroup->group_id){
                
                    $PrimeForum = $PrimeQuery->forums[$nextID];

                    $PrimeQuery->next[$type] += 1;

                    return $PrimeForum;
                
                }
                
            }
            
        break;
        case 'topic':
            
            if(isset($PrimeQuery->topics[$nextID])){
                
                $PrimeTopic = $PrimeQuery->topics[$nextID];
                
                $PrimeQuery->next[$type] += 1;
                
                return $PrimeTopic;
                
            }
            
        break;
        case 'post':
            
            if(isset($PrimeQuery->posts[$nextID])){
                
                $PrimePost = $PrimeQuery->posts[$nextID];
                
                $PrimeQuery->next[$type] += 1;
                
                return $PrimePost;
                
            }

        break;
        
    }

    return false;
    
}

