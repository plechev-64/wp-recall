<?php

if ( ! class_exists( 'Rcl_EditFields' ) ) 
        include_once RCL_PATH.'functions/class-rcl-editfields.php';

class Rcl_Public_Form_Fields extends Rcl_EditFields{
    
    public $inactive_fields;
    public $taxonomies;
    
    function __construct($args = false) {
        
        $this->post_type = (isset($args['post_type']))? $args['post_type']: 'post';
        
        parent::__construct($this->post_type,array('id'=>1, 'custom-slug'=>1, 'terms'=>1));
        
        $this->taxonomies = get_object_taxonomies( $this->post_type, 'objects' );

        $this->inactive_fields = $this->get_inactive_fields();
        
    }
    
    function get_custom_fields(){
        
        $defaultSlugs = $this->get_default_slugs();
        
        $customFields = array();
        
        foreach($this->fieldsData as $k => $field){
            
            if(in_array($field['slug'],$defaultSlugs)) continue;
            
            $customFields[] = $field;
            
        }
        
        return $customFields;
        
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
        
        return $content;
        
    }
    
    function inactive_fields_box(){

        $content = '<div id="rcl-inactive-public-form-fields" class="rcl-inactive-fields-box rcl-custom-fields-box">';
        
            $content .= '<h3>'.__('Неактивные поля','wp-recall').'</h3>';

            $content .= '<form>';

                $content .= '<ul class="rcl-sortable-fields">';

                    $content .= $this->loop($this->inactive_fields);

                $content .= '</ul>';

            $content .= '</form>';
        
        $content .= '</div>';
        
        return $content;
        
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
            
            if($this->post_type == 'post'){
                unset($this->taxonomies['post_format']);
            }
            
            foreach($this->taxonomies as $taxname => $object){
                
                $label = $object->labels->name;
                
                if($taxname == 'groups')
                    $label = __('Категории группы','wp-recall');
                
                $defaultFields[] = array(
                    'slug' => 'taxonomy-'.$taxname,
                    'title' => $label,
                    'type' => 'select'
                );
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
            'type' => 'textarea'
        );
        
        $defaultFields[] = array(
            'slug' => 'post_uploader',
            'title' => __('Медиазагрузчик','wp-recall'),
            'type' => 'custom'
        );
        
        $defaultFields = apply_filters('rcl_inactive_public_form_fields',$defaultFields,$this->post_type);
        
        $defaultFields = apply_filters('rcl_inactive_'.$this->post_type.'_form_fields',$defaultFields,$this->post_type);
        
        return $defaultFields;
        
    }
    
    function get_default_slugs(){
        
        $defaulFields = $this->get_default_fields();
        
        if(!$defaulFields) return false;
        
        $slugs = array();
        
        foreach($defaulFields as $field){
            
            $slugs[] = $field['slug'];
            
        }
        
        return $slugs;
        
    }
    
    function get_inactive_fields(){

        $inactive_fields = $this->get_default_fields();
        
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
        
        add_filter('rcl_custom_fields_form', array($this, 'add_content_form'));
        add_filter('rcl_custom_field_options', array($this, 'edit_field_options'), 10, 3);
        
        $content = $this->edit_form(
            
            array(
        
                $this->option('textarea',array(
                    'name'=>'notice',
                    'title'=>__('field description','wp-recall')
                )),
                
                $this->option('select',array(
                    'name'=>'required',
                    'title'=>__('required field','wp-recall'),
                    'value'=>array(__('No','wp-recall'),__('Yes','wp-recall'))
                ))

            )
                
        );
        
        return $content;
        
    }
    
    function add_content_form($content){
        
        $content .= '<input type="hidden" name="options[user-edit]" value="1">';
        
        return $content;
        
    }
    
    function edit_field_options($options, $field, $type){
        
        if($type != $this->post_type) return $options;
        
        $taxonomy = $this->is_taxonomy_field($field['slug']);
        
        if($taxonomy && $field['slug'] != 'taxonomy-groups'){
            
            if($this->is_hierarchical_tax($taxonomy)){
                
                $options[] = array(
                    'type' => 'number',
                    'slug' => 'number-select',
                    'title' => __('Кол-во к выбору','wp-recall'),
                    'notice' => __('только при выводе через select','wp-recall')
                );
                
                $options[] = array(
                    'type' => 'select',
                    'slug' => 'type-select',
                    'title' => __('Вариант вывода','wp-recall'),
                    'values' => array(
                        'select' => __('Select','wp-recall'),
                        'checkbox' => __('Checkbox','wp-recall')
                    )
                );
                
            }else{
                
               $options[] = array(
                    'type' => 'number',
                    'slug' => 'number-tags',
                    'title' => __('Максимально к выводу','wp-recall')
                );
                
                $options[] = array(
                    'type' => 'select',
                    'slug' => 'input-tags',
                    'title' => __('Поле ввода новых значений','wp-recall'),
                    'values' => array(
                        __('Отключить','wp-recall'),
                        __('Включить','wp-recall')
                    )
                ); 
                
            }
            
        }
        
        if($field['slug'] == 'post_content'){

            $options[] = array(
                'type' => 'checkbox',
                'slug' => 'post-editor',
                'title' => __('Настройки редактора','wp-recall'),
                'values' => array(
                    'media' => __('Медиазагрузчик','wp-recall'),
                    'html' => __('HTML редактор','wp-recall'),
                    'editor' => __('Визуальный редактор','wp-recall')
                )
            );
            
        }
        
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
        
        if($field['slug'] == 'taxonomy-groups'){
            
            foreach($options as $k => $option){
                
                if($option['slug'] == 'required'){
                    unset($options[$k]);
                }
                
                if($option['slug'] == 'values'){
                    unset($options[$k]);
                }
                
            }
            
        }
        
        return $options;
        
    }
    
}

