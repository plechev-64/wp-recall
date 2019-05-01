<?php

require_once 'classes/class-rcl-form-fields.php';
require_once 'classes/class-rcl-edit-terms-list.php';
require_once 'classes/class-rcl-list-terms.php';
require_once 'classes/class-rcl-public-form-uploader.php';
require_once 'classes/class-rcl-uploader-post-thumbnail.php';
require_once 'classes/class-rcl-public-form-fields.php';
require_once 'classes/class-rcl-public-form.php';
require_once 'classes/class-rcl-post-list.php';
require_once 'classes/class-rcl-edit-post.php';
require_once 'core.php';
require_once 'shortcodes.php';
require_once 'functions-ajax.php';
require_once 'init.php';
require_once 'upload-file.php';

if ( is_admin() ) {
	require_once 'classes/class-rcl-public-form-manager.php';
	require_once 'admin/index.php';
}

if ( !is_admin() ):
	add_action( 'rcl_enqueue_scripts', 'rcl_publics_scripts', 10 );
endif;
function rcl_publics_scripts() {
	rcl_enqueue_style( 'rcl-publics', rcl_addon_url( 'style.css', __FILE__ ) );
	rcl_enqueue_script( 'rcl-publics', rcl_addon_url( 'js/scripts.js', __FILE__ ) );
}

function rcl_autocomplete_scripts() {
	rcl_enqueue_style( 'magicsuggest', rcl_addon_url( 'js/magicsuggest/magicsuggest-min.css', __FILE__ ) );
	rcl_enqueue_script( 'magicsuggest', rcl_addon_url( 'js/magicsuggest/magicsuggest-min.js', __FILE__ ) );
}

