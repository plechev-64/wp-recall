<?php

class Rcl_Public_Form_Fields extends Rcl_Custom_Fields_Manager{

    public $taxonomies;
    public $form_id = 1;
    public $form_object;
    
    function __construct($args = false) {
        
        $this->post_type = (isset($args['post_type']))? $args['post_type']: 'post';
        $this->form_id = (isset($args['form_id']) && $args['form_id'])? $args['form_id']: 1;
        
        parent::__construct($this->post_type,array(
            'id'=>$this->form_id, 
            'custom-slug'=>1, 
            'terms'=>1)
        );

        $this->taxonomies = get_object_taxonomies( $this->post_type, 'objects' );
        
        if($this->post_type == 'post'){
            unset($this->taxonomies['post_format']);
        }
        
        $this->form_object = $this->get_object_form();

        add_filter('rcl_default_custom_fields',array($this, 'add_default_public_form_fields'));
        add_filter('rcl_custom_field_options', array($this, 'edit_field_options'), 10, 3);
        
        $this->fields = $this->get_fields();
        
    }

    function get_object_form(){
        $dataForm = array();
        $dataForm['post_id'] = $this->post_id;
        $dataForm['post_type'] = $this->post_type;
        $dataForm['post_status'] = ($this->post_id)? $this->post->post_type: 'new';
        $dataForm['post_content'] = ($this->post_id)? $this->post->post_content: '';
        $dataForm['post_excerpt'] = ($this->post_id)? $this->post->post_excerpt: '';
        $dataForm['post_title'] = ($this->post_id)? $this->post->post_title: '';
        $dataForm['ext_types'] = 'jpg, png, gif';
        $dataForm = (object)$dataForm;
        return $dataForm;
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
            
            if($field['slug'] == 'post_uploader'){
                if(isset($field['ext-types']) && $field['ext-types'])
                $this->form_object->ext_types = $field['ext-types'];
            }
            
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
            'title' => __('Заголовок','wp-recall'),
            'type' => 'text'
        );
        
        if($this->taxonomies){
            
            foreach($this->taxonomies as $taxonomy => $object){
                
                if($this->is_hierarchical_tax($taxonomy)){
                
                    $label = $object->labels->name;

                    if($taxonomy == 'groups')
                        $label = __('Категории группы','wp-recall');
                    
                    $options = array();
                    
                    if($taxonomy != 'groups'){
                        
                        $options = array(
                            array(
                                'type' => 'number',
                                'slug' => 'number-select',
                                'title' => __('Кол-во к выбору','wp-recall'),
                                'notice' => __('только при выводе через select','wp-recall')
                            ),
                            array(
                                'type' => 'select',
                                'slug' => 'type-select',
                                'title' => __('Вариант вывода','wp-recall'),
                                'values' => array(
                                    'select' => __('Select','wp-recall'),
                                    'checkbox' => __('Checkbox','wp-recall')
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
            'title' => __('Краткая запись','wp-recall'),
            'type' => 'textarea'
        );

        $fields[] = array(
            'slug' => 'post_content',
            'title' => __('Содержание публикации','wp-recall'),
            'type' => 'textarea',
            'post-editor' => array('html','editor'),
            'options-field' => array(
                array(
                    'type' => 'checkbox',
                    'slug' => 'post-editor',
                    'title' => __('Настройки редактора','wp-recall'),
                    'values' => array(
                        'media' => __('Медиазагрузчик','wp-recall'),
                        'html' => __('HTML редактор','wp-recall'),
                        'editor' => __('Визуальный редактор','wp-recall')
                    )
                )
            )
        );
        
        if(post_type_supports($this->post_type,'thumbnail')){
            
            $fields[] = array(
                'slug' => 'post_thumbnail',
                'title' => __('Миниатюра публикации','wp-recall'),
                'type' => 'custom'
            );
            
        }
        
        $fields[] = array(
            'slug' => 'post_uploader',
            'title' => __('Медиа-загрузчик WP-Recall','wp-recall'),
            'type' => 'custom',
            'ext-types' => 'png, gif, jpg',
            'options-field' => array(
                array(
                    'type' => 'text',
                    'slug' => 'ext-types',
                    'title' => __('Допустимые разрешения файлов','wp-recall'),
                    'notice' => __('Через запятую, например: jpg, zip, pdf. По-умолчанию: png, gif, jpg','wp-recall')
                )
            )
        );
        
        if($this->taxonomies){
            
            foreach($this->taxonomies as $taxonomy => $object){
                
                if(!$this->is_hierarchical_tax($taxonomy)){
                
                    $label = $object->labels->name;

                    $fields[] = array(
                        'slug' => 'taxonomy-'.$taxonomy,
                        'title' => $label,
                        'type' => 'select',
                        'number-tags' => 20,
                        'input-tags' => 1,
                        'options-field' => array(
                            array(
                                'type' => 'number',
                                'slug' => 'number-tags',
                                'title' => __('Максимально к выводу','wp-recall')
                            ),
                            array(
                                'type' => 'select',
                                'slug' => 'input-tags',
                                'title' => __('Поле ввода новых значений','wp-recall'),
                                'values' => array(
                                    __('Отключить','wp-recall'),
                                    __('Включить','wp-recall')
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
        
        if($type != $this->post_type) return $options;
        
        if($field['slug'] == 'post_uploader' || $field['slug'] == 'post_content'){
            
            foreach($options as $k => $option){
                
                if($option['slug'] == 'placeholder'){
                    unset($options[$k]);
                }
                
                if($option['slug'] == 'maxlength'){
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

