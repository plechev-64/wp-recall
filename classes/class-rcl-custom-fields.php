<?php

class Rcl_Custom_Fields{

    public $value;
    public $slug;
    public $required;
    public $files;
    public $field_id;
    public $placeholder;
    public $maxlength;
    public $rand;
    public $value_in_key;
    public $key_in_data;

    function __construct($args = false){
        $this->files = array();
    }

    function get_title($field){
        
        if($field['type']=='agree'&&$field['url-agreement']) 
            return '<a target="_blank" href="'.$field['url-agreement'].'">'.$field['title'].'</a>';
        
        return $field['title'];
        
    }

    function get_input($field, $value = false){
        global $user_LK,$user_ID;
        
        $this->rand = rand(0,100);
        
        if(isset($field['field_select'])){
            
            $field['values'] = rcl_edit_old_option_fields($field['field_select'], $field['type']);

        }

        if(isset($field['requared']))
            $field['required'] = $field['requared'];

        $this->value = (isset($field['default']) && $value === false)? $field['default']: stripslashes_deep($value);
        $this->slug = isset($field['slug'])? $field['slug']: '';
        $this->field_id = (isset($field['field-id']))? $field['field-id']: $this->slug;
        $this->value_in_key = (isset($field['value_in_key']))? $field['value_in_key']: false;
        $this->key_in_data = (isset($field['key_in_data']))? $field['key_in_data']: false;
        $this->required = (isset($field['required']) && $field['required'] == 1)? 'required': '';
        $this->placeholder = (isset($field['placeholder'])&&$field['placeholder'])? "placeholder='".str_replace("'",'"',$field['placeholder'])."'": '';
        $this->maxlength = (isset($field['maxlength'])&&$field['maxlength'])? "maxlength='".$field['maxlength']."'": '';
        
        if(!$field['type']) return false;
        
        if(!isset($field['name']))
            $field['name'] = $this->slug;
        
        if($user_ID){
            if(isset($field['admin']) && $field['admin']==1 &&  !rcl_is_user_role($user_ID, array('administrator'))){
                $value = get_user_meta($user_LK,$this->slug,1);
                if($value){
                    $html = $this->get_field_value($field,$value,false);               
                    return $html;
                }
            }
        }

        if($field['type']=='date') 
            rcl_datepicker_scripts();

        $callback = 'get_type_'.$field['type'];

        $fieldHtml = $this->$callback($field);
        
        if($this->maxlength){
            $fieldHtml .= '<script>rcl_init_field_maxlength("'.$this->field_id.'");</script>';
        }
        
        $content = '<div id="rcl-field-'.$this->field_id.'" class="rcl-field-input type-'.$field['type'].'-input">'
                        . '<div class="rcl-field-core">'
                            . $fieldHtml
                        . '</div>'
                        . $this->get_notice($field)
                    . '</div>';
        
        return $content;
    }
    
    function get_notice($field){
        
        if(isset($field['notice']) && $field['notice']) 
            return '<span class="rcl-field-notice"><i class="fa fa-info" aria-hidden="true"></i>'.$field['notice'].'</span>';
        
        return false;
        
    }
    
    function get_type_custom($args){
        
        if(isset($args['content'])){
            return $args['content'];
        }
        
        return;
    }
    
    function get_type_dynamic($args){
        
        $this->value = rcl_edit_old_option_fields($this->value);
        
        $field = '<span class="dynamic-values">';
        
        if($this->value && is_array($this->value)){
            $cnt = count($this->value);
            foreach((array)$this->value as $k=>$val){
                $field .= '<span class="dynamic-value">';
                $field .= '<input type="text" '.$this->required.' '.$this->placeholder.' name="'.$args['name'].'[]" value="'.$val.'"/>';
                if($cnt==($k+1)){
                    $field .= '<a href="#" onclick="rcl_add_dynamic_field(this);return false;"><i class="fa fa-plus" aria-hidden="true"></i></a>';
                }else{
                    $field .= '<a href="#" onclick="rcl_remove_dynamic_field(this);return false;"><i class="fa fa-minus" aria-hidden="true"></i></a>';
                }
                $field .= '</span>';
            }
        }else{
            $field .= '<span class="dynamic-value">';
            $field .= '<input type="text" '.$this->required.' '.$this->placeholder.' name="'.$args['name'].'[]" value=""/>';
            $field .= '<a href="#" onclick="rcl_add_dynamic_field(this);return false;"><i class="fa fa-plus" aria-hidden="true"></i></a>';
            $field .= '</span>';
        }
        
        $field .= '</span>';
        
        return $field;
    }

