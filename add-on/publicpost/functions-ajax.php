<?php

//удаление фото приложенных к публикации через загрузчик плагина
add_action('wp_ajax_rcl_ajax_delete_post', 'rcl_ajax_delete_post');
add_action('wp_ajax_nopriv_rcl_ajax_delete_post', 'rcl_ajax_delete_post');
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

    echo json_encode($log);
    exit;
}

//вызов быстрой формы редактирования публикации
add_action('wp_ajax_rcl_get_edit_postdata', 'rcl_get_edit_postdata');
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
    echo json_encode($log);
    exit;
}

//сохранение изменений в быстрой форме редактирования
add_action('wp_ajax_rcl_edit_postdata', 'rcl_edit_postdata');
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
    if($result){
        $log['result']=100;
        $log['content']=__('Publication updated','wp-recall');;
    }else{
        $log['error']=__('Changes to be saved not found','wp-recall');
    }

    echo json_encode($log);
    exit;
}

function rcl_edit_post(){
    $edit = new Rcl_EditPost();

}

//выборка меток по введенным значениям
add_action('wp_ajax_rcl_get_like_tags','rcl_get_like_tags');
add_action('wp_ajax_nopriv_rcl_get_like_tags','rcl_get_like_tags');
function rcl_get_like_tags(){
    global $wpdb;

    rcl_verify_ajax_nonce();

    if(!$_POST['query']){
        echo json_encode(array(array('id'=>'')));
        exit;
    };

    $query = $_POST['query'];
    $taxonomy = $_POST['taxonomy'];

    $terms = get_terms( $taxonomy, array('hide_empty'=>false,'name__like'=>$query) );

    $tags = array();
    foreach($terms as $key=>$term){
        $tags[$key]['id'] = $term->name;
        $tags[$key]['name'] = $term->name;
    }

    echo json_encode($tags);
    exit;
}


add_action('wp_ajax_rcl_preview_post','rcl_preview_post');
add_action('wp_ajax_nopriv_rcl_preview_post','rcl_preview_post');
function rcl_preview_post(){
    global $user_ID,$rcl_options;

    rcl_verify_ajax_nonce();

    $log = array();

    $user_can = $rcl_options['user_public_access_recall'];

    if(!$user_can&&!$user_ID){

        $email_new_user = sanitize_email($_POST['email-user']);
        $name_new_user = $_POST['name-user'];

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
    }
    
    
    $post_content = '';

    if(isset($_POST['post_content'])){
        
        $postContent = $_POST['post_content'];
        
        if(!$postContent){
            $log['error'] = __('Add contents of the publication!','wp-recall');

            if($log['error']){
                echo json_encode($log);
                exit;
            }
        }

        $post_content = stripslashes_deep($postContent);
        
        $post_content = rcl_get_editor_content($post_content,'preview');
        
    }

    $preview = '<h2>'.$_POST['post_title'].'</h2>';
	
    $preview .= $post_content;

    $preview .= '<div class="rcl-notice-preview">
                    <p>'.__('If everything is correct – publish it! If not, you can go back to editing.','wp-recall').'</p>
            </div>';

    $log['content'] = $preview;
    echo json_encode($log);
    exit;
}

