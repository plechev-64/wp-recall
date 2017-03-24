<?php

class Rcl_Public_Form_Manager extends Rcl_Public_Form_Fields{
    
    function __construct($args = false) {
        
        parent::__construct($args);
        
        add_filter('rcl_custom_fields_form', array($this, 'add_content_form'),10);
        add_filter('rcl_custom_field_options', array($this, 'edit_field_options'), 10, 3);
    }
    
    function active_fields_box(){
        
        $content = $this->edit_form(
            
            array(
        
                array(
                    'type' => 'textarea',
                    'slug' => 'notice',
                    'title' => __('field description','wp-recall')
                ),
                
                array(
                    'type' => 'select',
                    'slug' => 'required',
                    'title' =>__('required field','wp-recall'),
                    'values'  => array(
                        __('No','wp-recall'),
                        __('Yes','wp-recall')
                    )
                )

            )
                
        );
        
        return $content;
        
    }
    
    function inactive_fields_box(){

        $content = '<div id="rcl-inactive-public-form-fields" class="rcl-inactive-fields-box rcl-custom-fields-box">';
        
            $content .= '<h3>'.__('Неактивные поля','wp-recall').'</h3>';

            $content .= '<form>';

                $content .= '<ul class="rcl-sortable-fields">';

                    $content .= $this->loop($this->get_inactive_fields());

                $content .= '</ul>';

            $content .= '</form>';
        
        $content .= '</div>';
        
        return $content;
        
    }
    
    function form_navi(){
        
        $post_types = get_post_types(array(
                'public'   => true,
                '_builtin' => false
            ), 'objects');
        
        $types = array('post' => __('Записи','wp-recall'));
        
        foreach ($post_types  as $post_type ) {
            $types[$post_type->name] = $post_type->label;
        }

        $content = '<div class="rcl-public-form-navi">';
        
            $content .= '<ul class="rcl-types-list">';

            foreach ($types  as $type => $name ) {
                
                $class = ($this->post_type == $type)? 'class="current-item"': '';
                
                $content .= '<li '.$class.'><a href="'.admin_url('admin.php?page=manage-public-form&post-type='.$type).'">'.$name.'</a></li>';
            }

            $content .= '</ul>';

        $content .= '</div>';
        
        if($this->post_type == 'post'){
            
            global $wpdb;
            
            $postForms = $wpdb->get_col("SELECT option_name FROM ".$wpdb->options." WHERE option_name LIKE 'rcl_fields_post_%' ORDER BY option_id ASC");
                
            $content .= '<div class="rcl-public-form-navi">';
        
                $content .= '<ul class="rcl-types-list">';
                
                foreach($postForms as $name){
                    
                    $form_id = intval(preg_replace("/[a-z_]+/", '', $name));
                    
                    if(!$form_id) continue;
                    
                    $class = ($this->form_id == $form_id)? 'class="current-item"': '';
                    
                    $content .= '<li '.$class.'><a href="'.admin_url('admin.php?page=manage-public-form&post-type='.$this->post_type.'&form-id='.$form_id).'">'.__('Форма','wp-recall').' ID: '.$form_id.'</a></li>';
                }
                
                $content .= '<li><a class="action-form" href="'.wp_nonce_url(admin_url('admin.php?page=manage-public-form&form-action=new-form&form-id='.($form_id + 1)),'rcl-form-action').'"><i class="fa fa-plus"></i> '.__('Добавить форму','wp-recall').'</a></li>';
            
                $content .= '</ul>';

            $content .= '</div>';
            
            if($this->form_id != 1){
                
                $content .= '<div class="rcl-public-form-navi">';
        
                    $content .= '<ul class="rcl-types-list">';

                    $content .= '<li><a class="action-form" href="'.wp_nonce_url(admin_url('admin.php?page=manage-public-form&form-action=delete-form&form-id='.$this->form_id),'rcl-form-action').'" onclick="return confirm(\''.__('Are you sure?','wp-recall').'\');"><i class="fa fa-trash"></i> '.__('Удалить форму','wp-recall').'</a></li>';

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
    
    function edit_field_options($options, $field, $type){
        
        if($type != $this->post_type) return $options;
        
        if($field['slug'] == 'post_uploader' || $field['slug'] == 'post_content'){
            
            foreach($options as $k => $option){
                
                if($option['slug'] == 'placeholder'){
                    unset($options[$k]);
                }
                
                if($option['slug'] == 'required'){
                    unset($options[$k]);
                }
                
            }
            
        }

        if($this->is_taxonomy_field($field['slug'])){
            
            foreach($options as $k => $option){

                if($field['slug'] == 'taxonomy-groups'){

                    if($option['slug'] == 'required'){
                        unset($options[$k]);
                    }

                    if($option['slug'] == 'values'){
                        unset($options[$k]);
                    }

                }else{
                    
                    if($option['slug'] == 'values'){
                        $options[$k]['title'] = __('Указание term_ID к выбору','wp-recall');
                    }
                    
                }
                
            }
            
        }
        
        return $options;
        
    }
    
}