//выводим в медиабиблиотеке только медиафайлы текущего автора
add_action( 'pre_get_posts', 'rcl_restrict_media_library' );
function rcl_restrict_media_library( $wp_query_obj ) {
	global $current_user, $pagenow;

	if ( !is_a( $current_user, 'WP_User' ) )
		return;

	if ( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' )
		return;

	if ( rcl_check_access_console() )
		return;

	if ( !current_user_can( 'manage_media_library' ) )
		$wp_query_obj->set( 'author', $current_user->ID );

	return;
}

add_filter( 'pre_update_postdata_rcl', 'rcl_update_postdata_excerpt' );
function rcl_update_postdata_excerpt( $postdata ) {
	if ( !isset( $_POST['post_excerpt'] ) )
		return $postdata;
	$postdata['post_excerpt'] = sanitize_text_field( $_POST['post_excerpt'] );
	return $postdata;
}

add_filter( 'rcl_init_js_variables', 'rcl_add_public_errors', 10 );
function rcl_add_public_errors( $data ) {
	$data['errors']['empty_draft'] = __( 'Заголовок публикации обязателен', 'wp-recall' );
	return $data;
}

//формируем галерею записи
add_filter( 'the_content', 'rcl_post_gallery', 10 );
function rcl_post_gallery( $content ) {
	global $post;

	if ( !is_single() || $post->post_type == 'products' )
		return $content;

	$oldSlider	 = get_post_meta( $post->ID, 'recall_slider', 1 );
	$gallery	 = get_post_meta( $post->ID, 'rcl_post_gallery', 1 );

	if ( !$gallery && $oldSlider ) {

		$args		 = array(
			'post_parent'	 => $post->ID,
			'post_type'		 => 'attachment',
			'numberposts'	 => -1,
			'post_status'	 => 'any',
			'post_mime_type' => 'image'
		);
		$childrens	 = get_children( $args );
		if ( $childrens ) {
			$gallery = array();
			foreach ( ( array ) $childrens as $children ) {
				$gallery[] = $children->ID;
			}
		}
	}

	if ( !$gallery )
		return $content;

	$content = rcl_get_post_gallery( $post->ID, $gallery ) . $content;

	return $content;
}

function rcl_get_post_gallery( $gallery_id, $attachment_ids ) {

	return rcl_get_image_gallery( array(
		'id'			 => 'rcl-post-gallery-' . $gallery_id,
		'center_align'	 => true,
		'attach_ids'	 => $attachment_ids,
		//'width' => 500,
		'height'		 => 350,
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
}

//Выводим инфу об авторе записи в конце поста
add_filter( 'the_content', 'rcl_author_info', 70 );
function rcl_author_info( $content ) {

	if ( !rcl_get_option( 'info_author_recall' ) )
		return $content;

	if ( !is_single() )
		return $content;

	global $post;

	if ( $post->post_type == 'page' )
		return $content;

	if ( rcl_get_option( 'post_types_authbox' ) ) {

		if ( !in_array( $post->post_type, rcl_get_option( 'post_types_authbox' ) ) )
			return $content;
	}

	$content .= rcl_get_author_block();

	return $content;
}

add_filter( 'the_content', 'rcl_concat_post_meta', 10 );
function rcl_concat_post_meta( $content ) {
	global $post;

	if ( doing_filter( 'get_the_excerpt' ) )
		return $content;

	$option = rcl_get_option( 'pm_rcl' );

	if ( !$option )
		return $content;

	if ( $types = rcl_get_option( 'pm_post_types' ) ) {
		if ( !in_array( $post->post_type, $types ) )
			return $content;
	}

	$pm = rcl_get_post_custom_fields_box( $post->ID );

	if ( rcl_get_option( 'pm_place' ) == 1 )
		$content .= $pm;
	else
		$content = $pm . $content;

	return $content;
}

function rcl_get_post_custom_fields_box( $post_id ) {

	$post_type	 = get_post_type( $post_id );
	$form_id	 = get_post_meta( $post_id, 'publicform-id', 1 );

	$formFields = new Rcl_Public_Form_Fields( $post_type, array(
		'form_id' => $form_id
		) );

	$customFields = $formFields->get_custom_fields();

	if ( !$customFields )
		return false;

	$fieldsBox = '<div class="rcl-custom-fields">';

	foreach ( $customFields as $field_id => $field ) {
		//print_r(get_post_meta($post_id, $field_id, 1));exit;
		$field->set_prop( 'value', get_post_meta( $post_id, $field_id, 1 ) );
		$fieldsBox .= $field->get_field_value( true );
	}

	$fieldsBox .= '</div>';

	return $fieldsBox;
}

function rcl_delete_post() {
	global $user_ID;

	$post_id = intval( $_POST['post-rcl'] );

	$post = get_post( $post_id );

	if ( $post->post_type == 'post-group' ) {

		if ( !rcl_can_user_edit_post_group( $post_id ) )
			return false;
	}else {

		if ( !current_user_can( 'edit_post', $post_id ) )
			return false;
	}

	$post_id = wp_update_post( array(
		'ID'			 => $post_id,
		'post_status'	 => 'trash'
		) );

	do_action( 'after_delete_post_rcl', $post_id );

	wp_redirect( rcl_format_url( get_author_posts_url( $user_ID ) ) . '&public=deleted' );
	exit;
}

add_action( 'after_delete_post_rcl', 'rcl_delete_notice_author_post' );
function rcl_delete_notice_author_post( $post_id ) {

	if ( !$_POST['reason_content'] )
		return false;

	$post = get_post( $post_id );

	$subject	 = 'Ваша публикация удалена.';
	$textmail	 = '<h3>Публикация "' . $post->post_title . '" была удалена</h3>
	<p>Примечание модератора: ' . $_POST['reason_content'] . '</p>';
	rcl_mail( get_the_author_meta( 'user_email', $post->post_author ), $subject, $textmail );
}

if ( !is_admin() )
	add_filter( 'get_edit_post_link', 'rcl_edit_post_link', 100, 2 );
function rcl_edit_post_link( $admin_url, $post_id ) {
	global $user_ID;

	$frontEdit = rcl_get_option( 'front_editing', array( 0 ) );

	$user_info = get_userdata( $user_ID );

	if ( array_search( $user_info->user_level, $frontEdit ) !== false || $user_info->user_level < rcl_get_option( 'consol_access_rcl', 7 ) ) {
		$edit_url = rcl_format_url( get_permalink( rcl_get_option( 'public_form_page_rcl' ) ) );
		return $edit_url . 'rcl-post-edit=' . $post_id;
	} else {
		return $admin_url;
	}
}

add_action( 'rcl_post_bar_setup', 'rcl_setup_edit_post_button', 10 );
function rcl_setup_edit_post_button() {
	global $post, $user_ID, $current_user;

	if ( !is_user_logged_in() || !$post )
		return false;

	if ( is_front_page() || is_tax( 'groups' ) || $post->post_type == 'page' )
		return false;

	if ( !current_user_can( 'edit_post', $post->ID ) )
		return false;

	$user_info = get_userdata( $current_user->ID );

	if ( $post->post_author != $user_ID ) {
		$author_info = get_userdata( $post->post_author );
		if ( $user_info->user_level < $author_info->user_level )
			return false;
	}

	$frontEdit = rcl_get_option( 'front_editing', array( 0 ) );

	if ( false !== array_search( $user_info->user_level, $frontEdit ) || $user_info->user_level >= rcl_get_option( 'consol_access_rcl', 7 ) ) {

		if ( $user_info->user_level < 10 && rcl_is_limit_editing( $post->post_date ) )
			return false;

		rcl_post_bar_add_item( 'rcl-edit-post', array(
			'url'	 => get_edit_post_link( $post->ID ),
			'icon'	 => 'fa-pencil-square-o',
			'title'	 => __( 'Edit', 'wp-recall' )
			)
		);

		return true;
	}

	return false;
}

add_filter( 'pre_update_postdata_rcl', 'rcl_add_taxonomy_in_postdata', 50, 2 );
function rcl_add_taxonomy_in_postdata( $postdata, $data ) {

	$post_type = get_post_types( array( 'name' => $data->post_type ), 'objects' );

	if ( !$post_type )
		return false;

	if ( $data->post_type == 'post' ) {

		$post_type['post']->taxonomies = array( 'category' );

		if ( isset( $_POST['tags'] ) && $_POST['tags'] ) {
			$postdata['tags_input'] = $_POST['tags']['post_tag'];
		}
	}

	if ( isset( $_POST['cats'] ) && $_POST['cats'] ) {

		$FormFields = new Rcl_Public_Form_Fields( $data->post_type, array(
			'form_id' => $_POST['form_id']
			) );

		foreach ( $_POST['cats'] as $taxonomy => $terms ) {

			if ( !isset( $FormFields->taxonomies[$taxonomy] ) )
				continue;

			if ( !$FormFields->get_field_prop( 'taxonomy-' . $taxonomy, 'only-child' ) ) {

				$allCats = get_terms( $taxonomy );

				$RclTerms	 = new Rcl_Edit_Terms_List();
				$terms		 = $RclTerms->get_terms_list( $allCats, $terms );
			}

			$postdata['tax_input'][$taxonomy] = $terms;
		}
	}

	return $postdata;
}

add_action( 'update_post_rcl', 'rcl_update_postdata_product_tags', 10, 2 );
function rcl_update_postdata_product_tags( $post_id, $postdata ) {

	if ( !isset( $_POST['tags'] ) || $postdata['post_type'] == 'post' )
		return false;

	foreach ( $_POST['tags'] as $taxonomy => $terms ) {
		wp_set_object_terms( $post_id, $terms, $taxonomy );
	}
}

add_action( 'update_post_rcl', 'rcl_unset_postdata_tags', 20, 2 );
function rcl_unset_postdata_tags( $post_id, $postdata ) {

	if ( !isset( $_POST['tags'] ) ) {

		if ( $taxonomies = get_object_taxonomies( $postdata['post_type'], 'objects' ) ) {

			foreach ( $taxonomies as $taxonomy_name => $obj ) {

				if ( $obj->hierarchical )
					continue;

				wp_set_object_terms( $post_id, NULL, $taxonomy_name );
			}
		}
	}
}

add_action( 'update_post_rcl', 'rcl_set_object_terms_post', 10, 3 );
function rcl_set_object_terms_post( $post_id, $postdata, $update ) {

	if ( $update || !isset( $postdata['tax_input'] ) || !$postdata['tax_input'] )
		return false;

	foreach ( $postdata['tax_input'] as $taxonomy_name => $terms ) {
		wp_set_object_terms( $post_id, array_map( 'intval', $terms ), $taxonomy_name );
	}
}

add_filter( 'pre_update_postdata_rcl', 'rcl_register_author_post', 10 );
function rcl_register_author_post( $postdata ) {
	global $user_ID;

	if ( rcl_get_option( 'user_public_access_recall' ) || $user_ID )
		return $postdata;

	if ( !$postdata['post_author'] ) {

		$email_new_user = sanitize_email( $_POST['email-user'] );

		if ( $email_new_user ) {

			$user_id = false;

			$random_password				 = wp_generate_password( $length							 = 12, $include_standard_special_chars	 = false );

			$userdata = array(
				'user_pass'		 => $random_password,
				'user_login'	 => $email_new_user,
				'user_email'	 => $email_new_user,
				'display_name'	 => $_POST['name-user']
			);

			$user_id = rcl_insert_user( $userdata );

			if ( $user_id ) {

				//переназначаем временный массив изображений от гостя юзеру
				rcl_update_temp_media( array(
					'user_id'	 => $user_id,
					'session_id' => ''
					), array(
					'session_id' => $_COOKIE['PHPSESSID']
				) );

				//Сразу авторизуем пользователя
				if ( !rcl_get_option( 'confirm_register_recall' ) ) {
					$creds					 = array();
					$creds['user_login']	 = $email_new_user;
					$creds['user_password']	 = $random_password;
					$creds['remember']		 = true;
					$user					 = wp_signon( $creds );
					$user_ID				 = $user_id;
				}

				$postdata['post_author'] = $user_id;
				$postdata['post_status'] = 'pending';
			}
		}
	}

	return $postdata;
}

/* deprecated */
function rcl_form_field( $args ) {
	$field = new Rcl_Form_Fields();
	return $field->get_field( $args );
}

add_action( 'update_post_rcl', 'rcl_send_mail_about_new_post', 10, 3 );
function rcl_send_mail_about_new_post( $post_id, $postData, $update ) {

	if ( $update || rcl_check_access_console() )
		return false;

	$title	 = __( 'Новая публикация', 'wp-recall' );
	$email	 = get_option( 'admin_email' );

	$textm = '<p>' . sprintf( __( 'На сайте "%s" пользователь добавил новую публикацию!', 'wp-recall' ), get_bloginfo( 'name' ) ) . '</p>';
	$textm .= '<p>' . __( 'Наименование публикации', 'wp-recall' ) . ': <a href="' . get_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a>' . '</p>';
	$textm .= '<p>' . __( 'Автор публикации', 'wp-recall' ) . ': <a href="' . get_author_posts_url( $postData['post_author'] ) . '">' . get_the_author_meta( 'display_name', $postData['post_author'] ) . '</a>' . '</p>';
	$textm .= '<p>' . __( 'Не забудьте проверить, возможно, публикация ожидает модерации' ) . '</p>';

	rcl_mail( $email, $title, $textm );
}

add_filter( 'rcl_uploader_manager_items', 'rcl_add_post_uploader_image_buttons', 10, 3 );
function rcl_add_post_uploader_image_buttons( $items, $attachment_id, $uploader ) {

	if ( !in_array( $uploader->uploader_id, array( 'post', 'thumbnail' ) ) )
		return $items;

	$isImage = wp_attachment_is_image( $attachment_id );

	$formFields = new Rcl_Public_Form_Fields( $uploader->post_type, array(
		'form_id' => $uploader->form_id
		) );

	if ( $isImage && $formFields->is_active_field( 'post_thumbnail' ) ) {

		$items[] = array(
			'icon'		 => 'fa-image',
			'title'		 => __( 'Назначить миниатюрой', 'wp-recall' ),
			'onclick'	 => 'rcl_set_post_thumbnail(' . $attachment_id . ',' . $uploader->post_parent . ',this);return false;'
		);
	}

	//$addToClick = true;
	$addGallery = true;

	if ( $formFields->is_active_field( 'post_uploader' ) ) {

		$field = $formFields->get_field( 'post_uploader' );

		//if($field->isset_prop('add-to-click'))
		//$addToClick = $field->get_prop('add-to-click');

		if ( $field->isset_prop( 'gallery' ) )
			$addGallery = $field->get_prop( 'gallery' );
	}

	/* if($addToClick){

	  $fileSrc = 0;

	  if($isImage){

	  $size = ($default = rcl_get_option('public_form_thumb'))? $default: 'large';

	  $fileHtml = wp_get_attachment_image( $attachment_id, $size, false, array('srcset' => ' ') );

	  $fullSrc = wp_get_attachment_image_src( $attachment_id, 'full' );
	  $fileSrc = $fullSrc[0];

	  }else{

	  $_post = get_post( $attachment_id );

	  $fileHtml = $_post->post_title;

	  $fileSrc = wp_get_attachment_url( $attachment_id );

	  }

	  $items[] = array(
	  'icon' => 'fa-newspaper-o',
	  'title' => __('Добавить в редактор', 'wp-recall'),
	  'onclick' => 'rcl_add_attachment_in_editor('.$attachment_id.',"contentarea-'.$uploader->post_type.'",this);return false;',
	  'data' => array(
	  'html' => $fileHtml,
	  'src' => $fileSrc
	  )
	  );

	  } */

	if ( $isImage && $addGallery ) {

		$postGallery	 = get_post_meta( $uploader->post_parent, 'rcl_post_gallery', 1 );
		$valueGallery	 = ($postGallery && in_array( $attachment_id, $postGallery )) ? $attachment_id : '';

		$items[] = array(
			'icon'		 => ($postGallery && in_array( $attachment_id, $postGallery )) ? 'fa-toggle-on' : 'fa-toggle-off',
			'class'		 => 'rcl-switch-gallery-button-' . $attachment_id,
			'title'		 => __( 'Вывести в галерее', 'wp-recall' ),
			'content'	 => '<input type="hidden" id="rcl-post-gallery-attachment-' . $attachment_id . '" name="rcl-post-gallery[]" value="' . $valueGallery . '">',
			'onclick'	 => 'rcl_switch_attachment_in_gallery(' . $attachment_id . ',this);return false;'
		);
	}

	return $items;
}
