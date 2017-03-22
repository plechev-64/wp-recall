<?php

class Rcl_Public_Form{
    
    public $post_id = 0;
    public $post_type = 'post';
    public $form_id = 1;
    public $taxonomies;
    public $fields;
    public $post;
    public $current_field = array();
    public $options = array(
        'preview' => 1,
        'draft' => 1,
        'delete' => 1
    );
    public $user_can = array(
        'upload' => false,
        'publish' => false,
        'delete' => false,
        'draft' => false,
        'edit' => false
    );
    
    function __construct($args = false){
        
        $this->init_properties($args);
        
        if(isset($_GET['rcl-post-edit'])){

            $this->post_id = $_GET['rcl-post-edit'];
            $this->post = get_post($this->post_id);
            
            $this->post_type = $this->post->post_type;
            
        }
        
        $this->init_user_can();
        
        $this->taxonomies = get_object_taxonomies( $this->post_type, 'objects' );
        
        if($this->post_type == 'post'){
            unset($this->taxonomies['post_format']);
        }
        
        $this->fields = $this->get_fields();

        do_action('rcl_public_form_init', $this->get_object_form());

        $this->options = apply_filters('rcl_public_form_options', $this->options, $this->get_object_form());
        
        if($this->options['preview']) rcl_dialog_scripts();
        
        if($this->user_can['upload']){
            rcl_fileupload_scripts();
            add_action('wp_footer', array($this, 'init_form_scripts'), 100);
        }
        
    }
    
    function init_properties($args){
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
    }
    
    function get_object_form(){
        $dataForm = array();
        $dataForm['post_id'] = $this->post_id;
        $dataForm['post_type'] = $this->post_type;
        $dataForm['post_status'] = ($this->post_id)? $this->post->post_type: 'new';
        $dataForm['post_content'] = ($this->post_id)? $this->post->post_content: '';
        $dataForm['post_excerpt'] = ($this->post_id)? $this->post->post_excerpt: '';
        $dataForm['post_title'] = ($this->post_id)? $this->post->post_title: '';
        $dataForm = (object)$dataForm;
        return $dataForm;
    }
    
    function init_user_can(){
        global $user_ID,$rcl_options;

        $this->user_can['publish'] = true;
        
        $user_can = $rcl_options['user_public_access_recall'];
		
        if($user_can){

            if($user_ID){
                
                $userinfo = get_userdata( $user_ID );

                if($userinfo->user_level >= $user_can) 
                    $this->user_can['publish'] = true;
                else 
                    $this->user_can['publish'] = false;
                
            }else{
                
                $this->user_can['publish'] = false;
                
            }

        }
        
        $this->user_can['draft'] = $user_ID? true: false;
        
        $this->user_can['upload'] = $this->user_can['publish'];
        
        if($user_ID && $this->post_id){
            
            $this->user_can['edit'] = (current_user_can('edit_post', $this->post_id))? true: false;
            
            if(!$this->user_can['edit'] && $this->post_type == 'post-group'){
                
                $this->user_can['edit'] = (rcl_can_user_edit_post_group($this->post_id))? true: false;

            }
            
            $this->user_can['delete'] = $this->user_can['edit'];
            
        }
        
        $this->user_can = apply_filters('rcl_public_form_user_can', $this->user_can, $this->get_object_form());
                
        
    }
    
    function get_fields(){
        global $user_ID;
        
        if($this->post_type == 'post')
            $fields = get_option('rcl_fields_'.$this->post_type.'_1');
        else
            $fields = get_option('rcl_fields_'.$this->post_type);
		
        if(!$fields)
            $fields = array();
        
        $fields = apply_filters('rcl_public_form_fields', $fields, $this->get_object_form());
        
        $fields = apply_filters('rcl_'.$this->post_type.'_form_fields', $fields, $this->get_object_form());
        
        if(!isset($fields['options']['user-edit']) || !$fields['options']['user-edit']){

            $fields = array_merge($this->get_default_fields(), $fields);

        }
        
        if(!$user_ID){
            
            $guestFields = array(
                array(
                    'slug' => 'name-user',
                    'title' => __('Your Name','wp-recall'),
                    'required' => 1,
                    'type' => 'text'
                ),
                array(
                    'slug' => 'email-user',
                    'title' => __('Your E-mail','wp-recall'),
                    'required' => 1,
                    'type' => 'email'
                )
            );
            
            $fields = array_merge($guestFields, $fields);

        }
        
        if(isset($fields['options'])){
            
            unset($fields['options']);
            
        }

        return $fields;
        
    }
    
