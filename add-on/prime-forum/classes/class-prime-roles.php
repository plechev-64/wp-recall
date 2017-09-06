<?php

class PrimeRoles{

    public $roles = array();
    
    function __construct() {
        
        $this->setup_roles();
        
    }
    
    function get_default_roles(){
        
        $defaultRoles = array();
        
        $defaultRoles['ban'] = array(
            'name' => __('Ban','wp-recall'),
            'capabilities' => array()
        );

        $defaultRoles['guest'] = array(
            'name' => __('Guest','wp-recall'),
            'capabilities' => array(
                'forum_view' => true,
                'post_create' => (pfm_get_option('guest-post-create'))? true: false
            )
        );
        
        $defaultRoles['member'] = array(
            'name' => __('Member','wp-recall'),
            'capabilities' => array_merge( 
                $defaultRoles['guest']['capabilities'],
                array(
                    'topic_create' => true,
                    'post_create' => true,
                    'post_edit' => true,
                    'topic_edit' => true
                )
            )
        );

        $defaultRoles['moderator'] = array(
            'name' => __('Moderator','wp-recall'),
            'capabilities' => array_merge( 
                $defaultRoles['member']['capabilities'],
                array(
                    'topic_other_edit' => true,
                    'topic_fix' => true,
                    'topic_close' => true,
                    'topic_migrate' => true,
                    'post_other_edit' => true,
                    'post_migrate' => true,
                    'post_delete' => true
                )
            )
        );
        
        $defaultRoles['administrator'] = array(
            'name' => __('Administrator','wp-recall'),
            'capabilities' => array_merge( 
                $defaultRoles['moderator']['capabilities'],
                array(
                    'topic_delete' => true,
                    'topic_other_delete' => true,
                    'post_other_delete' => true
                )
            )
        );
        
        $defaultRoles = apply_filters('pfm_default_roles', $defaultRoles);

        return $defaultRoles;
        
    }
    
    function get_capabilities(){
        
        $capabilities = array(
            'forum_view' => false,
            'topic_create' => false,
            'topic_delete' => false,
            'topic_edit' => false,
            'topic_other_delete' => false,
            'topic_other_edit' => false,
            'topic_fix' => false,
            'topic_close' => false,
            'topic_migrate' => false,
            'post_create' => false,
            'post_edit' => false,
            'post_delete' => false,
            'post_other_edit' => false,
            'post_other_delete' => false,
            'post_migrate' => false
        );
        
        $capabilities = apply_filters('pfm_capabilities', $capabilities);
        
        return $capabilities;
        
    }
    
    function setup_roles(){
        
        $capabilities = $this->get_capabilities();
        
        $defaultRoles = $this->get_default_roles();

        $this->roles = apply_filters('pfm_roles',$defaultRoles);
        
        foreach($this->roles as $role => $prop){
            $this->roles[$role]['capabilities'] = wp_parse_args( $prop['capabilities'], $capabilities );
        }
        
    }
    
    function get_role_capabilities($role_name){
        
        $role = $this->get_role($role_name);
        
        if(!$role) return false;
        
        return $role['capabilities'];
    }
    
    function add_role($role, $prop){
        $this->roles[$role] = $prop;
    }
    
    function get_role($role){
        return $this->roles[$role];
    }
    
    function delete_role($role){
        unset($this->roles[$role]);
    }
    
}

