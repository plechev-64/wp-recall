<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class-rcl-uploader-avatar
 *
 * @author Андрей
 */
class Rcl_Uploader_Public_Form extends Rcl_Uploader {

	public $form_id = 1;
	public $post_type;

	function __construct( $args ) {

		$args = wp_parse_args( $args, array(
			'auto_upload'		 => true,
			'manager_balloon'	 => true,
			'crop'				 => true,
			'file_types'		 => 'jpg,png',
			'temp_media'		 => isset( $args['post_parent'] ) && $args['post_parent'] ? false : true,
			'dropzone'			 => true,
			'multiple'			 => true
			) );

		parent::__construct( 'post', $args );
	}

	function get_form_uploader() {

		$content = $this->get_form_gallery();

		$content .= $this->get_uploader();

		return $content;
	}

	function get_form_gallery() {

		$imagIds = array();

		if ( $this->post_parent ) {

			$args = array(
				'post_parent'	 => $this->post_parent,
				'post_type'		 => 'attachment',
				'numberposts'	 => -1,
				'post_status'	 => 'any'
			);

			$attachments = get_children( $args );

			if ( $attachments ) {
				$imagIds = array();
				foreach ( $attachments as $attachment ) {
					$imagIds[] = $attachment->ID;
				}
			}
		} else {

			$temps = rcl_get_temp_media( array(
				'user_id'			 => $this->user_id ? $this->user_id : 0,
				'session_id'		 => $this->user_id ? '' : $_COOKIE['PHPSESSID'],
				'uploader_id__in'	 => array( 'post', 'thumbnail' )
				) );

			if ( $temps ) {
				foreach ( $temps as $temp ) {
					$imagIds[] = $temp->media_id;
				}
			}
		}

		return $this->get_gallery( $imagIds );
	}

}
