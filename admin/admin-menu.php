<?php

add_filter( 'set-screen-option', function( $status, $option, $value ) {
	if ( in_array( $option, array( 'addons_per_page', 'templates_per_page' ) ) ) {
		return ( int ) $value;
	}
	return $status;
}, 10, 3 );

add_action( '_admin_menu', 'rcl_init_update_notice', 10 );
function rcl_init_update_notice() {
	global $rcl_update_notice;

	$rcl_update_notice = array();

	$need_update = get_site_option( 'rcl_addons_need_update' );

	if ( ! $need_update )
		return false;

	foreach ( $need_update as $addon_id => $data ) {

		if ( isset( $data['template'] ) ) {
			$update_notice['templates'][] = $addon_id;
			continue;
		}

		if ( isset( $data['custom-manager'] ) && $data['custom-manager'] == 'prime-forum' ) {
			$update_notice['prime-forum'][] = $addon_id;
			continue;
		}

		$update_notice['addons'][] = $addon_id;
	}

	$rcl_update_notice = apply_filters( 'rcl_update_notice', $update_notice );
}

add_action( 'admin_menu', 'rcl_admin_menu', 19 );
function rcl_admin_menu() {
	global $rcl_update_notice;

	$cnt_t	 = isset( $rcl_update_notice['templates'] ) ? count( $rcl_update_notice['templates'] ) : 0;
	$cnt_a	 = isset( $rcl_update_notice['addons'] ) ? count( $rcl_update_notice['addons'] ) : 0;

	$notice_t	 = ($cnt_t) ? ' <span class="update-plugins count-' . $cnt_t . '"><span class="plugin-count">' . $cnt_t . '</span></span>' : '';
	$notice_a	 = ($cnt_a) ? ' <span class="update-plugins count-' . $cnt_a . '"><span class="plugin-count">' . $cnt_a . '</span></span>' : '';

	add_menu_page( __( 'WP-RECALL', 'wp-recall' ), __( 'WP-RECALL', 'wp-recall' ), 'manage_options', 'manage-wprecall', 'rcl_dashboard' );
	add_submenu_page( 'manage-wprecall', __( 'Dashboard', 'wp-recall' ), __( 'Dashboard', 'wp-recall' ), 'manage_options', 'manage-wprecall', 'rcl_dashboard' );
	add_submenu_page( 'manage-wprecall', __( 'SETTINGS', 'wp-recall' ), __( 'SETTINGS', 'wp-recall' ), 'manage_options', 'rcl-options', 'rcl_global_options' );
	add_submenu_page( 'manage-wprecall', __( 'Repository', 'wp-recall' ), __( 'Repository', 'wp-recall' ), 'manage_options', 'rcl-repository', 'rcl_repository_page' );
	$hook	 = add_submenu_page( 'manage-wprecall', __( 'Add-ons', 'wp-recall' ) . $notice_a, __( 'Add-ons', 'wp-recall' ) . $notice_a, 'manage_options', 'manage-addon-recall', 'rcl_render_addons_manager' );
	add_action( "load-$hook", 'rcl_add_options_addons_manager' );
	$hook	 = add_submenu_page( 'manage-wprecall', __( 'Templates', 'wp-recall' ) . $notice_t, __( 'Templates', 'wp-recall' ) . $notice_t, 'manage_options', 'manage-templates-recall', 'rcl_render_templates_manager' );
	add_action( "load-$hook", 'rcl_add_options_templates_manager' );
	add_submenu_page( 'manage-wprecall', __( 'Tabs manager', 'wp-recall' ), __( 'Tabs manager', 'wp-recall' ), 'manage_options', 'rcl-tabs-manager', 'rcl_admin_tabs_manager' );
	add_submenu_page( 'manage-wprecall', __( 'Мастер настройки', 'wp-recall' ), __( 'Мастер настройки', 'wp-recall' ), 'manage_options', 'rcl-wizard', 'rcl_wizard_page' );
}

