<?php

function rcl_get_custom_post_meta($post_id){

    $get_fields = rcl_get_custom_fields($post_id);

    if($get_fields){
        $show_custom_field = '';
        $cf = new Rcl_Custom_Fields();
        foreach($get_fields as $custom_field){
            $custom_field = apply_filters('rcl_custom_post_meta',$custom_field);
            if(!$custom_field||!isset($custom_field['slug'])||!$custom_field['slug']) continue;
            $p_meta = get_post_meta($post_id,$custom_field['slug'],true);
            $show_custom_field .= $cf->get_field_value($custom_field,$p_meta);
        }

        return $show_custom_field;
    }
}

function rcl_get_postslist($post_type,$type_name){
    global $user_LK;
    
    if (!class_exists('Rcl_Postlist')) 
        include_once RCL_PATH .'add-on/publicpost/rcl_postlist.php';
    
    $list = new Rcl_Postlist($user_LK,$post_type,$type_name);
    
    return $list->get_postlist_block();
    
}

function rcl_tab_postform($master_id){    
    return do_shortcode('[public-form form_id="'.rcl_get_option('form-lk',1).'"]');
}

//Прикрепление новой миниатюры к публикации из произвольного места на сервере
function rcl_add_thumbnail_post($post_id,$filepath){

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

    $filename = basename($filepath);
    $file = explode('.',$filename);
    $thumbpath = $filepath;

    //if($file[0]=='image'){
            $data = getimagesize($thumbpath);
            $mime = $data['mime'];
    //}else $mime = mime_content_type($thumbpath);

    $cont = file_get_contents($thumbpath);
    $image = wp_upload_bits( $filename, null, $cont );

    $attachment = array(
            'post_mime_type' => $mime,
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($image['file'])),
            'post_content' => '',
            'guid' => $image['url'],
            'post_parent' => $post_id,
            'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment( $attachment, $image['file'], $post_id );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $image['file'] );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    $oldthumb = get_post_meta($post_id, '_thumbnail_id',1);
    if($oldthumb) wp_delete_attachment($oldthumb);

    update_post_meta($post_id, '_thumbnail_id', $attach_id);
}

function rcl_edit_post_button_html($post_id){
    return '<p class="post-edit-button">'
        . '<a title="'.__('Edit','wp-recall').'" object-id="none" href="'. get_edit_post_link($post_id) .'">'
            . '<i class="fa fa-pencil-square-o"></i>'
        . '</a>'
    . '</p>';
}



function rcl_get_editor_content($post_content){
    global $rcl_box;

    remove_filter('the_content','add_button_bmk_in_content',20);
    remove_filter('the_content','get_notifi_bkms',20);
    remove_filter('the_content','rcl_get_edit_post_button',999);
    
    $content = apply_filters('the_content',$post_content);

    return $content;
    
}

function rcl_is_limit_editing($post_date){
    
    $timelimit = apply_filters('rcl_time_editing',rcl_get_option('time_editing'));

    if($timelimit){
        $hours = (strtotime(current_time('mysql')) - strtotime($post_date))/3600;
        if($hours > $timelimit) return true;
    }

    return false;
}

function rcl_get_custom_fields_edit_box($post_id, $post_type = false, $form_id = 1){

    $post = get_post($post_id);

    $RclForm = new Rcl_Public_Form(array(
        'post_type' => $post->post_type,
        'post_id' => $post_id,
        'form_id' => $form_id
    ));
    
    $fields = $RclForm->get_custom_fields();
    
    if(!$fields) return false;
    
    $CF = new Rcl_Custom_Fields();
    
    $content = '<div class="rcl-custom-fields-box">';

    foreach($fields as $key => $field){
        
        if($key === 'options' || !isset($field['slug'])) continue;

        $star = ($field['required']==1)? '<span class="required">*</span> ': '';
        $postmeta = ($post_id)? get_post_meta($post_id,$field['slug'],1):'';
        
        $content .= '<div class="rcl-custom-field">';

            $content .= '<label>'.$CF->get_title($field).$star.'</label>';
            
            $content .= '<div class="field-value">';
                $content .= $CF->get_input($field,$postmeta);
            $content .= '</div>';
            
        $content .= '</div>';
        
    }

    $content .= '</div>';
    
    return $content;
    
}

