<?php

class PrimeForm extends Rcl_Custom_Fields{
    
    public $forum_id;
    public $topic_id;
    public $post_id;
    public $onclick;
    public $action;
    public $submit;
    public $fields;
    public $forum_list = false;
    public $values = array();
    public $exclude_fields = array();

    function __construct($args = false) {
        
        $this->init_properties($args);
        
        if(!$this->action) $this->action = 'topic_create';
        if(!$this->submit) $this->submit = __('Create topic','wp-recall');
        
        if($this->forum_id){
            add_filter('pfm_form_fields', array($this, 'add_forum_field'));
            add_filter('pfm_form_fields', array($this, 'add_topic_form_custom_fields'));
        }
        
        if($this->topic_id)
            add_filter('pfm_form_fields', array($this, 'add_topic_field'));
        
        if($this->post_id)
            add_filter('pfm_form_fields', array($this, 'add_post_field'));
        
        $this->fields = $this->setup_fields();

    }
    
    function init_properties($args){
        
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
        
    }
    
    function add_topic_form_custom_fields($fields){
        
        $group_id = pfm_get_forum_field($this->forum_id,'group_id');
        
        $customFields = get_option('rcl_fields_pfm_group_'.$group_id);
        
        if($customFields){
            
            foreach($customFields as $k => $field){
                $customFields[$k]['value_in_key'] = true;
            }
            
            $fields = array_merge($fields,$customFields);
        }
        
        return $fields;
        
    }
    
    function setup_fields(){
        global $user_ID;
        
        $fields = array();
        
        if($this->forum_list){
            
            $fields[] = array(
                'type' => 'custom',
                'title' => __('Choose forum','wp-recall'),
                'content' => pfm_get_forums_list()
            );
            
        }
        
        if($this->forum_id || $this->forum_list){
            
            $fields[] = array(
                'type' => 'text',
                'slug' => 'topic_name',
                'name' => 'pfm-data[topic_name]',
                'title' => __('Heading of the topic','wp-recall'),
                'required' => 1
            );
            
        }
        
        if(!$user_ID){
            if($this->action == 'post_create'){
                $fields[] = array(
                    'type' => 'text',
                    'slug' => 'guest_name',
                    'name' => 'pfm-data[guest_name]',
                    'title' => __('Your name','wp-recall'),
                    'required' => 1
                );
                $fields[] = array(
                    'type' => 'email',
                    'slug' => 'guest_email',
                    'name' => 'pfm-data[guest_email]',
                    'title' => __('Your E-mail','wp-recall'),
                    'notice' => __('not published','wp-recall'),
                    'required' => 1
                );
            }
        }

        $fields = apply_filters('pfm_form_fields', $fields, $this->action);
        
        if($this->fields)
            $fields = array_merge($fields,$this->fields);
        
        $fields[] = array(
            'type' => 'editor',
            'editor-id' => 'action_'.$this->action,
            //'tinymce' => true,
            'slug' => 'post_content',
            'name' => 'pfm-data[post_content]',
            'title' => __('Message text','wp-recall'),
            'required' => 1,
            'quicktags' => 'strong,img,em,link,code,close,block,del'
        );
        
        if($this->exclude_fields){
            
            foreach($fields as $k => $field){
                if(in_array($field['slug'],$this->exclude_fields)){
                    unset($fields[$k]);
                }
            }

        }
        
        return $fields;
    }
    
    function add_forum_field($fields){

        $fields[] = array(
            'type' => 'hidden',
            'slug' => 'forum_id',
            'name' => 'pfm-data[forum_id]',
            'value' => $this->forum_id
        );
        
        return $fields;
    }
    
    function add_topic_field($fields){

        $fields[] = array(
            'type' => 'hidden',
            'slug' => 'topic_id',
            'name' => 'pfm-data[topic_id]',
            'value' => $this->topic_id
        );
        
        return $fields;
    }
    
    function add_post_field($fields){
        
        $fields[] = array(
            'type' => 'hidden',
            'slug' => 'post_id',
            'name' => 'pfm-data[post_id]',
            'value' => $this->post_id
        );
        
        return $fields;
    }
    
    function get_form($args = false){

        $content = '<div id="prime-topic-form-box" class="rcl-form preloader-box">';
            
            $content .= '<form id="prime-topic-form" method="post" action="">';
            
                $content .= '<div class="post-form-top">';
                $content .= apply_filters('pfm_form_top','');
                $content .= '</div>';

                foreach($this->fields as $field){

                    $value = (isset($this->values[$field['slug']]))? $this->values[$field['slug']]: false;

                    $required = (isset($field['required']) && $field['required'] == 1)? '<span class="required">*</span>': '';

                    $content .= '<div id="field-'.$field['slug'].'" class="form-field rcl-option">';

                        if(isset($field['title'])){
                            $content .= '<h3 class="field-title">';
                            $content .= $this->get_title($field).' '.$required;
                            $content .= '</h3>';
                        }

                        $content .= $this->get_input($field,$value);

                    $content .= '</div>';

                }

                $content .= '<div class="post-form-bottom">';
                $content .= apply_filters('pfm_form_bottom', '', $this->action, array('topic_id'=>$this->topic_id, 'post_id'=>$this->post_id));
                $content .= '</div>';
                
                $args = array(
                    'method' => 'get_preview',
                    'serialize_form' => 'prime-topic-form',
                    'item_id' => $this->action
                );

                $content .= '<div class="submit-box">';
                
                if(!defined( 'DOING_AJAX' )){
                    $content .= '<a href="#" title="'.__('Preview','wp-recall').'" class="recall-button" onclick=\'pfm_ajax_action('.json_encode($args).',this);return false;\'>';
                    $content .= '<i class="fa fa-eye" aria-hidden="true"></i> '.__('Preview','wp-recall');
                    $content .= '</a>';
                }
                
                if($this->onclick){
                    $content .= '<a href="#" title="'.$this->submit.'" class="recall-button" onclick=\''.$this->onclick.'\'>';
                    $content .= '<i class="fa fa-check-circle" aria-hidden="true"></i> '.$this->submit;
                    $content .= '</a>';
                }else{
                    $content .= '<input type="submit" name="Submit" class="recall-button" value="'.$this->submit.'"/>';
                }

                $content .= '</div>';
                $content .= '<input type="hidden" name="pfm-data[action]" value="'.$this->action.'">';
                $content .= '<input type="hidden" name="pfm-data[form_load]" value="'.current_time('mysql').'">';
                $content .= wp_nonce_field('pfm-action','_wpnonce',true,false);

            $content .= '</form>';
            
        $content .= '</div>';
        
        return $content;
        
    }
    
}