    function get_type_file($field){
        global $user_ID;
        $input = '';

        if(is_admin()&&!(defined( 'DOING_AJAX' ) && DOING_AJAX)){
            
            $post_id = (isset($_GET['post']))? intval($_GET['post']): false;
            $user_id = (isset($_GET['user_id']))? intval($_GET['user_id']): false;
            
            $url = admin_url('?meta='.$this->slug.'&rcl-delete-file='.base64_encode($this->value));
            
            if($post_id){
                $url .= '&post_id='.$post_id;
            }else if($user_id){
                $url .= '&user_id='.$user_id;
            }else{
                $url .= '&user_id='.$user_ID;
            }
            
        }else{
            
            $url = get_bloginfo('wpurl').'/?meta='.$this->slug.'&rcl-delete-file='.base64_encode($this->value);
            
        }

        if($this->value){
            
            $input .= $this->get_field_value($field,$this->value,0);
            
            if(!$field['required']) 
                $input .= '<span class="delete-file-url"><a href="'.wp_nonce_url($url, 'user-'.$user_ID ).'"> <i class="fa fa-times-circle-o"></i>'.__('delete','wp-recall').'</a></span>';
            
            $input = '<span class="file-manage-box">'.$input.'</span>';
            
        }

        $accTypes = false;
        $extTypes = isset($field['ext-files'])? array_map('trim',explode(',',$field['ext-files'])): array();

        if($extTypes)
            $accTypes = rcl_get_mime_types($extTypes);

        $accept = ($accTypes)? 'accept="'.implode(',',$accTypes).'"': '';
        $required = (!$this->value)? $this->required: '';
        
        $size = ($field['sizefile'])? $field['sizefile']: 2;

        $input .= '<span id="'.$this->slug.'-content" class="file-field-upload">';
        $input .= '<input data-size="'.$size.'" '.($extTypes? 'data-ext="'.implode(',',$extTypes).'"': '').' type="file" '.$required.' '.$accept.' name="'.$field['name'].'" '.$this->get_class($field).' id="'.$this->field_id.'" value=""/> ';
        
        $notice = '(';
        
        if($extTypes)
            $notice .= '<span class="allowed-types">'.__('Allowed extensions','wp-recall').': '.$field['ext-files'].'<span>. ';
        
        $notice .= __('Max size','wp-recall').': '.$size.'MB';
        
        $notice .= ')';
        
        $input .= $notice;
        
        $input .= '<script type="text/javascript">rcl_init_field_file("'.$this->slug.'");</script>';
        $input .= '</span>';

        $this->files[$this->slug] = $size;

        return $input;
    }
    
    function get_class($field){
        
        $class = array($field['type'].'-field');
        
        if(isset($field['classes']) && $field['classes'])
            $class[] = $field['classes'];
        
        return 'class="'.implode(' ',$class).'"';
    }

    function get_type_select($field){
        
        $values = $field['values'];

        $emptyFirst = (isset($field['empty-first']))? $field['empty-first']: false;
        
        $content = '<select '.$this->required.' name="'.$field['name'].'" id="'.$this->field_id.'" '.$this->get_class($field).'>';

        if($emptyFirst)
            $content .= '<option value="">'.$emptyFirst.'</option>';
        
        if($values){
            foreach($values as $k => $value){

                $data = ($this->key_in_data)? 'data-key="'.$k.'"': '';

                if($this->value_in_key) $k = $value;

                $content .= '<option '.selected($this->value,$k,false).' '.$data.' value="'.trim($k).'">'.$value.'</option>';
            }
        }
        
        $content .= '</select>';

        return $content;
    }

