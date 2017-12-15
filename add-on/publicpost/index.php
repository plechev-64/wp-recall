<?php

require_once 'classes/class-rcl-form-fields.php';
require_once 'classes/class-rcl-edit-terms-list.php';
require_once 'classes/class-rcl-list-terms.php';
require_once 'classes/class-rcl-public-form-uploader.php';
require_once 'classes/class-rcl-public-form-fields.php';
require_once 'classes/class-rcl-public-form.php';
require_once 'classes/class-rcl-post-list.php';
require_once 'classes/class-rcl-edit-post.php';
require_once 'core.php';
require_once 'shortcodes.php';
require_once 'functions-ajax.php';
require_once 'init.php';
require_once 'upload-file.php';

if(is_admin()){
    require_once 'classes/class-rcl-public-form-manager.php';
    require_once 'admin/index.php';
}

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_publics_scripts',10);
endif;

function rcl_publics_scripts(){
    rcl_enqueue_style('rcl-publics',rcl_addon_url('style.css', __FILE__));
    rcl_enqueue_script( 'rcl-publics', rcl_addon_url('js/scripts.js', __FILE__) );
}

function rcl_autocomplete_scripts(){
    rcl_enqueue_style( 'magicsuggest', rcl_addon_url('js/magicsuggest/magicsuggest-min.css', __FILE__) );
    rcl_enqueue_script( 'magicsuggest', rcl_addon_url('js/magicsuggest/magicsuggest-min.js', __FILE__) );
}

add_action('rcl_setup_tabs','rcl_postlist_tab_add_types_data',10);
function rcl_postlist_tab_add_types_data(){
    global $rcl_postlist;
    
    if($rcl_postlist){

        foreach($rcl_postlist as $post_type=>$data){
            
            if($post_type == 'post') continue;
            
            $subtab = array(
                'id' => 'type-'.$post_type,
                'name' => $data['name'],
                'icon' => 'fa-list',
                'callback' => array(
                    'name'=>'rcl_get_postslist',
                    'args'=>array($post_type,$data['name'])
                )
            );

            rcl_add_sub_tab('publics',$subtab);
            
        }

    }

}

