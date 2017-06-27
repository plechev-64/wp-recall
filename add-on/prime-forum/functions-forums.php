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
    echo $PrimeForum->topic_count;
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

    echo implode(' ',$classes);
    
}

function pfm_the_forum_icons(){
    global $PrimeTopic,$PrimeForum;
    
    $icons = array();
    
    if(pfm_is_forum()){
    
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

function pfm_get_subforums_list($forum_id){
    
    $childs = pfm_get_forums(array(
        'parent_id' => $forum_id
    ));
    
    if(!$childs) return false;
    
    $forums = array();
    foreach($childs as $child){
        $forums[] = '<a href="'.pfm_get_forum_permalink($child->forum_id).'">'.$child->forum_name.'</a>';
    }

    return implode(', ',$forums);

}

function pfm_get_forums_list(){
    
    $groups = pfm_get_groups(array(
        'fields' => array(
            'group_id',
            'group_name'
        ),
        'order' => 'ASC',
        'orderby' => 'group_seq'
    ));
    
    $content = '<select name="pfm-data[forum_id]">';
    
    foreach($groups as $group){
        
        $forums = pfm_get_forums(array(
            'group_id' => $group->group_id,
            'fields' => array(
                'forum_id',
                'forum_name'
            ),
            'number' => -1,
            'order' => 'ASC',
            'orderby' => 'forum_name'
        ));
        
        if(!$forums) continue;
        
        $content .= '<optgroup label="'.$group->group_name.'">';
        
        foreach($forums as $forum){
            $content .= '<option value="'.$forum->forum_id.'">'.$forum->forum_name.'</option>';
        }
        
        $content .= '</optgroup>';
        
    }
    
    $content .= '</select>';
    
    return $content;
    
}
