<?php

class Rcl_EditPost {

    public $post_id; //идентификатор поста
    public $post_type; //тип записи
    public $update; //действие

    function __construct(){
        global $user_ID;

        if(!$user_ID) return false;

        if(isset($_FILES)){
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        }

        if($_POST['post-rcl']){

            $post_id = intval($_POST['post-rcl']);
            $this->post_id = $post_id;
            $pst = get_post($this->post_id);
            $this->post_type = $pst->post_type;

            if($this->post_type=='post-group'){

                if(!rcl_can_user_edit_post_group($post_id)) return false;

            }else if(!current_user_can('edit_post', $post_id)) return false;

            $this->update = true;
        }else{
            if (!session_id()) { session_start(); }
            unset($_SESSION['new-'.$this->post_type]);
            //session_destroy();
        }

        if($_POST['posttype']){

            $post_type = sanitize_text_field(base64_decode($_POST['posttype']));

            if(!get_post_types(array('name'=>$post_type))) wp_die(__('Error publishing!','rcl'));

            $this->post_type = $post_type;
            $this->update = false;
        }

        do_action('init_update_post_rcl',$this);

        add_filter('pre_update_postdata_rcl',array(&$this,'add_data_post'),10,2);
        add_action('update_post_rcl',array(&$this,'update_product_meta'),10,2);

        $this->update_post();
    }

    function update_thumbnail(){
        global $rcl_options;

        $thumb = $_POST['thumb'];
        if($rcl_options['media_downloader_recall']==1){
            if(isset($thumb)) update_post_meta($this->post_id, '_thumbnail_id', $thumb);
            else delete_post_meta($this->post_id, '_thumbnail_id');
        }else{
            if(!$this->update) return $this->rcl_add_attachments_in_temps();
            if($thumb){
                foreach((array)$thumb as $key=>$gal){
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

    function rcl_add_attachments_in_temps(){
        global $user_ID;

        $temp_gal = unserialize(get_the_author_meta('tempgallery',$user_ID));
        if($temp_gal){
            $thumb = $_POST['thumb'];
            foreach((array)$temp_gal as $key=>$gal){
                    if($thumb[$gal['ID']]==1) add_post_meta($this->post_id, '_thumbnail_id', $gal['ID']);
                    wp_update_post( array('ID'=>$gal['ID'],'post_parent'=>$this->post_id) );
            }
            if($_POST['add-gallery-rcl']==1) add_post_meta($this->post_id, 'recall_slider', 1);
            delete_usermeta($user_ID,'tempgallery');

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

    function upload_file(){
        global $user_ID,$sale_file;

        if($sale_file['file']){

            $attachment = array(
                    'post_mime_type' => $sale_file['type'],
                    'post_title' => 'salefile',
                    'post_content' => intval($_POST['sale_price']).'/3/86400' ,
                    'guid' => $sale_file['url'],
                    'post_parent' => $this->post_id,
                    'post_author' => $user_ID,
                    'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment( $attachment, $sale_file['file'], $this->post_id );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $sale_file['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );
        }
    }

    function update_product_meta($post_id,$postdata){
        if($postdata['post_type']!='products') return false;
        if(!$this->update) $this->upload_file();
    }

    function get_status_post($moderation){
        global $user_ID,$rcl_options;
        if($moderation==1) $post_status = 'pending';
        else $post_status = 'publish';

        if($rcl_options['nomoder_rayt']){
                $all_r = rcl_get_all_rating_user(0,$user_ID);
                if($all_r >= $rcl_options['nomoder_rayt']) $post_status = 'publish';
        }
        return $post_status;
    }

    function add_data_post($postdata,$data){
        global $rcl_options;

        if(!$_POST['cats']||$data->post_type!='post') return $postdata;

        $catargs = array(
                'orderby'                  => 'name'
                ,'order'                   => 'ASC'
                ,'hide_empty'              => 0
                ,'hierarchical'=>true
        );
        $cats = get_categories( $catargs );

        $term_l = new Rcl_Edit_Terms_List();
        $new_cat = $term_l->get_terms_list($cats,$_POST['cats']);

        $postdata['post_status'] = $this->get_status_post($rcl_options['moderation_public_post']);
        $postdata['post_category'] = $new_cat;

        return $postdata;

    }

    function update_post(){
	global $rcl_options,$user_ID;

        $postdata = array(
            'post_type'=>$this->post_type,
            'post_title'=>sanitize_text_field($_POST['post_title']),
            'post_content'=> $_POST['post_content'],
            'tags_input'=>sanitize_text_field($_POST['post_tags'])
        );

        $id_form = intval(base64_decode($_POST['id_form']));

        if($this->post_id) $postdata['ID'] = $this->post_id;
        else $postdata['post_author'] = $user_ID;

        $postdata = apply_filters('pre_update_postdata_rcl',$postdata,$this);

        if(!$postdata['post_status']) $postdata['post_status'] = 'publish';

        do_action('pre_update_post_rcl',$postdata);

        if(!$this->post_id){
            $this->post_id = wp_insert_post( $postdata );
            if($id_form>1) add_post_meta($this->post_id, 'publicform-id', $id_form);
        }else{
            wp_update_post( $postdata );
        }

        $this->update_thumbnail();

        if($_POST['add-gallery-rcl']==1) update_post_meta($this->post_id, 'recall_slider', 1);
	else delete_post_meta($this->post_id, 'recall_slider');

        rcl_update_post_custom_fields($this->post_id,$id_form);

        do_action('update_post_rcl',$this->post_id,$postdata,$this->update);

        if($postdata['post_status'] == 'pending'){
            wp_redirect(get_bloginfo('wpurl').'/?p='.$this->post_id.'&preview=true');  exit;
        }else{
            wp_redirect( get_permalink($this->post_id) );  exit;
        }
    }
}
