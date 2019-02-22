<?php

class Rcl_Public_Form_Manager extends Rcl_Public_Form_Fields {
	function __construct( $post_type, $args = false ) {

		parent::__construct( $post_type, $args );
	}

	function form_navi() {

		$post_types = get_post_types( array(
			'public'	 => true,
			'_builtin'	 => false
			), 'objects' );

		$types = array( 'post' => __( 'Records', 'wp-recall' ) );

		foreach ( $post_types as $post_type ) {
			$types[$post_type->name] = $post_type->label;
		}

		$content = '<div class="rcl-custom-fields-navi">';

		$content .= '<ul class="rcl-types-list">';

		foreach ( $types as $type => $name ) {

			$class = ($this->post_type == $type) ? 'class="current-item"' : '';

			$content .= '<li ' . $class . '><a href="' . admin_url( 'admin.php?page=manage-public-form&post-type=' . $type ) . '">' . $name . '</a></li>';
		}

		$content .= '</ul>';

		$content .= '</div>';

		//if ( $this->post_type == 'post' ) {

		global $wpdb;

		$form_id = 1;

		$postForms = $wpdb->get_col( "SELECT option_name FROM " . $wpdb->options . " WHERE option_name LIKE 'rcl_fields_" . $this->post_type . "_%' AND option_name NOT LIKE '%_structure' ORDER BY option_id ASC" );

		$content .= '<div class="rcl-custom-fields-navi">';

		$content .= '<ul class="rcl-types-list">';

		foreach ( $postForms as $name ) {
			preg_match( "/rcl_fields_" . $this->post_type . "_(\d)\z/", $name, $matches );

			if ( !$matches )
				continue;

			$id = intval( $matches[1] );

			if ( !$id )
				continue;

			$form_id = $id;

			$class = ($this->form_id == $form_id) ? 'class="current-item"' : '';

			$content .= '<li ' . $class . '><a href="' . admin_url( 'admin.php?page=manage-public-form&post-type=' . $this->post_type . '&form-id=' . $form_id ) . '">' . __( 'Form', 'wp-recall' ) . ' ID: ' . $form_id . '</a></li>';
		}

		$content .= '<li><a class="action-form" href="' . wp_nonce_url( admin_url( 'admin.php?page=manage-public-form&form-action=new-form&post-type=' . $this->post_type . '&form-id=' . ($form_id + 1) ), 'rcl-form-action' ) . '"><i class="rcli fa-plus"></i> ' . __( 'Add form', 'wp-recall' ) . '</a></li>';

		$content .= '</ul>';

		$content .= '</div>';

		if ( $this->form_id != 1 ) {

			$content .= '<div class="rcl-custom-fields-menu">';

			$content .= '<ul class="rcl-types-list">';

			$content .= '<li><a class="action-form" href="' . wp_nonce_url( admin_url( 'admin.php?page=manage-public-form&form-action=delete-form&post-type=' . $this->post_type . '&form-id=' . $this->form_id ), 'rcl-form-action' ) . '" onclick="return confirm(\'' . __( 'Are you sure?', 'wp-recall' ) . '\');"><i class="rcli fa-trash"></i> ' . __( 'Delete form', 'wp-recall' ) . '</a></li>';

			$content .= '</ul>';

			$content .= '</div>';
		}
		//}

		return $content;
	}

}
