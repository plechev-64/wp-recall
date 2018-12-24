<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-usp-uploader-avatar
 *
 * @author Андрей
 */
class Rcl_Uploader_Post_Thumbnail extends Rcl_Uploader{

    public $form_id = 1;
    public $post_type;

    function __construct($args){

        $args = wp_parse_args($args, array(
            'auto_upload' => false,
            'manager_balloon' => true,
            'crop' => true,
            'dropzone' => false,
            'multiple' => false
        ));

        parent::__construct('thumbnail', $args);

        add_action('rcl_upload', array($this, 'upload_thumbnail'), 10);

    }

    function get_thumbnail_uploader(){

        $content = $this->get_thumbnail();

        $content .= $this->get_uploader();

        return $content;

    }

    function get_thumbnail(){

        $imagIds = array();

        if($this->post_parent){

            if(has_post_thumbnail($this->post_parent)){
                $imagIds = array(get_post_thumbnail_id($this->post_parent));
            }

        }

        $content = $this->get_gallery($imagIds);
        $content .= '<input type="hidden" id="post-thumbnail" name="post-thumbnail" value="'.$imagIds[0].'">';

        return $content;

    }

    function upload_thumbnail($uploads){

        $thumbnail_id = $uploads[0]['id'];

        if($this->post_parent){

            update_post_meta($this->post_parent, '_thumbnail_id', $thumbnail_id);

            wp_update_post(array(
                'ID' => $thumbnail_id,
                'post_parent' => $this->post_parent
            ));

        }else{

            rcl_add_temp_media(array(
                'media_id' => $thumbnail_id,
                'uploader_id' => $this->uploader_id
            ));

        }

        do_action('rcl_upload_thumbnail', $thumbnail_id, $this);

        wp_send_json(array(
            'uploads' => $uploads
        ));
    }

}