<?php

class Rcl_EditFields extends Rcl_Custom_Fields{

    public $name_option;
    public $post_type;
    public $options;
    public $options_html;
    public $field;
    public $fields;
    public $status;
    public $primary;
    public $select_type;
    public $meta_key;
    public $exist_placeholder;
    public $sortable;
    public $fieldsData;
    public $name_field;
    
    public $defaultOptions = array();

    function __construct($post_type, $options = false){

        $this->select_type = (isset($options['select-type']))? $options['select-type']: true;
        $this->meta_key = (isset($options['meta-key']))? $options['meta-key']: true;
        $this->exist_placeholder = (isset($options['placeholder']))? $options['placeholder']: true;
        $this->sortable = (isset($options['sortable']))? $options['sortable']: true;
        $this->fields = (isset($options['fields']))? $options['fields']: array();
        $this->primary = $options;
        $this->post_type = $post_type;

        switch($this->post_type){
            case 'post': $name_option = 'rcl_fields_post_'.$this->primary['id']; break;
            case 'orderform': $name_option = 'rcl_cart_fields'; break;
            case 'profile': $name_option = 'rcl_profile_fields'; break;
            default: $name_option = 'rcl_fields_'.$this->post_type;
        }

        $fieldsData = apply_filters('rcl_edit_custom_fields_data',stripslashes_deep(get_option( $name_option )));
        
        if($fieldsData){

            foreach($fieldsData as $k => $field){
                
                if(isset($field['field_select'])){
                    
                    $field['field_select'] = rcl_edit_old_option_fields($field['field_select'], $field['type']);
                    
                    if(is_array($field['field_select'])){
                        
                        $fieldsData[$k]['values'] = $field['field_select'];
                        
                    }
                    
                }
                
            }
            
        }
        
        $this->fieldsData = $fieldsData;
        
        $this->name_option = $name_option;
        
    }

    function edit_form($defaultOptions = false){

        $this->defaultOptions = $defaultOptions;

        $form = '<div id="rcl-custom-fields-editor" data-type="'.$this->post_type.'" class="rcl-custom-fields-box">
            
            <h3>'.__('Активные поля','wp-recall').'</h3>
            
            <form action="" method="post">
            '.wp_nonce_field('rcl-update-custom-fields','_wpnonce',true,false).'
            <input type="hidden" name="rcl-fields-options[name-option]" value="'.$this->name_option.'">
            <input type="hidden" name="rcl-fields-options[placeholder]" value="'.$this->exist_placeholder.'">';
        
        $form .= apply_filters('rcl_custom_fields_form','',$this->name_option);

        $form .= '<ul id="rcl-fields-list" class="rcl-sortable-fields">';

        $form .= $this->loop();

        $form .= $this->empty_field();

        $form .= '</ul>';

        $form .= "<div class=fields-submit>
                <input type=button onclick='rcl_get_new_custom_field();' class='add-field-button button-secondary right' value='+ ".__('Add field','wp-recall')."'>
                <input class='button button-primary' type=submit value='".__('Save','wp-recall')."' name='rcl_save_custom_fields'>
                <input type=hidden id=rcl-deleted-fields name=rcl_deleted_custom_fields value=''>
            </div>
        </form>";
                
        if($this->sortable){
            $form .= '<script>
                jQuery(function(){
                    jQuery(".rcl-sortable-fields").sortable({
                        connectWith: ".rcl-sortable-fields",
                        handle: ".field-header",
                        cursor: "move",
                        placeholder: "ui-sortable-placeholder",
                        distance: 15,
                        receive: function(ev, ui) {
                            if(!ui.item.hasClass("must-receive"))
                              ui.sender.sortable("cancel");
                        }
                    });
                    return false;
                });
            </script>';
        }
        
        $form .= "<script>rcl_init_custom_fields(\"".$this->post_type."\",\"".wp_slash(json_encode($this->primary))."\",\"".wp_slash(json_encode($this->defaultOptions))."\");</script>";

        $form .= '</div>';

        return $form;
    }

    function loop($fields = null){
        
        $form = '';
        
        if(!isset($fields))
            $fields = $this->fieldsData;
        
        if($fields){
            
            foreach($fields as $key => $args){
                if($key==='options') continue;
                $form .= $this->field($args);
            }
            
        }

        return $form;
    }
    
