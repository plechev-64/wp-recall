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

        parent::__construct('thumbnail', array(
            'auto_upload' => false,
            //'max_files' => 1,
            //'temp_media' => isset($args['post_parent']) && $args['post_parent']? false: true,
            'crop' => true,
            'dropzone' => false,
            'multiple' => false
        ));

        $this->init_properties($args);

        //wp_enqueue_script( 'avatar-uploader', USP_URL.'/functions/supports/js/uploader-avatar.js',false,true );

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

}