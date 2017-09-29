<?php

function pfm_the_group_name(){
    global $PrimeGroup;
    echo $PrimeGroup->group_name;
}

function pfm_get_group_name($group_id){
    global $PrimeGroup;
    
    if($PrimeGroup && $PrimeGroup->group_id == $group_id){
        return $PrimeGroup->group_name;
    }
    
    return pfm_get_group_field($group_id,'group_name');
}

function pfm_the_group_description(){
    global $PrimeGroup;
    echo $PrimeGroup->group_desc;
}

function pfm_get_group_description($group_id){
    global $PrimeGroup;
    
    if($PrimeGroup && $PrimeGroup->group_id == $group_id){
        return $PrimeGroup->group_desc;
    }
    
    return pfm_get_group_field($group_id,'group_desc');
}

function pfm_the_forum_count(){
    global $PrimeGroup;
    echo $PrimeGroup->forum_count;
}

function pfm_group_field($field_name, $echo = 1){
    global $PrimeGroup;
    
    if(isset($PrimeGroup->$field_name)){
        if($echo)
            echo $PrimeGroup->$field_name;
        else
            return $PrimeGroup->$field_name;
    }
    
    return false;
    
}

function pfm_the_group_classes(){
    global $PrimeGroup,$PrimeQuery;
    
    $classes = array(
        'prime-group',
        'prime-group-'.$PrimeGroup->group_id
    );
    
    if(in_array($PrimeGroup->group_id, $PrimeQuery->parent_groups)){
        $classes[] = 'view-children-forums';
    }
    
    $classes = apply_filters('pfm_group_classes', $classes);
    
    echo implode(' ',$classes);
    
}

