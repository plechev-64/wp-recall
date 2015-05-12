<?php

class Rcl_EditFields {

    public $name_option;
    public $options;
    public $options_html;
    public $vals;
    public $status;
    public $primary;

    function __construct($posttype,$primary=false){
        global $Option_Value;
        $this->primary = $primary;

        switch($posttype){
            case 'post': $name_option = 'custom_public_fields_'.$this->primary['id']; break;
            case 'products': $name_option = 'custom_saleform_fields'; break;
            case 'orderform': $name_option = 'custom_orders_field'; break;
            case 'profile': $name_option = 'custom_profile_field'; break;
            default: $name_option = 'custom_fields_'.$posttype;
        }

        $Option_Value = get_option( $name_option );
        $this->name_option = $name_option;
    }

    function edit_form($options,$more=''){

        foreach($options as $opt){
            $this->options_html .= $opt;
        }

        $form = '<style>
                #inputs_public_fields textarea{width:100%;}
                #inputs_public_fields .menu-item-settings,
                #inputs_public_fields .menu-item-handle{padding-right:10px;width:100%;}
            </style>
            <form class="nav-menus-php" action="" method="post">
            '.wp_nonce_field('update-public-fields','_wpnonce',true,false).'
            <div id="inputs_public_fields" class="public_fields" style="width:550px;">
                '.$more;

            if($this->primary['terms'])
                $form .= $this->option('options',array(
                    'name'=>'terms',
                    'label'=>__('List of columns to select','rcl'),
                    'placeholder'=>__('ID separated by comma','rcl')
                ));

                $form .= '<ul id="sortable">
                    '.$this->loop().'
                </ul>
            </div>
            <p style="width:550px;"><input type="button" id="add_public_field"  class="button-secondary right" value="+ '.__('Add field','rcl').'"></p>
            <input id="save_menu_footer" class="button button-primary menu-save" type="submit" value="'.__('Save','rcl').'" name="add_field_public">
            <input type="hidden" id="deleted-fields" name="deleted" value="">
        </form>
        <script>jQuery(function(){jQuery("#sortable").sortable();return false;});</script>';

        return $form;
    }

    function loop(){
        global $Option_Value;
        $form = '';
        if($Option_Value){
            foreach($Option_Value as $key=>$vals){
                if($key==='options') continue;
                $form .= $this->field($vals);
            }
        }
        $form .= $this->empty_field();
        return $form;
    }