    function get_type_multiselect($field){
        
        rcl_multiselect_scripts();
        
        if(isset($field['field_select']))
            $field['values'] = rcl_edit_old_option_fields($field['field_select'], $field['type']);
        
        if(!$field['values']) return false;
        
        $this->value = ($this->value)? $this->value: array();
        
        if(!is_array($this->value))
            $this->value = array($this->value);

        $content = '<select '.$this->required.' name="'.$field['name'].'[]" id="'.$this->field_id.'" '.$this->get_class($field).' multiple>';

        foreach($field['values'] as $k => $value){
            
            if($this->value_in_key) $k = $value;
            
            $content .= '<option '.selected(in_array($k,$this->value),true,false).' value="'.trim($k).'">'.$value.'</option>';
        }
        
        $content .= '</select>';
        
        $init = 'jQuery("#'.$this->field_id.'").fSelect();';
        
        if(!defined( 'DOING_AJAX' )){
            $content .= '<script>jQuery(window).on("load", function() {' . $init . '});</script>';
        }else{
            $content .= '<script>' . $init . '</script>';
        }
        
        return $content;
    }

    function get_type_checkbox($field){
        
        $values = $field['values'];
        
        if(!$values) return false;
        
        $currentValues = (is_array($this->value))? $this->value: array();
        
        $field['classes'] = ($this->required) ? 'required-checkbox':'';
        
        $input = '';
        
        foreach($values as $k => $value){

            if($this->value_in_key) $k = $value;

            $checked = checked(in_array($k,$currentValues),true,false);
            
            $input .='<span class="rcl-checkbox-box">'
                    . '<input '.$this->required.' '.$checked.' id="'.$this->field_id.'_'.$k.$this->rand.'" type="checkbox" '.$this->get_class($field).' name="'.$field['name'].'[]" value="'.trim($k).'"> ';
            $input .='<label class="block-label" for="'.$this->field_id.'_'.$k.$this->rand.'">';
            $input .= (!isset($field['before']))? '': $field['before'];
            $input .= $value
                    .'</label>'
                    . '</span>';
            $input .= (!isset($field['after']))? '': $field['after'];
            
        }
        
        return $input;
    }

    function get_type_radio($field){
        
        if(isset($field['field_select']))
            $field['values'] = rcl_edit_old_option_fields($field['field_select'], $field['type']);
        
        if(!$field['values']) return false;

        $input = '';
        $a = 0;
        
        foreach($field['values'] as $k => $value){
            
            if($this->value_in_key) $k = $value;
            
            $input .='<span class="rcl-radio-box">'
                    . '<input '.$this->required.' '.checked($this->value,$k,false).' '.checked($a,0,false).' type="radio" '.$this->get_class($field).' id="'.$this->field_id.'_'.$k.$this->rand.'" name="'.$field['name'].'" value="'.trim($k).'"> ';
            $input .='<label class="block-label" for="'.$this->field_id.'_'.$k.$this->rand.'">';        
            $input .= $value
                    .'</label>'
                    . '</span>';
            
            $a++;
            
        }
        
        return $input;
    }
    
    function get_type_editor($field){
        
        $editor_id = false;
        
        if(isset($field['editor-id']))
            $editor_id = $field['editor-id'];
        
        if(!$editor_id)
            $editor_id = ($this->field && isset($this->field['slug']))? $this->field['slug']: $this->new_slug;

        if(!$editor_id)
            $editor_id = $field['slug'];
        
        $editor_id = 'editor-'.$editor_id;
        
        $tinymce = (isset($field['tinymce']))? $field['tinymce']: false;

        $data = array( 'wpautop' => 1
            ,'media_buttons' => false
            ,'textarea_name' => $field['name']
            ,'textarea_rows' => 10
            ,'tabindex' => null
            ,'editor_css' => ''
            ,'editor_class' => 'autosave'
            ,'teeny' => 0
            ,'dfw' => 0
            ,'tinymce' => $tinymce
            ,'quicktags' => (isset($field['quicktags']))? array('buttons'=>$field['quicktags']): true
        );
        
        ob_start();

        wp_editor( $this->value, $editor_id, $data );
        
        if(defined( 'DOING_AJAX' )){
            global $wp_scripts, $wp_styles;
            
            $wp_scripts->do_items(array(
                'quicktags'
            ));
            
            $wp_styles->do_items(array(
                'buttons'
            ));
        }

        $content = ob_get_contents();
        
        if(defined( 'DOING_AJAX' )){
            $content .= '<script>rcl_init_ajax_editor("'.$editor_id.'",'.json_encode(array(
                'tinymce' => $tinymce,
                'qt_buttons' => (isset($field['quicktags']))? $field['quicktags']: false
            )).');</script>';
        }

        ob_end_clean();

        return $content;
    }
    
