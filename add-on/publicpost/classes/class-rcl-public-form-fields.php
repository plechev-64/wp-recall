<?php

class Rcl_Public_Form_Fields extends Rcl_Custom_Fields_Manager{

    public $taxonomies;
    public $form_id = 1;
    
    function __construct($args = false) {
        
        $this->post_type = (isset($args['post_type']))? $args['post_type']: 'post';
        $this->form_id = (isset($args['form_id']) && $args['form_id'])? $args['form_id']: 1;
        
        parent::__construct($this->post_type,array(
            'id'=>$this->form_id, 
            'custom-slug'=>1, 
            'terms'=>1,
            'meta_delete'=>true
            )
        );

        $this->taxonomies = get_object_taxonomies( $this->post_type, 'objects' );
        
        if($this->post_type == 'post'){
            unset($this->taxonomies['post_format']);
        }

        add_filter('rcl_default_custom_fields',array($this, 'add_default_public_form_fields'));
        add_filter('rcl_custom_field_options', array($this, 'edit_field_options'), 10, 3);
        
        $this->fields = $this->get_fields();
        
    }

    function get_fields(){
        global $user_ID;

        $fields = $this->fields;
		
        if(!$fields)
            $fields = array();
        
        if(!isset($fields['options']['user-edit']) || !$fields['options']['user-edit']){

            $fields = array_merge($this->get_default_public_form_fields(), $fields);
            
        }

        if(isset($fields['options'])){
            
            $this->fields_options = $fields['options'];
            
            unset($fields['options']);
            
        }
        
        foreach($fields as $k => $field){
            
            if(!isset($field['value_in_key']))
                $fields[$k]['value_in_key'] = true;
            
        }

        return $fields;
        
    }
    
    function add_default_public_form_fields($fields){
        return array_merge($fields,$this->get_default_public_form_fields());
    }
    
    function get_default_public_form_fields(){
        
        $fields[] = array(
            'slug' => 'post_title',
            'maxlength' => 100,
            'title' => __('Title','wp-recall'),
            'type' => 'text'
        );
        
        if($this->taxonomies){
            
            foreach($this->taxonomies as $taxonomy => $object){
                
                if($this->is_hierarchical_tax($taxonomy)){
                
                    $label = $object->labels->name;

                    if($taxonomy == 'groups')
                        $label = __('Group category','wp-recall');
                    
                    $options = array();
                    
                    if($taxonomy != 'groups'){
                        
                        $options = array(
                            array(
                                'type' => 'number',
                                'slug' => 'number-select',
                                'title' => __('Amount to choose','wp-recall'),
                                'notice' => __('only when output through select','wp-recall')
                            ),
                            array(
                                'type' => 'select',
                                'slug' => 'type-select',
                                'title' => __('Output option','wp-recall'),
                                'values' => array(
                                    'select' => __('Select','wp-recall'),
                                    'checkbox' => __('Checkbox','wp-recall'),
                                    'multiselect' => __('Multiselect','wp-recall')
                                )
                            ),
                            array(
                                'type' => 'select',
                                'slug' => 'only-child',
                                'title' => __('Only child terms','wp-recall'),
                                'notice' => __('Attach only the selected child terms to the post, ignoring parents','wp-recall'),
                                'values' => array(
                                    __('Disable','wp-recall'),
                                    __('Enable','wp-recall')
                                )
                            )
                        );
                        
                    }

                    $fields[] = array(
                        'slug' => 'taxonomy-'.$taxonomy,
                        'title' => $label,
                        'type' => 'select',
                        'options-field' => $options
                    );
                
                }
                
            }
            
        }
        
        $fields[] = array(
            'slug' => 'post_excerpt',
            'maxlength' => 200,
            'title' => __('Short entry','wp-recall'),
            'type' => 'textarea'
        );

        $fields[] = array(
            'slug' => 'post_content',
            'title' => __('Content of the publication','wp-recall'),
            'type' => 'textarea',
            'required' => 1,
            'post-editor' => array('html','editor'),
            'options-field' => array(
                array(
                    'type' => 'checkbox',
                    'slug' => 'post-editor',
                    'title' => __('Editor settings','wp-recall'),
                    'values' => array(
                        'media' => __('Media loader','wp-recall'),
                        'html' => __('HTML editor','wp-recall'),
                        'editor' => __('Visual editor','wp-recall')
                    )
                )
            )
        );

        $fields[] = array(
            'slug' => 'post_uploader',
            'title' => __('WP-Recall media loader','wp-recall'),
            'type' => 'custom',
            'ext-types' => 'png, gif, jpg',
            'options-field' => array(
                array(
                    'type' => 'text',
                    'slug' => 'ext-types',
                    'title' => __('Valid file extensions','wp-recall'),
                    'notice' => __('Separated by comma, for example: jpg, zip, pdf. By default: png, gif, jpg','wp-recall')
                ),
                array(
                    'type' => 'runner',
                    'value_min' => 1,
                    'value_max' => 10,
                    'value_step' => 1,
                    'default' => 2,
                    'slug' => 'size-files',
                    'title' => __('The maximum file size, Mb','wp-recall'),
                    'notice' => __('Maximum file size in megabytes. By default, 2MB','wp-recall')
                ),
                array(
                    'type' => 'runner',
                    'value_min' => 1,
                    'value_max' => 50,
                    'value_step' => 1,
                    'default' => 10,
                    'slug' => 'max-files',
                    'title' => __('Number of files','wp-recall'),
                    'notice' => __('By default, 10','wp-recall')
                )
            )
        );
        
        if(post_type_supports($this->post_type,'thumbnail')){
            
            $fields[] = array(
                'slug' => 'post_thumbnail',
                'title' => __('Thumbnail of the publication','wp-recall'),
                'type' => 'custom',
                'options-field' => array(
                    array(
                        'type' => 'runner',
                        'value_min' => 1,
                        'value_max' => 10,
                        'value_step' => 1,
                        'default' => 2,
                        'slug' => 'size-files',
                        'title' => __('The maximum file size, Mb','wp-recall'),
                        'notice' => __('Maximum file size in megabytes. By default, 2MB','wp-recall')
                    )
                )
            );
            
        }
        
        if($this->taxonomies){
            
            foreach($this->taxonomies as $taxonomy => $object){
                
                if(!$this->is_hierarchical_tax($taxonomy)){
                
                    $label = $object->labels->name;

                    $fields[] = array(
                        'slug' => 'taxonomy-'.$taxonomy,
                        'title' => $label,
                        'type' => 'checkbox',
                        'number-tags' => 20,
                        'input-tags' => 1,
                        'options-field' => array(
                            array(
                                'type' => 'number',
                                'slug' => 'number-tags',
                                'title' => __('Maximum output','wp-recall')
                            ),
                            array(
                                'type' => 'select',
                                'slug' => 'input-tags',
                                'title' => __('New values entry field','wp-recall'),
                                'values' => array(
                                    __('Disable','wp-recall'),
                                    __('Enable','wp-recall')
                                )
                            )
                        )
                    );
                
                }
                
            }
            
        }
        
        $fields = apply_filters('rcl_default_public_form_fields', $fields, $this->post_type);

        return $fields;
        
    }
    