    function get_form(){

        if(!$this->user_can['publish']){
            
            if(!$user_ID)
                return '<p align="center" class="rcl-public-notice">'.__('You must be logged in to post. Login or register','wp-recall').'</p>';
            
            if($this->post_type == 'post-group'){
                return '<div class="public-post-group">'
                            . '<h3 >'.__('Sorry, but you have no rights to publish in this group :(','wp-recall').'</h3>'
                        . '</div>';
                
            }
            
            return '<p align="center" class="rcl-public-notice">'. __('Sorry, but you have no right to post on this site :(','wp-recall') . '</p>';
            
        }
        
        if($this->post_id && !$this->user_can['edit'])
            return '<p align="center" class="rcl-public-notice">'.__('You can not edit this publication :(','wp-recall').'</p>';

        
        $dataPost = $this->get_object_form();
        
        $defaultFields = array(
            'post_content',
            'post_title',
            'post_uploader',
            'post_excerpt'
        );
        
        $taxField = array();
        
        if($this->taxonomies){
            
            foreach($this->taxonomies as $taxname => $object){

                $taxField[] = 'taxonomy-'.$taxname;

            }
            
        }
        
        $content = '<div class="rcl-public-box rcl-table">';
        
        $content .= '<form action="" method="post" class="rcl-public-form" data-post_id="'.$this->post_id.'" data-post_type="'.$this->post_type.'">';
        
        if($this->fields){
                
            $CF = new Rcl_Custom_Fields();

            foreach($this->fields as $this->current_field){
                
                $this->current_field['value_in_key'] = true;

                $required = ($this->current_field['required'] == 1)? '<span class="required">*</span>': '';

                if($this->taxonomies && in_array($this->current_field['slug'],$taxField)){

                    if($taxonomy = $this->is_taxonomy_field($this->current_field['slug'])){

                        $contentField = $this->get_terms_list($taxonomy);
                        
                    }
                    
                }else{
                    
                    if(in_array($this->current_field['slug'],$defaultFields)){
                        
                        if($this->current_field['slug'] == 'post_content'){
                            
                            $contentField = $this->get_editor(array(
                                'post_content' => $dataPost->post_content, 
                                'options' => $this->current_field['post-editor']
                            ));
                            
                        }
                        
                        if($this->current_field['slug'] == 'post_excerpt'){
                            
                            $contentField = $CF->get_input($this->current_field,$dataPost->post_excerpt);
                            
                        }
                        
                        if($this->current_field['slug'] == 'post_title'){
                            
                            $contentField = $CF->get_input($this->current_field,$dataPost->post_title);
                            
                        }
                        
                        if($this->current_field['slug'] == 'post_uploader'){

                            $postUploder = new Rcl_Public_Form_Uploader(array(
                                'post_id' => $this->post_id,
                                'post_type' => $this->post_type
                            ));
                            
                            $contentField = $postUploder->get_uploader();
                            
                        }
                        
                    }else{
                        
                        $postmeta = ($this->post_id)? get_post_meta($this->post_id,$this->current_field['slug'],1): '';
                        
                        $contentField = $CF->get_input($this->current_field, $postmeta);
                        
                    }

                }
                
                if(!$contentField) continue;
                
                $content .= '<div id="form-field-'.$this->current_field['slug'].'" class="rcl-form-field field-'.$this->current_field['type'].'">';
                
                $content .= '<label>'.$CF->get_title($this->current_field).' '.$required.'</label>';
                
                $content .= $contentField;

                $content .= '</div>';
            }

        }
        
        $content .= apply_filters('rcl_public_form','',$this->get_object_form());

        $content .= '<div class="rcl-form-field submit-public-form">';
        
        if($this->options['draft'] && $this->user_can['draft'])
            $content .= '<a href="#" onclick="rcl_save_draft(this); return false;" id="rcl-draft-post" class="public-form-button recall-button"><i class="fa fa-history" aria-hidden="true"></i>'.__('Save as Draft','wp-recall').'</a>';
        
        if($this->options['preview'])
            $content .= '<a href="#" onclick="rcl_preview(this); return false;" id="rcl-preview-post" class="public-form-button  recall-button"><i class="fa fa-eye" aria-hidden="true"></i>'.__('Preview','wp-recall').'</a>';
        
        $content .= '<a href="#" onclick="rcl_publish(this); return false;" id="rcl-publish-post" class="public-form-button  recall-button"><i class="fa fa-print" aria-hidden="true"></i>'.__('Publish','wp-recall').'</a>';
        
        $content .= '</div>';
        
        if($this->form_id)
            $content .= '<input type="hidden" name="public_form_id" value="'.$this->form_id.'">';
        
        $content .= '<input type="hidden" name="post_id" value="'.$this->post_id.'">';
        $content .= '<input type="hidden" name="post_type" value="'.$this->post_type.'">';
        $content .= '<input type="hidden" name="rcl-edit-post" value="1">';
        $content .= wp_nonce_field('rcl-edit-post','_wpnonce',true,false);
        $content .= '</form>';
        
        if($this->user_can['delete']){
            
            $content .= '<div id="form-field-delete" class="rcl-form-field">';
            
            $content .= $this->get_delete_box();
            
            $content .= '</div>';
            
        }

        $content .= '</div>';
        
        return $content;
        
    }

