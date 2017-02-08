<?php

class Rcl_EditPost {

    public $post_id; //идентификатор поста
    public $post_type; //тип записи
    public $update; //действие

    function __construct(){
        global $user_ID,$rcl_options;
        
        $user_can = $rcl_options['user_public_access_recall'];

        if($user_can&&!$user_ID) return false;

        if(isset($_FILES)){
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        }
        
        if(isset($_POST['post-rcl'])&&$_POST['post-rcl']){

            $post_id = intval($_POST['post-rcl']);
            $this->post_id = $post_id;
            $post = get_post($this->post_id);
            $this->post_type = $post->post_type;

            if($this->post_type=='post-group'){
                
                if(!rcl_can_user_edit_post_group($post_id)) 
                    $this->error(__('Error publishing!','wp-recall').' Error 102');
                
            }else{

                if(!current_user_can('edit_post', $post_id)) 
                        $this->error(__('Error publishing!','wp-recall').' Error 103');

                $user_info = get_userdata($user_ID);

                if($post->post_author!=$user_ID){
                    $author_info = get_userdata($post->post_author);

                    if($user_info->user_level < $author_info->user_level) 
                        $this->error(__('Error publishing!','wp-recall').' Error 104');

                }

                if($user_info->user_level<10&&rcl_is_limit_editing($post->post_date)) 
                    $this->error(__('Error publishing!','wp-recall').' Error 105');
            }
            $this->update = true;
        }

        if($_POST['posttype']){

            $post_type = sanitize_text_field(base64_decode($_POST['posttype']));

            if(!get_post_types(array('name'=>$post_type))) $this->error(__('Error publishing!','wp-recall').' Error 100');

            $this->post_type = $post_type;
            $this->update = false;
        }

        do_action('init_update_post_rcl',$this);

        add_filter('pre_update_postdata_rcl',array(&$this,'add_data_post'),5,2);

        $this->update_post();
    }
    
    function error($error){
        if(defined( 'DOING_AJAX' ) && DOING_AJAX){
            echo json_encode(array('error'=>$error));
            exit;
        }else{
            wp_die($error);
        }
        
    }

    function update_thumbnail($postdata){
        global $rcl_options;

        $thumb = (isset($_POST['thumb']))? $_POST['thumb']: false;
        if(isset($rcl_options['media_downloader_recall'])&&$rcl_options['media_downloader_recall']==1){
            if($thumb) update_post_meta($this->post_id, '_thumbnail_id', $thumb);
            else delete_post_meta($this->post_id, '_thumbnail_id');
        }else{
            if(!$this->update) return $this->rcl_add_attachments_in_temps($postdata);
            if($thumb){
                foreach($thumb as $key=>$gal){
                        update_post_meta($this->post_id, '_thumbnail_id', $key);
                }
            }else{
                $args = array(
                'post_parent' => $this->post_id,
                'post_type'   => 'attachment',
                'numberposts' => 1,
                'post_status' => 'any',
                'post_mime_type'=> 'image'
                );

                $child = get_children($args);

                if($child){
                    foreach($child as $ch){
                        update_post_meta($this->post_id, '_thumbnail_id',$ch->ID);
                    }
                }
            }
        }
    }

    function rcl_add_attachments_in_temps($postdata){

        $user_id = $postdata['post_author'];
        $temps = get_option('rcl_tempgallery');            
        $temp_gal = (isset($temps[$user_id]))? $temps[$user_id]: 0;
        
        if($temp_gal){
            $thumb = $_POST['thumb'];
            foreach($temp_gal as $key=>$gal){
                
                if($thumb[$gal['ID']]==1) 
                    add_post_meta($this->post_id, '_thumbnail_id', $gal['ID']);

                $post_upd = array(
                    'ID'=>$gal['ID'],
                    'post_parent'=>$this->post_id,
                    'post_author'=>$user_id
                );

                wp_update_post( $post_upd );
            }
            if($_POST['add-gallery-rcl']==1) add_post_meta($this->post_id, 'recall_slider', 1);
            
            unset($temps[$user_id]);
            update_option('rcl_tempgallery',$temps);

            if(!$thumb){
                $args = array(
                'post_parent' => $this->post_id,
                'post_type'   => 'attachment',
                'numberposts' => 1,
                'post_status' => 'any',
                'post_mime_type'=> 'image'
                );
                $child = get_children($args);
                if($child){ foreach($child as $ch){add_post_meta($this->post_id, '_thumbnail_id',$ch->ID);} }
            }
        }
        return $temp_gal;
    }

