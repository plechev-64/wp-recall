<?php

class Rcl_EditPost {

    public $post_id; //идентификатор поста
    public $post_type; //тип записи
    public $update = false; //действие

    function __construct(){
        global $user_ID,$rcl_options;
        
        $user_can = $rcl_options['user_public_access_recall'];

        if($user_can&&!$user_ID) return false;

        if(isset($_FILES)){
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        }

        if(isset($_POST['post_type']) && $_POST['post_type']){
            $this->post_type = sanitize_text_field($_POST['post_type']);
        }
        
        if(isset($_POST['post_id'])&&$_POST['post_id']){

            $this->post_id = intval($_POST['post_id']);
            $this->post_type = get_post_type($this->post_id);

            if($this->post_type == 'post-group'){
                
                if(!rcl_can_user_edit_post_group($this->post_id)) 
                    $this->error(__('Error publishing!','wp-recall').' Error 102');
                
            }else{

                if(!current_user_can('edit_post', $this->post_id)) 
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

        $thumbnail_id = (isset($_POST['post-thumbnail']))? $_POST['post-thumbnail']: 0;
            
        if(!$this->update) 

            return $this->rcl_add_attachments_in_temps($postdata);
        
        $currentThID = get_post_meta($this->post_id, '_thumbnail_id');

        if($thumbnail_id){
            
            if($currentThID == $thumbnail_id) return false;

            update_post_meta($this->post_id, '_thumbnail_id', $thumbnail_id);

        }else{
            
            if($currentThID)
                delete_post_meta($this->post_id, '_thumbnail_id');
            
        }
            
    }

    function rcl_add_attachments_in_temps($postdata){

        $user_id = $postdata['post_author'];
        $temps = get_option('rcl_tempgallery');            
        $temp_gal = (isset($temps[$user_id]))? $temps[$user_id]: 0;
        
        if($temp_gal){
            
            $thumbnail_id = (isset($_POST['post-thumbnail']))? $_POST['post-thumbnail']: 0;
            
            foreach($temp_gal as $key=>$gal){
                
                if($thumbnail_id && $thumbnail_id == $gal['ID']) 
                    add_post_meta($this->post_id, '_thumbnail_id', $gal['ID']);

                $post_upd = array(
                    'ID'=>$gal['ID'],
                    'post_parent'=>$this->post_id,
                    'post_author'=>$user_id
                );

                wp_update_post( $post_upd );
            }
            
            if($_POST['add-gallery-rcl']==1) 
                add_post_meta($this->post_id, 'recall_slider', 1);
            
            unset($temps[$user_id]);
            
            update_option('rcl_tempgallery',$temps);

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

        $postdata = array(
            'post_type' => $this->post_type,
            'post_title' => sanitize_text_field($_POST['post_title']),
            'post_excerpt' => (isset($_POST['post_excerpt']))? $_POST['post_excerpt']: '',
            'post_content' => (isset($_POST['post_content']))? $_POST['post_content']: ''
        );

        if($this->post_id) 
            $postdata['ID'] = $this->post_id;
        else 
            $postdata['post_author'] = $user_ID;

        $postdata = apply_filters('pre_update_postdata_rcl',$postdata,$this);
        
        if(!$postdata) return false;

        do_action('pre_update_post_rcl',$postdata);

        if(!$this->post_id){
            $this->post_id = wp_insert_post( $postdata );

            if(!$this->post_id) 
                $this->error(__('Error publishing!','wp-recall').' Error 101');
            
            $id_form = intval($_POST['form_id']);
            
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
            if($user_ID) 
                $redirect_url = get_bloginfo('wpurl').'/?p='.$this->post_id.'&preview=true';
            else 
                $redirect_url = get_permalink($rcl_options['guest_post_redirect']);
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