    function get_terms_list($taxonomy){
        
        $content = '<div class="rcl-terms-select taxonomy-'.$taxonomy.'">';
        
        $terms = $this->current_field['values']? $this->current_field['values']: array();
        
        if($this->is_hierarchical_tax($taxonomy)){
            
            if($this->post_type == 'post-group'){
                
                global $rcl_group;
                
                if($rcl_group->term_id){
                    $group_id = $rcl_group->term_id;
                }else if($this->post_id){
                    $group_id = rcl_get_group_id_by_post($this->post_id);
                }

                $options_gr = rcl_get_options_group($group_id);
                
                $termList = rcl_get_tags_list_group($options_gr['tags'],$this->post_id);

                if(!$termList)
                    return false;
                
                $content .= $termList;
                
            }else{
                
                $type = (isset($this->current_field['type-select']) && $this->current_field['type-select'])? $this->current_field['type-select']: 'select';
                $number = (isset($this->current_field['number-select']) && $this->current_field['number-select'])? $this->current_field['number-select']: 1;

                $termList = new Rcl_List_Terms($taxonomy, $type, $this->current_field['required']);

                $content .= $termList->get_select_list($this->get_allterms($taxonomy),$this->get_post_terms($taxonomy),$number,$terms);
                
            }

        }else{

            $content .= $this->tags_field($taxonomy, $terms);

        }

        if(isset($this->current_field['notice']) && $this->current_field['notice']) 
            $content .= '<span class="rcl-field-notice"><i class="fa fa-info" aria-hidden="true"></i>'.$this->current_field['notice'].'</span>';

        $content .= '</div>';
        
        return $content;
        
    }
    
    function upload_box(){
        global $rcl_options;
        
        $wp_uploader = (isset($rcl_options['media_uploader']))? $rcl_options['media_uploader']: 0;
        
        if($wp_uploader) return false;
        
        $uploader = new Rcl_Public_Form_Uploader(array('post_id'=>$this->post_id));
        
        echo $uploader->get_gallery();
        
    }
    