function rcl_update_post_custom_fields($post_id,$id_form=false){

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');

    $post = get_post($post_id);

    $formFields = new Rcl_Public_Form_Fields(array(
        'post_type' => $post->post_type,
        'form_id' => $id_form
    ));
    
    $fields = $formFields->get_custom_fields();

    if($fields){

        $POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        foreach($fields as $field){
            
            $slug = $field['slug'];
            $value = isset($POST[$slug])? $POST[$slug]: false;

            if($field['type']=='checkbox'){
                $vals = array();
                
                if(isset($field['field_select']))
                    $field['values'] = rcl_edit_old_option_fields($field['field_select'], $field['type']);

                $count_field = count($field['values']);
                
                if($value && is_array($value)){
                    foreach($value as $val){
                        for($a=0;$a<$count_field;$a++){
                            if($field['values'][$a]==$val){
                                $vals[] = $val;
                            }
                        }
                    }
                }
                
                if($vals){
                    update_post_meta($post_id, $slug, $vals);
                }else{
                    delete_post_meta($post_id, $slug);
                }

            }else if($field['type']=='file'){

                $attach_id = rcl_upload_meta_file($field,$post->post_author,$post_id);
                
                if($attach_id) 
                    update_post_meta($post_id, $slug, $attach_id);

            }else{

                if($value){
                    update_post_meta($post_id, $slug, $value);
                }else{
                    if(get_post_meta($post_id, $slug, 1)) 
                            delete_post_meta($post_id, $slug);
                }

            }
        }
    }
}

rcl_ajax_action('rcl_save_temp_async_uploaded_thumbnail', true);
function rcl_save_temp_async_uploaded_thumbnail(){
    rcl_verify_ajax_nonce();
    
    $attachment_id = intval($_POST['attachment_id']);
    $attachment_url = $_POST['attachment_url'];
    
    if(!$attachment_id || !$attachment_url){
        wp_send_json(array(
            'error' => __('Error','wp-recall')
        ));
    }
    
    rcl_update_tempgallery($attachment_id, $attachment_url);
    
    wp_send_json(array(
        'save' => true
    ));
}

function rcl_update_tempgallery($attach_id,$attach_url){
    global $user_ID;

    $user_id = ($user_ID)? $user_ID: $_COOKIE['PHPSESSID'];

    $temp_gal = get_option('rcl_tempgallery');

    if(!$temp_gal) $temp_gal = array();

    $temp_gal[$user_id][] = array(
        'ID' => $attach_id,
        'url' => $attach_url
    );

    update_option('rcl_tempgallery',$temp_gal);

    return $temp_gal;
}

function rcl_get_attachment_box($attachment_id, $mime = 'image'){
    
    if($mime=='image'){
        
        $small_url = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
        $full_url = wp_get_attachment_image_src( $attachment_id, 'full' );
        
        if($default = rcl_get_option('default_size_thumb')) 
            $sizes = wp_get_attachment_image_src( $attachment_id, $default );
        else 
            $sizes = $small_url;
        
        $act_sizes = wp_constrain_dimensions($full_url[1],$full_url[2],$sizes[1],$sizes[2]);
        
        return '<a onclick="rcl_add_image_in_form(this,\'<a href='.$full_url[0].'><img height='.$act_sizes[1].' width='.$act_sizes[0].' class=aligncenter  src='.$full_url[0].'></a>\');return false;" href="#"><img src="'.$small_url[0].'"></a>';

    }else{
        
        $_post = get_post( $attachment_id );
        
        $url = wp_get_attachment_url( $attachment_id );
        
        return '<a href="#" onclick="rcl_add_image_in_form(this,\'<a href='.$url.'>'.$_post->post_title.'</a>\');return false;">'.wp_get_attachment_image( $attachment_id, array(100,100),true ).'</a>';
    }
}

function rcl_get_html_attachment($attach_id,$mime_type){

    $mime = explode('/',$mime_type);

    $content = "<li id='attachment-$attach_id' class='post-attachment attachment-$mime[0]' data-mime='$mime[0]' data-attachment-id='$attach_id'>";
    $content .= rcl_button_fast_delete_post($attach_id);
    $content .= sprintf("<label>%s</label>",apply_filters('rcl_post_attachment_html',rcl_get_attachment_box($attach_id,$mime[0]),$attach_id,$mime));
    $content .= "</li>";
            
    return $content;
}

function rcl_button_fast_edit_post($post_id){
    return '<a class="rcl-edit-post rcl-service-button" data-post="'.$post_id.'" onclick="rcl_edit_post(this); return false;"><i class="fa fa-pencil-square-o"></i></a>';
}

function rcl_button_fast_delete_post($post_id){
    return '<a class="rcl-delete-post rcl-service-button" data-post="'.$post_id.'" onclick="return confirm(\''.__('Are you sure?','wp-recall').'\')? rcl_delete_post(this): false;"><i class="fa fa-trash"></i></a>';
}

