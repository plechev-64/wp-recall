<?php

class PrimeForumsList{
    
    public $forums = array();
    public $groups = array();
    public $parents = array();
    
    function __construct(){
        $this->groups = $this->get_groups();
        $this->forums = $this->get_forums();
    }
    
    function get_forums(){
        
        $PrimeForums = new PrimeForums();
        
        return $PrimeForums->get_results(array(
            'fields' => array(
                'forum_id',
                'group_id',
                'forum_name',
                'parent_id'
            ),
            'number' => -1,
            'order' => 'ASC',
            'orderby' => 'forum_name'
        ));
    }
    
    function get_groups(){
        
        $PrimeGroups = new PrimeGroups();
        
        return $PrimeGroups->get_results(array(
            'fields' => array(
                'group_id',
                'group_name'
            ),
            'order' => 'ASC',
            'number' => -1,
            'orderby' => 'group_seq'
        ));
    }
    
    function get_parent_forums($group_id){
        
        if(!$this->forums) return false;
        
        $forums = array();
        
        foreach($this->forums as $forum){
            
            if($forum->parent_id){
                $this->parents[] = $forum->parent_id; continue;
            }
            
            if($group_id != $forum->group_id) continue;
            
            $forums[] = $forum;
        }
        
        array_unique($this->parents);
        
        return $forums;
        
    }
    
    function get_children_forums($forum_id){
        
        $forums = array();
        
        foreach($this->forums as $forum){
            
            if($forum_id != $forum->parent_id) continue;
            
            $forums[] = $forum;
        }
        
        return $forums;
        
    }
    
    function get_list(){

        $content = '<select name="pfm-data[forum_id]">';

        foreach($this->groups as $group){

            $forums = $this->get_parent_forums($group->group_id);
            
            if(!$forums) continue;

            $content .= '<optgroup label="'.$group->group_name.'">';

            $content .= $this->get_forums_list($forums, 0);

            $content .= '</optgroup>';

        }

        $content .= '</select>';

        return $content;

    }
    
    function get_forums_list($forums, $level){
        
        $content = '';
        
        foreach($forums as $forum){
                
            $content .= '<option value="'.$forum->forum_id.'">'.str_pad('', $level*3, "-- ", STR_PAD_LEFT).$forum->forum_name.'</option>';

            if(!in_array($forum->forum_id, $this->parents)) continue;
            
            $childrens = $this->get_children_forums($forum->forum_id);
            
            $content .= $this->get_forums_list($childrens, $level + 1);
            
        }
        
        return $content;
        
    }
    
}