    function get_editor($args = false){
        global $rcl_options;
        
        $wp_uploader = false;
        $quicktags = false;
        $tinymce = false;
        
        if(isset($args['options'])){
            
            if(in_array('media',$args['options']))
                    $wp_uploader = true;
            
            if(in_array('html',$args['options']))
                    $quicktags = true;
            
            if(in_array('editor',$args['options']))
                    $tinymce = true;
            
        }

        $data = array( 'wpautop' => 1
            ,'media_buttons' => $wp_uploader
            ,'textarea_name' => 'post_content'
            ,'textarea_rows' => 10
            ,'tabindex' => null
            ,'editor_css' => ''
            ,'editor_class' => 'autosave'
            ,'teeny' => 0
            ,'dfw' => 0
            ,'tinymce' => $tinymce
            ,'quicktags' => $quicktags
        );

        $post_content = (isset($args['post_content']))? $args['post_content']: false;
        
        ob_start();

        wp_editor( $post_content, 'contentarea-'.$this->post_type, $data );
        
        $content = ob_get_contents();
        
        ob_end_clean();
        
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
    
    function get_tags_checklist($taxonomy, $t_args = array()){

        if(!is_array($t_args) || $t_args === false) return false;

        $tags = get_terms($taxonomy, $t_args);

        $post_tags = ($this->post_id)? $this->get_tags($this->post_id,$taxonomy): array();

        $content = '<div id="rcl-tags-list-'.$taxonomy.'" class="rcl-tags-list">';

        if($tags){
            
            $content .= '<span class="rcl-field-input type-checkbox-input">';
            
            foreach ($tags as $tag){
                
                $checked = false;
                
                if(isset($post_tags[$tag->slug]) && $tag->name == $post_tags[$tag->slug]->name){
                    $checked = true;
                    unset($post_tags[$tag->slug]);
                }
                
                $args = array(
                    'type' => 'checkbox',
                    'id' => 'tag-'.$tag->slug,
                    'name' => 'tags['.$taxonomy.'][]',
                    'checked' => $checked,
                    'label' => $tag->name,
                    'value' => $tag->name
                );
                
                if($this->current_field['required']){                   
                    $args['required'] = true;
                    $args['class'] = 'required-checkbox';
                }
                
                $content .= rcl_form_field($args);
            }
            
            $content .= '</span>';
            
        }

        if($post_tags){
            
            $content .= '<span class="rcl-field-input type-checkbox-input">';
            
            foreach ($post_tags as $tag){
                
                $args = array(
                    'type' => 'checkbox',
                    'id' => 'tag-'.$tag->slug,
                    'name' => 'tags['.$taxonomy.'][]',
                    'checked' => true,
                    'label' => $tag->name,
                    'value' => $tag->name
                );
                
                $content .= rcl_form_field($args);
                
            }
            
            $content .= '</span>';
        }

        $content .= '</div>';
        
        return $content;
    }
    
    function get_tags($post_id, $taxonomy='post_tag'){
        
        $posttags = get_the_terms( $post_id, $taxonomy );

        $tags = array();
        if ($posttags) {
            foreach($posttags as $tag){ $tags[$tag->slug] = $tag; }
        }
        
        return $tags;
    }
    
    function tags_field($taxonomy, $terms){

        if(!$this->taxonomies || !isset($this->taxonomies[$taxonomy])) return false;
        
        $number = (isset($this->current_field['number-tags']) && $this->current_field['number-tags'])? $this->current_field['number-tags']: 20;
        $input = (isset($this->current_field['input-tags']))? $this->current_field['input-tags']: true;

        $args = array(
            'input_field' => $input,
            'terms_cloud' => array(
                'hide_empty' => false,
                'number' => $number,
                'orderby' => 'count',
                'order' => 'DESC',
                'include' => $terms
            )
        );

        $args = apply_filters('rcl_public_form_tags',$args,$taxonomy,$this->get_object_form());

        $content = $this->get_tags_checklist($taxonomy, $args['terms_cloud']);
        
        if($args['input_field']) 
            $content .= $this->get_tags_input($taxonomy);
        
        if(!$content) return false;

        $content = '<div class="rcl-tags-list">'.$content.'</div>';
        
        return $content;
    }
    
    function get_tags_input($taxonomy = 'post_tag'){

        rcl_autocomplete_scripts();

        $args = array(
            'type' => 'text',
            'id' => 'rcl-tags-'.$taxonomy,
            'name' => 'tags['.$taxonomy.']',
            'placeholder' => __('Enter your tags','wp-recall'),
            'label' => '<span>'.__('Add your tags','wp-recall').'</span><br><small>'.__('Each tag is separated with Enter','wp-recall').'</small>'
        );

        $fields .= rcl_form_field($args);

        $fields .= "<script>
        jQuery(function($){
            $('#rcl-tags-".$taxonomy."').magicSuggest({
                data: Rcl.ajaxurl,
                dataUrlParams: { action: 'rcl_get_like_tags',taxonomy: '".$taxonomy."',ajax_nonce:Rcl.nonce },
                noSuggestionText: '".__("Not found","wp-recall")."',
                ajaxConfig: {
                      xhrFields: {
                        withCredentials: true,
                      }
                }
            });
        });
        </script>";

        return $fields;
    }
    
    function get_allterms($taxonomy){

        $args = array(
            'number'        => 0
            ,'offset'       => 0
            ,'orderby'      => 'id'
            ,'order'        => 'ASC'
            ,'hide_empty'   => false
            ,'fields'       => 'all'
            ,'slug'         => ''
            ,'hierarchical' => true
            ,'name__like'   => ''
            ,'pad_counts'   => false
            ,'get'          => ''
            ,'child_of'     => 0
            ,'parent'       => ''
        );

        $allcats = get_terms($taxonomy, $args);

        return $allcats;
    }
    
    function get_post_terms($taxonomy){

        if(!isset($this->taxonomies[$taxonomy])) return false;

        if($this->post_type == 'post'){

            $post_terms = get_the_category($this->post_id);

        }else{

            $post_terms = get_the_terms( $this->post_id, $taxonomy );

        }

        if($post_terms){
            
            foreach( $post_terms as $key => $term ){
                
                foreach($post_terms as $t){
                    
                    if($t->parent == $term->term_id){
                        unset($post_terms[$key]);
                        break;
                    }
                    
                }
                
            }
            
        }

        return $post_terms;
    }
    
    function get_default_fields(){
        
        $formFields = new Rcl_Public_Form_Fields(array(
            'post_type' => $this->post_type
        ));
        
        $defaultFields = $formFields->get_default_fields();
        
        return $defaultFields;
        
    }
    
    function get_delete_box(){
        global $user_ID;
        
        if($this->post->post_author == $user_ID){
            
            $content = '<form method="post" action="" onsubmit="return confirm(\''.__('Are you sure?','wp-recall').'\');">
                        '.wp_nonce_field('delete-post-rcl','_wpnonce',true,false).'
                        <input class="alignleft recall-button delete-post-submit public-form-button" type="submit" name="delete-post-rcl" value="'.__('Delete post','wp-recall').'">
                        <input type="hidden" name="post-rcl" value="'.$this->post_id.'">'
                    . '</form>';
            
        }else{

            $content = '<div id="rcl-delete-post">
                        <a href="#" class="public-form-button recall-button delete-toggle"><i class="fa fa-trash" aria-hidden="true"></i>'.__('Delete post','wp-recall').'</a>
                        <div class="delete-form-contayner">
                            <form action="" method="post"  onsubmit="return confirm(\''.__('Are you sure?','wp-recall').'\');">
                            '.wp_nonce_field('delete-post-rcl','_wpnonce',true,false).'
                            '.$this->get_reasons_list().'
                            <label>'.__('or enter your own','wp-recall').'</label>
                            <textarea required id="reason_content" name="reason_content"></textarea>
                            <p><input type="checkbox" name="no-reason" onclick="(!document.getElementById(\'reason_content\').getAttribute(\'disabled\')) ? document.getElementById(\'reason_content\').setAttribute(\'disabled\', \'disabled\') : document.getElementById(\'reason_content\').removeAttribute(\'disabled\')" value="1"> '.__('Without notice','wp-recall').'</p>
                            <input class="floatright recall-button delete-post-submit" type="submit" name="delete-post-rcl" value="'.__('Delete post','wp-recall').'">
                            <input type="hidden" name="post-rcl" value="'.$this->post_id.'">
                            </form>
                        </div>
                    </div>';
        }
        
        return $content;
    }

    function get_reasons_list(){

        $reasons = array(
            array(
                'value'=>__('Does not correspond the topic','wp-recall'),
                'content'=>__('The publication does not correspond to the site topic','wp-recall'),
            ),
            array(
                'value'=>__('Not completed','wp-recall'),
                'content'=>__('Publication does not correspond the rules','wp-recall'),
            ),
            array(
                'value'=>__('Advertising/Spam','wp-recall'),
                'content'=>__('Publication labeled as advertising or spam','wp-recall'),
            )
        );

        $reasons = apply_filters('rcl_public_form_delete_reasons', $reasons, $this->get_object_form());

        if(!$reasons) return false;

        $content = '<label>'.__('Use blank notice','wp-recall').':</label>';
        
        foreach($reasons as $reason){
            $content .= '<input type="button" class="recall-button reason-delete" onclick="document.getElementById(\'reason_content\').value=\''.$reason['content'].'\'" value="'.$reason['value'].'">';
        }

        return $content;
        
    }
    
    function init_form_scripts(){
        
        $obj = $this->get_object_form();

        echo '<script type="text/javascript">'
                . 'rcl_init_public_form({'
                . 'post_type:"'.$obj->post_type.'",'
                . 'post_id:"'.$obj->post_id.'",'
                . 'post_status:"'.$obj->post_status.'"'
            . '});</script>';
        
    }
    
}

class Rcl_PublicForm {