    function get_options_field(){
        
        $types = array(
            'select',
            'multiselect',
            'checkbox',
            'agree',
            'radio',
            'file'
        );
        
        $options = array();

        if(in_array($this->field['type'],$types)){
            
            if($this->field['type']=='file'){
                
                $options[] = array(
                    'type' => 'number',
                    'slug' => 'sizefile',
                    'title' => __('Размер файла','wp-recall'),
                    'notice' => __('maximum size of uploaded file, MB (Default - 2)','wp-recall')
                );
                
                $options[] = array(
                    'type' => 'textarea',
                    'slug' => 'field_select',
                    'title' => __('Разрешенные типы файлов','wp-recall'),
                    'notice' => __('allowed types of files are divided by comma, for example: pdf, zip, jpg','wp-recall')
                );
                
            }else if($this->field['type']=='agree'){
                
                $options[] = array(
                    'type' => 'url',
                    'slug' => 'url-agreement',
                    'title' => __('Agreement URL','wp-recall')
                );
                
                $options[] = array(
                    'type' => 'textarea',
                    'slug' => 'field_select',
                    'title' => __('Текст ссылки на соглашение','wp-recall'),
                    'notice' => __('Enter the text of the link to User agreement','wp-recall')
                );
                
            }else{
                
                $options[] = array(
                    'type' => 'dynamic',
                    'slug' => 'values',
                    'title' => __('Указание опций','wp-recall'),
                    'notice' => __('указывайте каждую опцию в отдельном поле','wp-recall')
                );
                
            }
            
        }else{
            
            if($this->exist_placeholder){
                
                $options[] = array(
                    'type' => 'text',
                    'slug' => 'placeholder',
                    'title' => __('Placeholder','wp-recall')
                );
                
            }
            
        }
        
        $options = array_merge($options, $this->defaultOptions);
        
        return $options;
        
    }
    
    function get_input_option($option, $value = false){
        
        $value = (isset($this->field[$option['slug']]))? $this->field[$option['slug']]: $value;
        
        if($this->field['slug'])
            $option['name'] = 'field['.$this->field['slug'].']['.$option['slug'].']';
        else
            $option['name'] = 'new-field['.$option['slug'].'][]';
        
        return $this->get_input($option, $value);
        
    }
    
    function get_options(){
        
        $options = apply_filters('rcl_custom_field_options', $this->get_options_field(), $this->field, $this->post_type);
        
        if(!$options) return false;
        
        $content = '';
        
        foreach($options as $option){
            
            $content .= $this->get_option($option);
            
        }
        
        return $content;
        
    }
    
    function get_option($option, $value = false){
        
        $content = '<div class="option-content">';
            $content .= '<label>'.$this->get_title($option).'</label>';
            $content .= '<div class="option-input">';
                $content .= $this->get_input_option($option, $value);
            $content .= '</div>';
        $content .= '</div>';
        
        return $content;
    }
    
    function header_field(){
        
        $delete = (isset($this->field['delete']))? $this->field['delete']: true;
        
        $content = '<div class="field-header">
                    <span class="field-type type-'.$this->field['type'].'"></span>
                    <span class="field-title">'.$this->field['title'].'</span>                           
                    <span class="field-controls">
                    ';
        
        if($delete)
            $content .= '<a class="field-delete field-control" title="'.__('Delete','wp-recall').'" href="#"></a>';
                                
        $content .= '<a class="field-edit field-control" href="#" title="'.__('Edit','wp-recall').'"></a>
                    </span>
                </div>';
        
        return $content;
    }

    function field($args){
        
        $this->field = $args;
        
        $this->status = true;
        
        $classes = array('rcl-custom-field');
           
        if(isset($this->field['class']))
            $classes[] = $this->field['class'];

        $typeEdit = (isset($this->field['type-edit']))? $this->field['type-edit']: true;

        $field = '<li id="field-'.$this->field['slug'].'" data-slug="'.$this->field['slug'].'" data-type="'.$this->field['type'].'" class="'.implode(' ',$classes).'">
                    '.$this->header_field().'
                    <div class="field-settings">';
        
                        $field .= $this->get_field_value(array(
                                'type' => 'text',
                                'slug' => 'slug',
                                'title' => __('Meta-key','wp-recall'),
                            ),
                            $this->field['slug']  
                        );

                        $field .= $this->get_option(array(
                                'type' => 'text',
                                'slug' => 'title',
                                'title' => __('Title','wp-recall'),
                                'required' => 1,
                            ),
                            $this->field['title']  
                        );

                        if($typeEdit)
                            $field .= $this->get_types();
                        else
                            $field .= '<input type="hidden" name="field['.$this->field['slug'].'][type]" value="'.$this->field['type'].'">';

                        $field .= '<div class="options-custom-field">';
                        $field .= $this->get_options();
                        $field .= '</div>';

                    $field .= '</div>';
                    
                    $field .= '<input type="hidden" name="fields[]" value="'.$this->field['slug'].'">';
                    
                $field .= '</li>';
                        
        $this->field = false;

        return $field;

    }

