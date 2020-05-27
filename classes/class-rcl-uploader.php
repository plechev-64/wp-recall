<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class-rcl-file-uploader
 *
 * @author Андрей
 */
class Rcl_Uploader {

	public $uploader_id		 = '';
	public $fix_editor		 = false;
	public $required		 = false;
	public $action			 = 'rcl_upload';
	public $temp_media		 = false;
	public $input_attach	 = false;
	public $auto_upload		 = true;
	public $user_id			 = 0;
	public $post_parent		 = 0;
	public $input_name		 = 'rcl-upload';
	public $dropzone		 = false;
	public $max_files		 = 10;
	public $max_size		 = 512;
	public $min_width		 = false;
	public $min_height		 = false;
	public $resize			 = array();
	public $file_types		 = array( 'jpg', 'png', 'jpeg' );
	public $multiple		 = false;
	public $crop			 = false;
	public $image_sizes		 = true;
	public $files			 = array();
	public $mode_output		 = 'grid';
	public $manager_balloon	 = false;
	public $class_name		 = '';
	protected $accept		 = array( 'image/*' );

	function __construct( $uploader_id, $args = false ) {

		rcl_sortable_scripts();

		if ( ! isset( $args['user_id'] ) ) {

			global $user_ID;

			$args['user_id'] = $user_ID;
		}

		$args['class_name'] = get_class( $this );

		$this->uploader_id = $uploader_id;

		if ( $args )
			$this->init_properties( $args );

		if ( ! is_array( $this->file_types ) ) {
			$this->file_types = array_map( 'trim', explode( ',', $this->file_types ) );
		}

		if ( ! $this->file_types )
			$this->file_types = array( 'jpg', 'png', 'jpeg' );

		if ( $this->resize && ! is_array( $this->resize ) ) {
			$this->resize = array_map( 'trim', explode( ',', $this->resize ) );
		}

		$this->accept = $this->get_accept();

		$this->init_scripts();
	}

	function init_scripts() {

		rcl_fileupload_scripts();

		if ( $this->crop ) {
			rcl_dialog_scripts();
			rcl_crop_scripts();
		}
	}

	function init_properties( $args ) {

		$properties = get_class_vars( get_class( $this ) );

		foreach ( $args as $name => $value ) {
			//if(isset($args[$name])){
			//$value = $args[$name];

			if ( is_array( $value ) ) {

				foreach ( $value as $k => $v ) {
					if ( is_object( $v ) )
						$value[$k] = ( array ) $v;
				}
				$value = ( array ) $value;
			}else if ( is_object( $value ) ) {
				$value = ( array ) $value;
			}

			$this->$name = $value;
			//}
		}
	}

	function get_uploader( $args = false ) {

		$defaults = array(
			'allowed_types' => true
		);

		$args = wp_parse_args( $args, $defaults );

		$content = '<div id="rcl-uploader-' . $this->uploader_id . '" class="rcl-uploader">';

		if ( $this->dropzone )
			$content .= $this->get_dropzone();

		$content .= '<div class="rcl-uploader-button-box">';

		$content .= $this->get_button( $args );

		if ( $args['allowed_types'] )
			$content .= '<small class="notice">' . __( 'Типы файлов', 'rcl-public' ) . ': ' . implode( ', ', $this->file_types ) . '</small>';

		$content .= '</div>';

		$content .= '</div>';

		return $content;
	}

	function get_input() {

		rcl_fileupload_scripts();

		return '<input id="rcl-uploader-input-' . $this->uploader_id . '" class="uploader-input" data-uploader_id="' . $this->uploader_id . '" name="' . $this->input_name . '[]" type="file" accept="' . implode( ', ', $this->accept ) . '" ' . ($this->multiple ? 'multiple' : '') . '>'
			. '<script>rcl_init_uploader(' . json_encode( $this ) . ');</script>';
	}

	function get_button( $args ) {

		$defaults = array(
			'button_label'	 => __( 'Загрузить файл', 'rcl-public' ),
			'button_icon'	 => 'fa-upload',
			'button_type'	 => 'simple'
		);

		$args = wp_parse_args( $args, $defaults );

		$bttnArgs = array(
			'icon'		 => $args['button_icon'],
			'type'		 => $args['button_type'],
			'label'		 => $args['button_label'],
			'class'		 => array( 'rcl-uploader-button', 'rcl-uploader-button-' . $this->uploader_id ),
			'content'	 => $this->get_input()
		);

		return rcl_get_button( $bttnArgs );
	}

	function get_dropzone() {

		$content = '<div id="rcl-dropzone-' . $this->uploader_id . '" class="rcl-dropzone">
				<div class="dropzone-upload-area">
					' . __( 'Добавить файлы в очередь загрузки', 'wp-recall' ) . '
				</div>
			</div>';

		return $content;
	}

