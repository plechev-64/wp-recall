<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class-rcl-custom-field-text
 *
 * @author Андрей
 */
class Rcl_Field_Uploader extends Rcl_Field_Abstract {

	public $required;
	public $file_types	 = 'jpg, jpeg, png';
	public $max_size	 = 512;
	public $max_files	 = 5;
	public $multiple	 = 0;
	public $dropzone	 = 0;
	public $mode_output	 = 'grid';

	function __construct( $args ) {
		parent::__construct( $args );
	}

	function get_options() {

		$options = array(
			array(
				'slug'			 => 'icon',
				'default'		 => 'fa-file',
				'placeholder'	 => 'fa-file',
				'class'			 => 'rcl-iconpicker',
				'type'			 => 'text',
				'title'			 => __( 'Icon class of  font-awesome', 'wp-recall' ),
				'notice'		 => __( 'Source', 'wp-recall' ) . ' <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank">http://fontawesome.io/</a>'
			),
			array(
				'slug'		 => 'max_size',
				'default'	 => $this->max_size,
				'type'		 => 'runner',
				'unit'		 => 'Kb',
				'value_min'	 => 256,
				'value_max'	 => 5120,
				'value_step' => 256,
				'title'		 => __( 'File size', 'wp-recall' ),
				'notice'	 => __( 'maximum size of uploaded file, Kb (Default - 512)', 'wp-recall' )
			),
			array(
				'slug'		 => 'file_types',
				'default'	 => $this->file_types,
				'type'		 => 'text',
				'title'		 => __( 'Allowed file types', 'wp-recall' ),
				'notice'	 => __( 'allowed types of files are divided by comma, for example: pdf, zip, jpg', 'wp-recall' )
			),
			array(
				'slug'		 => 'max_files',
				'default'	 => $this->max_files,
				'type'		 => 'runner',
				'value_min'	 => 1,
				'value_max'	 => 100,
				'value_step' => 1,
				'title'		 => __( 'Макс. количество файлов', 'wp-recall' ),
			),
			array(
				'slug'		 => 'multiple',
				'default'	 => $this->multiple,
				'type'		 => 'radio',
				'values'	 => array(
					__( 'Отключено', 'wp-recall' ),
					__( 'Включено', 'wp-recall' )
				),
				'title'		 => __( 'Множественная загрузка', 'wp-recall' ),
			),
			array(
				'slug'		 => 'dropzone',
				'default'	 => $this->dropzone,
				'type'		 => 'radio',
				'values'	 => array(
					__( 'Отключено', 'wp-recall' ),
					__( 'Включено', 'wp-recall' )
				),
				'title'		 => __( 'Dropzone', 'wp-recall' ),
			),
			array(
				'slug'		 => 'mode_output',
				'default'	 => $this->mode_output,
				'type'		 => 'radio',
				'values'	 => array(
					'grid'		 => __( 'Плитка', 'wp-recall' ),
					'list'		 => __( 'Список', 'wp-recall' ),
					'gallery'	 => __( 'Галлерея', 'wp-recall' )
				),
				'title'		 => __( 'Режим вывода файлов', 'wp-recall' ),
			),
			array(
				'slug'		 => 'fix_editor',
				'default'	 => $this->fix_editor,
				'type'		 => 'text',
				'title'		 => __( 'ID прикрепленного редактора', 'wp-recall' ),
				'notice'	 => __( 'Можно закрепить загрузчик за одним из имеющихся текстовых редакторов, указав его ID', 'wp-recall' ),
			)
		);

		return $options;
	}

