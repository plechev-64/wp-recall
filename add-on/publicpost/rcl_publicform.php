<?php

class Rcl_PublicForm {

    public $post_id;//идентификатор записи
    public $post_type; //тип записи
    public $terms; //список категорий доступных для выбора
    public $form_id; //идентификатор формы
    public $id_upload;
    public $accept;
    public $type_editor;
    public $wp_editor;
    public $can_edit;
    public $select_amount;
    public $select_type;
    public $preview;
    public $taxonomy;

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
            'group_id'=>$group_id
        ),
        $atts));

        $this->post_type = $post_type;
        $this->id_upload = $id_upload;
        $this->terms = $cats;
        $this->form_id = $id;
        $this->accept = $accept;
        
        $this->preview = (isset($rcl_options['public_preview'])&&!$rcl_options['public_preview'])? 1: 0;

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
        }else $this->wp_editor = $wp_editor;

        $this->type_editor = null;

        if(isset($_GET['rcl-post-edit'])){

            $this->post_id = $_GET['rcl-post-edit'];
            $editpost = get_post($this->post_id);
            $this->post_type = $editpost->post_type;

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
            if($this->post_id) add_filter('after_public_form_rcl',array(&$this,'delete_button'),10,2);
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

                if(!$this->preview){
                    $inputs[] = array('type'=>'submit','value'=>__('To publish','wp-recall'),'id'=>'edit-post-rcl','class'=>'recall-button');
                }else{
                    rcl_dialog_scripts();
                    $inputs[] = array('type'=>'button','value'=>__('Preview','wp-recall'),'onclick'=>'rcl_preview(this);','class'=>'rcl-preview-post recall-button');
                }

		if($this->post_id) $inputs[] = array('type'=>'hidden','value'=>$formData->post_id,'name'=>'post-rcl');
		else $inputs[] = array('type'=>'hidden','value'=>base64_encode($formData->post_type),'name'=>'posttype');

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
            $cnt .= '<form method="post" action="" onsubmit="return confirm(\''.__('Are you seriously','wp-recall').'?\');">
            '.wp_nonce_field('delete-post-rcl','_wpnonce',true,false).'
            <input class="alignleft recall-button delete-post-submit" type="submit" name="delete-post-rcl" value="'.__('Delete post','wp-recall').'">
            <input type="hidden" name="post-rcl" value="'.$formData->post_id.'"></form>';
        }else{

            $cnt .= '<div id="rcl-delete-post">
                        <a href="#" class="recall-button delete-toggle">'.__('Delete post','wp-recall').'</a>
                        <div class="delete-form-contayner">
                            <form action="" method="post"  onsubmit="return confirm(\''.__('Are you seriously','wp-recall').'?\');">
                            '.wp_nonce_field('delete-post-rcl','_wpnonce',true,false).'
                            '.$this->reasons_delete().'
                            <label>'.__('or enter its cause','wp-recall').'</label>
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
                        'value'=>__('Not correspond the subject','wp-recall'),
                        'content'=>__('The publication does not correspond to the subject site','wp-recall'),
                ),
                array(
                        'value'=>__('Not furnished','wp-recall'),
                        'content'=>__('Publication is not formalized under the rules','wp-recall'),
                ),
                array(
                        'value'=>__('Advertising/Spam','wp-recall'),
                        'content'=>__('Publication labeled as advertising or spam','wp-recall'),
                )
        );

        $reasons = apply_filters('rcl_reasons_delete',$reasons);

        if(!$reasons) return false;

        $content = '<label>'.__('Use the blank notice','wp-recall').':</label>';
        foreach($reasons as $reason){
                $content .= '<input type="button" class="recall-button reason-delete" onclick="document.getElementById(\'reason_content\').value=\''.$reason['content'].'\'" value="'.$reason['value'].'">';
        }

        return $content;
    }

    function public_form(){
        global $user_ID,$formFields,$formData;

            if(!$formData->can_edit) 
                return '<p align="center">'.__('You can not edit this publication :(','wp-recall').'</p>';

            if(!$this->user_can()){
                if($formData->post_type=='post-group') return '<div class="public-post-group">'
                    . '<h3 >'.__('Sorry, but you have no rights to publish within groups :(','wp-recall').'</h3>'
                        . '</div>';
                else{

                    if(!$user_ID) return '<p align="center">'.__('You must be logged in to post. Login or register','wp-recall').'</p>';

                    return '<h3 class="aligncenter">'
                        . __('Sorry, but you have no right<br>to publish the records on this site :(','wp-recall')
                            . '</h3>';
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

            if(!$formFields['tags']) remove_filter('public_form_rcl','rcl_add_tags_input',10);

            $form = '<div class="public_block">';

                $id_post = ($formData->post_id)? $formData->post_id : 0;

                $id_form = 'form-'.$formData->post_type.'-'.$id_post;
                
                $classes = array('rcl-public-form');
                
                $classes[] = ($formData->post_id)? 'edit-form' : 'public-form';

                $form .= '<form id="'.$id_form.'" data-post_type="'.$formData->post_type.'" class="'.implode(' ',$classes).'" ';

                if(!$formData->preview){
                    $form .= ' onsubmit="document.getElementById(\'edit-post-rcl\').disabled=true;document.getElementById(\'edit-post-rcl\').value=\''.__('Being sent, please wait...','wp-recall').'\';"';  
                }
                
                $form .= 'action="" method="post" enctype="multipart/form-data">
                 '.wp_nonce_field('edit-post-rcl','_wpnonce',true,false);

                    if(!$user_ID) $form .= '<div class="rcl-form-field">
                            <label>'.__('Your Name','wp-recall').' <span class="required">*</span></label>
                            <input required type="text" value="" name="name-user">
                    </div>
                    <div class="rcl-form-field">
                            <label>'.__('Your E-mail','wp-recall').' <span class="required">*</span></label>
                            <input required type="text" value="" name="email-user">
                    </div>';

                    if(rcl_get_template_path($formData->post_type.'-form.php',__FILE__)) $form .= rcl_get_include_template($formData->post_type.'-form.php',__FILE__);
                        else $form .= rcl_get_include_template('public-form.php',__FILE__);

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
        $id_post = ($formData->post_id)? $formData->post_id : 0;
        echo '<script type="text/javascript">rcl_init_public_form("'.$formData->post_type.'","'.$id_post.'");</script>';
    }
}

function rcl_publication_title(){
    global $editpost;
    $title = (isset($editpost->post_title))? $editpost->post_title: false;
    echo $title;
}

function rcl_publication_excerpt(){
    global $editpost;
    $excerpt = (isset($editpost->post_excerpt))? $editpost->post_excerpt: false;
    echo $excerpt;
}

function rcl_publication_termlist($tax=false){
    global $group_id,$rcl_options,$options_gr,$formData;
    if($tax) $formData->taxonomy[$formData->post_type] = $tax;
    if(!isset($formData->taxonomy[$formData->post_type])) return false;

    $ctg = ($formData->terms)? $formData->terms: 0;

    if($formData->post_type=='post'){
        $cf = rcl_get_custom_fields($formData->post_id,$formData->post_type,$formData->form_id);
        if(!$ctg) $ctg = (isset($cf['options']['terms'])&&$cf['options']['terms'])? $cf['options']['terms']: $rcl_options['id_parent_category'];
    }
    
    $cnt = $formData->select_amount;
    $select_type = $formData->select_type;

    if($formData->post_type=='post-group'){
        
        $options_gr = rcl_get_options_group($group_id);
        $catlist = rcl_get_tags_list_group($options_gr['tags'],$formData->post_id);
        
        if($catlist)
            echo '<label>'.__('Group categories','wp-recall').':</label>'.$catlist;

    }else{
        
        $cnt = (!isset($cnt)||!$cnt)? 1: $cnt;

        $taxonomy_objects = get_object_taxonomies( $formData->post_type, 'objects' ); 

        foreach($formData->taxonomy[$formData->post_type] as $taxonomy){
            
            if($taxonomy_objects[$taxonomy]->hierarchical!=1) continue;
            
            $cat_list = ($formData->post_id)? get_public_catlist($taxonomy): '';
            
            $sel = new Rcl_List_Terms($taxonomy,$select_type);
            $catlist = $sel->get_select_list(get_public_allterms($taxonomy),$cat_list,$cnt,$ctg);
            if(!$catlist) continue;
            
            echo '<label>'.$taxonomy_objects[$taxonomy]->labels->name.':</label>'.$catlist;
        }   

    }
 
}

function get_public_catlist($taxonomy=false){
    global $formData;

    if(!isset($formData->taxonomy[$formData->post_type])) return false;

    if($formData->post_type=='post'){
        
        $post_cat = get_the_category($formData->post_id);
        
    }else{
        
        $post_cat = get_the_terms( $formData->post_id, $taxonomy );
 
    }
	
    if($post_cat){
        foreach( $post_cat as $key => $p_cat ){
            foreach($post_cat as $pc){
                if($pc->parent==$p_cat->term_id){
                    unset($post_cat[$key]);
                    break;
                }
            }
        }
    }

    return $post_cat;
}

function get_public_allterms($taxonomy=false){
    global $formData;

    //if($formData->post_type&&!isset($formData->taxonomy[$formData->post_type])) return false;

    if($formData->post_type=='post'||!$formData->post_type){

        $catargs = array(
            'orderby'   => 'name'
            ,'order'    => 'ASC'
            ,'hide_empty'   => 0
            ,'hierarchical' =>true
        );

        $allcats = get_categories( $catargs );

    }else{

        $term_args = array(
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

        $allcats = get_terms($taxonomy, $term_args);

    }

    return $allcats;
}

function rcl_publication_upload(){
    global $formData,$rcl_options,$user_ID;
    $wp_uploader = (isset($rcl_options['media_uploader']))? $rcl_options['media_uploader']: 0;
    if($wp_uploader&&$user_ID) return false;
    $gallery = new Rcl_Thumb_Form($formData->post_id,$formData->id_upload);
    echo $gallery->get_gallery();
}

add_action('public_form','rcl_filter_public_form');
function rcl_filter_public_form(){
    global $formData;
    $fields = '';
    echo apply_filters('public_form_rcl',$fields,$formData);
}

function rcl_publication_custom_fields(){
    global $formData;
    echo rcl_get_list_custom_fields($formData->post_id,$formData->post_type,$formData->form_id);
}

function rcl_publication_editor(){
    global $editpost,$rcl_options,$formfields,$formData;

    if($formData->type_editor){

            rcl_wp_editor();

    }else{

        $content = (is_object($editpost)&&$editpost->post_content)? $editpost->post_content: '';

        rcl_sortable_scripts();

        echo '<script>
        jQuery(function(){
                jQuery(".rcl-editor-content").sortable({ axis: "y", containment: "parent", handle: ".move-box", cursor: "move" });
        });
        </script>';

        if($content){
                $rcl_box = strpos($content, '[rcl-box');
                if($rcl_box===false){
                        rcl_wp_editor();
                        return;
                }
        }else{
            rcl_wp_editor();
            return;
        }

        $panel = '';
        $buttons = array();

        if(isset($rcl_options['rcl_editor_buttons'])){
                $icons = array(
                        'text'=>'fa-align-left',
                        'header'=>'fa-header',
                        'image'=>'fa-picture-o',
                        'html'=>'fa-code',
                );
                $names = array(
                        'text'=>__('Text Box','wp-recall'),
                        'header'=>__('Subtitle','wp-recall'),
                        'image'=>__('Image','wp-recall'),
                        'html'=>__('HTML- code','wp-recall'),
                );

                foreach($rcl_options['rcl_editor_buttons'] as $type){
                        $buttons[] = '<li><a href="#" title="'.$names[$type].'" class="get-'.$type.'-box" onclick="return rcl_add_editor_box(this,\''.$type.'\');"><i class="fa '.$icons[$type].'"></i></a></li>';
                }

                if($buttons){
                        $panel = '<div class="rcl-tools-panel">
                                        <ul>'
                                                .implode('',$buttons)
                                        .'</ul>
                                        </div>';
                }
        }

        echo '
        <div class="rcl-public-editor">

                <div class="rcl-editor-content">
                        '.rcl_get_editor_content($content).'
                </div>
                '.$panel.'
        </div>';

    }

}

add_filter('public_form_rcl','rcl_add_non_hierarchical_tags_field',100,2);
function rcl_add_non_hierarchical_tags_field($fields,$formData){
    global $formData;
    
    $taxonomy_objects = get_object_taxonomies( $formData->post_type, 'objects' );
    
    if(!$taxonomy_objects) return false;
    
    $tagslist = '';

    foreach($formData->taxonomy[$formData->post_type] as $taxonomy){  
        
        if($taxonomy_objects[$taxonomy]->hierarchical==1) continue;
        
        $args = array(
            'input_field'=>true,
            'terms_cloud'=>array(
                'hide_empty'=>false,
                'number'=>20,
                'orderby'=>'count',
                'order'=>'DESC'
            )
        );
        
        $args = apply_filters('rcl_public_form_tags',$args,$taxonomy,$formData->post_type);
        
        $tagslist .= rcl_get_tags_checklist($formData->post_id,$taxonomy,$args['terms_cloud']);
        
        if($args['input_field']) $tagslist .= rcl_get_tags_input($formData->post_id,$taxonomy);
    }
    
    if($tagslist){
        $fields .= '<div id="rcl-tags-list">';
        $fields .= $tagslist;
        $fields .= '</div>';
    }

    return $fields;
}

function rcl_get_tags($post_id,$taxonomy='post_tag'){
    $posttags = get_the_terms( $post_id, $taxonomy );

    $tags = array();
    if ($posttags) {
        foreach($posttags as $tag){ $tags[$tag->slug] = $tag; }
    }
    return $tags;
}

function rcl_get_tags_checklist($post_id=false,$taxonomy='post_tag',$t_args = array()){
    global $rcl_options,$formData;
    
    if(!is_array($t_args)||$t_args===false) return false;
    
    $tags = get_terms($taxonomy,$t_args);

    $post_tags = ($post_id)? rcl_get_tags($post_id,$taxonomy): array();
    
    $taxonomy_objects = get_object_taxonomies( $formData->post_type, 'objects' ); 

    $checks = '<label>'.$taxonomy_objects[$taxonomy]->labels->name.':</label>
    <div id="rcl-tags-list-'.$taxonomy.'" class="rcl-tags-list">';
    
    if($tags){
        $checks .= '<span class="rcl-field-input type-checkbox-input">';
        foreach ($tags as $tag){
            $checked = false;
            if(isset($post_tags[$tag->slug])&&$tag->name==$post_tags[$tag->slug]->name){
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
            $checks .= rcl_form_field($args);
        }
        $checks .= '</span>';
    }

    if($post_tags){
        $checks .= '<span class="rcl-field-input type-checkbox-input">';
        foreach ($post_tags as $tag){
            $args = array(
                'type' => 'checkbox',
                'id' => 'tag-'.$tag->slug,
                'name' => 'tags['.$taxonomy.'][]',
                'checked' => true,
                'label' => $tag->name,
                'value' => $tag->name
            );
            $checks .= rcl_form_field($args);
        }
        $checks .= '</span>';
    }

    $checks .= '</div>';
    return $checks;
}

function rcl_get_tags_input($post_id=false,$taxonomy='post_tag'){
    global $rcl_options,$formData;

    $fields = '';

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

function rcl_get_edit_box($type){
    return rcl_get_include_template('editor-'.$type.'-box.php',__FILE__);
}