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
class Rcl_Uploader_Public_Form extends Rcl_Uploader{

    public $form_id = 1;
    public $post_type;

    function __construct($args){

        $this->init_properties($args);

        parent::__construct('post', array(
            'auto_upload' => true,
            'crop' => true,
            'temp_media' => isset($args['post_parent']) && $args['post_parent']? false: true,
            'dropzone' => true,
            'multiple' => true
        ));

        //wp_enqueue_script( 'avatar-uploader', USP_URL.'/functions/supports/js/uploader-avatar.js',false,true );

    }

    function get_form_uploader(){

        $content = $this->get_form_gallery();

        $content .= $this->get_uploader();

        return $content;

    }

    function get_form_gallery(){

        $imagIds = array();

        if($this->post_parent){

            $args = array(
                'post_parent' => $this->post_parent,
                'post_type'   => 'attachment',
                'numberposts' => -1,
                'post_status' => 'any'
            );

            $attachments = get_children( $args );

            if($attachments){
                $imagIds = array();
                foreach($attachments as $attachment){
                    $imagIds[] = $attachment->ID;

                }

            }

        }else{

            $temps = rcl_get_temp_media(array(
                'user_id' => $this->user_id? $this->user_id: 0,
                'session_id' => $this->user_id? '': $_COOKIE['PHPSESSID'],
                'uploader_id__in' => array('post','thumbnail')
            ));

            if($temps){
                foreach($temps as $temp){
                    $imagIds[] = $temp->media_id;
                }
            }

        }

        return $this->get_gallery($imagIds);

    }

    function update_temporary_gallery($uploads){

        $user_id = ($this->user_id)? $this->user_id: $_COOKIE['PHPSESSID'];

        $gallery = get_site_option('rcl_temporary_gallery');

        if(!$gallery) $gallery = array();

        foreach($uploads as $upload){
            $gallery[$user_id][] = $upload['id'];
        }

        update_site_option('rcl_temporary_gallery', $gallery);

        return $gallery;

    }

}