    function edit_field_options($options, $field, $type){
        
        if(!isset($field['slug']) || $type != $this->post_type) return $options;
        
        if($field['slug'] == 'post_uploader' || $field['slug'] == 'post_content'){
            
            foreach($options as $k => $option){
                
                if($option['slug'] == 'placeholder'){
                    unset($options[$k]);
                }
                
                if($option['slug'] == 'maxlength'){
                    unset($options[$k]);
                }
                
                if($field['slug'] == 'post_uploader' && $option['slug'] == 'required'){
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
                        $options[$k]['title'] = __('Specify term_ID to be selected','wp-recall');
                    }
                    
                }
                
                if($option['slug'] == 'empty-first'){
                    unset($options[$k]);
                }
                
            }
            
        }
        
        return $options;
        
    }
    
    function get_custom_fields(){
        
        if(!$this->fields) return false;
        
        $defaultSlugs = $this->get_default_slugs();
        
        $customFields = array();
        
        foreach($this->fields as $k => $field){
            
            if(in_array($field['slug'],$defaultSlugs)) continue;
            
            $customFields[] = $field;
            
        }
        
        return $customFields;
        
    }

    function is_taxonomy_field($slug){
        
        if(!$this->taxonomies) return false;
        
        foreach($this->taxonomies as $taxname => $object){
            
            if($slug == 'taxonomy-'.$taxname) return $taxname;
            
        }
        
        return false;
        
    }
    
    function is_hierarchical_tax($taxonomy){
        
        if(!$this->taxonomies || !isset($this->taxonomies[$taxonomy])) return false;
        
        if($this->taxonomies[$taxonomy]->hierarchical) return true;
        
        return false;
        
    }
    
    function get_default_slugs(){
        
        $defaulFields = $this->get_default_fields();
        
        if(!$defaulFields) return false;
        
        $default = array(
            'post_title',
            'post_content',
            'post_excerpt',
            'post_uploader',
            'post_thumbnail'
        );
        
        $slugs = array();
        
        foreach($defaulFields as $field){
            
            if(in_array($field['slug'],$default) || $this->is_taxonomy_field($field['slug'])){
            
                $slugs[] = $field['slug'];
            
            }
            
        }
        
        return $slugs;
        
    }

}