    function field($vals){

        $this->vals = $vals;
        $this->status = true;

        $types = array(
            'select'=>1,
            'checkbox'=>1,
            'agree'=>1,
            'radio'=>1
        );

        $textarea_select = (isset($types[$this->vals['type']]))?
            $textarea_select = __('the list of options to share the " # " sign','rcl').'<br>'
                        . '<textarea rows="1" class="field-select" style="height:50px" name="field[field_select][]">'.$this->vals['field_select'].'</textarea>'
        : '';

        $field = '<li id="item-'.$this->vals['slug'].'" class="menu-item menu-item-edit-active">
                '.$this->header_field().'
                <div id="settings-'.$this->vals['slug'].'" class="menu-item-settings" style="display: none;">
                        <p class="link-to-original" style="clear:both;">
                            '.$this->option('text',array(
                                'name'=>'slug',
                                'label'=>__('MetaKey','rcl').':',
                                'notice'=>__('not necessarily<br>if you want to enlist their arbitrary field, we list the meta_key in this field','rcl'),
                                'placeholder'=>__('Latin and numbers','rcl')
                            ),false).'
                        </p>
                        <div class="link-to-original" style="overflow:hidden;">
                                <p class="description description-thin" style="width: 300px;">
                                    <label>
                                        '.$this->option('text',array(
                                            'name'=>'title',
                                            'label'=>__('Title','rcl').'<br>'
                                        )).'
                                    </label>
                                </p>
                                <p class="description description-thin">
                                    <label>'.$this->get_types().'</label>
                                </p>
                        </div>
                        <p class="place-sel link-to-original">
                        '.$textarea_select.'
                        '.$this->get_options().'</p>
                        <p align="right"><a id="'.$this->vals['slug'].'" class="item-delete field-delete deletion" href="#">'.__('Delete','rcl').'</a></p>
                </div>
        </li>';

        return $field;

    }

    function get_types(){
        return $this->option('select',array(
            'label'=>__('The field type','rcl'),
            'name'=>'type',
            'class'=>'typefield',
            'value'=>array(
                'text'=>__('Text','rcl'),
                'textarea'=>__('Textarea','rcl'),
                'select'=>__('Select','rcl'),
                'checkbox'=>__('Checkbox','rcl'),
                'radio'=>__('Radiobutton','rcl'),
                'email'=>__('E-mail','rcl'),
                'tel'=>__('Phone','rcl'),
                'number'=>__('Number','rcl'),
                'date'=>__('Date','rcl'),
                'time'=>__('Time','rcl'),
                'url'=>__('Url','rcl'),
                'agree'=>__('Agreement','rcl')
            )
        ));
    }

    function get_options(){
        $opt = '';
        foreach($this->options as $option){
            foreach($option as $type=>$args){
                if($type=='options') continue;
                $opt .= $this->option($type,$args);
            }
        }
        return $opt;
    }

    function header_field(){
        return '<dl class="menu-item-bar">
                    <dt class="menu-item-handle">
                        <span class="item-title">'.$this->vals['title'].'</span>
                        <span class="item-controls">
                        <span class="item-type">'.$this->vals['type'].'</span>
                        <a id="edit-'.$this->vals['slug'].'" class="profilefield-item-edit item-edit" href="#" title="'.__('Change','rcl').'">'.__('Change','rcl').'</a>
                        </span>
                    </dt>
                </dl>';
    }

    function empty_field(){
        $this->status = false;

        $field = '<li class="menu-item menu-item-edit-active">
                    <dl class="menu-item-bar">
                        <dt class="menu-item-handle">
                            <span class="item-title">
                                '.$this->option('text',array('name'=>'title')).'
                            </span>
                            <span class="item-controls">
                                <span class="item-type">
                                    '.$this->get_types().'
                                </span>
                            </span>
                        </dt>
                    </dl>
                    <div class="menu-item-settings" style="display: block;">
                            <p class="link-to-original" style="clear:both;">';

                            $edit = ($this->primary['custom-slug'])? true: false;

                            $field .= $this->option('text',array(
                                'name'=>'slug',
                                'label'=>__('MetaKey','rcl'),
                                'notice'=>__('not necessarily<br>if you want to enlist their arbitrary field, we list the meta_key in this field','rcl'),
                                'placeholder'=>__('Latin and numbers','rcl')
                            ),
                            $edit);

                            $field .= '</p>
                            <p class="place-sel link-to-original">
                            '.$this->get_options().'
                            </p>
                    </div>
            </li>';

        return $field;
    }

    function get_vals($name){
        global $Option_Value;

        foreach($Option_Value as $vals){
            if($vals[$name]) return $vals;
        }
    }

    function option($type,$args,$edit=true){
        $fld = '';

        if(!$this->vals&&!isset($this->status)){
            $this->options[][$type] = $args;
        }
        if($this->status&&!$this->vals)
            $this->vals = $this->get_vals($args['name']);

        if(!$this->status) $this->vals = '';

        if(isset($args['label'])&&$args['label']) $fld .= $args['label'].' ';
        $fld .= $this->$type($args,$edit);
        return $fld;
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
        if(isset($args['notice'])) $field .= $args['notice'];
        $field .= '<br />';
        return $field;
    }

    function text($args,$edit){
	$val = ($this->vals)? $this->vals[$args['name']]: '';
        if(!$edit) return $val.'<input type="hidden" name="field['.$args['name'].'][]" value="'.$val.'">';
        $ph = (isset($args['placeholder']))? $args['placeholder']: '';
        $field = '<input type="text" placeholder="'.$ph.'" name="field['.$args['name'].'][]" value="'.$val.'"> ';
        if(isset($args['notice'])) $field .= $args['notice'];
        return $field;
    }

    function options($args){
        global $Option_Value;
        $val = ($Option_Value['options']) ? $Option_Value['options'][$args['name']]: '';
        $ph = (isset($args['placeholder']))? $args['placeholder']: '';
        $field = '<input type="text" placeholder="'.$ph.'" name="options['.$args['name'].']" value="'.$val.'"> ';
        if(isset($args['notice'])) $field .= $args['notice'];
        return $field;
    }

    function verify(){
        if(!isset($_POST['add_field_public'])||!wp_verify_nonce( $_POST['_wpnonce'], 'update-public-fields' )) return false;
        return true;
    }

    function delete($slug,$table){
        global $wpdb;
        if($slug) $res = $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."$table WHERE meta_key = '%d' OR meta_key LIKE %s'",$slug,$slug.'%'));
        if($res) echo __('All values of a custom field with meta_key','rcl').' "'.$slug.'" '.__('were removed from the Database','rcl').'<br/>';
    }

    function update_fields($table='postmeta'){
        global $Option_Value;

        $fields = array();

	$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        if(isset($_POST['options'])){
                foreach($_POST['options'] as $key=>$val){
                        $fields['options'][$key] = $val;
                }
        }
        $fs = 0;
        $tps = array('select'=>1,'radio'=>1,'checkbox'=>1,'agree'=>1);
        foreach($_POST['field'] as $key=>$data){
            if($key=='field_select') continue;
            foreach($data as $a=>$value){
                if($table&&!$_POST['field']['title'][$a]){
                    if($_POST['field']['slug'][$a]){
                        $this->delete($_POST['field']['slug'][$a],$table);
                    }
                    continue;
                }
                if($key=='slug'&&!$value){
                    $value = str_replace('-','_',sanitize_title($_POST['field']['title'][$a]).'-'.rand(10,100));
                }
                if($key=='type'){
                    if(isset($tps[$_POST['field']['type'][$a]])){
                        $fields[$a]['field_select'] = $_POST['field']['field_select'][$fs++];
                    }
                }
                $fields[$a][$key] = $value;
            }
        }

        if($table&&$_POST['deleted']){
            $dels = explode(',',$_POST['deleted']);
            foreach($dels as $del){
                $this->delete($del,$table);
            }
        }

        $res = update_option( $this->name_option, $fields );

        if($res) $Option_Value = $fields;

        return $res;
    }
}