    function get_status_post($moderation){
        global $user_ID,$rcl_options;
        
        if(isset($_POST['save-as-draft']))
            return 'draft';
        
        if(rcl_is_user_role($user_ID,array('administrator','editor')))
            return 'publish';
        
        $post_status = ($moderation==1)? 'pending': 'publish';

        if($rcl_options['rating_no_moderation']){
            $all_r = rcl_get_user_rating($user_ID);
            if($all_r >= $rcl_options['rating_no_moderation']) $post_status = 'publish';
        }

        return $post_status;
    }

    function add_data_post($postdata,$data){
        global $rcl_options;

        $postdata['post_status'] = $this->get_status_post($rcl_options['moderation_public_post']);

        return $postdata;

    }

    function update_post(){
        global $rcl_options,$user_ID;

        $post_content = '';
        $post_excerpt = (isset($_POST['post_excerpt']))? $_POST['post_excerpt']: '';

        if(!is_array($_POST['post_content'])) $post_content = $_POST['post_content'];

        $postdata = array(
            'post_type'=>$this->post_type,
            'post_title'=>sanitize_text_field($_POST['post_title']),
            'post_excerpt'=> $post_excerpt,
            'post_content'=> $post_content
        );

        $id_form = intval(base64_decode($_POST['id_form']));

        if($this->post_id) $postdata['ID'] = $this->post_id;
        else $postdata['post_author'] = $user_ID;

        $postdata = apply_filters('pre_update_postdata_rcl',$postdata,$this);
        
        if(!$postdata) return false;

        do_action('pre_update_post_rcl',$postdata);

        if(!$this->post_id){
            $this->post_id = wp_insert_post( $postdata );

            if(!$this->post_id) 
                $this->error(__('Error publishing!','wp-recall').' Error 101');
            
            if($id_form>1) 
                add_post_meta($this->post_id, 'publicform-id', $id_form);
            
        }else{
            wp_update_post( $postdata );
        }
        
        $this->update_thumbnail($postdata);

        if(isset($_POST['add-gallery-rcl'])&&$_POST['add-gallery-rcl']==1) 
            update_post_meta($this->post_id, 'recall_slider', 1);
        else 
            delete_post_meta($this->post_id, 'recall_slider');

        rcl_update_post_custom_fields($this->post_id,$id_form);

        do_action('update_post_rcl',$this->post_id,$postdata,$this->update);

        if($postdata['post_status'] == 'pending'){
            if($user_ID) $redirect_url = get_bloginfo('wpurl').'/?p='.$this->post_id.'&preview=true';
            else $redirect_url = get_permalink($rcl_options['guest_post_redirect']);
        }else{
            $redirect_url = get_permalink($this->post_id);
        }

        if(defined( 'DOING_AJAX' ) && DOING_AJAX){
            echo json_encode(array('redirect'=>$redirect_url));
            exit;
        }

        wp_redirect($redirect_url);  exit;

    }
}

add_filter('pre_update_postdata_rcl','rcl_add_taxonomy_in_postdata',50,2);
function rcl_add_taxonomy_in_postdata($postdata,$data){

    if(!isset($_POST['cats'])||!$_POST['cats']) return $postdata;

    $post_type = get_post_types( array('name' => $data->post_type), 'objects' );
    
    if($data->post_type=='post'){
        
        $post_type['post']->taxonomies = array('category'); 
        
        if(isset($_POST['tags'])&&$_POST['tags']){
            $postdata['tags_input'] = $_POST['tags']['post_tag'];
        }
        
    }

    foreach($post_type[$data->post_type]->taxonomies as $taxonomy){
        if(!isset($_POST['cats'][$taxonomy])) continue;

        $cats = get_terms($taxonomy);

        $term_l = new Rcl_Edit_Terms_List();
        $new_cat = $term_l->get_terms_list($cats,$_POST['cats'][$taxonomy]);

        $postdata['tax_input'][$taxonomy] = $new_cat;
    }

    return $postdata;

}

add_action('update_post_rcl','rcl_update_postdata_product_tags',10,2);
function rcl_update_postdata_product_tags($post_id,$postdata){

    if(!isset($_POST['tags'])||$postdata['post_type']=='post') return false;
    
    foreach($_POST['tags'] as $taxonomy=>$terms){
        wp_set_object_terms( $post_id, $terms, $taxonomy );
    }
}

