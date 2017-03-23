<?php

if ( ! class_exists( 'Rcl_EditFields' ) ) 
        include_once RCL_PATH.'functions/class-rcl-editfields.php';

class Rcl_Profile_Fields extends Rcl_EditFields{
    
    public $inactive_fields;
    
    function __construct($typeFields,$args) {
        
        parent::__construct($typeFields,$args);
        
        $this->inactive_fields = $this->get_inactive_fields();
        
        add_filter('rcl_custom_fields_form',array($this, 'add_users_page_option'));
        add_filter('rcl_custom_field_options', array($this, 'edit_field_options'), 10, 3);
        
    }
    
    function inactive_fields_box(){

        $content = '<div id="rcl-inactive-profile-fields" class="rcl-inactive-fields-box rcl-custom-fields-box">';
        
        $content .= '<h3>'.__('Неактивные поля','wp-recall').'</h3>';
        
        $content .= '<form>';
        
        $content .= '<ul class="rcl-sortable-fields">';
        
        $content .= $this->loop($this->inactive_fields);
        
        $content .= '</ul>';
        
        $content .= '</form>';
        
        $content .= '</div>';
        
        return $content;
        
    }
    
    function get_inactive_fields(){
        
        $inactive_fields = get_option('rcl_inactive_profile_fields');
        
        $inactive_fields = apply_filters('rcl_inactive_profile_fields',$inactive_fields);
        
        if($inactive_fields){
            
            foreach($inactive_fields as $k => $field){
                
                if($this->exist_active_field($field['slug'])){
                    unset($inactive_fields[$k]); continue;
                }
                
                $inactive_fields[$k]['class'] = 'must-receive';
                $inactive_fields[$k]['type-edit'] = false;
                
            }
            
        }
        
        return $inactive_fields;
        
    }
    
    function exist_active_field($slug){
        
        if(!$this->fieldsData) return false;
        
        foreach($this->fieldsData as $k => $field){
            
            if($field['slug'] == $slug){
                
                $this->fieldsData[$k]['class'] = 'must-receive';
                $this->fieldsData[$k]['type-edit'] = false;
                
                return true;
                
            }
            
        }
        
        return false;
        
    }
    
    function active_fields_box(){
        global $rcl_options;
        
        $content = $this->edit_form(
            
            array(
        
                array(
                    'type' => 'textarea',
                    'slug'=>'notice',
                    'title'=>__('field description','wp-recall')
                ),

                array(
                    'type' => 'select',
                    'slug'=>'required',
                    'title'=>__('required field','wp-recall'),
                    'values'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                ),

                array(
                    'type' => 'select',
                    'slug'=>'req',
                    'title'=>__('show the content to other users','wp-recall'),
                    'values'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                ),

                array(
                    'type' => 'select',
                    'slug'=>'admin',
                    'title'=>__('can be changed only by the site administration','wp-recall'),
                    'values'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                ),

                array(
                    'type' => 'select',
                    'slug'=>'filter',
                    'title'=>__('Filter users by this field','wp-recall'),
                    'values'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                )

            )
  
        );
        
        return $content;
        
    }
    
    function edit_field_options($options, $field, $type){
        
        if($type != $this->post_type) return $options;
        
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
 
            }
            
        }
        
        return $options;
        
    }
    
    function add_users_page_option($content){
        global $rcl_options;
        
        $content .= '<h4>'.__('Users page','wp-recall').'</h4>'
            . wp_dropdown_pages( array(
                'selected'   => $rcl_options['users_page_rcl'],
                'name'       => 'users_page_rcl',
                'show_option_none' => __('Not selected','wp-recall'),
                'echo'             => 0 )
            )
            .'<p>'.__('This page is required to filter users by value of profile fields','wp-recall').'</p>';
        
        return $content;
        
    }
    
}

