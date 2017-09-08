<?php

class PrimeUser extends PrimeRoles{
    
    public $user_id;
    public $user_role;
    public $default_role = 'member';
    public $user_capabilities;
    
    function __construct($args = array()) {
        global $user_ID;

        if(!isset($args['user_id']))
            $args['user_id'] = $user_ID;
        
        $args = apply_filters('pfm_setup_user', $args);
        
        $this->init_properties($args);
        
        parent::__construct();

        if(!$this->user_role)
            $this->user_role = $this->user_id? $this->get_user_role($this->user_id): 'guest';
        
        $this->user_capabilities = $this->get_role_capabilities($this->user_role);
        
    }
    
    function init_properties($args){
        
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
        
    }
    
    function get_user_role($user_id){
        global $PrimeUser;
        
        if(!$user_id) return 'guest';
        
        if($PrimeUser && $PrimeUser->user_id == $user_id){
            $role = $PrimeUser->user_role;
        }else{
            $role = get_user_meta($user_id, 'pfm_role', 1);
        }
        
        $role = apply_filters('pfm_user_role', $role, $user_id);
        
        return ($this->get_role($role))? $role: $this->default_role;
    }
    
    function is_can($action){
        
        if(!isset($this->user_capabilities[$action])) return false;
        
        return apply_filters('pfm_is_can',$this->user_capabilities[$action],$action,$this->user_id);
        
    }
    
    function get_user_rolename($user_id){
        $roleID = $this->get_user_role($user_id);
        $role = $this->get_role($roleID);
        return $role['name'];
    }
    
    function is_role($roleName){
        
        if(is_array($roleName)){
            if(in_array($this->user_role, $roleName)) return true;
        }else{
            if($roleName = $this->user_role) return true;
        }
        
        return false;
        
    }
    
    function is_can_posts($topic_id){
        
        $posts = new PrimePosts();

        $post_id = $posts->get_var(array(
            'topic_id' => $topic_id,
            'fields' => array('post_id'),
            'orderby' => 'post_id',
            'order' => 'ASC',
            'user_id' => $this->user_id
        ));
        
        return $post_id? true: false;
        
    }
    
}

