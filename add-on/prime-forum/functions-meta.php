<?php

/*forum authors meta*/
function pfm_add_author_meta($user_id, $meta_key, $meta_value){
    return pfm_add_meta($user_id, 'author', $meta_key, $meta_value);
}

function pfm_get_author_meta($user_id, $meta_key){
    return pfm_get_meta($user_id, 'author', $meta_key);
}

function pfm_update_author_meta($user_id, $meta_key, $meta_value){
    return pfm_update_meta($user_id, 'author', $meta_key, $meta_value);
}

function pfm_delete_author_meta($user_id, $meta_key, $meta_value = false){
    return pfm_delete_meta($user_id, 'author', $meta_key, $meta_value);
}

/*forum groups meta*/
function pfm_add_group_meta($user_id, $meta_key, $meta_value){
    return pfm_add_meta($user_id, 'group', $meta_key, $meta_value);
}

function pfm_get_group_meta($user_id, $meta_key){
    return pfm_get_meta($user_id, 'group', $meta_key);
}

function pfm_update_group_meta($user_id, $meta_key, $meta_value){
    return pfm_update_meta($user_id, 'group', $meta_key, $meta_value);
}

function pfm_delete_group_meta($user_id, $meta_key, $meta_value = false){
    return pfm_delete_meta($user_id, 'group', $meta_key, $meta_value);
}

/*forum forums meta*/
function pfm_add_forum_meta($user_id, $meta_key, $meta_value){
    return pfm_add_meta($user_id, 'forum', $meta_key, $meta_value);
}

function pfm_get_forum_meta($user_id, $meta_key){
    return pfm_get_meta($user_id, 'forum', $meta_key);
}

function pfm_update_forum_meta($user_id, $meta_key, $meta_value){
    return pfm_update_meta($user_id, 'forum', $meta_key, $meta_value);
}

function pfm_delete_forum_meta($user_id, $meta_key, $meta_value = false){
    return pfm_delete_meta($user_id, 'forum', $meta_key, $meta_value);
}

/*forum topics meta*/
function pfm_add_topic_meta($user_id, $meta_key, $meta_value){
    return pfm_add_meta($user_id, 'topic', $meta_key, $meta_value);
}

function pfm_get_topic_meta($user_id, $meta_key){
    return pfm_get_meta($user_id, 'topic', $meta_key);
}

function pfm_update_topic_meta($user_id, $meta_key, $meta_value){
    return pfm_update_meta($user_id, 'topic', $meta_key, $meta_value);
}

function pfm_delete_topic_meta($user_id, $meta_key, $meta_value = false){
    return pfm_delete_meta($user_id, 'topic', $meta_key, $meta_value);
}

/*forum posts meta*/
function pfm_add_post_meta($user_id, $meta_key, $meta_value){
    return pfm_add_meta($user_id, 'post', $meta_key, $meta_value);
}

function pfm_get_post_meta($user_id, $meta_key){
    return pfm_get_meta($user_id, 'post', $meta_key);
}

function pfm_update_post_meta($user_id, $meta_key, $meta_value){
    return pfm_update_meta($user_id, 'post', $meta_key, $meta_value);
}

function pfm_delete_post_meta($user_id, $meta_key, $meta_value = false){
    return pfm_delete_meta($user_id, 'post', $meta_key, $meta_value);
}

function pfm_get_query_meta_value($object_id,$object_type,$meta_key){
    global $PrimeQuery;
    if(!$PrimeQuery) return false;
    return $PrimeQuery->search_meta_value($object_id,$object_type,$meta_key);
}