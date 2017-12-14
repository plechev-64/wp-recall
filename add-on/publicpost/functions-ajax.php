<?php

//удаление фото приложенных к публикации через загрузчик плагина
rcl_ajax_action('rcl_ajax_delete_post', true);
function rcl_ajax_delete_post(){
    global $user_ID;

    rcl_verify_ajax_nonce();

    $user_id = ($user_ID)? $user_ID: $_COOKIE['PHPSESSID'];
        
    $temps = get_option('rcl_tempgallery');            
    $temp_gal = $temps[$user_id];

    if($temp_gal){

        foreach((array)$temp_gal as $key=>$gal){ if($gal['ID']==$_POST['post_id']) unset($temp_gal[$key]); }
        foreach((array)$temp_gal as $t){ $new_temp[] = $t; }

        if($new_temp) $temps[$user_id] = $new_temp;
        else unset($temps[$user_id]);
    }

    update_option('rcl_tempgallery',$temps);
    
    $post = get_post(intval($_POST['post_id']));
    
    if(!$post){
        $log['success']=__('Material successfully removed!','wp-recall');
        $log['post_type']='attachment';
    }else{
    
        $res = wp_delete_post( $post->ID );

        if($res){
            $log['success']=__('Material successfully removed!','wp-recall');
            $log['post_type']=$post->post_type;
        }else {
            $log['error']=__('Deletion failed!','wp-recall');
        }
    
    }

    wp_send_json($log);

}

//вызов быстрой формы редактирования публикации
rcl_ajax_action('rcl_get_edit_postdata', false);
function rcl_get_edit_postdata(){
    global $user_ID;

    rcl_verify_ajax_nonce();

    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);

    if($user_ID){
        $log['result']=100;
        $log['content']= "
        <form id='rcl-edit-form' method='post'>
                <label>".__("Name",'wp-recall').":</label>
                 <input type='text' name='post_title' value='$post->post_title'>
                 <label>".__("Description",'wp-recall').":</label>
                 <textarea name='post_content' rows='10'>$post->post_content</textarea>
                 <input type='hidden' name='post_id' value='$post_id'>
        </form>";
    }else{
        $log['error']=__('Failed to get the data','wp-recall');
    }
    
    wp_send_json($log);

}

//сохранение изменений в быстрой форме редактирования
rcl_ajax_action('rcl_edit_postdata', false);
function rcl_edit_postdata(){
    global $wpdb;

    rcl_verify_ajax_nonce();

    $post_array = array();
    $post_array['post_title'] = sanitize_text_field($_POST['post_title']);
    $post_array['post_content'] = esc_textarea($_POST['post_content']);

    $post_array = apply_filters('rcl_pre_edit_post',$post_array);

    $result = $wpdb->update(
        $wpdb->posts,
        $post_array,
        array('ID'=>intval($_POST['post_id']))
    );
    
    if(!$result){
        wp_send_json(array('error'=>__('Changes to be saved not found','wp-recall')));
    }

    wp_send_json(array(
        'success' => __('Publication updated','wp-recall'),
        'dialog' => array('close')
    ));

}

function rcl_edit_post(){
    $edit = new Rcl_EditPost();

}

//выборка меток по введенным значениям
rcl_ajax_action('rcl_get_like_tags', true);
function rcl_get_like_tags(){
    global $wpdb;

    rcl_verify_ajax_nonce();

    if(!$_POST['query']){
        wp_send_json(array(array('id'=>'')));
    };

    $query = $_POST['query'];
    $taxonomy = $_POST['taxonomy'];

    $terms = get_terms( $taxonomy, array('hide_empty'=>false,'name__like'=>$query) );

    $tags = array();
    foreach($terms as $key=>$term){
        $tags[$key]['id'] = $term->name;
        $tags[$key]['name'] = $term->name;
    }

    wp_send_json($tags);

}

rcl_ajax_action('rcl_preview_post', true);
function rcl_preview_post(){
    global $user_ID;

    rcl_verify_ajax_nonce();

    $log = array();
    $postdata = $_POST;

    if(!rcl_get_option('user_public_access_recall') && !$user_ID){

        $email_new_user = sanitize_email($postdata['email-user']);
        $name_new_user = $postdata['name-user'];

        if(!$email_new_user){
            $log['error'] = __('Enter your e-mail!','wp-recall');
        }
        if(!$name_new_user){
            $log['error'] = __('Enter your name!','wp-recall');
        }

        $res_email = email_exists( $email_new_user );
        $res_login = username_exists($email_new_user);
        $correctemail = is_email($email_new_user);
        $valid = validate_username($email_new_user);

        if($res_login||$res_email||!$correctemail||!$valid){

            if(!$valid||!$correctemail){
                $log['error'] .= __('You have entered an invalid email!','wp-recall');
            }
            if($res_login||$res_email){
                $log['error'] .= __('This email is already used!','wp-recall').'<br>'
                              .__('If this is your email, then log in and publish your post','wp-recall');
            }
        }
        
        if($log['error']){
            wp_send_json($log);
        }
    }
    
    $formFields = new Rcl_Public_Form_Fields(array(
        'post_type' => $postdata['post_type'],
        'form_id' => isset($postdata['form_id'])? $postdata['form_id']: 1
    ));
    
    foreach($formFields->fields as $field){
        
        if(in_array($field['type'],array('runner'))){
            
            $value = isset($postdata[$field['slug']])? $postdata[$field['slug']]: 0;
            $min = isset($field['value_min'])? $field['value_min']: 0;
            $max = isset($field['value_max'])? $field['value_max']: 100;
            
            if($value < $min || $value > $max){
                wp_send_json(array('error' => __('Некорректные значения некоторых полей, выставите корректные значения!','wp-recall')));
            }
            
        }
        
    }
    
    if($formFields->exist_active_field('post_thumbnail')){
        
        $thumbnail_id = (isset($postdata['post-thumbnail']))? $postdata['post-thumbnail']: 0;
        
        $field = $formFields->get_field('post_thumbnail');
        
        if($field['required'] && !$thumbnail_id){
            wp_send_json(array('error' => __('Upload or specify an image as a thumbnail','wp-recall')));
        }
    }

    $post_content = '';
    
    if($formFields->exist_active_field('post_content')){
        
        $postContent = $postdata['post_content'];

        $field = $formFields->get_field('post_content');
        
        if($field['required'] && !$postContent){
            wp_send_json(array('error' => __('Add contents of the publication!','wp-recall')));
        }
        
        $post_content = stripslashes_deep($postContent);
        
        $post_content = rcl_get_editor_content($post_content,'preview');
        
    }
    
    do_action('rcl_preview_post', $postdata);

    $preview = '<h2>'.$postdata['post_title'].'</h2>';
	
    $preview .= $post_content;

    $preview .= '<div class="rcl-notice-preview">
                    <p>'.__('If everything is correct – publish it! If not, you can go back to editing.','wp-recall').'</p>
            </div>';

    $log['content'] = $preview;
    
    wp_send_json($log);

}

rcl_ajax_action('rcl_get_post_thumbnail_html', true);
function rcl_get_post_thumbnail_html(){

    $thumbnail_id = intval($_POST['thumbnail_id']);
    
    $result = array(
        'thumbnail_image' => wp_get_attachment_image( $thumbnail_id, 'thumbnail')
    );
    
    wp_send_json($result); 
    
}