    public $post_id;//идентификатор записи
    public $post_type; //тип записи
    public $post_status; //тип записи
    public $terms; //список категорий доступных для выбора
    public $form_id; //идентификатор формы
    public $id_upload;
    public $accept;
    public $type_editor;
    public $wp_editor;
    public $can_edit;
    public $select_amount;
    public $select_type;
    public $taxonomy;
    public $button_draft;
    public $button_preview;
    public $button_delete;

    function __construct($atts){
        global $editpost,$group_id,$rcl_options,$user_ID,$formData;

        $editpost = false;
        $this->can_edit = true;

        extract(shortcode_atts(array(
            'cats' => false,
            'id' => 1,
            'id_upload' => 'upload-public-form',
            'accept' => 'image/*',
            'post_type'=> 'post',
            'wp_editor'=> null,
            'select_amount'=> false,
            'select_type'=> false,
            'group_id'=>$group_id,
            'button_draft'=>1,
            'button_delete'=>1,
            'button_preview'=>1
        ),
        $atts));

        $this->post_type = $post_type;
        $this->id_upload = $id_upload;
        $this->terms = $cats;
        $this->form_id = $id;
        $this->accept = $accept;
        $this->button_draft = ($user_ID)? $button_draft: false;
        $this->button_delete = $button_delete;
        
        $this->button_preview = (isset($rcl_options['public_preview'])&&!$rcl_options['public_preview'])? 1: 0;

        if(!isset($wp_editor)){
            if(isset($rcl_options['wp_editor'])){
                $cnt = count($rcl_options['wp_editor']);
                if($cnt==1){
                        $type = $rcl_options['wp_editor'][0];
                }else{
                        $type=3;
                }
            }
            $this->wp_editor = (isset($type))? $type: 0;
        }else 
            $this->wp_editor = $wp_editor;

        $this->type_editor = null;

        if(isset($_GET['rcl-post-edit'])){

            $this->post_id = $_GET['rcl-post-edit'];
            $editpost = get_post($this->post_id);
            $this->post_type = $editpost->post_type;
            $this->post_status = $editpost->post_status;

            if($this->post_type=='post-group'){
                
                if(!$group_id) 
                    $group_id = rcl_get_group_id_by_post($this->post_id);

                if(!rcl_can_user_edit_post_group($this->post_id)&&!current_user_can('edit_post', $this->post_id)) 
                        $this->can_edit = false;

            }else if(!current_user_can('edit_post', $this->post_id)){
                $this->can_edit = false;
            }

            $form_id = get_post_meta($this->post_id,'publicform-id',1);

            if($form_id) $this->form_id = $form_id;
        }

        $post_types = get_post_types( array('public' => true,'_builtin' => false), 'objects', 'and' );

        $this->taxonomy = array('post'=>array('category','post_tag'));
        foreach($post_types as $p_type=>$p_data){
            $this->taxonomy[$p_type] = $p_data->taxonomies;
        }

        if(isset($rcl_options['accept-'.$this->post_type])) $this->accept = $rcl_options['accept-'.$this->post_type];

        $this->select_type = (isset($rcl_options['output_category_list']))? $rcl_options['output_category_list']: 'select';
        if($select_type) $this->select_type = $select_type;
        
        if($this->select_type=='select'){
            $this->select_amount = (isset($rcl_options['count_category_post'])&&$rcl_options['output_category_list']=='select')? $rcl_options['count_category_post']:0;
            if($select_amount) $this->select_amount = $select_amount;
        }
        
        $formData = apply_filters('rcl_public_form_object',$this);

        if($this->user_can()){
            rcl_fileupload_scripts();
            add_action('wp_footer',array(&$this,'init_form_scripts'),999);
            if($this->post_id && $this->button_delete) add_filter('after_public_form_rcl',array(&$this,'delete_button'),10,2);
        }

    }