//выводим в медиабиблиотеке только медиафайлы текущего автора
add_action('pre_get_posts','rcl_restrict_media_library');
function rcl_restrict_media_library( $wp_query_obj ) {
    global $current_user, $pagenow;
	
    if( !is_a( $current_user, 'WP_User') ) return;

    if( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' ) return;
	
    if(rcl_check_access_console()) return;
	
    if( !current_user_can('manage_media_library') ) $wp_query_obj->set('author', $current_user->ID );
	
    return;
}

add_filter('pre_update_postdata_rcl','rcl_update_postdata_excerpt');
function rcl_update_postdata_excerpt($postdata){
    if(!isset($_POST['post_excerpt'])) return $postdata;
    $postdata['post_excerpt'] = sanitize_text_field($_POST['post_excerpt']);
    return $postdata;
}

//формируем галерею записи
add_filter('the_content','rcl_post_gallery',10);
function rcl_post_gallery($content){
    global $post;
    if(get_post_meta($post->ID, 'recall_slider', 1)!=1||!is_single()||$post->post_type=='products') return $content;
    $gallery = do_shortcode('[gallery-rcl post_id="'.$post->ID.'"]');
    return $gallery.$content;
}

//Выводим инфу об авторе записи в конце поста
add_filter('the_content','rcl_author_info',70);
function rcl_author_info($content){

    if(!rcl_get_option('info_author_recall')) 
        return $content;
    
    if(!is_single()) 
        return $content;
    
    global $post;

    if($post->post_type=='page') 
        return $content;
    
    if(rcl_get_option('post_types_authbox')){
        
        if(!in_array($post->post_type,rcl_get_option('post_types_authbox'))) return $content;
        
    }
    
    $content .= rcl_get_author_block();

    return $content;
}

add_filter('the_content','rcl_concat_post_meta',10);
function rcl_concat_post_meta($content){
    global $post;
    
    $option = rcl_get_option('pm_rcl');
    
    if(!$option)
        return $content;
    
    if($types = rcl_get_option('pm_post_types')){
        if(!in_array($post->post_type, $types)) return $content;
    }
    
    $pm = rcl_get_custom_post_meta($post->ID);
    
    if(rcl_get_option('pm_place') == 1) 
        $content .= $pm;
    else 
        $content = $pm.$content;
    
    return $content;
}

/*14.2.0*/
//очищаем временный массив загруженных изображений к публикациям 
//и удаляем все изображения к неопубликованным записям
add_action('rcl_cron_daily','rcl_clear_temps_gallery',10);
function rcl_clear_temps_gallery(){
    
    $temps = get_option('rcl_tempgallery');
    
    if(!$temps) return false;
    
    foreach($temps as $user_id=>$usertemps){
        foreach($usertemps as $temp){
            $post_id = intval($temp['ID']);
            if($post_id)
                wp_delete_post( $post_id );
        }
    }
    
    $temps = array();
    
    update_option('rcl_tempgallery',$temps);
    
}

function rcl_delete_post(){
    global $user_ID;
    
    $post_id = intval($_POST['post-rcl']);
    
    $post = get_post($post_id);

    if($post->post_type == 'post-group'){

        if(!rcl_can_user_edit_post_group($post_id)) return false;

    }else{

        if(!current_user_can('edit_post', $post_id)) return false;

    }
            
    $post_id = wp_update_post( array(
        'ID' => $post_id,
        'post_status' => 'trash'
    ));
    
    do_action('after_delete_post_rcl',$post_id);
    
    wp_redirect(rcl_format_url(get_author_posts_url($user_ID)).'&public=deleted'); exit;
    
}

add_action('after_delete_post_rcl','rcl_delete_notice_author_post');
function rcl_delete_notice_author_post($post_id){

    if(!$_POST['reason_content']) return false;

    $post = get_post($post_id);

    $subject = 'Ваша публикация удалена.';
    $textmail = '<h3>Публикация "'.$post->post_title.'" была удалена</h3>
    <p>Примечание модератора: '.$_POST['reason_content'].'</p>';
    rcl_mail(get_the_author_meta('user_email',$post->post_author),$subject,$textmail);
    
}

if(!is_admin())
    add_filter('get_edit_post_link','rcl_edit_post_link',100,2);
function rcl_edit_post_link($admin_url, $post_id){
    global $user_ID;

    $frontEdit = rcl_get_option('front_editing',array(0));

    $user_info = get_userdata($user_ID);

    if ( array_search($user_info->user_level, $frontEdit)!==false ||$user_info->user_level < rcl_get_option('consol_access_rcl',7) ){
        $edit_url = rcl_format_url(get_permalink(rcl_get_option('public_form_page_rcl')));
        return $edit_url.'rcl-post-edit='.$post_id;
    }else{
        return $admin_url;
    }
}

add_action('rcl_post_bar_setup','rcl_setup_edit_post_button',10);
function rcl_setup_edit_post_button(){
    global $post,$user_ID,$current_user;
    
    if(!is_user_logged_in() || !$post) return false;
    
    if(is_front_page() || is_tax('groups') || $post->post_type=='page') return false;

    if(!current_user_can('edit_post', $post->ID)) return false;

    $user_info = get_userdata($current_user->ID);

    if($post->post_author!=$user_ID){
        $author_info = get_userdata($post->post_author);
        if($user_info->user_level < $author_info->user_level) return false;
    }

    $frontEdit = rcl_get_option('front_editing',array(0));

    if( false!==array_search($user_info->user_level, $frontEdit) || $user_info->user_level >= rcl_get_option('consol_access_rcl',7) ) {

        if($post->post_type=='task'){
            if(get_post_meta($post->ID,'step_order',1)!=1) return false;
        }

        if($user_info->user_level<10&&rcl_is_limit_editing($post->post_date)) return false;

        rcl_post_bar_add_item('rcl-edit-post',
            array(                
                'url'=>get_edit_post_link($post->ID),
                'icon'=>'fa-pencil-square-o',
                'title'=>__('Edit','wp-recall')
            )
        );
        
        return true;

    }
    
    return false;
}

add_filter('pre_update_postdata_rcl','rcl_add_taxonomy_in_postdata',50,2);
function rcl_add_taxonomy_in_postdata($postdata,$data){

    if(!isset($_POST['cats'])||!$_POST['cats']) return $postdata;

    $post_type = get_post_types( array('name' => $data->post_type), 'objects' );
    
    if(!$post_type) return false;
    
    if($data->post_type == 'post'){
        
        $post_type['post']->taxonomies = array('category'); 
        
        if(isset($_POST['tags'])&&$_POST['tags']){
            $postdata['tags_input'] = $_POST['tags']['post_tag'];
        }
        
    }
    
    $FormFields = new Rcl_Public_Form_Fields(array(
        'post_type' => $data->post_type,
        'form_id' => $_POST['form_id']
    ));

    foreach($post_type[$data->post_type]->taxonomies as $taxonomy){

        if(!isset($_POST['cats'][$taxonomy])) continue;

        $postCats = $_POST['cats'][$taxonomy];

        if(!$FormFields->get_field_option('taxonomy-'.$taxonomy, 'only-child')){

            $allCats = get_terms($taxonomy);
            
            $RclTerms = new Rcl_Edit_Terms_List();
            $postCats = $RclTerms->get_terms_list($allCats, $postCats);

        }

        $postdata['tax_input'][$taxonomy] = $postCats;
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
    global $user_ID;
    
    if(rcl_get_option('user_public_access_recall')||$user_ID) return $postdata;

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
                if(!rcl_get_option('confirm_register_recall')){
                    $creds = array();
                    $creds['user_login'] = $email_new_user;
                    $creds['user_password'] = $random_password;
                    $creds['remember'] = true;
                    $user = wp_signon( $creds );
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

//удаляем папку с изображениями при удалении поста
add_action('delete_post','rcl_delete_tempdir_attachments');
function rcl_delete_tempdir_attachments($postid){
    $dir_path = RCL_UPLOAD_PATH.'post-media/'.$postid;
    rcl_remove_dir($dir_path);
}

/*deprecated*/
function rcl_form_field($args){
    $field = new Rcl_Form_Fields();
    return $field->get_field($args);
}