    function empty_field(){
        
        $this->status = false;

        $field = '<li data-slug="" data-type="" class="rcl-custom-field new-field">
                    <div class="field-header">
                        <span class="field-title half-width">'.$this->get_option(array('type'=>'text','slug'=>'title','title'=>__('Name','wp-recall'))).'</span>
                        <span class="field-controls half-width">
                            <a class="field-edit field-control" href="#" title="'.__('Edit','wp-recall').'"></a>
                        </span>
                    </div>
                    <div class="field-settings">';
        
                    if($this->meta_key){

                        $edit = ($this->primary['custom-slug'])? true: false;

                        $field .= $this->get_option(array(
                            'type' => 'text',
                            'slug'=>'slug',
                            'title'=>__('MetaKey','wp-recall'),
                            'notice'=>__('not required, but you can list your own meta_key in this field','wp-recall'),
                            'placeholder'=>__('Latin letters and numbers','wp-recall')
                        ));

                    } 
                    
                    $field .= $this->get_types();

                    $field .= '<div class="options-custom-field">';
                    $field .= $this->get_options();
                    $field .= '</div>';
                    
                $field .= '</div>';
                
                $field .= '<input type="hidden" name="fields[]" value="">';
                
            $field .= '</li>';

        return $field;
    }
    
    function get_types(){
        
        if(!$this->select_type) return false;
        
        $fields = array(
            'text'=>__('Text','wp-recall'),
            'textarea'=>__('Multiline text area','wp-recall'),
            'select'=>__('Select','wp-recall'),
            'multiselect'=>__('MultiSelect','wp-recall'),
            'checkbox'=>__('Checkbox','wp-recall'),
            'radio'=>__('Radiobutton','wp-recall'),
            'email'=>__('E-mail','wp-recall'),
            'tel'=>__('Phone','wp-recall'),
            'number'=>__('Number','wp-recall'),
            'date'=>__('Date','wp-recall'),
            'time'=>__('Time','wp-recall'),
            'url'=>__('Url','wp-recall'),
            'agree'=>__('Agreement','wp-recall'),
            'file'=>__('File','wp-recall'),
            'dynamic'=>__('Dynamic','wp-recall')
        );
        
        if($this->fields){
            
            $newFields = array();
            
            foreach($fields as $key => $fieldname){
                
                if(!in_array($key,$this->fields)) continue;
                
                $newFields[$key] = $fieldname;
                
            }
            
            $fields = $newFields;
            
        }
        
        $content .= $this->get_option(array(
            'title'=>__('Field type','wp-recall'),
            'slug' => 'type',
            'type' => 'select',
            'class' => 'typefield',
            'values' => $fields
        ));
        
        return $content;
        
    }

    function get_vals($name){
        foreach($this->fieldsData as $field){
            if($field[$name]) return $field;
        }
    }

    function option($type, $args, $edit = true, $key = false){
        
        $args['type'] = $type;
        
        if(isset($args['label']))
            $args['title'] = $args['label'];
        
        if(isset($args['name']))
            $args['slug'] = $args['name'];
        
        if(isset($args['value']))
            $args['values'] = $args['value'];

        return $args;
        
    }

    function options($args){
        
        $val = ($this->fieldsData['options']) ? $this->fieldsData['options'][$args['name']]: '';
        $ph = (isset($args['placeholder']))? $args['placeholder']: '';
        $pattern = (isset($args['pattern']))? 'pattern="'.$args['pattern'].'"': '';
        $field = '<input type="text" placeholder="'.$ph.'" title="'.$ph.'" '.$pattern.' name="options['.$args['name'].']" value="'.$val.'"> ';
        
        return $field;
    }

    function verify(){
        
    }

    function update_fields($table='postmeta'){
        
    }
}
