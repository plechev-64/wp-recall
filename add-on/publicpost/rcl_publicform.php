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
            'type_editor'=> null,
			'wp_editor'=> null,
            'group_id'=>$group_id
            ),
        $atts));

        $this->post_type = $post_type;
        $this->id_upload = $id_upload;
        $this->terms = $cats;
        $this->form_id = $id;
		$this->accept = $accept;

        if(!isset($wp_editor)){
			if(isset($rcl_options['wp_editor'])){
				$cnt = count($rcl_options['wp_editor']);
				if($cnt==1){
					$type = $rcl_options['wp_editor'][0];
				}else{
					$type=3;
				}
			}
			$this->wp_editor = ($type)? $type: 0;
		}else $this->wp_editor = $wp_editor;

        if(isset($_GET['rcl-post-edit'])){

            $this->post_id = $_GET['rcl-post-edit'];
            $editpost = get_post($this->post_id);
            $this->post_type = $editpost->post_type;

            if($this->post_type=='post-group'){

                if(!rcl_can_user_edit_post_group($this->post_id)&&!current_user_can('edit_post', $this->post_id)) $this->can_edit = false;

                $group_id = rcl_get_group_id_by_post($this->post_id);

            }else if(!current_user_can('edit_post', $this->post_id)) $this->can_edit = false;

            $form_id = get_post_meta($this->post_id,'publicform-id',1);
            if($form_id) $this->form_id = $form_id;
        }

        $taxs = array();
        $taxs = apply_filters('taxonomy_public_form_rcl',$taxs);

		$this->type_editor = ($rcl_options['type_editor-'.$this->post_type])? $rcl_options['type_editor-'.$this->post_type]: $rcl_options['type_text_editor'];
		if(!$this->type_editor) $this->type_editor = $type_editor;

		if($rcl_options['accept-'.$this->post_type]) $this->accept = $rcl_options['accept-'.$this->post_type];

        $formData = (object)array(
            'form_id' =>$this->form_id,
            'post_id' =>$this->post_id,
            'post_type' =>$this->post_type,
            'id_upload' =>$this->id_upload,
            'terms' =>$this->terms,
            'accept' =>$this->accept,
            'type_editor' =>$this->type_editor,
			'wp_editor' =>$this->wp_editor,
            'taxonomy' =>$taxs
        );

        rcl_fileapi_scripts();

        if($this->post_id) add_filter('after_public_form_rcl',array(&$this,'delete_button'),10,2);

    }

    function user_can(){
        global $rcl_options,$user_ID;

        if(!$user_ID) return false;

        if($this->post_type=='post-group') $user_can = $rcl_options['user_public_access_group'];
        else $user_can = $rcl_options['user_public_access_recall'];

        if(!$user_can) return true;

        $userinfo = get_userdata( $user_ID );

        if($userinfo->user_level>=$user_can) $can = true;
        else $can = false;

        $can = apply_filters('rcl_user_can_public',$can,$this);

        return $can;
    }

    function submit_and_hidden(){
        global $group_id,$post;

		$inputs = array(
			//array('type'=>'submit','value'=>__('To publish','rcl'),'id'=>'edit-post-rcl','class'=>'recall-button'),
			array('type'=>'button','value'=>__('Preview','rcl'),'onclick'=>'rcl_preview(this);','class'=>'rcl-preview-post recall-button'),
			array('type'=>'hidden','value'=>1,'name'=>'edit-post-rcl'),
			array('type'=>'hidden','value'=>base64_encode($this->form_id),'name'=>'id_form'),
		);

		if($this->post_id) $inputs[] = array('type'=>'hidden','value'=>$this->post_id,'name'=>'post-rcl');
		else $inputs[] = array('type'=>'hidden','value'=>base64_encode($this->post_type),'name'=>'posttype');

		$hiddens = array(
            'post-group' => array('term_id'=>base64_encode($group_id)),
            'products' => array('formpage'=>$post->ID),
            'task' => array('formpage'=>$post->ID)
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
		global $user_ID,$editpost;
        if($editpost->post_author==$user_ID){
            $cnt .= '<form method="post" action="" onsubmit="return confirm(\''.__('Are you seriously','rcl').'?\');">
            '.wp_nonce_field('delete-post-rcl','_wpnonce',true,false).'
            <input class="alignleft recall-button delete-post-submit" type="submit" name="delete-post-rcl" value="'.__('Delete post','rcl').'">
            <input type="hidden" name="post-rcl" value="'.$this->post_id.'"></form>';
        }else{

			$cnt .= '
				<div id="rcl-delete-post">
					<a href="#" class="recall-button delete-toggle">'.__('Delete post','rcl').'</a>
					<div class="delete-form-contayner">
						<form action="" method="post"  onsubmit="return confirm(\''.__('Are you seriously','rcl').'?\');">
						'.wp_nonce_field('delete-post-rcl','_wpnonce',true,false).'
						'.$this->reasons_delete().'
						<label>'.__('or enter its cause','rcl').'</label>
						<textarea required id="reason_content" name="reason_content"></textarea>
						<p><input type="checkbox" name="no-reason" onclick="(!document.getElementById(\'reason_content\').getAttribute(\'disabled\')) ? document.getElementById(\'reason_content\').setAttribute(\'disabled\', \'disabled\') : document.getElementById(\'reason_content\').removeAttribute(\'disabled\')" value="1"> '.__('Without notice','rcl').'</p>
						<input class="floatright recall-button delete-post-submit" type="submit" name="delete-post-rcl" value="'.__('Delete post','rcl').'">
						<input type="hidden" name="post-rcl" value="'.$this->post_id.'">
						</form>
					</div>
				</div>
			';
		}
        return $cnt;
    }

	function reasons_delete(){

		$reasons = array(
			array(
				'value'=>__('Not correspond the subject','rcl'),
				'content'=>__('The publication does not correspond to the subject site','rcl'),
			),
			array(
				'value'=>__('Not furnished','rcl'),
				'content'=>__('Publication is not formalized under the rules','rcl'),
			),
			array(
				'value'=>__('Advertising/Spam','rcl'),
				'content'=>__('Publication labeled as advertising or spam','rcl'),
			)
		);

		$reasons = apply_filters('rcl_reasons_delete',$reasons);

		if(!$reasons) return false;

		$content = '<label>'.__('Use the blank notice','rcl').':</label>';
		foreach($reasons as $reason){
			$content .= '<input type="button" class="recall-button reason-delete" onclick="document.getElementById(\'reason_content\').value=\''.$reason['content'].'\'" value="'.$reason['value'].'">';
		}

		return $content;
	}

    function public_form(){
        global $user_ID,$formFields;

            if(!$user_ID) return '<p align="center">'.__('You must be logged in to post. Login or register','rcl').'</p>';

			if(!$this->can_edit) return '<p align="center">'.__('You can not edit this publication :(','rcl').'</p>';

            if(!$this->user_can()){
                if($this->post_type=='post-group') return '<div class="public-post-group">'
                    . '<h3 >'.__('Sorry, but you have no rights to publish within groups :(','rcl').'</h3>'
                        . '</div>';
                else return '<h3 class="aligncenter">'
                    . __('Sorry, but you have no right<br>to publish the records on this site :(','rcl')
                        . '</h3>';
            }

            $formfields = array(
            	'title'=>true,
            	'termlist'=>true,
            	'editor'=>true,
            	'custom_fields'=>true,
				'upload'=>true
            );

            $formFields = apply_filters('fields_public_form_rcl',$formfields,$this);

            $form = '<div class="public_block">';

                $id_form = ($this->post_id)? $this->post_id : 0;

                if(!$id_form){
                    if(!isset($_SESSION['new-'.$this->post_type])){
                        $_SESSION['new-'.$this->post_type] = 1;
                        $form .= '<script>Object.keys(localStorage)
                                .forEach(function(key){
                                     if (/^form-'.$this->post_type.'-0/.test(key)) {
                                         localStorage.removeItem(key);
                                     }
                             });</script>';
                    }
                }

                $id_form = 'form-'.$this->post_type.'-'.$id_form;

                $form .= '<form id="'.$id_form.'" class="';
                $form .= ($this->post_id)? 'edit-form' : 'public-form';
                $form .= '" onsubmit="document.getElementById(\'edit-post-rcl\').disabled=true;document.getElementById(\'edit-post-rcl\').value=\''.__('Being sent, please wait...','rcl').'\';"  action="" method="post" enctype="multipart/form-data">
                '.wp_nonce_field('edit-post-rcl','_wpnonce',true,false);

                    if(rcl_get_template_path($this->post_type.'-form.php',__FILE__)) $form .= rcl_get_include_template($this->post_type.'-form.php',__FILE__);
                        else $form .= rcl_get_include_template('public-form.php',__FILE__);

					$fields = '';

					$form .= apply_filters('rcl_public_form',$fields,$this);

                    $form .= $this->submit_and_hidden()

               . '</form>';

               $form .= '<script type="text/javascript">
					function addfile_content($file){
						var ifr = jQuery("#contentarea_ifr").contents().find("#tinymce").html();
						jQuery("#contentarea").insertAtCaret($file+"&nbsp;");
						jQuery("#contentarea_ifr").contents().find("#tinymce").html(ifr+$file+"&nbsp;");
						return false;
					}
				</script>';

               $after = '';
               $form .= apply_filters('after_public_form_rcl',$after,$this);

           $form .= '</div>';

        return $form;
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
    if(!isset($formData->taxonomy[$formData->post_type])&&$formData->post_id) return false;

    $ctg = ($formData->terms)? $formData->terms: 0;

    if($formData->post_type=='post'){
        $cf = rcl_get_custom_fields($formData->post_id,$formData->post_type,$formData->form_id);
        if(!$ctg) $ctg = (isset($cf['options']['terms']))? $cf['options']['terms']: $ctg = $rcl_options['id_parent_category'];
        $cnt = (isset($rcl_options['count_category_post']))? $rcl_options['count_category_post']:0;
    }

    if($formData->post_type=='post-group'){
        $options_gr = rcl_get_options_group($group_id);
        $catlist = rcl_get_tags_list_group($options_gr['tags'],$formData->post_id);

    }else{
        $cnt = (!$cnt)? 1: $cnt;
        $cat_list = ($formData->post_id)? get_public_catlist(): '';
        $sel = new Rcl_List_Terms();
        $catlist = $sel->get_select_list(get_public_allterms(),$cat_list,$cnt,$ctg);

    }
    if(!$catlist) return false;

    echo '<label>'.__('Category','rcl').':</label>'.$catlist;
}

function get_public_catlist(){
    global $formData;

    if(!isset($formData->taxonomy[$formData->post_type])) return false;

    if($formData->post_type=='post'){
        $cat_list = get_the_category($formData->post_id);
    }else{
        $post_cat = get_the_terms( $formData->post_id, $formData->taxonomy[$formData->post_type] );

        $Child_Terms = new Rcl_Child_Terms();
        $cat_list = $Child_Terms->get_terms_post($post_cat);
    }

    return $cat_list;
}

function get_public_allterms(){
    global $formData;

    if($formData->post_type&&!isset($formData->taxonomy[$formData->post_type])) return false;

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

        $allcats = get_terms($formData->taxonomy[$formData->post_type], $term_args);

    }

    return $allcats;
}

