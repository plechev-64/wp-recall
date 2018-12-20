<?php

class Rcl_Public_Form extends Rcl_Public_Form_Fields{

    public $post_id = 0;
    public $post_type = 'post';
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

    public $core_fields = array(
        'post_content',
        'post_title',
        'post_uploader',
        'post_excerpt',
        'post_thumbnail'
    );

    public $tax_fields = array();

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
                $this->form_id = get_post_meta($this->post_id, 'rcl_publicform_id', 1);
            }

        }

        if(!$this->form_id)
            $this->form_id = 1;

        parent::__construct($this->post_type, array(
            'form_id' => $this->form_id
        ));

        $this->setup_user_can();

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

        if($this->is_active_field('post_thumbnail'))
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
        $dataForm['file_types'] = 'jpg, png, gif';
        $dataForm['max_size'] = 2;
        $dataForm['max_files'] = 10;

        foreach($this->fields as $field_id => $field){

            if($field_id == 'post_uploader'){

                if($field->isset_prop('file_types'))
                    $dataForm['file_types'] = $field->get_prop('file_types');

                if($field->isset_prop('max_size'))
                    $dataForm['max_size'] = $field->get_prop('file_types');

                if($field->isset_prop('max_files'))
                    $dataForm['max_files'] = $field->get_prop('file_types');

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

    function setup_user_can(){
        global $user_ID;

        $this->user_can['publish'] = true;

        $user_can = rcl_get_option('public_access');

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

        if($this->taxonomies){

            foreach($this->taxonomies as $taxname => $object){

                $this->tax_fields[] = 'taxonomy-'.$taxname;

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
                                . '<i class="rcli fa-list" aria-hidden="true"></i> ' . __('Edit this form', 'wp-recall')
                            . '</a>'
                        . '</div>';
        }

        $content .= '<form action="" method="post" '.implode(' ',$attrsForm).'>';

        if($this->fields){

            $content .= $this->get_content_form();

        }

        $content .= apply_filters('rcl_public_form','',$this->get_object_form());

        $content .= '<div class="rcl-form-field submit-public-form">';

        if($this->options['draft'] && $this->user_can['draft'])
            $content .= rcl_get_button(array(
                'onclick' => 'rcl_save_draft(this); return false;',
                'label' => __('Save as Draft','wp-recall'),
                'class' => array('public-form-button'),
                'id' => 'rcl-draft-post',
                'icon' => 'fa-print'
            ));

        if($this->options['preview'])
            $content .= rcl_get_button(array(
                'onclick' => 'rcl_preview(this); return false;',
                'label' => __('Preview','wp-recall'),
                'class' => array('public-form-button'),
                'id' => 'rcl-preview-post',
                'icon' => 'fa-print'
            ));

        $content .= rcl_get_button(array(
            'onclick' => 'rcl_publish(this); return false;',
            'label' => __('Publish','wp-recall'),
            'class' => array('public-form-button'),
            'id' => 'rcl-publish-post',
            'icon' => 'fa-print'
        ));

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

    function get_field_form($field_id){

        $dataPost = $this->get_object_form();

        $field = $this->get_field($field_id);

        $this->current_field = $field;

        if($this->taxonomies && in_array($field_id, $this->tax_fields)){

            if($taxonomy = $this->is_taxonomy_field($field_id)){

                $contentField = $this->get_terms_list($taxonomy, $field_id);

            }

        }else{

            if(in_array($field_id, $this->core_fields)){

                if($field_id == 'post_content'){

                    $contentField = $this->get_editor(array(
                        'post_content' => $dataPost->post_content,
                        'options' => $field->get_prop('post-editor')
                    ));

                    $contentField .= $field->get_notice();

                }

                if($field_id == 'post_excerpt'){

                    $field->set_prop('value', $dataPost->post_excerpt);

                    $contentField = $field->get_field_input();

                }

                if($field_id == 'post_title'){

                    $field->set_prop('value', esc_textarea($dataPost->post_title));

                    $contentField = $field->get_field_input(esc_textarea($dataPost->post_title));

                }

                if($field_id == 'post_thumbnail'){

                    $contentField = $this->get_thumbnail_box($field_id);

                    $contentField .= $field->get_field_input();

                }

                if($field_id == 'post_uploader'){

                    if(!$field->isset_prop('add-to-click')){
                        $field->set_prop('add-to-click',1);
                    }

                    if(!$field->isset_prop('gallery')){
                        $field->set_prop('gallery',1);
                    }

                    $postUploder = new Rcl_Uploader_Public_Form(array(
                        'post_parent' => $this->post_id,
                        'form_id' => $this->form_id,
                        'post_type' => $this->post_type,
                        'file_types' => $field->get_prop('file_types'),
                        'max_size' => $field->get_prop('max_size'),
                        'max_files' => $field->get_prop('max_files'),
                        'required' => intval($field->get_prop('required'))
                    ));

                    $contentField = $postUploder->get_form_uploader();

                    $contentField .= $field->get_notice();

                }

            }else{

                $field->set_prop('value', ($this->post_id)? get_post_meta($this->post_id, $field_id, 1): '');

                $contentField = $field->get_field_input();

            }

        }

        if(!$contentField) return false;

        $content = '<div id="form-field-'.$field_id.'" class="rcl-form-field field-'.$field_id.'">';

        $content .= '<label>'.$field->get_title().'</label>';

        $content .= $contentField;

        $content .= '</div>';

        return $content;

    }

    function get_thumbnail_box($field_id){

        $field = $this->get_field($field_id);

        //$thumbnail_id = ($this->post_id)? get_post_thumbnail_id( $this->post_id ): 0;

        $postUploder = new Rcl_Uploader_Post_Thumbnail(array(
            'form_id' => $this->form_id,
            'post_type' => $this->post_type,
            'post_parent' => $this->post_id,
            'max_size' => $field->get_prop('max_size'),
            'required' => intval($field->get_prop('required'))
        ));

        $content = $postUploder->get_thumbnail_uploader();
        //$content .= '<input class="post-thumbnail" type="hidden" name="post-thumbnail" value="'.$thumbnail_id.'">';

        return $content;

    }

    function get_terms_list($taxonomy, $field_id){

        $field = $this->get_field($field_id);

        $content = '<div class="rcl-terms-select taxonomy-'.$taxonomy.'">';

        $terms = $field->isset_prop('values')? $field->get_prop('values'): array();

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

                $type = ($val = $field->get_prop('type-select'))? $val: 'select';
                $number = ($val = $field->get_prop('number-select'))? $val: 1;
                $req = ($val = $field->get_prop('number-select'))? $val: false;

                $termList = new Rcl_List_Terms($taxonomy, $type, $req);
                $post_terms = $this->get_post_terms($taxonomy);

                $content .= $termList->get_select_list($this->get_allterms($taxonomy),$post_terms,$number,$terms);

            }

        }else{

            $content .= $this->tags_field($taxonomy, $terms);

        }

        $content .= $field->get_notice();

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

                if($this->current_field->get_prop('required')){
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
            'input_field' => $this->current_field->get_prop('input-tags'),
            'terms_cloud' => array(
                'hide_empty' => false,
                'number' => $this->current_field->get_prop('number-tags'),
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
        jQuery(window).on('load', function(){
            jQuery('#rcl-tags-".$taxonomy."').magicSuggest({
                data: Rcl.ajax_url,
                dataUrlParams: { action: 'rcl_get_like_tags',taxonomy: '".$taxonomy."',ajax_nonce:Rcl.nonce },
                noSuggestionText: '".__("Not found","rcl-public")."',
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

        $args = apply_filters('rcl_public_form_hierarchical_terms', $args, $taxonomy, $this->get_object_form());

        $allcats = get_terms($taxonomy, $args);

        return $allcats;
    }

    function get_post_terms($taxonomy){

        if(!isset($this->taxonomies[$taxonomy])) return false;

        if($this->post_type == 'post'){

            $post_terms = get_the_terms( $this->post_id, $taxonomy );

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
                        '.wp_nonce_field('rcl-delete-post','_wpnonce',true,false).'
                        '.rcl_get_button(array(
                            'submit' => true,
                            'label' => __('Delete post','wp-recall'),
                            'class' => array('delete-post-submit public-form-button'),
                            'icon' => 'fa-trash'
                        )).'
                        <input type="hidden" name="rcl-delete-post" value="1">
                        <input type="hidden" name="post_id" value="'.$this->post_id.'">'
                    . '</form>';

        }else{

            $content = '<div id="rcl-delete-post">
                        '.rcl_get_button(array(
                            'label' => __('Delete post','wp-recall'),
                            'class' => array('public-form-button delete-toggle'),
                            'icon' => 'fa-trash'
                        )).'
                        <div class="delete-form-contayner">
                            <form action="" method="post"  onsubmit="return confirm(\''.__('Are you sure?','wp-recall').'\');">
                            '.wp_nonce_field('rcl-delete-post','_wpnonce',true,false).'
                            '.$this->get_reasons_list().'
                            <label>'.__('or enter your own','wp-recall').'</label>
                            <textarea required id="reason_content" name="reason_content"></textarea>
                            <p><input type="checkbox" name="no-reason" onclick="(!document.getElementById(\'reason_content\').getAttribute(\'disabled\')) ? document.getElementById(\'reason_content\').setAttribute(\'disabled\', \'disabled\') : document.getElementById(\'reason_content\').removeAttribute(\'disabled\')" value="1"> '.__('Without notice','wp-recall').'</p>
                            '.rcl_get_button(array(
                                'submit' => true,
                                'label' => __('Delete post','wp-recall'),
                                'icon' => 'fa-trash'
                            )).'
                            <input type="hidden" name="rcl-delete-post" value="1">
                            <input type="hidden" name="post_id" value="'.$this->post_id.'">
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
            $content .= rcl_get_button(array(
                'onclick' => 'document.getElementById(\'reason_content\').value=\''.$reason['content'].'\'',
                'label' => $reason['value'],
                'class' => 'reason-delete'
            ));
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
                . 'file_types:"'.$obj->file_types.'",'
                . 'max_size:"'.$obj->max_size.'",'
                . 'max_files:"'.$obj->max_files.'",'
                . 'form_id:"'.$this->form_id.'"'
            . '});</script>';

    }

}