    function get_type_runner($field){
        
        rcl_slider_scripts();
        
        $idRunner = rand(0,10000);
        
        $min = isset($field['value_min'])? $field['value_min']: 0;
        $max = isset($field['value_max'])? $field['value_max']: 100;
        $step = isset($field['value_step'])? $field['value_step']: 1;
        
        $content = '<div id="rcl-runner-'.$idRunner.'" class="rcl-runner">';
            $content .= '<span class="rcl-runner-value"></span>';
            $content .= '<div class="rcl-runner-box"></div>';
            $content .= '<input type="hidden" class="rcl-runner-field" id="'.$this->field_id.'" name="'.$field['name'].'" value="'.$min.'">';
        $content .= '</div>';
        
        $init = 'rcl_init_runner('.json_encode(array(
                'id' => $idRunner,
                'value' => $this->value? $this->value: 0,
                'min' => $min,
                'max' => $max,
                'step' => $step,
            )).');';
        
        if(!defined( 'DOING_AJAX' )){
            $content .= '<script>jQuery(window).on("load", function() {' . $init . '});</script>';
        }else{
            $content .= '<script>' . $init . '</script>';
        }
        
        return $content;
    }
    
    function get_type_range($field){
        
        rcl_slider_scripts();
        
        $idRunner = rand(0,10000);
        
        $min = isset($field['value_min'])? $field['value_min']: 0;
        $max = isset($field['value_max'])? $field['value_max']: 100;
        $step = isset($field['value_step'])? $field['value_step']: 1;
        
        $content = '<div id="rcl-range-'.$idRunner.'" class="rcl-range">';
            $content .= '<span class="rcl-range-value">'.(implode(' - ', array($min, $max))).'</span>';
            $content .= '<div class="rcl-range-box"></div>';
            $content .= '<input type="hidden" class="rcl-range-min" name="'.$field['name'].'[]" value="'.$min.'">';
            $content .= '<input type="hidden" class="rcl-range-max" name="'.$field['name'].'[]" value="'.$max.'">';
        $content .= '</div>';
        
        $init = 'rcl_init_range('.json_encode(array(
                'id' => $idRunner,
                'values' => $this->value? $this->value: array($min,$field['value_max']),
                'min' => $min,
                'max' => $max,
                'step' => $step,
            )).');';
        
        if(!defined( 'DOING_AJAX' )){
            $content .= '<script>jQuery(window).on("load", function() {' . $init . '});</script>';
        }else{
            $content .= '<script>' . $init . '</script>';
        }
        
        return $content;
    }
    
    function get_type_color($field){

        wp_enqueue_style('wp-color-picker'); 
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script('wp-color-picker');
        
        $content = '<input type="text" '.$this->get_class($field).' name="'.$field['name'].'" id="'.$this->field_id.'" value="'.$this->value.'"/>';
        
        $init = 'rcl_init_color("'.$this->field_id.'",'.json_encode(array(
            'defaultColor' => $this->value
        )).')';
        
        if(!defined( 'DOING_AJAX' )){
            $content .= '<script>jQuery(window).on("load", function() {' . $init . '});</script>';
        }else{
            $content .= '<script>' . $init . '</script>';
        }
        
        return $content;
    }