    function user_can(){
        global $rcl_options,$user_ID,$formData;

        $user_can = $rcl_options['user_public_access_recall'];
		
        if($user_can){

            if($user_ID){
                $userinfo = get_userdata( $user_ID );

                if($userinfo->user_level>=$user_can) $can = true;
                else $can = false;
            }else{
                $can = false;
            }

        }else{
            $can = true;
        }
        
        $can = apply_filters('rcl_user_can_public',$can,$formData);

        return $can;
    }

    function submit_and_hidden(){
        global $group_id,$post,$rcl_options,$formData;

		$inputs = array(
                    array('type'=>'hidden','value'=>1,'name'=>'edit-post-rcl'),
                    array('type'=>'hidden','value'=>base64_encode($formData->form_id),'name'=>'id_form'),
		);
                
                if($formData->button_draft)
                    $inputs[] = array('type'=>'button','value'=>__('Save as Draft','wp-recall'),'onclick'=>'rcl_save_draft(this);','id'=>'save-draft-rcl','class'=>'recall-button');

                if(!$formData->button_preview){
                    $inputs[] = array('type'=>'button','value'=>__('Publish','wp-recall'),'onclick'=>'rcl_publish(this);','id'=>'edit-post-rcl','class'=>'recall-button');
                }else{
                    rcl_dialog_scripts();
                    $inputs[] = array('type'=>'button','value'=>__('Preview','wp-recall'),'onclick'=>'rcl_preview(this);','id'=>'edit-post-rcl','class'=>'rcl-preview-post recall-button');
                }
                
                $inputs[] = array('type'=>'hidden','value'=>$formData->button_draft,'name'=>'button-draft');
                $inputs[] = array('type'=>'hidden','value'=>$formData->button_delete,'name'=>'button-delete');
                $inputs[] = array('type'=>'hidden','value'=>$formData->button_preview,'name'=>'button-preview');

                $inputs[] = array('type'=>'hidden','value'=>$formData->post_id,'name'=>'post-rcl');

		if(!$formData->post_id) 
                    $inputs[] = array('type'=>'hidden','value'=>base64_encode($formData->post_type),'name'=>'posttype');
                else
                    $inputs[] = array('type'=>'hidden','value'=>$formData->post_status,'name'=>'post_status');

		$post_id = (isset($post))? $post->ID: 0;

		$hiddens = array(
                    'post-group' => array('term_id'=>base64_encode($group_id)),
                    'products' => array('formpage'=>$post_id),
                    'task' => array('formpage'=>$post_id)
                );

		if(isset($hiddens[$this->post_type])){
			foreach($hiddens[$this->post_type] as $name=>$val){
				$inputs[] = array('type'=>'hidden','value'=>$val,'name'=>$name);
			}
		}

		$inputs = apply_filters('rcl_submit_hiddens_form',$inputs);

		foreach($inputs as $input){
			$attrs = array();
			foreach($input as $attr=>$val){
				$attrs[] = $attr."='$val'";
			}
			$html[] = "<input ".implode(' ',$attrs).">";
		}

        return '<div class="submit-public-form">'.implode('',$html).'</div>';
    }

