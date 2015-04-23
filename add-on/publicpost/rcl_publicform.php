<?php

class Rcl_PublicForm {
    
    public $post_id;//идентификатор записи
    public $post_type; //тип записи
    public $terms; //список категорий доступных для выбора
    public $form_id; //идентификатор формы
	public $id_upload;
	public $accept;
	public $type_editor;
    
    function __construct($atts){
        global $editpost,$group_id,$rcl_options,$user_ID,$formData;
        
        extract(shortcode_atts(array(
            'cats' => false,
            'id' => 1,
            'id_upload' => 'postupload',
            'post_type'=> 'post',
            'type_editor'=> null,
            'group_id'=>$group_id
            ),
        $atts));
		
        $this->post_type = $post_type;
        $this->id_upload = $id_upload;
        $this->terms = $cats;
        $this->form_id = $id;
	$this->accept = 'image/*';
		
        if(!isset($type_editor)) $this->type_editor = $rcl_options['type_text_editor'];
        else $this->type_editor = $type_editor;

        if(isset($_GET['rcl-post-edit'])){
            
            $this->post_id = $_GET['rcl-post-edit']; 
            $editpost = get_post($this->post_id);
            $this->post_type = $editpost->post_type;
            
            if($this->post_type=='post-group'){
                
                if(!user_can_edit_post_group($this->post_id)&&!current_user_can('edit_post', $this->post_id)) return false;
                
                $group_id = get_group_id_by_post($this->post_id);
                
            }else if(!current_user_can('edit_post', $this->post_id)) return false; 
            
            $form_id = get_post_meta($this->post_id,'publicform-id',1);
            if($form_id) $this->form_id = $form_id;
            
            //if($rcl_options['accept-'.$this->post_type]) $this->accept = $rcl_options['accept-'.$this->post_type];
            if($this->post_type=='task') $this->id_upload = 'freelanceupload';
        }
        
        $taxs = array();       
        $taxs = apply_filters('taxonomy_public_form_rcl',$taxs);
        
        $formData = (object)array(
            'form_id' =>$this->form_id,
            'post_id' =>$this->post_id,
            'post_type' =>$this->post_type,
            'id_upload' =>$this->id_upload,
            'terms' =>$this->terms,
            'accept' =>$this->accept,
            'type_editor' =>$this->type_editor,
            'taxonomy' =>$taxs
        );
        
        fileapi_footer_scripts();
        add_filter('public_form_rcl',array(&$this,'add_tags_input'),10);
        add_filter('after_public_form_rcl',array(&$this,'delete_button'),10,2);
        
    }
    
    function get_tags(){
        $posttags = get_the_tags($this->post_id); 
        $taglist = '';
        if ($posttags) { 
            $cnt=0;           
            foreach((array)$posttags as $tag){ 
                if(++$cnt>1)$taglist .= ',';
                $taglist .= $tag->name;
            }
        }
        return $taglist;
    }
    
    function user_can(){
        global $rcl_options,$user_ID;
        
        if(!$user_ID) return false;
        
        if($this->post_type=='post-group') $user_can = $rcl_options['user_public_access_group'];
        else $user_can = $rcl_options['user_public_access_recall'];

        if(!$user_can) return true;

        $userinfo = get_userdata( $user_ID );

        if($userinfo->user_level>=$user_can) return true;
        else return false;
    }
    
    function add_tags_input($fls){
        
        if($this->post_type=='post') 
            $fls .= '<table>
                <tr>
                    <td>
                    <label>
                        '.__('Метки').': 
                        <small>
                            '.__('(метки вписываются через запятую)').'
                        </small>
                    </label>
                    </td><td>
                         <input type="text" maxlength="200" name="post_tags" id="post_tags" value="'.$this->get_tags().'">'
                    . '</td>'
                . '</tr>'
                . '</table>';
        
        return $fls;
    }

    function submit_and_hidden(){
        global $group_id,$post;
        
        $hiddens = array(
            'post-group' => array('term_id'=>base64_encode($group_id)),
            'products' => array('formpage'=>$post->ID),
            'task' => array('formpage'=>$post->ID)
        );
        
        $fls = '<div align="right">';

            if($this->post_id){	

                $fls .= '<input class="recall-button" type="submit" id="edit-post-rcl" value="'.__('Изменить').'">
                <input type="hidden" name="post-rcl" value="'.$this->post_id.'">';

            }else{

                $fls .= '<input class="recall-button" id="edit-post-rcl" type="submit" value="'.__('Опубликовать').'">'
                        . '<input type="hidden" name="posttype" value="'.base64_encode($this->post_type).'">';

                if(isset($hiddens[$this->post_type])){
                    foreach($hiddens[$this->post_type] as $name=>$val){
                        $fls .= '<input type="hidden" name="'.$name.'" value="'.$val.'">';
                    }
                }
            }

            $fls .= '<input type="hidden" name="edit-post-rcl" value="1">';
            $fls .= '<input type="hidden" value="'.base64_encode($this->form_id).'" name="id_form" id="id_form">'
                    
                    
        . '</div>';
        
        return $fls;
    }
    