    function get_type_agree($field){
        
        $text = (isset($field['text-confirm']) && $field['text-confirm'])? $field['text-confirm']: __('I agree with the text of the agreement','wp-recall');
        
        $input = '<span class="rcl-checkbox-box">';
        $input .= '<input type="checkbox" '.checked($this->value,1,false).' '.$this->required.' name="'.$field['name'].'" id="'.$this->field_id.$this->rand.'" value="1"/> '
                . '<label class="block-label" for="'.$this->field_id.$this->rand.'">'.$text.'</label>';
        $input .= '</span>';
        
        return $input;
    }
    
    function get_type_textarea($field){
        return '<textarea name="'.$field['name'].'" '.$this->maxlength.' '.$this->required.' '.$this->placeholder.' '.$this->get_class($field).' id="'.$this->field_id.'" rows="5" cols="50">'.$this->value.'</textarea>';
    }
    
    function get_type_text($field){
        
        $pattern = (isset($field['pattern']) && $field['pattern'])? 'pattern="'.$field['pattern'].'"': '';
        
        return '<input type="text" '.$pattern.' '.$this->maxlength.' '.$this->required.' '.$this->placeholder.' '.$this->get_class($field).' name="'.$field['name'].'" id="'.$this->field_id.'" value="'.$this->value.'"/>';
    }
    
    function get_type_password($field){
        return '<input type="password" '.$this->required.' '.$this->placeholder.' '.$this->get_class($field).' name="'.$field['name'].'" id="'.$this->field_id.'" value="'.$this->value.'"/>';
    }

    function get_type_tel($field){
        return '<input type="tel" '.$this->required.' '.$this->placeholder.' '.$this->get_class($field).' name="'.$field['name'].'" id="'.$this->field_id.'" maxlength="50" value="'.$this->value.'"/>';
    }

    function get_type_email($field){
        return '<input type="email" '.$this->required.' '.$this->placeholder.' '.$this->get_class($field).' name="'.$field['name'].'" id="'.$this->field_id.'" maxlength="50" value="'.$this->value.'"/>';
    }

    function get_type_url($field){
        return '<input type="url" '.$this->required.' '.$this->placeholder.' '.$this->get_class($field).' name="'.$field['name'].'" id="'.$this->field_id.'" value="'.$this->value.'"/>';
    }

    function get_type_date($field){
        
        $field['classes'] = 'rcl-datepicker';
        
        $content = '<input type="text" '.$this->get_class($field).' onclick="rcl_show_datepicker(this);" title="'.__('Use the format','wp-recall').': yyyy-mm-dd" pattern="(\d{4}-\d{2}-\d{2})" '.$this->required.' '.$this->placeholder.' class="rcl-datepicker" name="'.$field['name'].'" id="'.$this->field_id.'" value="'.$this->value.'"/>';
        
        return $content;
    }

    function get_type_time($field){
        return '<input type="time" '.$this->required.' '.$this->placeholder.' '.$this->get_class($field).' name="'.$field['name'].'" id="'.$this->field_id.'" maxlength="50" value="'.$this->value.'"/>';
    }

    function get_type_number($field){
        return '<input type="number" '.$this->required.' '.$this->placeholder.' '.$this->get_class($field).' name="'.$field['name'].'" id="'.$this->field_id.'" maxlength="50" value="'.$this->value.'"/>';
    }
    
    function get_type_hidden($field){
        return '<input type="hidden" name="'.$field['name'].'" id="'.$this->field_id.'" value="'.$field['value'].'"/>';
    }

