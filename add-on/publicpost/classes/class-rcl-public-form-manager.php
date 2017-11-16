<?php

class Rcl_Public_Form_Manager extends Rcl_Public_Form_Fields{
    
    function __construct($args = false) {
        
        parent::__construct($args);
        
        add_filter('rcl_custom_fields_form', array($this, 'add_content_form'),10);
    }
    
    function active_fields_box(){
        
        $defaultOptions = array(
        
            array(
                'type' => 'textarea',
                'slug' => 'notice',
                'title' => __('field description','wp-recall')
            ),

            array(
                'type' => 'radio',
                'slug' => 'required',
                'title' =>__('required field','wp-recall'),
                'values'  => array(
                    __('No','wp-recall'),
                    __('Yes','wp-recall')
                )
            )

        );
        
        $content = $this->manager_form($defaultOptions);
        
        return $content;
        
    }
    
    function form_navi(){
        
        $post_types = get_post_types(array(
                'public'   => true,
                '_builtin' => false
            ), 'objects');
        
        $types = array('post' => __('Records','wp-recall'));
        
        foreach ($post_types  as $post_type ) {
            $types[$post_type->name] = $post_type->label;
        }

        $content = '<div class="rcl-custom-fields-navi">';
        
            $content .= '<ul class="rcl-types-list">';

            foreach ($types  as $type => $name ) {
                
                $class = ($this->post_type == $type)? 'class="current-item"': '';
                
                $content .= '<li '.$class.'><a href="'.admin_url('admin.php?page=manage-public-form&post-type='.$type).'">'.$name.'</a></li>';
            }

            $content .= '</ul>';

        $content .= '</div>';
        
        if($this->post_type == 'post'){
            
            global $wpdb;
            
            $form_id = false;
            
            $postForms = $wpdb->get_col("SELECT option_name FROM ".$wpdb->options." WHERE option_name LIKE 'rcl_fields_post_%' ORDER BY option_id ASC");
                
            $content .= '<div class="rcl-custom-fields-navi">';
        
                $content .= '<ul class="rcl-types-list">';
                
                foreach($postForms as $name){
                    
                    $id = intval(preg_replace("/[a-z_]+/", '', $name));
                    
                    if(!$id) continue;
                    
                    $form_id = $id;
                    
                    $class = ($this->form_id == $form_id)? 'class="current-item"': '';
                    
                    $content .= '<li '.$class.'><a href="'.admin_url('admin.php?page=manage-public-form&post-type='.$this->post_type.'&form-id='.$form_id).'">'.__('Form','wp-recall').' ID: '.$form_id.'</a></li>';
                }
                
                $content .= '<li><a class="action-form" href="'.wp_nonce_url(admin_url('admin.php?page=manage-public-form&form-action=new-form&form-id='.($form_id + 1)),'rcl-form-action').'"><i class="fa fa-plus"></i> '.__('Add form','wp-recall').'</a></li>';
            
                $content .= '</ul>';

            $content .= '</div>';
            
            if($this->form_id != 1){
                
                $content .= '<div class="rcl-custom-fields-menu">';
        
                    $content .= '<ul class="rcl-types-list">';

                    $content .= '<li><a class="action-form" href="'.wp_nonce_url(admin_url('admin.php?page=manage-public-form&form-action=delete-form&form-id='.$this->form_id),'rcl-form-action').'" onclick="return confirm(\''.__('Are you sure?','wp-recall').'\');"><i class="fa fa-trash"></i> '.__('Delete form','wp-recall').'</a></li>';

                    $content .= '</ul>';

                $content .= '</div>';
                
            }
                
        }
        
        return $content;
        
    }

    function add_content_form($content){
        
        $content .= '<input type="hidden" name="options[user-edit]" value="1">';
        
        return $content;
        
    }
 
}