    function delete_button($cnt,$data){
        global $user_ID,$editpost,$formData;
        
        if($editpost->post_author==$user_ID){
            $cnt .= '<form method="post" action="" onsubmit="return confirm(\''.__('Are you sure?','wp-recall').'\');">
            '.wp_nonce_field('delete-post-rcl','_wpnonce',true,false).'
            <input class="alignleft recall-button delete-post-submit" type="submit" name="delete-post-rcl" value="'.__('Delete post','wp-recall').'">
            <input type="hidden" name="post-rcl" value="'.$formData->post_id.'"></form>';
        }else{

            $cnt .= '<div id="rcl-delete-post">
                        <a href="#" class="recall-button delete-toggle">'.__('Delete post','wp-recall').'</a>
                        <div class="delete-form-contayner">
                            <form action="" method="post"  onsubmit="return confirm(\''.__('Are you sure?','wp-recall').'\');">
                            '.wp_nonce_field('delete-post-rcl','_wpnonce',true,false).'
                            '.$this->reasons_delete().'
                            <label>'.__('or enter your own','wp-recall').'</label>
                            <textarea required id="reason_content" name="reason_content"></textarea>
                            <p><input type="checkbox" name="no-reason" onclick="(!document.getElementById(\'reason_content\').getAttribute(\'disabled\')) ? document.getElementById(\'reason_content\').setAttribute(\'disabled\', \'disabled\') : document.getElementById(\'reason_content\').removeAttribute(\'disabled\')" value="1"> '.__('Without notice','wp-recall').'</p>
                            <input class="floatright recall-button delete-post-submit" type="submit" name="delete-post-rcl" value="'.__('Delete post','wp-recall').'">
                            <input type="hidden" name="post-rcl" value="'.$formData->post_id.'">
                            </form>
                        </div>
                    </div>';
        }
        return $cnt;
    }

    function reasons_delete(){

        $reasons = array(
                array(
                        'value'=>__('Does not correspond the topic','wp-recall'),
                        'content'=>__('The publication does not correspond to the site topic','wp-recall'),
                ),
                array(
                        'value'=>__('Not completed','wp-recall'),
                        'content'=>__('Publication does not correspond the rules','wp-recall'),
                ),
                array(
                        'value'=>__('Advertising/Spam','wp-recall'),
                        'content'=>__('Publication labeled as advertising or spam','wp-recall'),
                )
        );

        $reasons = apply_filters('rcl_reasons_delete',$reasons);

        if(!$reasons) return false;

        $content = '<label>'.__('Use blank notice','wp-recall').':</label>';
        foreach($reasons as $reason){
                $content .= '<input type="button" class="recall-button reason-delete" onclick="document.getElementById(\'reason_content\').value=\''.$reason['content'].'\'" value="'.$reason['value'].'">';
        }

        return $content;
    }

