<?php

class Rcl_List_Terms{
	
    public $taxonomy;
    public $terms;
    public $selected_term;
    public $datalist;
    public $post_terms;
    public $include_terms;
    public $select_amount;
    public $type_output;
	
    function __construct($taxonomy = false, $type_output = 'select'){

        $this->taxonomy = $taxonomy;
        $this->type_output = $type_output;

    }
	
    function get_select_list($terms, $post_terms, $select_amount, $include_terms = false, $type_output = false){
        
        $this->include_terms = ($include_terms)? array_map('trim',explode(',', $include_terms)): false;

        $this->terms = $terms;
        $this->datalist = $this->setup_data($terms);
        
        $this->post_terms = ($post_terms)? $this->setup_data($post_terms): 0;
        $this->select_amount = $select_amount;

        if($type_output) 
            $this->type_output = $type_output;

        $method = 'get_'.$this->type_output;

        return $this->$method();

    }
	
    function get_select(){

        $content = '<div class="rcl-terms-select">';

        for($a=0;$a<$this->select_amount;$a++){

            $this->selected_term = false;

            $content .= '<select class="postform" name="cats['.$this->taxonomy.'][]">';

            if($a>0) 
                    $content .= '<option value="">'.__('Not selected','wp-recall').'</option>';			

            $content .= $this->get_options_list();

            $content .= '</select>';

        }

        $content .= '</div>';

        return $content;
    }
	
    function get_checkbox(){

        $content = '<div class="rcl-terms-select">';

        $content .= '<div class="category-list rcl-field-input type-checkbox-input">'; 

        $content .= $this->get_checkbox_list();

        $content .= '</div>';

        $content .= '</div>';

        return $content;
    }
	
    function setup_data($terms){

        $newterms = array();
        foreach($terms as $term){
            $newterms[$term->term_id] = array(
                'term_id'=>$term->term_id,
                'name'=>$term->name,
                'parent'=>$term->parent
            );

        }

        $datalist = array();
        foreach($newterms as $term_id=>$term){
            
            if($this->include_terms){
                if(in_array($term_id,$this->include_terms)){
                    
                    $datalist[$term_id] = $term;
                    $datalist[$term_id]['parent'] = 0;
                    
                    $childrens = $this->get_childrens($term_id);

                    if($childrens){

                        $datalist[$term_id]['childrens'] = $childrens;
                        
                        $childs_tree = $this->get_childrens_tree($term_id);
                        
                        if($childs_tree){
                        
                            foreach($childs_tree as $child_id){

                                $datalist[$child_id] = $newterms[$child_id];

                                $childs = $this->get_childrens($child_id);

                                if($childs){
                                    $datalist[$child_id]['childrens'] = $childs;
                                }
                            }
                        
                        }
                        
                    }

                }
                
                continue;
            }

            $datalist[$term_id] = $term;

            $childrens = $this->get_childrens($term_id,$newterms);

            if($childrens){
                $datalist[$term_id]['childrens'] = $childrens;
            }

        }

        return $datalist;

    }
    
    function get_childrens_tree($term_id){
        $childrens = $this->get_childrens($term_id);
        $sub_childrens = $childrens;
        foreach($childrens as $child_id){
            $sub_childrens = array_merge($this->get_childrens_tree($child_id),$sub_childrens);
        }
        return $sub_childrens;
    }
    
    function get_childrens($term_id){
        $childs = array();
        foreach($this->terms as $term){
            if($term->parent!=$term_id) continue;
            $childs[] = $term->term_id;
        }
        return $childs;
    }
	
    function get_options_list($term_ids = false){

        $terms_data = ($term_ids)? $this->get_terms_data($term_ids): $this->datalist;

        foreach($terms_data as $term_id=>$term){

            if($term['parent']) continue;

            if($term['childrens']){
                    $options[] = '<optgroup label="'.$term['name'].'">'.$this->get_options_list($term['childrens']).'</optgroup>';
                    continue;
            }

            if($this->post_terms){
                if(!$this->selected_term&&selected(isset($this->post_terms[$term_id]),true,false)){

                    unset($this->post_terms[$term_id]);

                    $this->selected_term = $term_id;

                }
            }

            $options[] = '<option '.selected($this->selected_term,$term_id,false).' value="'.$term_id.'">'.$term['name'].'</option>';

        }

        return implode('',$options);

    }
	
    function get_terms_data($term_ids){
        $terms = array();
        foreach($term_ids as $term_id){
                $terms[$term_id] = $this->datalist[$term_id];
                $terms[$term_id]['parent'] = 0;
        }
        return $terms;
    }
	
