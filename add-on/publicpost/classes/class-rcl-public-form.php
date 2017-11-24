<?php

class Rcl_Public_Form extends Rcl_Public_Form_Fields{
    
    public $post_id = 0;
    public $fields_options;
    public $form_object;
    public $post;
    public $form_id;
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
        global $user_ID;
        
        $this->init_properties($args);
        
        if(isset($_GET['rcl-post-edit'])){
            $this->post_id = intval($_GET['rcl-post-edit']);
        }
        
        if($this->post_id){
			
            $this->post = get_post($this->post_id);
            $this->post_type = $this->post->post_type;
			
            if($this->post_type == 'post'){
                $this->form_id = get_post_meta($this->post_id, 'publicform-id', 1);
            }
			
        }
		
        if(!$this->form_id) 
            $this->form_id = 1;
        
        parent::__construct(array(
            'post_type' => $this->post_type,
            'form_id' => $this->form_id
        ));
        
        $this->init_user_can();
        
        $this->init_options();

        do_action('rcl_public_form_init', $this->get_object_form());

        if($this->options['preview']) rcl_dialog_scripts();
        
        if($this->user_can['upload']){
            rcl_fileupload_scripts();
            add_action('wp_footer', array($this, 'init_form_scripts'), 100);
        }

        if($this->user_can['publish'] && !$user_ID)
            add_filter('rcl_public_form_fields',array($this,'add_guest_fields'), 10);

        $this->fields = $this->get_public_fields();
        
        $this->form_object = $this->get_object_form();
        
        if($this->exist_active_field('post_thumbnail'))
            add_filter('rcl_post_attachment_html','rcl_add_attachment_thumbnail_button', 10, 3);
        
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
        $dataForm['ext_types'] = 'jpg, png, gif';
        $dataForm['size_files'] = 2;
        $dataForm['max_files'] = 10;
        
        foreach($this->fields as $k => $field){
            
            if($field['slug'] == 'post_uploader'){
                
                if(isset($field['ext-types']) && $field['ext-types'])
                $dataForm['ext_types'] = $field['ext-types'];
                
                if(isset($field['size-files']) && $field['size-files'])
                $dataForm['size_files'] = $field['size-files'];
                
                if(isset($field['max-files']) && $field['max-files'])
                $dataForm['max_files'] = $field['max-files'];
                
                break;
            }
            
        }

        $dataForm = (object)$dataForm;
        