add_action('update_post_rcl','rcl_unset_postdata_tags',20,2);
function rcl_unset_postdata_tags($post_id,$postdata){

    if(!isset($_POST['tags'])){
        
        $obj = get_post_type_object( $postdata['post_type'] );
        
        if($obj->taxonomies){

            foreach($obj->taxonomies as $taxonomy_name){
                
                $taxonomy = get_taxonomy( $taxonomy_name );
                
                if($taxonomy->hierarchical) continue;
                
                wp_set_object_terms( $post_id, NULL, $taxonomy_name );
            } 
            
        }
        
    }

}

add_action('update_post_rcl','rcl_set_object_terms_post',10,3);
function rcl_set_object_terms_post($post_id,$postdata,$update){
    
    if($update||!isset($postdata['tax_input'])||!$postdata['tax_input']) return false;
    
    foreach($postdata['tax_input'] as $taxonomy_name=>$terms){
        wp_set_object_terms( $post_id, array_map('intval', $terms), $taxonomy_name );
    }
}

add_filter('pre_update_postdata_rcl','rcl_register_author_post',10);
function rcl_register_author_post($postdata){
    global $user_ID,$rcl_options,$wpdb;
    $user_can = $rcl_options['user_public_access_recall'];
    if($user_can||$user_ID) return $postdata;

    if(!$postdata['post_author']){

        $email_new_user = sanitize_email($_POST['email-user']);

        if($email_new_user){

            $user_id = false;

            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );

            $userdata = array(
                'user_pass'=>$random_password,
                'user_login'=>$email_new_user,
                'user_email'=>$email_new_user,
                'display_name'=>$_POST['name-user']
            );

            $user_id = rcl_insert_user($userdata);

            if($user_id){
                
                //переназначаем временный массив изображений от гостя юзеру
                $temp_id = $_COOKIE['PHPSESSID'];
                $temps = get_option('rcl_tempgallery'); 
                if(isset($temps[$temp_id])){
                    $temp_gal = $temps[$temp_id];
                    unset($temps[$temp_id]);
                    $temps[$user_id] = $temp_gal;
                    update_option('rcl_tempgallery',$temps);
                }

                //Сразу авторизуем пользователя
                if(!$rcl_options['confirm_register_recall']){
                    $creds = array();
                    $creds['user_login'] = $email_new_user;
                    $creds['user_password'] = $random_password;
                    $creds['remember'] = true;
                    $user = wp_signon( $creds, false );
                    $user_ID = $user_id;
                }

                $postdata['post_author'] = $user_id;
                $postdata['post_status'] = 'pending';

            }
        }
    }

    return $postdata;
}

//Сохранение данных публикации в редакторе wp-recall
add_action('update_post_rcl','rcl_add_box_content',10,3);
function rcl_add_box_content($post_id,$postdata,$update){

	if(!isset($_POST['post_content'])||!is_array($_POST['post_content'])) return false;

	$post_content = '';
	$thumbnail = false;

        $POST = add_magic_quotes($_POST['post_content']);

	foreach($POST as $k=>$contents){
            foreach($contents as $type=>$content){
                if($type=='text') $content = strip_tags($content);
                if($type=='header') $content = sanitize_text_field($content);
                if($type=='html') $content = str_replace('\'','"',$content);

                if($type=='image'){
                    $path_media = rcl_path_by_url($content);
                    $filename = basename($content);

                    $dir_path = RCL_UPLOAD_PATH.'post-media/';
                    $dir_url = RCL_UPLOAD_URL.'post-media/';
                    if(!is_dir($dir_path)){
                            mkdir($dir_path);
                            chmod($dir_path, 0755);
                    }

                    $dir_path = RCL_UPLOAD_PATH.'post-media/'.$post_id.'/';
                    $dir_url = RCL_UPLOAD_URL.'post-media/'.$post_id.'/';
                    if(!is_dir($dir_path)){
                            mkdir($dir_path);
                            chmod($dir_path, 0755);
                    }

                    if(copy($path_media, $dir_path.$filename)){
                            unlink($path_media);
                    }

                    if(!$thumbnail) $thumbnail = $dir_path.$filename;

                    $content = $dir_url.$filename;
                }

                $post_content .= "[rcl-box type='$type' content='$content']";
            }
	}

	if($thumbnail) rcl_add_thumbnail_post($post_id,$thumbnail);

	wp_update_post( array('ID'=> $post_id,'post_content'=> $post_content));

}