	function get_input() {
		global $user_ID;

		//if($this->fix_editor)
		//add_filter('rcl_uploader_manager_items', array($this, 'add_uploader_buttons'), 10, 3);

		$uploader = new Rcl_Uploader( 'field_' . $this->id, array(
			'temp_media'	 => true,
			'fix_editor'	 => $this->fix_editor,
			'required'		 => intval( $this->required ),
			'user_id'		 => $user_ID,
			'min_width'		 => 200,
			'min_height'	 => 200,
			//'resize' => array(500, 500),
			'dropzone'		 => $this->dropzone,
			'multiple'		 => $this->multiple,
			'max_size'		 => $this->max_size,
			'auto_upload'	 => $this->multiple ? true : false,
			'file_types'	 => array_map( 'trim', explode( ',', $this->file_types ) ),
			'max_files'		 => $this->max_files,
			'crop'			 => $this->multiple ? false : true,
			'input_attach'	 => $this->id,
			'mode_output'	 => $this->mode_output
			/* 'image_sizes' => array(
			  array(
			  'width' => 150,
			  'height' => 150,
			  'crop' => 1
			  )
			  ) */
			) );

		$content = '';

		if ( rcl_is_ajax() ) {

			ob_start();

			global $wp_scripts, $wp_styles;

			$wp_scripts->do_items( array(
				'rcl-core-scripts',
				'jquery-ui-widget',
				'load-image',
				'canvas-to-blob',
				'jquery-iframe-transport',
				'jquery-fileupload',
				'jquery-fileupload-process',
				'jquery-fileupload-image',
				'rcl-uploader-scripts',
				'jquery-ui-sortable'
			) );

			$wp_styles->do_items( array(
				'rcl-uploader-style'
			) );

			$content .= ob_get_contents();

			ob_end_clean();
		}

		$content .= $uploader->get_gallery( $this->value, true );

		$content .= $uploader->get_uploader();

		return $content;
	}

	function get_value() {

		if ( !$this->value )
			return false;

		$content = '<div id="rcl-gallery-' . $this->id . '" class="rcl-upload-gallery mode-' . $this->mode_output . '">';

		if ( $this->mode_output == 'gallery' ) {

			/* $width = 100;

			  $galArgs = array(
			  'id' => 'rcl-gallery-'.$this->id,
			  'attach_ids' => $this->value,
			  //'center_align' => true,
			  //'width' => (count($this->value) < 7)? count($this->value) * 73: 500,
			  'height' => $width,
			  'slides' => array(
			  'slide' => array($width,$width),
			  'full' => 'large'
			  ),
			  'options' => array(
			  '$SlideWidth' => $width,
			  '$SlideSpacing' => 3
			  )
			  );

			  if(count($attach_ids) >= 7){
			  $galArgs['navigator'] = array(
			  'arrows' => true
			  );
			  }

			  $content = rcl_get_image_gallery($galArgs); */

			$content = rcl_get_image_gallery( array(
				'id'			 => 'rcl-gallery-' . $this->id,
				'center_align'	 => true,
				'attach_ids'	 => $this->value,
				//'width' => 500,
				'height'		 => 250,
				'slides'		 => array(
					'slide'	 => 'large',
					'full'	 => 'large'
				),
				'navigator'		 => array(
					'thumbnails' => array(
						'width'	 => 50,
						'height' => 50,
						'arrows' => true
					)
				)
				) );
		} else {

			foreach ( $this->value as $attach_id ) {

				$is_image = wp_attachment_is_image( $attach_id ) ? true : false;

				if ( $is_image ) {

					$image = wp_get_attachment_image( $attach_id, 'thumbnail' );
				} else {

					$image = wp_get_attachment_image( $attach_id, array( 100, 100 ), true );
				}

				if ( !$image )
					return false;

				$url = wp_get_attachment_url( $attach_id );

				$content .= '<div class="gallery-attachment gallery-attachment-' . $attach_id . ' ' . ($is_image ? 'type-image' : 'type-file') . '">';

				$content .= '<a href="' . $url . '" target="_blank">' . $image . '</a>';

				$content .= '<div class="attachment-title">';
				$content .= '<a href="' . $url . '" target="_blank">' . basename( get_post_field( 'guid', $attach_id ) ) . '</a>';
				$content .= '</div>';

				$content .= '</div>';
			}
		}

		$content .= '</div>';

		return $content;
	}

}
