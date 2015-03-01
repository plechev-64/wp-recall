<?php

class Rcl_PublicForm {
    
    public $post_id;//идентификатор записи
    public $post_type; //тип записи
    public $cats; //список категорий доступных для выбора
    public $form_id; //идентификатор формы
	public $id_upload;
	public $accept;
	public $type_editor;
    
    function __construct($atts){
        global $postdata,$group_id,$rcl_options,$taxs,$user_ID,$wpdb;
        
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
        $this->cats = $cats;
        $this->form_id = $id;
	$this->accept = 'image/*';
		
        if(!isset($type_editor)) $this->type_editor = $rcl_options['type_text_editor'];
        else $this->type_editor = $type_editor;
        
        $taxs = array();       
        $taxs = apply_filters('taxonomy_public_form_rcl',$taxs);
        
        if(isset($_GET['rcl-post-edit'])){
            
            $this->post_id = $_GET['rcl-post-edit']; 
            $postdata = get_post($this->post_id);
            $this->post_type = $postdata->post_type;
            
            if($this->post_type=='post-group'){
                
                if(!user_can_edit_post_group($this->post_id)) return false;
                $group_id = get_group_id_by_post($this->post_id);
                
            }else if(!current_user_can('edit_post', $this->post_id)) return false; 
            
            $form_id = get_post_meta($this->post_id,'publicform-id',1);
            if($form_id) $this->form_id = $form_id;
            
            //if($rcl_options['accept-'.$this->post_type]) $this->accept = $rcl_options['accept-'.$this->post_type];
            if($this->post_type=='task') $this->id_upload = 'freelanceupload';
        }
        
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
    
    function get_catlist(){
        
        global $taxs;

        if(!$taxs[$this->post_type]) return false;
        
        if($this->post_type=='post'){ 
            $cat_list = get_the_category($this->post_id);
        }else{
            $post_cat = get_the_terms( $this->post_id, $taxs[$this->post_type] );
            
            $Child_Terms = new Get_Child_Terms_Rcl();
            $cat_list = $Child_Terms->get_terms_post($post_cat);           
        }
        return $cat_list;
    }
    
    function get_editor(){
        global $rcl_options,$postdata;
        
        if($rcl_options['media_downloader_recall']==1) $media_buttons = 1;
        else $media_buttons = 0;

        $tinymce = 0;
        $quicktags = 0;
        
        if($this->type_editor==1||$this->type_editor==3) $tinymce = 1;
        if($this->type_editor==2||$this->type_editor==3) $quicktags = 1;

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
        
        $label = apply_filters('label_editor_public_form','Текст публикации:',$this);
        $editor = '<label>'.$label.'</label>'
           .$this->get_media_button();
        
        ob_start();
        
        if(isset($postdata->post_content)) $content = $postdata->post_content;
        else $content = '';
        
        wp_editor( $content, 'contentarea', $args );
        
        $editor .= ob_get_contents();
        ob_end_clean();

        return $editor;
    }
    
    function get_upload_form(){
        ob_start();
        new Rcl_Thumb_Form($this->post_id,$this->id_upload);
        $form = ob_get_contents();
        ob_end_clean();
        return $form;
    }
    
    function get_allterms(){
        
        global $taxs;
        
        if($this->post_type&&!isset($taxs[$this->post_type])) return false;
        
        if($this->post_type=='post'||!$this->post_type){

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
            
            $allcats = get_terms($taxs[$this->post_type], $term_args); 
            
        }

        return $allcats;
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
    
    function get_custom_fields($post_id){
        return get_custom_fields_list_rcl($post_id,$this->post_type,$this->form_id);
    }
    
    function get_termlist(){
        global $group_id,$rcl_options,$wpdb,$taxs,$options_gr;
        
        if(!isset($taxs[$this->post_type])&&$this->post_id) return false;
        
        $cnt = 0;
        $ctg = 0;
        
        if($this->cats) $ctg = $this->cats;
        
        if($this->post_type=='post'){
            $cf = get_custom_fields_rcl($this->post_id,$this->post_type,$this->form_id);
            
            if(!$ctg) if(isset($cf['options']['terms'])) $ctg = $cf['options']['terms'];
            
            if(!$ctg) $ctg = $rcl_options['id_parent_category'];

           /*if($ctg){
               $ctg_ar = explode(',',$ctg);
               $cnt_c = count($ctg_ar);
           }*/

           $cnt = (isset($rcl_options['count_category_post']))? $rcl_options['count_category_post']:0;
        }
        
        if($this->post_type=='post-group'){
            $options_gr = get_options_group($group_id);
            $catlist = get_tags_list_group_rcl($options_gr['tags'],$this->post_id);

        }else{
            $cat_list = '';
            if(!$cnt) $cnt = 1;
            $allcats = $this->get_allterms();
            if($this->post_id) $cat_list = $this->get_catlist();
            $sel = new List_Terms_rcl();
            
            $catlist = $sel->get_select_list($allcats,$cat_list,$cnt,$ctg);
            
        }
        //print_r($ctg);
        if(!$catlist) return false; 
        return '<label>Категория:</label>'.$catlist;
    }
    
    function get_media_button(){
        global $rcl_options;
        if($rcl_options['media_downloader_recall']!=1)
            return get_button_rcl('Добавить медиафайл','#',array('icon'=>'fa-folder-open','id'=>'get-media-rcl'));
    }

    function add_tags_input($fls){
        
        if($this->post_type=='post') 
            $fls .= '<table>
                <tr>
                    <td>
                    <label>
                        Метки: 
                        <small>
                            (метки вписываются через запятую)
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

                $fls .= '<input class="recall-button" type="submit" id="edit-post-rcl" value="Изменить">
                <input type="hidden" name="post-rcl" value="'.$this->post_id.'">';

            }else{

                $fls .= '<input class="recall-button" id="edit-post-rcl" type="submit" value="Опубликовать">'
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
            <input class="alignleft recall-button" type="submit" style="width:120px;" onsubmit="return confirm(\'Вы уверены? Потом восстановить не получиться!\');" name="delete-post-rcl" value="Удалить">				
            <input type="hidden" name="post-rcl" value="'.$this->post_id.'"></form>';
        }
        return $cnt;
    }
    
    function get_title(){
        global $postdata;
        if(isset($postdata->post_title)) $title = $postdata->post_title;
        else $title = '';
        $label = apply_filters('label_title_public_form','Заголовок',$this);
        return '<label>
            '.$label.' <span class="required">*</span>:
            </label>
            <input type="text" maxlength="150" required value="'.$title.'" name="post_title" id="post_title_input">';
    }
    
    function public_form(){
        global $user_ID;

            if(!$user_ID) return '<p align="center">Вы должны быть авторизованы для возможности делать публикации.<br>Войдите или зарегистрируйтесь</p>';
            
            if(!$this->user_can()){
                if($this->post_type=='post-group') return '<div class="public-post-group">'
                    . '<h3 >Сожалеем, но у вас нет прав для публикации внутри групп :(</h3>'
                        . '</div>';
                else return '<h3 class="aligncenter">'
                    . 'Сожалеем, но у вас нет прав<br>для публикации записей на этом сайте :('
                        . '</h3>';
            }
            
            $formfields = array(
            	'title'=>true,
            	'termlist'=>true,
            	'editor'=>true,
            	'custom_fields'=>true,
            	'upload'=>true
            );
            
            $formfields = apply_filters('fields_public_form_rcl',$formfields,$this);
				
            $public_fields = ($formfields['custom_fields'])? $this->get_custom_fields($this->post_id): false;

            $form = '<div class="public_block">
                <form id="form-'.$this->post_type.'-';
                $form .= ($this->post_id)? $this->post_id : '0';
                $form .= '" class="';
                $form .= ($this->post_id)? 'edit-form' : 'public-form';
                $form .= '" onsubmit="document.getElementById(\'edit-post-rcl\').disabled=true;document.getElementById(\'edit-post-rcl\').value=\'Идет отправка, пожалуйста, подождите..\';"  action="" method="post" enctype="multipart/form-data">
                '.wp_nonce_field('edit-post-rcl','_wpnonce',true,false);

                    if($formfields['title']) $form .= $this->get_title();

                    if($formfields['termlist']) $form .= $this->get_termlist();

                    if($formfields['editor']) $form .= $this->get_editor();

                    if($formfields['upload']) $form .= $this->get_upload_form();
                    
                    $fields = '';

                    $form .= apply_filters('public_form_rcl',$fields,$this);

                   if($public_fields) $form .= $public_fields;

                    $form .= $this->submit_and_hidden()

               . '</form>';
               $after = '';
               $form .= apply_filters('after_public_form_rcl',$after,$this);
               
           $form .= '</div>';

        return $form;
    }
}
