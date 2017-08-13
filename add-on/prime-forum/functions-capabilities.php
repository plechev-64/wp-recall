<?php

function pfm_is_can($action){
    global $user_ID;
    return pfm_is_user_can($user_ID, $action);
}

function pfm_is_user_can($user_id, $action){
    global $PrimeUser;
    
    if(!$PrimeUser || $PrimeUser->user_id != $user_id){
        $PrimeUser = new PrimeUser(array('user_id' => $user_id));
    }
    
    return $PrimeUser->is_can($action);
}

function pfm_is_can_topic_edit($topic_id){
    
    if(pfm_is_can('topic_other_edit')){
        
        return true;
        
    }else{
        
        global $PrimeTopic,$user_ID;
        
        if($PrimeTopic->topic_closed) return false;
    
        $topic = ($PrimeTopic && $PrimeTopic->topic_id == $topic_id)? $PrimeTopic: pfm_get_topic($topic_id);

        if($topic->user_id == $user_ID){
            return pfm_is_can('topic_edit');
        }
        
        return false;
        
    }
 
}

function pfm_is_can_topic_delete($topic_id){
    
    if(pfm_is_can('topic_other_delete')){
        
        return true;
        
    }else{
        
        global $PrimeTopic,$user_ID;
        
        if($PrimeTopic->topic_closed) return false;
    
        $topic = ($PrimeTopic && $PrimeTopic->topic_id == $topic_id)? $PrimeTopic: pfm_get_topic($topic_id);

        if($topic->user_id == $user_ID){
            return pfm_is_can('topic_delete');
        }
        
        return false;
        
    }
 
}

function pfm_is_can_post_edit($post_id){
    
    if(pfm_is_can('post_other_edit')){
        
        return true;
        
    }else{
        
        global $PrimeTopic,$PrimePost,$user_ID;
        
        if($PrimeTopic->topic_closed) return false;
    
        $post = ($PrimePost && $PrimePost->post_id == $post_id)? $PrimePost: pfm_get_post($post_id);

        if($post->user_id == $user_ID){

            if(pfm_is_last_post($post_id)){
                return pfm_is_can('post_edit');
            }

        }

        return false;
        
    }

}

function pfm_is_can_post_delete($post_id){
    
    if(pfm_is_can('post_other_delete')){
        
        return true;
        
    }else{
        
        global $PrimeTopic,$PrimePost,$user_ID;
        
        if($PrimeTopic->topic_closed) return false;
    
        $post = ($PrimePost && $PrimePost->post_id == $post_id)? $PrimePost: pfm_get_post($post_id);

        if($post->user_id == $user_ID){

            if(pfm_is_last_post($post_id)){
                return pfm_is_can('post_delete');
            }

        }

        return false;
        
    }
 
}

function pfm_is_last_post($post_id){
    global $PrimeTopic,$PrimePost;
    
    $post = ($PrimePost && $PrimePost->post_id == $post_id)? $PrimePost: pfm_get_post($post_id);
    
    $topic = ($PrimeTopic && $PrimeTopic->topic_id == $post->topic_id)? $PrimeTopic: pfm_get_topic($post->topic_id);
    
    if($topic->last_post_date == $post->post_date) return true;
    
    return false;
    
}

function pfm_is_role($roleName){
    global $PrimeUser;
    return $PrimeUser->is_role($roleName);
}

add_filter('pfm_check_forum_errors','pfm_check_access_global_view',10);
function pfm_check_access_global_view($errors){
    global $PrimeUser;
    
    if(!$PrimeUser->is_can('forum_view'))
        $errors['error'][] = __('You are not allowed to view contents of the forum','wp-recall');
    
    return $errors;
}