    function public_form(){
        global $user_ID,$formFields,$formData;

            if(!$formData->can_edit) 
                return '<p align="center" class="rcl-public-notice">'.__('You can not edit this publication :(','wp-recall').'</p>';

            if(!$this->user_can()){
                if($formData->post_type=='post-group') return '<div class="public-post-group">'
                    . '<h3 >'.__('Sorry, but you have no rights to publish in this group :(','wp-recall').'</h3>'
                        . '</div>';
                else{

                    if(!$user_ID) return '<p align="center" class="rcl-public-notice">'.__('You must be logged in to post. Login or register','wp-recall').'</p>';

                    return '<p align="center" class="rcl-public-notice">'. __('Sorry, but you have no right to post on this site :(','wp-recall') . '</p>';
                }
            }
            
            $no_view = apply_filters('rcl_public_form_check_view',false,$formData);
            
            if($no_view){
                return '<div class="rcl-warning-form">' . $no_view . '</div>';
            }

            $formfields = array(
            	'title'=>true,
            	'termlist'=>true,
            	'editor'=>true,
                'excerpt'=>false,
            	'custom_fields'=>true,
                'upload'=>true,
                'tags'=>true
            );

            $formFields = apply_filters('fields_public_form_rcl',$formfields,$formData);

            if(!$formFields['tags']) 
                remove_filter('public_form_rcl','rcl_add_non_hierarchical_tags_field',100);

            $form = '<div class="public_block">';

                $id_post = ($formData->post_id)? $formData->post_id : 0;

                $id_form = 'form-'.$formData->post_type.'-'.$id_post;
                
                $classes = array('rcl-public-form');
                
                $classes[] = ($formData->post_id)? 'edit-form' : 'public-form';
                
                $data_form_id = ($this->form_id)? $this->form_id: 0;
                $data_post_id = ($this->post_id)? $this->post_id: 0;
                
                $attrs_form = array(
                    'id'=>$id_form,
                    'data-form_id'=>$data_form_id,
                    'data-post_id'=>$data_post_id,
                    'data-post_type'=>$formData->post_type,
                    'class'=>$classes
                );
                
                $attrs_form = apply_filters('rcl_public_form_attributes', $attrs_form, $formData);
                
                $attrs = array();
                foreach($attrs_form as $k=>$v){
                    if(is_array($v)){
                        $attrs[] = $k.'="'.implode(' ',$v).'"';
                        continue;
                    }
                    $attrs[] = $k.'="'.$v.'"';
                }

                $form .= '<form '.implode(' ',$attrs).' ';

                if(!$formData->preview){
                    $form .= ' onsubmit="document.getElementById(\'edit-post-rcl\').disabled=true;document.getElementById(\'edit-post-rcl\').value=\''.__('Being sent, please wait...','wp-recall').'\';"';  
                }
                
                $form .= 'action="" method="post" enctype="multipart/form-data">'
                 .wp_nonce_field('edit-post-rcl','_wpnonce',true,false);

                    if(!$user_ID) $form .= '<div class="rcl-form-field">
                            <label>'.__('Your Name','wp-recall').' <span class="required">*</span></label>
                            <input required type="text" value="" name="name-user">
                    </div>
                    <div class="rcl-form-field">
                            <label>'.__('Your E-mail','wp-recall').' <span class="required">*</span></label>
                            <input required type="text" value="" name="email-user">
                    </div>';

                    if(rcl_get_template_path($formData->post_type.'-form.php',__FILE__)) 
                        $form .= rcl_get_include_template($formData->post_type.'-form.php',__FILE__);
                    else 
                        $form .= rcl_get_include_template('public-form.php',__FILE__);

                    $fields = '';

                    $form .= apply_filters('rcl_public_form',$fields,$formData);

                    $form .= $this->submit_and_hidden()

               . '</form>';

               $after = '';
               $form .= apply_filters('after_public_form_rcl',$after,$formData);

           $form .= '</div>';

        return $form;
    }
    
    function init_form_scripts(){
        global $formData;
        $id_post = ($formData->post_id)? $formData->post_id : 0;
        echo '<script type="text/javascript">'
                . 'rcl_init_public_form({'
                . 'post_type:"'.$formData->post_type.'",'
                . 'post_id:"'.$id_post.'",'
                . 'post_status:"'.$formData->post_status.'"'
            . '});</script>';
    }
}