    function delete_button($cnt,$data){
        if($this->post_id){
            $cnt .= '<form method="post" action="">
            '.wp_nonce_field('delete-post-rcl','_wpnonce',true,false).'
            <input class="alignleft recall-button" type="submit" style="width:120px;" onsubmit="return confirm(\''.__('Вы уверены? Потом восстановить не получиться!').'\');" name="delete-post-rcl" value="'.__('Удалить').'">				
            <input type="hidden" name="post-rcl" value="'.$this->post_id.'"></form>';
        }
        return $cnt;
    }
    
    function public_form(){
        global $user_ID,$formFields;

            if(!$user_ID) return '<p align="center">'.__('Вы должны быть авторизованы для возможности делать публикации.<br>Войдите или зарегистрируйтесь').'</p>';
            
            if(!$this->user_can()){
                if($this->post_type=='post-group') return '<div class="public-post-group">'
                    . '<h3 >'.__('Сожалеем, но у вас нет прав для публикации внутри групп :(').'</h3>'
                        . '</div>';
                else return '<h3 class="aligncenter">'
                    . __('Сожалеем, но у вас нет прав<br>для публикации записей на этом сайте :(')
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
                $form .= '" onsubmit="document.getElementById(\'edit-post-rcl\').disabled=true;document.getElementById(\'edit-post-rcl\').value=\''.__('Идет отправка, пожалуйста, подождите..').'\';"  action="" method="post" enctype="multipart/form-data">
                '.wp_nonce_field('edit-post-rcl','_wpnonce',true,false);
                    
                    if(get_template_rcl($this->post_type.'-form.php',__FILE__)) $form .= get_include_template_rcl($this->post_type.'-form.php',__FILE__);
                        else $form .= get_include_template_rcl('public-form.php',__FILE__);

                    $form .= $this->submit_and_hidden()

               . '</form>';
                    
               $after = '';
               $form .= apply_filters('after_public_form_rcl',$after,$this);
               
           $form .= '</div>';

        return $form;
    }
}

function the_public_title(){
    global $editpost;
    $title = (isset($editpost->post_title))? $editpost->post_title: false;
    echo $title;
}

function the_public_termlist($tax=false){
    global $group_id,$rcl_options,$options_gr,$formData;
    if($tax) $formData->taxonomy[$formData->post_type] = $tax;  
    if(!isset($formData->taxonomy[$formData->post_type])&&$formData->post_id) return false;

    $ctg = ($formData->terms)? $formData->terms: 0;

    if($formData->post_type=='post'){
        $cf = get_custom_fields_rcl($formData->post_id,$formData->post_type,$formData->form_id);
        if(!$ctg) $ctg = (isset($cf['options']['terms']))? $cf['options']['terms']: $ctg = $rcl_options['id_parent_category'];
        $cnt = (isset($rcl_options['count_category_post']))? $rcl_options['count_category_post']:0;
    }

    if($formData->post_type=='post-group'){
        $options_gr = get_options_group($group_id);
        $catlist = get_tags_list_group_rcl($options_gr['tags'],$formData->post_id);

    }else{
        $cnt = (!$cnt)? 1: $cnt;
        $cat_list = ($formData->post_id)? get_public_catlist(): '';
        $sel = new List_Terms_rcl();
        $catlist = $sel->get_select_list(get_public_allterms(),$cat_list,$cnt,$ctg);

    }
    if(!$catlist) return false;
    
    echo '<label>Категория:</label>'.$catlist;
}

function get_public_catlist(){       
    global $formData;
    
    if(!isset($formData->taxonomy[$formData->post_type])) return false;

    if($formData->post_type=='post'){ 
        $cat_list = get_the_category($formData->post_id);
    }else{
        $post_cat = get_the_terms( $formData->post_id, $formData->taxonomy[$formData->post_type] );

        $Child_Terms = new Get_Child_Terms_Rcl();
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

function the_public_editor(){
    global $rcl_options,$editpost,$formData;
        
    $media_buttons = ($rcl_options['media_downloader_recall']==1)? $media_buttons = 1: 0;
    $tinymce = ($formData->type_editor==1||$formData->type_editor==3)? $tinymce = 1: 0;
    $quicktags = ($formData->type_editor==2||$formData->type_editor==3)? $quicktags = 1: 0;

    $args = array( 'wpautop' => 1  
        ,'media_buttons' => $media_buttons  
        ,'textarea_name' => 'post_content'
        ,'textarea_rows' => 20  
        ,'tabindex' => null  
        ,'editor_css' => ''  
        ,'editor_class' => 'autosave'  
        ,'teeny' => 0  
        ,'dfw' => 0  
        ,'tinymce' => $tinymce  
        ,'quicktags' => $quicktags  
    );

    if($rcl_options['media_downloader_recall']!=1)
        echo get_button_rcl(__('Добавить медиафайл'),'#',array('icon'=>'fa-folder-open','id'=>'get-media-rcl'));

    $content = (isset($editpost->post_content))? $editpost->post_content: '';

    wp_editor( $content, 'contentarea', $args );
}

function the_public_upload(){
    global $formData;
    new Rcl_Thumb_Form($formData->post_id,$formData->id_upload);
}

add_action('public_form','add_filter_public_form');
function add_filter_public_form(){
    global $formData;
    $fields = '';
    echo apply_filters('public_form_rcl',$fields,$formData);
}

function the_public_custom_fields(){
    global $formData;
    echo get_custom_fields_list_rcl($formData->post_id,$formData->post_type,$formData->form_id);
}
