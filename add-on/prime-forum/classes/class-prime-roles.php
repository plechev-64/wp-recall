<?php

class PrimeRoles{

    public $roles = array();
    
    function __construct() {
        
        $this->setup_roles();
        
    }
    
    function setup_roles(){
        
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
            'post_migrate' => false,
            'member_edit' => false
        );
        
        $this->add_role('ban',array(
            'name' => __('Бан'),
            'capabilities' => array()
        ));
        
        $this->add_role('guest',array(
            'name' => __('Гость'),
            'capabilities' => array(
                'forum_view' => true,
                'post_create' => (pfm_get_option('guest-post-create'))? true: false
            )
        ));
        
        $this->add_role('member',array(
            'name' => __('Участник'),
            'capabilities' => array_merge( 
                $this->roles['guest']['capabilities'],
                array(
                    'topic_create' => true,
                    'post_create' => true,
                    'post_edit' => true,
                    'topic_edit' => true
                )
            )
        ));
        
        $this->add_role('moderator',array(
            'name' => __('Модератор'),
            'capabilities' => array_merge( 
                $this->roles['member']['capabilities'],
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
        ));
        
        $this->add_role('administrator',array(
            'name' => __('Администратор'),
            'capabilities' => array_merge( 
                $this->roles['moderator']['capabilities'],
                array(
                    'topic_delete' => true,
                    'topic_other_delete' => true,
                    'post_other_delete' => true,
                    'member_edit' => true
                )
            )
        ));
        
        
        $this->roles = apply_filters('pfm_setup_roles',$this->roles);
        
        foreach($this->roles as $role => $prop){
            $this->roles[$role]['capabilities'] = wp_parse_args( $prop['capabilities'], $capabilities );
        }
        
    }
    
    function get_capabilities($role_name){
        
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