	private function get_mime_type_by_ext( $file_ext ) {

		if ( ! $file_ext )
			return false;

		$mimes = get_allowed_mime_types();

		foreach ( $mimes as $type => $mime ) {
			if ( strpos( $type, $file_ext ) !== false ) {
				return $mime;
			}
		}

		return false;
	}

	private function get_accept() {

		if ( ! $this->file_types )
			return false;

		$accept = array();

		foreach ( $this->file_types as $type ) {
			if ( ! $type )
				continue;
			$accept[] = $this->get_mime_type_by_ext( $type );
		}

		return $accept;
	}

	function get_gallery( $imagIds = false, $getTemps = false ) {

		if ( ! $imagIds && $getTemps ) {

			$imagIds = RQ::tbl( new Rcl_Temp_Media() )->select( ['media_id' ] )
				->where( [
					'uploader_id'	 => $this->uploader_id,
					'user_id'		 => $this->user_id ? $this->user_id : 0,
					'session_id'	 => $this->user_id ? '' : $_COOKIE['PHPSESSID'],
				] )
				->get_col();
		}

		$content = '<div id="rcl-upload-gallery-' . $this->uploader_id . '" class="rcl-upload-gallery mode-' . $this->mode_output . ' ' . ($this->manager_balloon ? 'balloon-manager' : 'simple-manager') . '">';

		if ( $imagIds && is_array( $imagIds ) ) {
			//$content .= '<div class="ui-sortable-placeholder"></div>';
			foreach ( $imagIds as $imagId ) {
				$content .= $this->gallery_attachment( $imagId );
			}
		}

		$content .= '</div>';

		return $content;
	}

	function gallery_attachment( $attach_id ) {

		if ( ! get_post_type( $attach_id ) )
			return false;

		$is_image = wp_attachment_is_image( $attach_id ) ? true : false;

		if ( $is_image ) {

			$image = wp_get_attachment_image( $attach_id, 'thumbnail' );
		} else {

			$image = wp_get_attachment_image( $attach_id, array( 100, 100 ), true );
		}

		if ( ! $image )
			return false;

		$content = '<div class="gallery-attachment gallery-attachment-' . $attach_id . ' ' . ($is_image ? 'type-image' : 'type-file') . '" id="gallery-' . $this->uploader_id . '-attachment-' . $attach_id . '">';

		$content .= $image;

		$content .= '<div class="attachment-title">';
		$content .= basename( get_post_field( 'guid', $attach_id ) );
		$content .= '</div>';

		$content .= $this->get_attachment_manager( $attach_id );

		if ( $this->input_attach )
			$content .= '<input type="hidden" name="' . $this->input_attach . '[]" value="' . $attach_id . '">';

		$content .= '</div>';

		return $content;
	}

	function get_src( $attachment_id, $size = 'attachment' ) {

		$isImage = wp_attachment_is_image( $attachment_id );

		$fileSrc = 0;

		if ( $isImage ) {

			$fullSrc = wp_get_attachment_image_src( $attachment_id, $size );

			$fileSrc = $fullSrc[0];
		} else {

			$_post = get_post( $attachment_id );

			$fileSrc = wp_get_attachment_url( $attachment_id );
		}

		return $fileSrc;
	}

	function add_fix_editor_buttons( $items, $attachment_id ) {

		$isImage = wp_attachment_is_image( $attachment_id );

		$fileSrc = 0;

		if ( $isImage ) {

			$size	 = ($default = rcl_get_option( 'public_form_thumb' )) ? $default : 'large';

			$fileHtml = wp_get_attachment_image( $attachment_id, $size, false, array( 'srcset' => ' ' ) );

			$fullSrc = wp_get_attachment_image_src( $attachment_id, 'full' );
			$fileSrc = $fullSrc[0];
		} else {

			$_post = get_post( $attachment_id );

			$fileHtml = $_post->post_title;

			$fileSrc = wp_get_attachment_url( $attachment_id );
		}

		$items[] = array(
			'icon'		 => 'fa-newspaper-o',
			'title'		 => __( 'Добавить в редактор', 'wp-recall' ),
			'onclick'	 => 'rcl_add_attachment_in_editor(' . $attachment_id . ',"' . $this->fix_editor . '",this);return false;',
			'data'		 => array(
				'html'	 => $fileHtml,
				'src'	 => $fileSrc
			)
		);

		return $items;
	}

	function get_attachment_manager( $attach_id ) {

		$items = array(
			array(
				'icon'		 => 'fa-trash',
				'title'		 => __( 'Удалить файл', 'wp-recall' ),
				'onclick'	 => 'rcl_delete_attachment(' . $attach_id . ',' . $this->post_parent . ',this);return false;'
			)
		);

		if ( $this->fix_editor ) {
			$items = $this->add_fix_editor_buttons( $items, $attach_id );
		}

		$manager_items = apply_filters( 'rcl_uploader_manager_items', $items, $attach_id, $this );

		if ( ! $manager_items )
			return false;

		$content = '<div class="attachment-manager ' . ($this->manager_balloon ? 'rcl-balloon' : '') . '">';

		foreach ( $manager_items as $item ) {
			$item['type'] = 'simple';
			$content .= rcl_get_button( $item );
		}

		$content .= '</div>';

		if ( $this->manager_balloon ) {
			$content = '<div class="attachment-manager-balloon rcl-balloon-hover"><i class="rcli fa-cogs" aria-hidden="true"></i>' . $content . '</div>';
		}

		return $content;
	}

