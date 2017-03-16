<?php

if ( ! class_exists( 'Rcl_EditFields' ) ) 
        include_once RCL_PATH.'functions/class-rcl-editfields.php';

class Rcl_Profile_Fileds extends Rcl_EditFields{
    
    public $inactive_fields;
    
    function __construct($typeFields,$args) {
        
        parent::__construct($typeFields,$args);
        
        $this->inactive_fields = $this->get_inactive_fields();
        
    }
    
    function inactive_fields_box(){

        $content = '<div id="rcl-inactive-profile-fields" class="rcl-custom-fields-box">';
        
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
        
                $this->option('textarea',array(
                    'name'=>'notice',
                    'label'=>__('field description','wp-recall')
                )),

                $this->option('select',array(
                    'name'=>'required',
                    'notice'=>__('required field','wp-recall'),
                    'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                )),

                $this->option('select',array(
                    'name'=>'register',
                    'notice'=>__('display in registration form','wp-recall'),
                    'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                )),

                $this->option('select',array(
                    'name'=>'order',
                    'notice'=>__('display at checkout for guests','wp-recall'),
                    'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                )),

                $this->option('select',array(
                    'name'=>'req',
                    'notice'=>__('show the content to other users','wp-recall'),
                    'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                )),

                $this->option('select',array(
                    'name'=>'admin',
                    'notice'=>__('can be changed only by the site administration','wp-recall'),
                    'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                )),

                $this->option('select',array(
                    'name'=>'filter',
                    'notice'=>__('Filter users by this field','wp-recall'),
                    'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                ))

            ),
            
            '<h4>'.__('Users page','wp-recall').'</h4>'
            . wp_dropdown_pages( array(
                'selected'   => $rcl_options['users_page_rcl'],
                'name'       => 'users_page_rcl',
                'show_option_none' => __('Not selected','wp-recall'),
                'echo'             => 0 )
            )
            .'<p>'.__('This page is required to filter users by value of profile fields','wp-recall').'</p>'       
        );
        
        return $content;
        
    }
    
    
    
}