    function get_field_value($field,$value=false,$title=true){
        global $user_ID;
        
        if(!isset($field['type'])||!$value) return false;
        
        $show = '';
        
        if($value) 
            $value = stripslashes_deep($value);
        
        if(is_array($value)){
          
            if($field['filter']){
                
                $links = array();
                
                foreach($value as $val){
                    
                    if(!$val) continue;
                    
                    $links[] = '<a href="'.$this->get_filter_url($field['slug'],$val).'" target="_blank">'.$val.'</a>';
                    
                }
                
                $value = $links;
            }
            
            $array_types = array('checkbox','multiselect','dynamic','range');
            
            if(in_array($field['type'],$array_types)){
                if($value){
                    if($field['type'] == 'range'){
                        $show = __('от','wp-recall').' '.$value[0].' '.__('до','wp-recall').' '.$value[1];
                    }else{
                        $show = implode(', ',$value);
                    }
                    
                }
            }
            
        }else if($field['type'] == 'editor'){
            
            $value = $value;
            
        }else{
            
            $value = esc_html($value);
            
            if(isset($field['filter'])&&$field['filter']){
                $value = '<a href="'.$this->get_filter_url($field['slug'],$value).'" target="_blank">'.$value.'</a>';
            }

        }
        
        $types = array('text','tel','time','date','number','select','radio','runner');
            
        if(in_array($field['type'],$types)){
            $show = $value;
        }

        if($field['type']=='file')
                $show = '<i class="fa fa-upload" aria-hidden="true"></i><a href="'.wp_nonce_url(get_bloginfo('wpurl').'/?rcl-download-file='.base64_encode($value), 'user-'.$user_ID ).'">'.__('Upload the downloaded file','wp-recall').'</a>';
        if($field['type']=='email')
                $show = '<a rel="nofollow" target="_blank" href="mailto:'.$value.'">'.$value.'</a>';
        if($field['type']=='url')
                $show = '<a rel="nofollow" target="_blank" href="'.$value.'">'.$value.'</a>';            
        if($field['type']=='textarea' || $field['type']=='editor')
                $show = nl2br($value);
        if($field['type']=='agree')
                $show = __('Accepted','wp-recall');
        
        if(!$show) return false;
            
        $show = '<span class="rcl-field-value type-'.$field['type'].'-value">'.$show.'</span>';
        
        if(isset($field['after'])) 
            $show .= ' '.$field['after'];

        $content = '<p class="rcl-custom-fields">';

        if(isset($field['title']) && $title) 
            $content .= '<b>'.$field['title'].':</b> ';

        $content .= $show.'</p>';

        return $content;
    }
    
    function get_filter_url($slug,$value){
        if(!rcl_get_option('users_page_rcl')) return false;
        return rcl_format_url(get_permalink(rcl_get_option('users_page_rcl'))).'usergroup='.$slug.':'.urlencode($value) ;
    }

    function register_user_metas($user_id){

        rcl_update_profile_fields($user_id);

    }

}