	function upload() {

		rcl_verify_ajax_nonce();

		if ( ! $_FILES[$this->input_name] ) {
			return false;
		}

		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		do_action( 'rcl_pre_upload', $this );

		$files = array();
		foreach ( $_FILES[$this->input_name] as $nameProp => $values ) {
			foreach ( $values as $k => $value ) {
				$files[$k][$nameProp] = $value;
			}
		}

		$uploads = array();
		foreach ( $files as $file ) {

			$filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );

			if ( ! in_array( $filetype['ext'], $this->file_types ) ) {
				wp_send_json( array( 'error' => __( 'Forbidden file extension. Allowed:', 'wp-recall' ) . ' ' . implode( ', ', $this->file_types ) ) );
			}

			$file['name'] = rcl_sanitize_string( str_replace( '.' . $file['ext'], '', $file['name'] ) ) . '.' . $filetype['ext'];

			$image = wp_handle_upload( apply_filters( 'rcl_pre_upload_file_data', $file ), array( 'test_form' => FALSE ) );

			if ( $image['file'] ) {

				$this->setup_image_sizes( $image['file'] );

				if ( $this->crop ) {

					$this->crop_image( $image['file'] );
				}

				if ( $this->resize ) {

					$this->resize_image( $image['file'] );
				}

				$attachment = array(
					'post_mime_type' => $image['type'],
					'post_title'	 => preg_replace( '/\.[^.]+$/', '', basename( $image['file'] ) ),
					'post_content'	 => '',
					'guid'			 => $image['url'],
					'post_parent'	 => $this->post_parent,
					'post_author'	 => $this->user_id,
					'post_status'	 => 'inherit'
				);

				if ( ! $this->user_id ) {
					$attachment['post_content'] = $_COOKIE['PHPSESSID'];
				}

				$attach_id = wp_insert_attachment( $attachment, $image['file'], $this->post_parent );

				$attach_data = wp_generate_attachment_metadata( $attach_id, $image['file'] );

				wp_update_attachment_metadata( $attach_id, $attach_data );

				if ( $this->temp_media ) {
					rcl_add_temp_media( array(
						'media_id'		 => $attach_id,
						'uploader_id'	 => $this->uploader_id
					) );
				}

				$uploads[] = array(
					'id'	 => $attach_id,
					'src'	 => $this->get_src( $attach_id, 'full' ),
					'html'	 => $this->gallery_attachment( $attach_id )
				);
			}
		}

		do_action( 'rcl_upload', $uploads, $this );

		return $uploads;
	}

	function setup_image_sizes( $image_src ) {

		if ( ! $this->image_sizes || is_array( $this->image_sizes ) ) {

			$thumbSizes = wp_get_additional_image_sizes();

			foreach ( $thumbSizes as $thumbName => $sizes ) {
				remove_image_size( $thumbName );
			}

			if ( is_array( $this->image_sizes ) ) {

				list($width, $height) = getimagesize( $image_src );

				foreach ( $this->image_sizes as $k => $thumbData ) {

					$thumbData = wp_parse_args( $thumbData, array(
						'width'	 => $width,
						'height' => $height,
						'crop'	 => 1
						) );

					add_image_size( $k . '-' . current_time( 'mysql' ), $thumbData['width'], $thumbData['height'], $thumbData['crop'] );
				}
			}
		}
	}

	function crop_image( $image_src ) {

		list($width, $height) = getimagesize( $image_src );

		$crop	 = isset( $_POST['crop_data'] ) ? $_POST['crop_data'] : false;
		$size	 = isset( $_POST['image_size'] ) ? $_POST['image_size'] : false;

		if ( ! $crop )
			return false;

		list($crop_x, $crop_y, $crop_w, $crop_h) = explode( ',', $crop );
		list($viewWidth, $viewHeight) = explode( ',', $size );

		$cf = 1;
		if ( $viewWidth < $width ) {
			$cf = $width / $viewWidth;
		}

		$crop_x *= $cf;
		$crop_y *= $cf;
		$crop_w *= $cf;
		$crop_h *= $cf;


		$image = wp_get_image_editor( $image_src );

		if ( ! is_wp_error( $image ) ) {
			$image->crop( $crop_x, $crop_y, $crop_w, $crop_h );
			$image->save( $image_src );
		}
	}

	function resize_image( $image_src ) {

		if ( ! $this->resize )
			return false;

		$image = wp_get_image_editor( $image_src );

		if ( ! is_wp_error( $image ) ) {
			$image->resize( $this->resize[0], $this->resize[1], false );
			$image->save( $image_src );
		}
	}

}
