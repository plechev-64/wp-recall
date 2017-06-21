<?php

class Prime_Form_Manager extends Rcl_Custom_Fields_Manager{
    
    function __construct($args = false) {
        
        parent::__construct($args['form_slug']);
        
        //add_filter('rcl_custom_fields_form', array($this, 'add_content_form'),10);
    }
    
    function active_fields_box(){
        
        $defaultOptions = array(
        
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

        );
        
        $content = $this->manager_form($defaultOptions);
        
        return $content;
        
    }
    
    function form_navi(){
        
        $groups = pfm_get_groups(array(
            'order' => 'ASC',
            'orderby' => 'group_seq'
        ));

        $types = array();
        foreach ($groups  as $group ) {
            $types[$group->group_id] = $group->group_name;
        }
        
        $content = '<div class="rcl-custom-fields-navi">';
        
            $content .= '<ul class="rcl-types-list">';

            foreach ($types  as $group_id => $name ) {
                
                $class = ($this->post_type == 'pfm_group_'.$group_id)? 'class="current-item"': '';
                
                $content .= '<li '.$class.'><a href="'.admin_url('admin.php?page=manage-topic-form&group-id='.$group_id).'">'.$name.'</a></li>';
            }

            $content .= '</ul>';

        $content .= '</div>';

        return $content;
        
    }

    function add_content_form($content){
        
        $content .= '<input type="hidden" name="options[user-edit]" value="1">';
        
        return $content;
        
    }
 
}