        return $dataForm;
    }
    
    function get_public_fields(){
        
        return apply_filters('rcl_public_form_fields', $this->fields, $this->get_object_form());
        
    }
    
    function add_guest_fields($fields){
        
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
        
        return $fields;
        
    }
    
    function init_options(){
        
        $this->options['preview'] = rcl_get_option('public_preview');
        $this->options['draft'] = rcl_get_option('public_draft');
        
        $this->options = apply_filters('rcl_public_form_options', $this->options, $this->get_object_form());

    }

    function init_user_can(){
        global $user_ID;

        $this->user_can['publish'] = true;
        
        $user_can = rcl_get_option('user_public_access_recall');
		
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
    
    function get_errors(){
        global $user_ID;
        
        $errors = array();

        if(!$this->user_can['publish']){
            
            if(!$user_ID)
                $errors[] = __('You must be logged in to post. Login or register','wp-recall');
            else if($this->post_type == 'post-group'){
                $errors[] = __('Sorry, but you have no rights to publish in this group :(','wp-recall'); 
            }else{
                $errors[] = __('Sorry, but you have no right to post on this site :(','wp-recall');
            }
            
        }else if($this->post_id && !$this->user_can['edit']){
            $errors[] = __('You can not edit this publication :(','wp-recall');
        }
        
        $errors = apply_filters('rcl_public_form_errors', $errors, $this);
        
        return $errors;
        
    }
    
    function get_errors_content(){
        
        $errorContent = '';
            
        foreach($this->get_errors() as $error){
            $errorContent .= '<p align="center" class="rcl-public-notice">'.$error.'</p>';
        }
        
        return $errorContent;
        
    }

    function get_form(){
        global $user_ID;
        
        if($this->get_errors()){

            return $this->get_errors_content();
            
        }
        
        $dataPost = $this->get_object_form();
        
        $defaultFields = array(
            'post_content',
            'post_title',
            'post_uploader',
            'post_excerpt',
            'post_thumbnail'
        );
        
        $taxField = array();
        
        if($this->taxonomies){
            
            foreach($this->taxonomies as $taxname => $object){

                $taxField[] = 'taxonomy-'.$taxname;

            }
            
        }
        
        $attrs = array(
            'data-form_id'=> $this->form_id,
            'data-post_id'=> $this->post_id,
            'data-post_type'=> $this->post_type,
            'class'=> array('rcl-public-form')
        );

        $attrs = apply_filters('rcl_public_form_attributes', $attrs, $dataPost);
        
        $attrsForm = array();
        foreach($attrs as $k=>$v){
            if(is_array($v)){
                $attrsForm[] = $k.'="'.implode(' ',$v).'"';
                continue;
            }
            $attrsForm[] = $k.'="'.$v.'"';
        }
        
        $content = '<div class="rcl-public-box rcl-form">';
        
        if(rcl_check_access_console()){
            $content .= '<div class="edit-form-link">'
                            . '<a target="_blank" href="'.admin_url('admin.php?page=manage-public-form&post-type=').$this->post_type.'">'
                                . '<i class="fa fa-list" aria-hidden="true"></i> ' . __('Edit this form', 'wp-recall')
                            . '</a>'
                        . '</div>';
        }
        
        $content .= '<form action="" method="post" '.implode(' ',$attrsForm).'>';
        
        if($this->fields){
            
            $CF = new Rcl_Custom_Fields();

            foreach($this->fields as $this->current_field){

                $required = (isset($this->current_field['required']) && $this->current_field['required'] == 1)? '<span class="required">*</span>': '';

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
                            
                            $contentField .= $CF->get_notice($this->current_field);
                            
                        }
                        
                        if($this->current_field['slug'] == 'post_excerpt'){
                            
                            $contentField = $CF->get_input($this->current_field,$dataPost->post_excerpt);
                            
                        }
                        
                        if($this->current_field['slug'] == 'post_title'){
                            
                            $contentField = $CF->get_input($this->current_field,esc_textarea($dataPost->post_title));
                            
                        }
                        
                        if($this->current_field['slug'] == 'post_thumbnail'){
                            
                            $contentField = $this->get_thumbnail_box();
                            
                            $contentField .= $CF->get_notice($this->current_field);
                            
                        }
                        
                        if($this->current_field['slug'] == 'post_uploader'){
                            
                            $postUploder = new Rcl_Public_Form_Uploader(array(
                                'post_id' => $this->post_id,
                                'post_type' => $this->post_type,
                                'ext_types' => $this->form_object->ext_types
                            ));
                            
                            $contentField = $postUploder->get_uploader();
                            
                            $contentField .= $CF->get_notice($this->current_field);
                            
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
            $content .= '<input type="hidden" name="form_id" value="'.$this->form_id.'">';
        
        $content .= '<input type="hidden" name="post_id" value="'.$this->post_id.'">';
        $content .= '<input type="hidden" name="post_type" value="'.$this->post_type.'">';
        $content .= '<input type="hidden" name="rcl-edit-post" value="1">';
        $content .= wp_nonce_field('rcl-edit-post','_wpnonce',true,false);
        $content .= '</form>';
        
        if($this->user_can['delete'] && $this->options['delete']){
            
            $content .= '<div id="form-field-delete" class="rcl-form-field">';
            
            $content .= $this->get_delete_box();
            
            $content .= '</div>';
            
        }
        
        $content .= apply_filters('after_public_form_rcl','',$this->get_object_form());

        $content .= '</div>';
        
        return $content;
        
    }
    
    function get_thumbnail_box(){
        
        $sizeFile = (isset($this->current_field['size-files']) && $this->current_field['size-files'])? $this->current_field['size-files']: 2;
        
        $content = '<div id="rcl-thumbnail-post">';
        
        $content .= '<div class="thumbnail-wrapper">';
        
        $content .= '<a href="#" class="rcl-service-button delete-post-thumbnail" onclick="rcl_remove_post_thumbnail();return false;"><i class="fa fa-trash"></i></a>';
        
        $content .= '<div class="thumbnail-image">';
        
        if($this->post_id){
            
            if(has_post_thumbnail($this->post_id)){
                
                $content .= get_the_post_thumbnail( $this->post_id, 'thumbnail');
                
            }
            
        }
        
        $content .= '</div>';

        $thumbnail_id = ($this->post_id)? get_post_thumbnail_id( $this->post_id ): 0;
        
        $content .= '<input class="thumbnail-id" type="hidden" name="post-thumbnail" value="'.$thumbnail_id.'">';
        
        $content .= '</div>';
        
        $postUploder = new Rcl_Public_Form_Uploader(array(
            'post_id' => $this->post_id,
            'post_type' => $this->post_type,
            'ext_types' => 'jpg,png,jpeg'
        ));

        $content .= $postUploder->get_upload_button(array(
            'multiple' => false,
            'id' => 'rcl-thumbnail-uploader',
            'title' => __('Upload thumbnail','wp-recall'),
            'onclick' => 'rcl_init_thumbnail_uploader(this,{size:\''.$sizeFile.'\'});'
        ));
        
        $content .= '</div>';
        
        return $content;
        
    }

    function get_terms_list($taxonomy){
        
        $content = '<div class="rcl-terms-select taxonomy-'.$taxonomy.'">';
        
        $terms = isset($this->current_field['values'])? $this->current_field['values']: array();
        
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

                $req = isset($this->current_field['required'])? $this->current_field['required']: false;
                
                $termList = new Rcl_List_Terms($taxonomy, $type, $req);

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
    
    function get_editor($args = false){
        
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
                
                if(isset($this->current_field['required']) && $this->current_field['required']){                   
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

        $args = array(
            'input_field' => $this->current_field['input-tags'],
            'terms_cloud' => array(
                'hide_empty' => false,
                'number' => $this->current_field['number-tags'],
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

        $fields = rcl_form_field($args);

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
        
        $args = apply_filters('rcl_public_form_hierarchical_terms', $args, $this->get_object_form());

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
        
        $obj = $this->form_object;

        echo '<script type="text/javascript">'
                . 'rcl_init_public_form({'
                . 'post_type:"'.$obj->post_type.'",'
                . 'post_id:"'.$obj->post_id.'",'
                . 'post_status:"'.$obj->post_status.'",'
                . 'ext_types:"'.$obj->ext_types.'",'
                . 'size_files:"'.$obj->size_files.'",'
                . 'max_files:"'.$obj->max_files.'"'
            . '});</script>';
        
    }
    
}