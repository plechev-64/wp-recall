<?php

if ( ! class_exists( 'Rcl_EditFields' ) ) 
    include_once RCL_PATH.'functions/class-rcl-editfields.php';

class Rcl_Public_Form_Fields extends Rcl_EditFields{

    public $taxonomies;
    public $form_id = 1;
    public $form_object;
    
    function __construct($args = false) {
        
        $this->post_type = (isset($args['post_type']))? $args['post_type']: 'post';
        $this->form_id = (isset($args['form_id']) && $args['form_id'])? $args['form_id']: 1;
        
        parent::__construct($this->post_type,array('id'=>$this->form_id, 'custom-slug'=>1, 'terms'=>1));
        
        $this->taxonomies = get_object_taxonomies( $this->post_type, 'objects' );
        
        if($this->post_type == 'post'){
            unset($this->taxonomies['post_format']);
        }
        
        $this->form_object = $this->get_object_form();
        
        $this->fields = $this->get_fields();

        $this->init_active_fields();
        
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

            $fields = array_merge($this->get_default_fields(), $fields);

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
    
    //добавляем к активным полям опции зарегистрированных дефолтных полей
    function init_active_fields(){
        
        if(!$this->fields) return false;
        
        $options = $this->get_default_fields_options();

        foreach($this->fields as $k => $field){
            
            if($this->is_default_field($field['slug'])){
                
                if(isset($options[$field['slug']])){
                    $this->fields[$k]['options-field'] = $options[$field['slug']];
                }
                
                $this->fields[$k]['type-edit'] = false;
                $this->fields[$k]['class'] = 'must-receive';
                
            }
            
        }
        
    }
    
    function is_default_field($slug){
        
        $fields = $this->get_default_fields();
        
        foreach($fields as $field){
            
            if($field['slug'] == $slug) return true;
            
        }
        
        return false;
        
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
    
    function get_default_fields(){
        
        $defaultFields[] = array(
            'slug' => 'post_title',
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

                    $defaultFields[] = array(
                        'slug' => 'taxonomy-'.$taxonomy,
                        'title' => $label,
                        'type' => 'select',
                        'options-field' => $options
                    );
                
                }
                
            }
            
        }
        
        $defaultFields[] = array(
            'slug' => 'post_excerpt',
            'title' => __('Краткая запись','wp-recall'),
            'type' => 'textarea'
        );

        $defaultFields[] = array(
            'slug' => 'post_content',
            'title' => __('Содержание','wp-recall'),
            'type' => 'textarea',
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
        
        $defaultFields[] = array(
            'slug' => 'post_uploader',
            'title' => __('Медиа-загрузчик','wp-recall'),
            'type' => 'custom',
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

                    $defaultFields[] = array(
                        'slug' => 'taxonomy-'.$taxonomy,
                        'title' => $label,
                        'type' => 'select',
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
        
        $defaultFields = apply_filters('rcl_default_public_form_fields',$defaultFields,$this->post_type);

        return $defaultFields;
        
    }
    
    function get_default_fields_options(){
        
        $fields = $this->get_default_fields();
        
        if(!$fields) return $fields;
        
        $options = array();
        foreach($fields as $field){
            
            if(!isset($field['options-field'])) continue;
            
            $slug = $field['slug'];
            
            $options[$slug] = $field['options-field'];
            
        }
        
        return $options;
        
    }
    
    function get_default_slugs(){
        
        $defaulFields = $this->get_default_fields();
        
        if(!$defaulFields) return false;
        
        $default = array(
            'post_title',
            'post_content',
            'post_excerpt',
            'post_uploader',
        );
        
        $slugs = array();
        
        foreach($defaulFields as $field){
            
            if(in_array($field['slug'],$default) || $this->is_taxonomy_field($field['slug'])){
            
                $slugs[] = $field['slug'];
            
            }
            
        }

        return $slugs;
        
    }
    
    function get_inactive_fields(){

        $fields = $this->get_default_fields();
        
        if($fields){
            
            foreach($fields as $k => $field){
                
                if($this->exist_active_field($field['slug'])){
                    unset($fields[$k]); continue;
                }
                
                $fields[$k]['class'] = 'must-receive';
                $fields[$k]['type-edit'] = false;
                
            }
            
        }
        
        return $fields;
        
    }
    
    function exist_active_field($slug){
        
        if(!$this->fields) return false;
        
        foreach($this->fields as $k => $field){
            
            if($field['slug'] == $slug){
                
                $this->fields[$k]['class'] = 'must-receive';
                $this->fields[$k]['type-edit'] = false;
                
                return true;
                
            }
            
        }
        
        return false;
        
    }
 
}

