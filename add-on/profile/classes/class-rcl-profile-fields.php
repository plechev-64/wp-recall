<?php

class Rcl_Profile_Fields extends Rcl_Custom_Fields_Manager{
    
    function __construct($typeFields,$args) {
        
        parent::__construct($typeFields,$args);
        
        add_filter('rcl_default_custom_fields',array($this, 'add_default_profile_fields'));
        add_filter('rcl_custom_fields_form',array($this, 'add_users_page_option'));
        add_filter('rcl_custom_field_options', array($this, 'edit_field_options'), 10, 3);
        
    }
    
    function add_default_profile_fields($fields){
        
        $fields[] = array(
            'slug' => 'first_name',
            'title' => __('Firstname','wp-recall'),
            'type' => 'text'
        );

        $fields[] = array(
            'slug' => 'last_name',
            'title' => __('Surname','wp-recall'),
            'type' => 'text'
        );

        $fields[] = array(
            'slug' => 'display_name', 
            'title' => __('Name to be displayed','wp-recall'),
            'type' => 'text'
        );

        $fields[] = array(
            'slug' => 'user_url',
            'title' => __('Website','wp-recall'),
            'type' => 'url'
        );

        $fields[] = array(
            'slug' => 'description',
            'title' => __('Status','wp-recall'),
            'type' => 'textarea'
        );
        
        $fields[] = array(
            'slug' => 'rcl_birthday',
            'title' => __('Birthday','wp-recall'),
            'type' => 'date'
        );
        
        return apply_filters('rcl_default_profile_fields', $fields);
    }

    function active_fields_box(){
        
        $content = $this->manager_form(
            
            array(
        
                array(
                    'type' => 'textarea',
                    'slug'=>'notice',
                    'title'=>__('field description','wp-recall')
                ),

                array(
                    'type' => 'radio',
                    'slug'=>'required',
                    'title'=>__('required field','wp-recall'),
                    'values'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                ),

                array(
                    'type' => 'radio',
                    'slug'=>'req',
                    'title'=>__('show the content to other users','wp-recall'),
                    'values'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                ),

                array(
                    'type' => 'radio',
                    'slug'=>'admin',
                    'title'=>__('can be changed only by the site administration','wp-recall'),
                    'values'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                ),

                array(
                    'type' => 'radio',
                    'slug'=>'filter',
                    'title'=>__('Filter users by this field','wp-recall'),
                    'values'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                )

            )
  
        );
        
        return $content;
        
    }
    
    function edit_field_options($options, $field, $type){
        
        if(!isset($field['slug']) || $type != $this->post_type) return $options;
        
        $defaultFields = array(
            'first_name',
            'last_name',
            'display_name',
            'url',
            'description'
        );
        
        if(in_array($field['slug'],$defaultFields)){
            
            foreach($options as $k => $option){
                
                if($option['slug'] == 'filter'){
                    unset($options[$k]);
                }
                
                if($field['slug'] == 'description'){
                    
                    if($option['slug'] == 'req'){
                        unset($options[$k]);
                    }
                    
                }
 
            }
            
        }
        
        return $options;
        
    }
    
    function add_users_page_option($content){
        
        $content .= '<h4>'.__('Users page','wp-recall').'</h4>'
                . '<style>#users_page_rcl{max-width:100%;}</style>'
                . wp_dropdown_pages( array(
                    'selected'   => rcl_get_option('users_page_rcl'),
                    'name'       => 'users_page_rcl',
                    'show_option_none' => __('Not selected','wp-recall'),
                    'echo'             => 0 )
                )
                .'<p>'.__('This page is required to filter users by value of profile fields','wp-recall').'</p>';
        
        return $content;
        
    }
    
}