    function get_checkbox_list($term_ids = false){

        $terms_data = ($term_ids)? $this->get_terms_data($term_ids): $this->datalist;

        foreach($terms_data as $term_id=>$term){

            if($term['parent']) continue;

            if($term['childrens']){
                $options[] = '<div class="child-list-category">'
                            . '<span class="parent-category">'.$term['name'].'</span>'
                            . $this->get_checkbox_list($term['childrens'])
                            .'</div>';
                continue;
            }

            $options[] = '<span class="rcl-checkbox-box">'
                        . '<input '.checked(isset($this->post_terms[$term_id]),true,false).' id="category-'.$term_id.'" type="checkbox" name="cats['.$this->taxonomy.'][]" value="'.$term_id.'">'
                        . '<label class="block-label" for="category-'.$term_id.'">'.$term['name'].'</label>'
                        . '</span>';
        }

        return implode('',$options);

    }

}

class Rcl_Edit_Terms_List{

    public $cats;
    public $new_cat = array();

    function get_terms_list($cats,$post_cat){
        $this->cats = $cats;
        $this->new_cat = $post_cat;
        $cnt = count($post_cat);
        for($a=0;$a<$cnt;$a++){
            foreach((array)$cats as $cat){
                if($cat->term_id!=$post_cat[$a]) continue;
                if($cat->parent==0) continue;
                $this->new_cat = $this->get_parents($cat->term_id);
            }
        }
        return $this->new_cat;
    }
	
    function get_parents($term_id){
        foreach($this->cats as $cat){
            if($cat->term_id!=$term_id) continue;
            if($cat->parent==0) continue;
            $this->new_cat[] = $cat->parent;
            $this->new_cat = $this->get_parents($cat->parent);
        }
        return $this->new_cat;
    }
}

class Rcl_Thumb_Form{

    public $post_id;
    public $thumb = 0;
    public $id_upload;

    function __construct($p_id=false,$id_upload='upload-public-form') {
        global $user_ID,$formData;
        
        if(!$user_ID) return false;

        $this->post_id = $p_id;
        $this->id_upload = ($id_upload)? $id_upload: $formData->id_upload;
        
        if($this->post_id) 
            $this->thumb = get_post_meta($this->post_id, '_thumbnail_id',1);

    }

    function get_gallery($accept='image/*'){
        global $user_ID,$formData;
        
        $accept = ($formData->accept)? $formData->accept: $accept;
        if(!$this->id_upload) $this->id_upload = $formData->id_upload;

        if($this->post_id) $gal = get_post_meta($this->post_id, 'recall_slider', 1);
        else $gal = 0;

        if($this->post_id){
            $args = array(
                'post_parent' => $this->post_id,
                'post_type'   => 'attachment',
                'numberposts' => -1,
                'post_status' => 'any'
            );
            $child = get_children( $args );
            if($child){ foreach($child as $ch){$temp_gal[]['ID']=$ch->ID;} }

        }else{
            $user_id = ($user_ID)? $user_ID: $_COOKIE['PHPSESSID'];
            $temps = get_option('rcl_tempgallery');            
            $temp_gal = $temps[$user_id];
        }

        $attachlist = '';
        if($temp_gal){
            $attachlist = $this->get_gallery_list($temp_gal);
        }

        if($formData) $content = '<small class="notice-upload">'.__('Click on Priceline the image to add it to the content of the publication','wp-recall').'</small>';

        $content .= '<ul id="temp-files-'.$formData->post_type.'" class="attachments-post">'.$attachlist.'</ul>';
		
        if($formData){
            $content .= '<div class="rcl-form-field">'
                . '<span class="rcl-field-input type-checkbox-input">'
                . '<span class="rcl-checkbox-box">'
                . '<input id="rcl-gallery" type="checkbox" '.checked($gal,1,false).' name="add-gallery-rcl" value="1">'
                . '<label for="rcl-gallery" class="block-label"> - '.__('Display all attached images in the gallery.','wp-recall').'</label>'
                . '</span>'
                . '</span>'
                . '</div>';
        }
	
        $content .= '<div id="status-temp"></div>
        <div>
            <div id="rcl-public-dropzone-'.$formData->post_type.'" class="rcl-dropzone mass-upload-box">
                <div class="mass-upload-area">
                        '.__('To add files to the download queue','wp-recall').'
                </div>
                <hr>
                <div class="recall-button rcl-upload-button">
                        <span>'.__('Add','wp-recall').'</span>
                        <input id="'.$this->id_upload.'-'.$formData->post_type.'" name="uploadfile[]" type="file" accept="'.$accept.'" multiple>
                </div>
                <small class="notice">'.__('Allowed extensions','wp-recall').': '.$accept.'</small>
            </div>
        </div>';
        
        return $content;
    }

    function get_gallery_list($temp_gal){
        $attachlist = '';
        foreach((array)$temp_gal as $attach){
            $mime_type = get_post_mime_type( $attach['ID'] );
            $attachlist .= rcl_get_html_attachment($attach['ID'],$mime_type);
        }
        return $attachlist;
    }

}