function rcl_publication_upload(){
    global $formData;
    new Rcl_Thumb_Form($formData->post_id,$formData->id_upload);
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

function rcl_get_tags($post_id){
	$posttags = get_the_tags($post_id);
	$tags = array();
	if ($posttags) {
		foreach($posttags as $tag){ $tags[$tag->slug] = $tag; }
	}
	return $tags;
}

function rcl_get_tags_checklist($post_id=false){
	global $rcl_options;

	$t_args = array('hide_empty'=>false);

	if($rcl_options['limit_tags']){
		$include = explode(',',$rcl_options['limit_tags']);
		$t_args['include'] = array_map('trim', $include);
	}

	$tags = get_tags($t_args);

	$post_tags = ($post_id)? rcl_get_tags($post_id): array();

	$checks = '<label>'.__('Select a tag from the list','rcl').'</label>
	<div id="rcl-tags-list">';
	foreach ($tags as $tag){
		$checked = false;
		if($tag->name==$post_tags[$tag->slug]->name){
			$checked = true;
			unset($post_tags[$tag->slug]);
		}
		$args = array(
			'type' => 'checkbox',
			'name' => 'tags[]',
			'checked' => $checked,
			'label' => $tag->name,
			'value' => $tag->name
		);
		$checks .= rcl_form_field($args);
	}

	if($post_tags){
		foreach ($post_tags as $tag){
			$args = array(
				'type' => 'checkbox',
				'name' => 'tags[]',
				'checked' => true,
				'label' => $tag->name,
				'value' => $tag->name
			);
			$checks .= rcl_form_field($args);
		}
        }else{
            return false;
        }

	$checks .= '</div>';
	return $checks;
}

function rcl_publication_editor(){
	global $editpost,$rcl_options,$formfields,$formData;

	if($formData->type_editor){

		rcl_wp_editor();

	}else{

		if($editpost->post_content){
			$rcl_box = strpos($editpost->post_content, '[rcl-box');
			if($rcl_box===false){
				rcl_wp_editor(array('type_editor'=>1,'wp_editor'=>3),$editpost->post_content);
				return;
			}
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
				'text'=>__('Text Box','rcl'),
				'header'=>__('Subtitle','rcl'),
				'image'=>__('Image','rcl'),
				'html'=>__('HTML- code','rcl'),
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
			'.$panel.'
			<div class="rcl-editor-content">
				'.rcl_get_editor_content($editpost->post_content).'
			</div>
			'.$panel.'
		</div>';

	}

}

function rcl_get_tags_input($post_id=false){
	global $rcl_options;

	rcl_autocomplete_scripts();

	$fields = '';

	if($rcl_options['display_tags']==1) $fields .= rcl_get_tags_checklist($post_id);

	$args = array(
		'type' => 'text',
		'id' => 'rcl_post_tags',
		'name' => 'tags',
		'placeholder' => __('Enter your tags','rcl'),
		'label' => __('Add your tags','rcl').'<br><small>'.__('Each tag is separated with Enter','rcl').'</small>'
	);

	$fields .= rcl_form_field($args);

	$fields .= "<script>
	jQuery(function($){
		$('#rcl_post_tags').magicSuggest({
		  data: wpurl+'wp-admin/admin-ajax.php',
		  dataUrlParams: { action: 'rcl_get_like_tags' },
		  noSuggestionText: '".__("Not found","rcl")."',
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

add_filter('public_form_rcl','rcl_add_tags_input',10,2);
function rcl_add_tags_input($fields,$formData){

	if($formData->post_type!='post') return $fields;

	$fields .= rcl_get_tags_input($formData->post_id);

	return $fields;
}

function rcl_get_edit_box($type){
	return rcl_get_include_template('editor-'.$type.'-box.php',__FILE__);
}