function rcl_wizard_page() {
	require_once RCL_PATH . 'classes/class-rcl-wizard.php';

	$Wizard = new Rcl_Wizard( array(
		'image'	 => RCL_URL . 'assets/img/wp-recall-icon.png',
		'title'	 => __( 'Мастер настройки плагина WP-Recall', 'wp-recall' )
		) );

	$options = get_site_option( 'options' );

	$Wizard->add_step( array(
		'title'			 => __( 'Первый шаг', 'wp-recall' ),
		'description'	 => __( 'Какой то поясняющий текст для первого шага', 'wp-recall' ),
		'options'		 => array(
			array(
				'slug'	 => 'options[one]',
				'type'	 => 'text',
				'title'	 => __( 'Первая опция' ),
				'value'	 => $options['one']
			),
			array(
				'slug'	 => 'options[two]',
				'type'	 => 'radio',
				'title'	 => __( 'Вторая опция' ),
				'values' => [
					1, 2, 3
				],
				'value'	 => $options['two']
			),
			array(
				'slug'	 => 'options[three]',
				'type'	 => 'select',
				'title'	 => __( 'Третья опция' ),
				'values' => [
					1, 2, 3
				],
				'value'	 => $options['three'],
			)
		)
	) );

	if ( $options['two'] != 1 ) {

		$Wizard->add_step( array(
			'title'			 => __( 'Второй шаг', 'wp-recall' ),
			'description'	 => __( 'Какой то поясняющий текст для второго шага', 'wp-recall' ),
			'options'		 => array(
				array(
					'slug'	 => 'options_one',
					'type'	 => 'text',
					'title'	 => __( 'Первая опция' ),
					'value'	 => get_site_option( 'options_one' )
				),
				array(
					'slug'	 => 'options_two',
					'type'	 => 'radio',
					'title'	 => __( 'Вторая опция' ),
					'values' => [
						1, 2, 3
					],
					'value'	 => get_site_option( 'options_two' )
				),
				array(
					'slug'	 => 'options_three',
					'type'	 => 'select',
					'title'	 => __( 'Третья опция' ),
					'values' => [
						1, 2, 3
					],
					'value'	 => get_site_option( 'options_three' )
				)
			)
		) );

		$Wizard->add_step( array(
			'title'			 => __( 'Активация дополнений', 'wp-recall' ),
			'description'	 => __( 'Какой то поясняющий текст для шага', 'wp-recall' ),
			'options'		 => array(
				array(
					'slug'	 => 'addons[rcl-chat]',
					'type'	 => 'radio',
					'title'	 => __( 'Дополнение чата' ),
					'values' => array(
						__( 'Отключено' ),
						__( 'Включено' )
					),
					'value'	 => rcl_exist_addon( 'rcl-chat' ) ? 1 : 0
				),
				array(
					'slug'	 => 'addons[profile]',
					'type'	 => 'radio',
					'title'	 => __( 'Дополнение профиля' ),
					'values' => array(
						__( 'Отключено' ),
						__( 'Включено' )
					),
					'value'	 => rcl_exist_addon( 'profile' ) ? 1 : 0
				),
				array(
					'slug'	 => 'addons[prime-forum]',
					'type'	 => 'radio',
					'title'	 => __( 'Дополнение форума' ),
					'values' => array(
						__( 'Отключено' ),
						__( 'Включено' )
					),
					'value'	 => rcl_exist_addon( 'prime-forum' ) ? 1 : 0
				),
			)
		) );
	}

	$Wizard->add_step( array(
		'title'			 => __( 'Последний шаг', 'wp-recall' ),
		'description'	 => '<p>' . __( 'Какой то текст' ) . '</p>'
		. '<p>' . rcl_get_button( array(
			'icon'	 => 'fa-home',
			'label'	 => __( 'Перейти на главную' ),
			'href'	 => '/'
		) ) . '</p>'
	) );

	echo $Wizard->get_wizard();
}

function rcl_dashboard() {
	/** Load WordPress dashboard API */
	require_once(ABSPATH . 'wp-admin/includes/dashboard.php');

	wp_enqueue_script( 'dashboard' );

	do_action( 'rcl_add_dashboard_metabox', get_current_screen() );

	require_once 'pages/dashboard.php';
}

function rcl_add_options_addons_manager() {
	global $Rcl_Addons_Manager;

	require_once "add-on-manager.php";

	$option	 = 'per_page';
	$args	 = array(
		'label'		 => __( 'Add-ons', 'wp-recall' ),
		'default'	 => 100,
		'option'	 => 'addons_per_page'
	);

	add_screen_option( $option, $args );
	$Rcl_Addons_Manager = new Rcl_Addons_Manager();

	do_action( 'rcl_init_addons_manager' );
}

function rcl_add_options_templates_manager() {
	global $Rcl_Templates_Manager;

	require_once "templates-manager.php";

	$option	 = 'per_page';
	$args	 = array(
		'label'		 => __( 'Templates', 'wp-recall' ),
		'default'	 => 100,
		'option'	 => 'templates_per_page'
	);

	add_screen_option( $option, $args );
	$Rcl_Templates_Manager = new Rcl_Templates_Manager();

	do_action( 'rcl_init_themes_manager' );
}

add_action( 'rcl_before_include_addons', 'rcl_template_update_status' );
function rcl_template_update_status() {

	if ( wp_doing_ajax() )
		return false;

	$page = ( isset( $_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
	if ( 'manage-templates-recall' != $page )
		return;

	if ( isset( $_GET['template'] ) && isset( $_GET['action'] ) ) {

		global $wpdb, $user_ID, $active_addons;

		$addon	 = $_GET['template'];
		$action	 = rcl_wp_list_current_action();

		if ( $action == 'connect' ) {
			rcl_deactivate_addon( get_site_option( 'rcl_active_template' ) );

			rcl_activate_addon( $addon );

			update_site_option( 'rcl_active_template', $addon );
			header( "Location: " . admin_url( 'admin.php?page=manage-templates-recall&update-template=activate' ), true, 302 );
			exit;
		}

		if ( $action == 'delete' ) {
			rcl_delete_addon( $addon );
			header( "Location: " . admin_url( 'admin.php?page=manage-templates-recall&update-template=delete' ), true, 302 );
			exit;
		}
	}
}

//Настройки плагина в админке
function rcl_global_options() {
	require_once 'pages/options.php';
}

function rcl_repository_page() {
	require_once 'pages/repository.php';
}

function rcl_admin_tabs_manager() {
	require_once 'pages/tabs-manager.php';
}

function rcl_render_addons_manager() {
	require_once 'pages/addons-manager.php';
}

function rcl_render_templates_manager() {
	require_once 'pages/themes-manager.php';
}
