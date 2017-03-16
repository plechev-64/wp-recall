<?php

class Rcl_EditFields {

    public $name_option;
    public $post_type;
    public $options;
    public $options_html;
    public $vals;
    public $fields;
    public $status;
    public $primary;
    public $select_type;
    public $meta_key;
    public $placeholder;
    public $sortable;
    public $fieldsData;

    function __construct($post_type,$primary=false){

        $this->select_type = (isset($primary['select-type']))? $primary['select-type']: true;
        $this->meta_key = (isset($primary['meta-key']))? $primary['meta-key']: true;
        $this->placeholder = (isset($primary['placeholder']))? $primary['placeholder']: true;
        $this->sortable = (isset($primary['sortable']))? $primary['sortable']: true;
        $this->fields = (isset($primary['fields']))? $primary['fields']: array();
        $this->primary = $primary;
        $this->post_type = $post_type;

        switch($this->post_type){
            case 'post': $name_option = 'rcl_fields_post_'.$this->primary['id']; break;
            case 'orderform': $name_option = 'rcl_cart_fields'; break;
            case 'profile': $name_option = 'rcl_profile_fields'; break;
            default: $name_option = 'rcl_fields_'.$this->post_type;
        }

        $this->fieldsData = apply_filters('rcl_edit_custom_fields_data',stripslashes_deep(get_option( $name_option )));
        
        $this->name_option = $name_option;
        
    }

