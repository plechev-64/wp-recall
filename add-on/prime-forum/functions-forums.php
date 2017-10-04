<?php

function pfm_have_subforums(){
    global $PrimeForum;
    return ($PrimeForum->subforum_count)? true: false;
}

function pfm_the_forum_name(){
    global $PrimeForum;
    echo $PrimeForum->forum_name;
}

function pfm_get_forum_name($forum_id){
    global $PrimeForum;
    
    if($PrimeForum && $PrimeForum->forum_id == $forum_id){
        return $PrimeForum->forum_name;
    }
    
    return pfm_get_forum_field($forum_id,'forum_name');
}

function pfm_the_forum_description(){
    global $PrimeForum;
    echo $PrimeForum->forum_desc;
}

function pfm_get_forum_description($forum_id){
    global $PrimeForum;
    
    if($PrimeForum && $PrimeForum->forum_id == $forum_id){
        return $PrimeForum->forum_desc;
    }
    
    return pfm_get_forum_field($forum_id,'forum_desc');
}

function pfm_the_topic_count(){
    global $PrimeForum;
    
    $topic_count = $PrimeForum->topic_count;
    
    if(pfm_have_subforums()){

        $topic_count += pfm_subforums_topic_count($PrimeForum->forum_id);

    }
    
    echo $topic_count;
}

function pfm_forum_field($field_name, $echo = 1){
    global $PrimeForum;
    
    if(isset($PrimeForum->$field_name)){
        if($echo)
            echo $PrimeForum->$field_name;
        else
            return $PrimeForum->$field_name;
    }
    
    return false;
    
}

function pfm_the_forum_classes(){
    global $PrimeForum;
    
    $classes = array(
        'prime-forum',
        'prime-forum-'.$PrimeForum->forum_id
    );
    
    $classes = apply_filters('pfm_forum_classes', $classes);

    echo implode(' ',$classes);
    
}

function pfm_the_forum_icons(){
    global $PrimeTopic,$PrimeForum;
    
    $icons = array();
    
    if($PrimeTopic){
    
        if($PrimeTopic->topic_closed){
            $icons[] = 'fa-lock';
        }

        if($PrimeTopic->topic_fix){
            $icons[] = 'fa-star';
        }
    
    }
    
    if(pfm_is_group() || pfm_is_home()){
    
        if($PrimeForum->forum_closed){
            $icons[] = 'fa-lock';
        }
    
    }
    
    $icons = apply_filters('pfm_icons', $icons);
    
    if(!$icons) return false;
    
    $content = '<div class="prime-topic-icons">';
    
    foreach($icons as $icon){
        $content .= '<div class="topic-icon">';
            $content .= '<i class="fa '.$icon.'" aria-hidden="true"></i>';
        $content .= '</div>';
    }
    
    $content .= '</div>';  
    
    echo $content;
}

function pfm_subforums_list(){
    global $PrimeForum;
    
    if(!$PrimeForum->subforum_count) return false;
    
    $content = pfm_get_subforums_list($PrimeForum->forum_id);
    
    echo $content;
}

function pfm_get_subforums($forum_id){
    return pfm_get_forums(array(
        'parent_id' => $forum_id
    ));
}

function pfm_get_subforums_list($forum_id){
    
    $childs = pfm_get_subforums($forum_id);
    
    if(!$childs) return false;
    
    $forums = array();
    foreach($childs as $child){
        $forums[] = '<a href="'.pfm_get_forum_permalink($child->forum_id).'">'.$child->forum_name.'</a>';
    }

    return implode(', ',$forums);

}

function pfm_get_forums_list(){
    
    require_once 'classes/class-prime-forums-list.php';
    
    $List = new PrimeForumsList();
    
    return $List->get_list();
    
}

add_action('pfm_delete_forum','pfm_delete_forum_metas',10);
function pfm_delete_forum_metas($forum_id){
    global $wpdb;
    return $wpdb->query("DELETE FROM ".RCL_PREF."pforum_meta WHERE object_type='forum' AND object_id='$forum_id'");
}
