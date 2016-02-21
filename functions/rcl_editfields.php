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
            case 'post': $name_option = 'rcl_fields_post_'.$this->primary['id']; break;
            case 'products': $name_option = 'rcl_fields_products'; break;
            case 'orderform': $name_option = 'rcl_cart_fields'; break;
            case 'profile': $name_option = 'rcl_profile_fields'; break;
            default: $name_option = 'rcl_fields_'.$posttype;
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
                    'label'=>__('List of columns to select','wp-recall'),
                    'placeholder'=>__('ID separated by comma','wp-recall'),
                    'pattern'=>'^([0-9,])*$'
                ));

                $form .= '<ul id="sortable">
                    '.$this->loop().'
                </ul>
            </div>
            <p style="width:550px;" id="add_public_field"><input type="button" class="button-secondary right" value="+ '.__('Add field','wp-recall').'"></p>
            <input id="save_menu_footer" class="button button-primary menu-save" type="submit" value="'.__('Save','wp-recall').'" name="add_field_public">
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
            'multiselect'=>1,
            'checkbox'=>1,
            'agree'=>1,
            'radio'=>1,
            'file'=>1
        );

        $notice = ($this->vals['type']=='file')? __('specify the types of files that are loaded by a comma, for example: pdf, zip, jpg','wp-recall'): __('the list of options to share the " # " sign','wp-recall');

        $textarea_select = (isset($types[$this->vals['type']]))?
            $notice.'<br>'
                        . '<textarea rows="1" class="field-select" style="height:50px" name="field[field_select][]">'.$this->vals['field_select'].'</textarea>'
        : '';

        $textarea_select .= ($this->vals['type']=='file')? '<input type="number" name="field[sizefile]['.$this->vals['slug'].']" value="'.$this->vals['sizefile'].'"> '.__('maximum size of uploaded file, MB (Default - 2)','wp-recall').'<br>':'';
        $textarea_select .= ($this->vals['type']=='agree')? '<input type="url" name="field[url-agreement]['.$this->vals['slug'].']" value="'.$this->vals['url-agreement'].'"> '.__('URL Agreement','wp-recall').'<br>':'';

        $field = '<li id="item-'.$this->vals['slug'].'" class="menu-item menu-item-edit-active">
                '.$this->header_field().'
                <div id="settings-'.$this->vals['slug'].'" class="menu-item-settings" style="display: none;">
                        <p class="link-to-original" style="clear:both;">
                            '.$this->option('text',array(
                                'name'=>'slug',
                                'label'=>__('MetaKey','wp-recall').':',
                                'notice'=>__('not necessarily<br>if you want to enlist their arbitrary field, we list the meta_key in this field','wp-recall'),
                                'placeholder'=>__('Latin and numbers','wp-recall')
                            ),false).'
                        </p>
                        <div class="link-to-original" style="overflow:hidden;">
                                <p class="description description-thin" style="width: 300px;">
                                    <label>
                                        '.$this->option('text',array(
                                            'name'=>'title',
                                            'label'=>__('Title','wp-recall').'<br>'
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
                </div>
        </li>';

        return $field;

    }

    function get_types(){
        return $this->option('select',array(
            'label'=>__('The field type','wp-recall'),
            'name'=>'type',
            'class'=>'typefield',
            'value'=>array(
                'text'=>__('Text','wp-recall'),
                'textarea'=>__('Textarea','wp-recall'),
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
                'file'=>__('File','wp-recall')
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
                            <a id="'.$this->vals['slug'].'" class="item-delete field-delete deletion" title="'.__('Delete','wp-recall').'" href="#"></a>
                            <a id="edit-'.$this->vals['slug'].'" class="profilefield-item-edit" href="#" title="'.__('Edit','wp-recall').'"></a>
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
                                'label'=>__('MetaKey','wp-recall'),
                                'notice'=>__('not necessarily<br>if you want to enlist their arbitrary field, we list the meta_key in this field','wp-recall'),
                                'placeholder'=>__('Latin and numbers','wp-recall')
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
        $pattern = (isset($args['pattern']))? 'pattern="'.$args['pattern'].'"': '';
        $field = '<input type="text" placeholder="'.$ph.'" '.$pattern.' name="field['.$args['name'].'][]" value="'.$val.'"> ';
        if(isset($args['notice'])) $field .= $args['notice'];
        return $field;
    }

    function options($args){
        global $Option_Value;
        $val = ($Option_Value['options']) ? $Option_Value['options'][$args['name']]: '';
        $ph = (isset($args['placeholder']))? $args['placeholder']: '';
        $pattern = (isset($args['pattern']))? 'pattern="'.$args['pattern'].'"': '';
        $field = '<input type="text" placeholder="'.$ph.'" title="'.$ph.'" '.$pattern.' name="options['.$args['name'].']" value="'.$val.'"> ';
        if(isset($args['notice'])) $field .= $args['notice'];
        return $field;
    }

    function verify(){
        if(!isset($_POST['add_field_public'])||!wp_verify_nonce( $_POST['_wpnonce'], 'update-public-fields' )) return false;
        return true;
    }

    function delete($slug,$table){
        global $wpdb;
        if($slug) $res = $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."$table WHERE meta_key = '%s'",$slug));
        if($res) echo __('All values of a custom field with meta_key','wp-recall').' "'.$slug.'" '.__('were removed from the Database','wp-recall').'<br/>';
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
        $tps = array('select'=>1,'multiselect'=>1,'radio'=>1,'checkbox'=>1,'agree'=>1,'file'=>1);
        foreach($_POST['field'] as $key=>$data){
            if($key=='field_select'||$key=='sizefile') continue;
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

                    if($_POST['field']['type'][$a]=='file'){
                        $fields[$a]['sizefile'] = $_POST['field']['sizefile'][$_POST['field']['slug'][$a]];
                    }
                    if($_POST['field']['type'][$a]=='agree'){
                        $fields[$a]['url-agreement'] = $_POST['field']['url-agreement'][$_POST['field']['slug'][$a]];
                    }
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
