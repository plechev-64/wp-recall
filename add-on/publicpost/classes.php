<?php

class Rcl_List_Terms{

    public $a;
    public $ctg;
    public $sel;
    public $cat_list;
    public $allcats;
    public $selected;
    public $taxonomy;
    public $output;

    function __construct($taxonomy=false){
        global $rcl_options;
        $this->taxonomy = $taxonomy;
        $this->output = (isset($rcl_options['output_category_list']))? $rcl_options['output_category_list']: 'select';
    }

    function get_select_list($allcats,$cat_list,$cnt,$ctg,$output=false){                      
        if(!$allcats) return false;
        
        $catlist = '';

        if($output) $this->output = $output;

        if($ctg) $this->ctg = $ctg;
        $this->allcats = $allcats;

        /*if($cat_list&&is_array($cat_list)&&$this->taxonomy){
            $cat_list = get_terms( $this->taxonomy, array('include'=>$cat_list) );
            //print_r($cat_list);
        }*/

        $this->cat_list = $cat_list;

        for($this->sel=0;$this->sel<$cnt;$this->sel++){
            $this->selected = false;
            if($this->output=='select'){
                $catlist .= '<select class="postform" name="cats['.$this->taxonomy.'][]">';
                if($this->sel>0) $catlist .= '<option value="">'.__('Not selected','wp-recall').'</option>';
                $catlist .= $this->get_option_list();
                $catlist .= '</select>';
            }
            if($this->output=='checkbox'){
                $catlist .= '<div class="category-list">';                          
                $catlist .= $this->get_option_list();
                $catlist .= '</div>';
            }
        }
        return $catlist;
    }

    function get_option_list(){
        if($this->ctg){
            $ctg_ar = explode(',',$this->ctg);
            $cnt_c = count($ctg_ar);
        }
        $catlist = '';
        foreach($this->allcats as $cat){

            $this->a = 0;

            if($this->ctg){

                for($z=0;$z<$cnt_c;$z++){
                    if($ctg_ar[$z]==$cat->term_id){
                        $catlist .= $this->get_loop_child($cat);
                    }
                }

            }else{
                if($cat->parent!=0) continue;
                $catlist .= $this->get_loop_child($cat);
            }
        }
        return $catlist;
    }

    function get_loop_child($cat){

        $catlist = false;
        $child = $this->get_child_option($cat->term_id,$this->a);

        if($child){
            if($this->output=='select'){
                $catlist = '<optgroup label="'.$cat->name.'">'.$child.'</optgroup>';
            }else{
                $catlist = '<div class="child-list-category">'
                . '<span class="parent-category">'.$cat->name.'</span>'
                . $child
                .'</div>';
            }

        }else{

            $selected = '';
            if(!$this->selected&&$this->cat_list){
                foreach($this->cat_list as $key=>$sel){
                    if($sel->term_id==$cat->term_id){
                        //echo $sel->term_id.' - '.$cat->term_id.'<br>';
                        if($this->output=='select') $selected = selected($sel->term_id,$cat->term_id,false);
                        if($this->output=='checkbox') $selected = checked($sel->term_id,$cat->term_id,false);
                        $this->selected = true;
                        unset($this->cat_list[$key]);
                        break;
                    }
                }
            }

            if($this->output=='select') $catlist = '<option '.$selected.' value="'.$cat->term_id.'">'.$cat->name.'</option>';
            if($this->output=='checkbox') $catlist = '<label><input type="checkbox" '.$selected.' name="cats['.$this->taxonomy.'][]" value="'.$cat->term_id.'">'.$cat->name.'</label>';
            $this->selected = false;
        }
        return $catlist;
    }

    function get_child_option($term_id,$a){
        $catlist = false;
        foreach($this->allcats as $cat){
            if($cat->parent!==$term_id) continue;
            $child = '';
            $b = '-'.$a;
            $child = $this->get_child_option($cat->term_id,$b);

            if($child){

                if($this->output=='select'){
                    $catlist .= '<optgroup label="&nbsp;&nbsp;&nbsp;'.$cat->name.'">'.$child.'</optgroup>';
                }else{
                    $catlist .= '<div class="child-list-category">'
                    . '<span class="parent-category">'.$cat->name.'</span>'
                    . $child
                    .'</div>';
                }

            }else{

                $selected = '';
                if(!$this->selected&&$this->cat_list){

                    foreach($this->cat_list as $key=>$sel){
                        if($sel->term_id==$cat->term_id){
                            if($this->output=='select') $selected = selected($sel->term_id,$cat->term_id,false);
                            if($this->output=='checkbox') $selected = checked($sel->term_id,$cat->term_id,false);
                            $this->selected = true;
                            unset($this->cat_list[$key]);
                            break;
                        }
                    }
                }

                if($this->output=='select') $catlist .= '<option '.$selected.' value="'.$cat->term_id.'">&nbsp;&nbsp;&nbsp;'.$cat->name.'</option>';
                if($this->output=='checkbox') $catlist .= '<label><input type="checkbox" '.$selected.' name="cats['.$this->taxonomy.'][]" value="'.$cat->term_id.'">'.$cat->name.'</label>';
                $this->selected = false;

                        }
            $this->a = $a;
        }
        return $catlist;
    }

    function get_parent_option($child,$term_id,$a){
        foreach($this->allcats as $cat){
            if($cat->term_id!=$term_id) continue;

            if($this->output=='select'){
                $parent = '<optgroup label="'.$cat->name.'">'.$child.'</optgroup>';
            }else{
                $catlist = '<div class="child-list-category">'
                . '<span class="parent-category">'.$cat->name.'</span>'
                . $child
                .'</div>';
            }
        }
        return $parent;
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

        $content .= '<ul id="temp-files">'.$attachlist.'</ul>';
		
        if($formData){
                $content .= '<p><label><input ';
                //if(!$this->post_id) echo 'checked="checked"';
                $content .= 'type="checkbox" '.checked($gal,1,false).' name="add-gallery-rcl" value="1"> - '.__('Display all attached images in the gallery.','wp-recall').'</label></p>';
        }
	
        $content .= '<div id="status-temp"></div>
        <div>
            <div id="rcl-public-dropzone" class="rcl-dropzone mass-upload-box">
                <div class="mass-upload-area">
                        '.__('To add files to the download queue','wp-recall').'
                </div>
                <hr>
                <div class="recall-button rcl-upload-button">
                        <span>'.__('Add','wp-recall').'</span>
                        <input id="'.$this->id_upload.'" name="uploadfile[]" type="file" accept="'.$accept.'" multiple>
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