function rcl_upload_meta_file($field, $user_id, $post_id = 0){
    
    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

    $slug = $field['slug'];
    $maxsize = ($field['sizefile'])? $field['sizefile']: 2;
    
    if(!isset($_FILES[$slug]) && $post_id){
        delete_post_meta($post_id, $slug);
        return false;
    }

    if(!$_FILES[$slug]['tmp_name']){
        return false;
    }

    if ($_FILES[$slug]["size"] > $maxsize*1024*1024){
        wp_die( __('File size exceedes maximum!','wp-recall'));
    }
    
    $accept = array();
    $attachment = array();
    
    if($field['ext-files']){
        
        $valid_types = array_map('trim',explode(',',$field['ext-files']));
        $filetype = wp_check_filetype_and_ext( $_FILES[$slug]['tmp_name'], $_FILES[$slug]['name'] );

        if (!in_array($filetype['ext'], $valid_types)){ 
            wp_die( __('Prohibited file type!','wp-recall'));
        }
    }

    $file = wp_handle_upload( $_FILES[$slug], array('test_form' => FALSE) );

    if($file['url']){

        if($post_id) 
            $file_id = get_post_meta($post_id,$slug,1);
        else 
            $file_id = get_user_meta($user_id,$slug,1);
        
        if($file_id) 
            wp_delete_attachment($file_id);

        $attachment = array(
            'post_mime_type' => $file['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($file['file'])),
            'post_name' => $slug.'-'.$user_id.'-'.$post_id,
            'post_content' => '',
            'guid' => $file['url'],
            'post_parent' => $post_id,
            'post_author' => $user_id,
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment( $attachment, $file['file'], $post_id );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file['file'] );
        
        wp_update_attachment_metadata( $attach_id, $attach_data );

        return $attach_id;

    }
}

function rcl_get_custom_fields($post_id, $post_type = false, $id_form = false){

    if($post_id){
        $post = get_post($post_id);
        $post_type = $post->post_type;
    }

    switch($post_type){
        case 'post':
            if(isset($post)) $id_form = get_post_meta($post->ID,'publicform-id',1);
            if(!$id_form) $id_form = 1;
            $id_field = 'rcl_fields_post_'.$id_form;
        break;
        default: $id_field = 'rcl_fields_'.$post_type;
    }

    return apply_filters('rcl_custom_fields_post', get_option($id_field), $post_id, $post_type);
    
}

add_action('wp','rcl_download_file');
function rcl_download_file(){
    global $user_ID;

    if ( !isset( $_GET['rcl-download-file'] ) ) return false;
    
    $fileID = intval(base64_decode($_GET['rcl-download-file']));

    if (!$fileID || !wp_verify_nonce( $_GET['_wpnonce'], 'user-'.$user_ID ) ) return false;

    $file = get_post($fileID);

    if(!$file) wp_die(__('File does not exist on the server!','wp-recall'));
    
    while (ob_get_level()) {
        ob_end_clean();
    }

    $path = get_attached_file($fileID);

    header( 'Content-Disposition: attachment; filename="'.basename($path).'"' );
    header( "Content-Transfer-Encoding: binary");
    header( 'Pragma: no-cache');
    header( 'Expires: 0');
    header( 'Content-Length: '.filesize($path));
    header( 'Accept-Ranges: bytes' );
    header( 'Content-Type: application/octet-stream' );
    readfile($path);
    exit;
}

if(!is_admin()) 
    add_action('wp','rcl_delete_file');
function rcl_delete_file(){
    global $user_ID;

    if ( !isset( $_GET['rcl-delete-file'] ) ) return false;
    $id_file = intval(base64_decode($_GET['rcl-delete-file']));

    if ( !$user_ID||!wp_verify_nonce( $_GET['_wpnonce'], 'user-'.$user_ID ) ) return false;

    $file = get_post($id_file);

    if(!$file) wp_die(__('File does not exist on the server!','wp-recall'));

    wp_delete_attachment($file->ID);

    if($file->post_parent){
        wp_redirect(rcl_format_url(get_permalink(rcl_get_option('public_form_page_rcl'))).'rcl-post-edit='.$file->post_parent);
    }else{
        wp_redirect(rcl_get_tab_permalink($user_ID,'profile').'&file=deleted');
    }

    exit;
}

if(is_admin()) 
    add_action('admin_init','rcl_delete_file_admin');
function rcl_delete_file_admin(){
    global $user_ID;

    if ( !isset( $_GET['rcl-delete-file'] ) ) return false;
    $id_file = intval(base64_decode($_GET['rcl-delete-file']));

    if ( !$user_ID||!wp_verify_nonce( $_GET['_wpnonce'], 'user-'.$user_ID ) ) return false;
    
    $post_id = (isset($_GET['post_id']))? intval($_GET['post_id']): false;
    $user_id = (isset($_GET['user_id']))? intval($_GET['user_id']): false;

    $file = get_post($id_file);

    if(!$file) wp_die(__('File does not exist on the server!','wp-recall'));

    wp_delete_attachment($file->ID);
    
    if($post_id){
        $url = admin_url('post.php?post='.$post_id.'&action=edit');
    }else if($user_id){
        $url = admin_url('user-edit.php?user_id='.$user_id);
    }else{
        $url = admin_url('profile.php');
    }

    wp_redirect($url);

    exit;
}

add_action('delete_attachment','rcl_delete_file_meta');
function rcl_delete_file_meta($post_id){
    $post = get_post($post_id);
    $slug = explode('-',$post->post_name);
    if($post->post_parent) delete_post_meta($post->post_parent,$slug[0],$post_id);
    else delete_user_meta($post->post_author,$slug[0],$post_id);
}

add_action('wp','rcl_delete_file_notice');
function rcl_delete_file_notice(){
    if (isset($_GET['file'])&&$_GET['file']='deleted') rcl_notice_text(__('File has been deleted','wp-recall'),'success');
}