    function edit_form($options=false,$more=''){
        
        $this->options = apply_filters('rcl_custom_fields_options',$this->options,$this->post_type);

        $form = '<div id="rcl-custom-fields-editor" class="rcl-custom-fields-box">
            
            <form action="" method="post">

            '.$more.'
            <h3>'.__('Активные поля','wp-recall').'</h3>
             
            '.wp_nonce_field('rcl-update-custom-fields','_wpnonce',true,false).'
            <input type="hidden" name="rcl-fields-options[name-option]" value="'.$this->name_option.'">
            <input type="hidden" name="rcl-fields-options[placeholder]" value="'.$this->placeholder.'">';
        
            if(isset($this->primary['terms'])&&$this->primary['terms'])
                $form .= $this->option('options',array(
                    'name'=>'terms',
                    'label'=>__('List of categories to select','wp-recall'),
                    'placeholder'=>__('ID separated by comma','wp-recall'),
                    'pattern'=>'^([0-9,])*$'
                ));

                $form .= '<ul id="rcl-fields-list" class="rcl-sortable-fields">';
                
                $form .= $this->loop();
                
                $form .= $this->empty_field();
                
                $form .= '</ul>';
                
                $form .= '<div class="fields-submit">
                    <input type="button" class="add-field-button button-secondary right" value="+ '.__('Add field','wp-recall').'">
                    <input class="button button-primary" type="submit" value="'.__('Save','wp-recall').'" name="rcl_save_custom_fields">
                    <input type="hidden" id="rcl-deleted-fields" name="rcl_deleted_custom_fields" value="">
                </div>
            </form>';
                
            if($this->sortable){
                $form .= '<script>
                    jQuery(function(){
                        jQuery(".rcl-sortable-fields").sortable({
                            connectWith: ".rcl-sortable-fields",
                            /*containment: "parent",*/
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
            
            $form .= '</div>';

        return $form;
    }

    function loop($fields = null){
        
        $form = '';
        
        if(!isset($fields))
            $fields = $this->fieldsData;
        
        if($fields){
            
            foreach($fields as $key=>$vals){
                if($key==='options') continue;
                $form .= $this->field($vals);
            }
            
        }

        return $form;
    }

    function field($vals){

        $this->vals = $vals;
        $this->status = true;
        
        $classes = array('rcl-custom-field');
        
        if(isset($this->vals['class']))
            $classes[] = $this->vals['class'];

        $types = array(
            'select'=>1,
            'multiselect'=>1,
            'checkbox'=>1,
            'agree'=>1,
            'radio'=>1,
            'file'=>1
        );
        
        $typeEdit = (isset($this->vals['type-edit']))? $this->vals['type-edit']: true;
        
        switch($this->vals['type']=='file'){
            case 'agree': $notice = __('Enter the text of the link to User agreement','wp-recall'); break;
            case 'file': $notice = __('allowed types of files are divided by comma, for example: pdf, zip, jpg','wp-recall'); break;
            default: $notice = __('the list of options is divided by "#"','wp-recall');
        }

        $textarea_select = (isset($types[$this->vals['type']]))?
            '<span class="textarea-notice">'.$notice.'</span><br>'
            . '<textarea rows="1" class="field-select" style="height:50px" name="field[field_select][]">'.$this->vals['field_select'].'</textarea>'
        : '';

        $textarea_select .= ($this->vals['type']=='file')? '<input type="number" name="field[sizefile]['.$this->vals['slug'].']" value="'.$this->vals['sizefile'].'"> '.__('maximum size of uploaded file, MB (Default - 2)','wp-recall').'<br>':'';
        $textarea_select .= ($this->vals['type']=='agree')? '<input type="url" name="field[url-agreement]['.$this->vals['slug'].']" value="'.$this->vals['url-agreement'].'"> '.__('Agreement URL','wp-recall').'<br>':'';
        
        if($this->placeholder&&!isset($types[$this->vals['type']])){
            $placeholder = (isset($this->vals['placeholder']))? $this->vals['placeholder']: '';
            $textarea_select .= "<div class='field-option placeholder-field'><input type=text name='field[placeholder][]' value='".$placeholder."'><br>placeholder</div>";
        }

        $field = '<li id="field-'.$this->vals['slug'].'" data-slug="'.$this->vals['slug'].'" data-type="'.$this->vals['type'].'" class="'.implode(' ',$classes).'">
                '.$this->header_field().'
                <div class="field-settings">';
                    if($this->meta_key){
                       $field .= '<div class="field-options-box">
                           '.$this->option('text',array(
                               'name'=>'slug',
                               'label'=>__('MetaKey','wp-recall').':',
                               'notice'=>__('not required <br>but you can list your own meta_key in this field','wp-recall'),
                               'placeholder'=>__('Latin letters and numbers','wp-recall')
                           ),false).'
                       </div>';
                    }
                    $field .= '<div class="field-options-box">
                        <div class="half-width">
                            '.$this->option('text',array(
                                'name'=>'title',
                                'label'=>__('Title','wp-recall').'<br>',
                                'required'=>true
                            )).'
                        </div>';
                    
                    if($typeEdit)
                        $field .= '<div class="half-width">'.$this->get_types().'</div>';
                    else
                        $field .= '<input type="hidden" name="field[type][]" value="'.$this->vals['type'].'">';
                        
                    $field .= '</div>
                    <div class="field-options-box secondary-settings">'
                        .$textarea_select
                        .$this->get_options()
                    .'</div>
                </div>
        </li>';

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
        
        return $this->option('select',array(
            'label'=>__('Field type','wp-recall'),
            'name' => 'type',
            'class' => 'typefield',
            'value' => $fields
        ));
    }

    function get_options(){

        if(!$this->options) return false;
        
        $opt = '';
        foreach($this->options as $option){
            foreach($option as $type=>$args){
                if($type=='options') continue;
                $opt .= '<div class="field-option">'.$this->option($type,$args).'</div>';
            }
        }
        return $opt;
    }

    function header_field(){
        
        $delete = (isset($this->vals['delete']))? $this->vals['delete']: true;
        
        $content = '<div class="field-header">
                    <span class="field-title">'.$this->vals['title'].'</span>                           
                    <span class="field-controls">
                        <span class="field-type">'.$this->vals['type'].'</span>';
        
        if($delete)
            $content .= '<a class="field-delete field-control" title="'.__('Delete','wp-recall').'" href="#"></a>';
                                
        $content .= '<a class="field-edit field-control" href="#" title="'.__('Edit','wp-recall').'"></a>
                    </span>
                </div>';
        
        return $content;
    }

    function empty_field(){
        $this->status = false;

        $field = '<li class="rcl-custom-field new-field">
                <div class="field-header">
                    <span class="field-title half-width">'.__('Name','wp-recall').' '.$this->option('text',array('name'=>'title')).'</span>
                    <span class="field-controls half-width">
                        <a class="field-edit field-control" href="#" title="'.__('Edit','wp-recall').'"></a>
                        <span class="field-type">'.$this->get_types().'</span>
                    </span>
                </div>
                <div class="field-settings">';
                if($this->meta_key){
                    $field .= '<div class="field-options-box">';

                        $edit = ($this->primary['custom-slug'])? true: false;

                        $field .= $this->option('text',array(
                            'name'=>'slug',
                            'label'=>__('MetaKey','wp-recall'),
                            'notice'=>__('not required <br>but you can list your own meta_key in this field','wp-recall'),
                            'placeholder'=>__('Latin letters and numbers','wp-recall')
                        ),
                        $edit);

                    $field .= '</div>';
                } 
                
                $field .= '<div class="field-options-box secondary-settings">';
                
                if($this->placeholder){
                    $field .='<div class="field-option placeholder-field"><input type="text" name="field[placeholder][]" value=""><br>placeholder</div>';
                }

                $field .=$this->get_options()
                    .'</div>
                </div>
            </li>';

        return $field;
    }

    function get_vals($name){
        foreach($this->fieldsData as $vals){
            if($vals[$name]) return $vals;
        }
    }

    function option($type,$args,$edit=true){
        $field = '';

        if(!$this->vals&&!isset($this->status)){
            $this->options[][$type] = $args;
        }
        if($this->status&&!$this->vals)
            $this->vals = $this->get_vals($args['name']);

        if(!$this->status) $this->vals = '';

        if(isset($args['label'])&&$args['label']) 
            $field .= '<span class="field-label">'.$args['label'].' </span>';
        
        $field .= $this->$type($args,$edit);
        
        if($edit&&isset($args['notice'])&&$args['notice']) 
            $field .= '<span class="field-notice">'.$args['notice'].'</span>';
        
        return $field;
    }

    function select($args,$edit){

        if(!$edit) return $val.'<input type="hidden" name="field['.$args['name'].'][]" value="'.$key.'">';

        $class = (isset($args['class'])&&$args['class'])? 'class="'.$args['class'].'"': '';

        $field = '<select '.$class.' name="field['.$args['name'].'][]">';
        foreach($args['value'] as $key=>$val){
            $sel = ($this->vals)? selected($this->vals[$args['name']],$key,false): '';
            $field .= '<option '.$sel.' value="'.$key.'">'.$val.'</option>';
        }
        $field .= '</select> ';

        return $field;
    }
    
    function multiselect($args,$edit){

        $class = (isset($args['class'])&&$args['class'])? 'class="'.$args['class'].'"': '';
        
        $rand = rand(1,1000);
        
        $field = '<select '.$class.' multiple size="5" name="field['.$args['name'].'-'.$rand.'][]">';
        foreach($args['value'] as $key=>$val){
            $sel = ($this->vals&&isset($this->vals[$args['name']]))? selected(in_array($key,$this->vals[$args['name']]),true,false): '';
            $field .= '<option '.$sel.' value="'.$key.'">'.$val.'</option>';
        }
        $field .= '</select> '
                . '<input type="hidden" name="field['.$args['name'].'][]" value="'.$rand.'">';

        return $field;
    }

    function text($args,$edit){
        
        $required = (isset($args['required']) && $args['required'])? 'required': '';
	$val = ($this->vals)? esc_textarea( str_replace("'",'"',$this->vals[$args['name']] )): '';
        
        if(!$edit) 
            return $val.'<input type="hidden" '.$required.' name="field['.$args['name'].'][]" value="'.$val.'">';
        
        $ph = (isset($args['placeholder']))? $args['placeholder']: '';
        $pattern = (isset($args['pattern']))? 'pattern="'.$args['pattern'].'"': '';
        $field = "<input type=text ".$required." placeholder='".$ph."' ".$pattern." name=field[".$args['name']."][] value='".$val."'> ";
        
        return $field;
    }
    
    function textarea($args){
	$value = ($this->vals)? esc_textarea( $this->vals[$args['name']] ): '';        
        $placeholder = (isset($args['placeholder']))? 'placeholder="'.$args['placeholder']."'": '';
        $pattern = (isset($args['pattern']))? 'pattern="'.$args['pattern'].'"': '';        
        $field = '<textarea '.$placeholder.' '.$pattern.' name="field['.$args['name'].'][]" value="'.$value.'">'.$value.'</textarea>';
        return $field;
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
