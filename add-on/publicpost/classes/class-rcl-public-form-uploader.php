<?php

class Rcl_Public_Form_Uploader{

    public $post_id = 0;
    public $post_type;
    public $thumbnail_id = 0;
    public $ext_types;
    public $view_gallery = false;

    function __construct($args = false) {
        
        $this->init_properties($args);
        
        if($this->post_id){
            $this->thumbnail_id = get_post_meta($this->post_id, '_thumbnail_id',1);
            $this->view_gallery = get_post_meta($this->post_id, 'recall_slider', 1);
        }
        
        if(!$this->ext_types){
            $this->ext_types = 'jpg,png,jpeg';
        }

    }
    
    function init_properties($args){
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
    }
    
    function get_attachments(){

        $attachs = array();
        
        if($this->post_id){
            
            $args = array(
                'post_parent' => $this->post_id,
                'post_type'   => 'attachment',
                'numberposts' => -1,
                'post_status' => 'any'
            );
            
            $attachments = get_children( $args );
            
            if($attachments){ 
                
                foreach($attachments as $attachment){
                    $attachs[]['ID'] = $attachment->ID;
                    
                } 
                
            }

        }else{
            
            global $user_ID;
            
            $userId = ($user_ID)? $user_ID: $_COOKIE['PHPSESSID'];
            
            $temps = get_option('rcl_tempgallery'); 
            
            if($temps && isset($temps[$userId])){
                $attachs = $temps[$userId];
            }
            
        }
        
        return $attachs;
        
    } 

    function get_uploader(){

        $thumbList = $this->get_thumbs_list();

        $content = '<small class="notice-upload">'.__('Click on the image to add it to the publication','wp-recall').'</small>';

        if($thumbList){
            
            $content .= $thumbList;
            
        }else{
            
            $content .= '<ul id="temp-files-'.$this->post_type.'" class="attachments-post"></ul>';
            
        }

        $content .= '<div class="rcl-form-field">'
                        . '<span class="rcl-field-input type-checkbox-input">'
                            . '<span class="rcl-checkbox-box">'
                                . '<input id="rcl-gallery" type="checkbox" '.checked($this->view_gallery,1,false).' name="add-gallery-rcl" value="1">'
                                . '<label for="rcl-gallery" class="block-label"> - '.__('Display all attached images in the gallery.','wp-recall').'</label>'
                            . '</span>'
                        . '</span>'
                    . '</div>';	

	
        $content .= $this->get_dropzone();
        
        return $content;
    }
    
    function get_dropzone(){

        $content = '<div>
            <div id="rcl-public-dropzone-'.$this->post_type.'" class="rcl-dropzone mass-upload-box">
                <div class="mass-upload-area">
                    '.__('Add files to the download queue','wp-recall').'
                </div>
                <hr>
                '.$this->get_upload_button().'
                <small class="notice">'.__('Allowed extensions','wp-recall').': '.$this->ext_types.'</small>
            </div>
        </div>';
        
        return $content;
        
    }
    
    function get_upload_button($args = false){
        
        $defaults = array(
            'title' => __('Add','wp-recall'),
            'ext_types' => $this->ext_types,
            'id' => 'upload-public-form-'.$this->post_type,
            'name' => 'uploadfile[]',
            'multiple' => true,
            'onclick' => false
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        if($args['ext_types']){
            $mTypes = rcl_get_mime_types(array_map('trim',explode(',',$args['ext_types'])));
        }else{
            $mTypes = array('image/*');
        }
        
        $content = '<div class="recall-button rcl-upload-button">
                        <span>'.$args['title'].'</span>
                        <input id="'.$args['id'].'" name="'.$args['name'].'" type="file" '.($args['onclick']? 'onclick="'.$args['onclick'].'"': '').' accept="'.implode(',',$mTypes).'" '.($args['multiple']? 'multiple': '').'>
                    </div>';
        
        return $content;
    }

    function get_thumbs_list(){
        
        $attachs = $this->get_attachments();
        
        if(!$attachs) return false;
        
        $content = '<ul id="temp-files-'.$this->post_type.'" class="attachments-post">';
        
        foreach($attachs as $attach){
            
            $content .= rcl_get_html_attachment($attach['ID'],get_post_mime_type( $attach['ID'] ));
            
        }
        
        $content .= '</ul>';
        
        return $content;
    }

}
