<?php

add_filter( 'rcl_commerce_options', 'rcl_user_account_options', 10 );
function rcl_user_account_options( $options ) {
	global $rcl_gateways;

	require_once RCL_PATH . 'classes/class-rcl-options.php';

	$payment_opt = array( __( 'Payment from user’s personal account', 'wp-recall' ) );

	$systems = array();
	foreach ( Rcl_Gateways()->gateways as $id => $className ) {
		if ( $id == 'user_balance' )
			continue;
		$systems[$id] = Rcl_Gateways()->gateway( $id )->label;
	}

	if ( $systems ) {
		$payment_opt[]	 = __( 'Payment through payment systems', 'wp-recall' );
		$payment_opt[]	 = __( 'Offer both options', 'wp-recall' );
	}

	if ( $options->isset_box( 'shop' ) ) {
		$options->box( 'shop' )->add_group( 'order-payment', array(
			'title' => __( 'Оплата заказа', 'wp-recall' )
		) )->add_options( array(
			array(
				'type'	 => 'select',
				'slug'	 => 'type_order_payment',
				'values' => $payment_opt,
				'notice' => __( 'If the connection to the payment aggregator not used, apply "Funds from user personal account"!', 'wp-recall' )
			)
		) );
	}

	$group = $options->add_box( 'payments', array(
			'title' => __( 'Платежные настройки', 'wp-recall' )
		) )->add_group( 'primary', array(
		'title' => __( 'Основные настройки платежей', 'wp-recall' )
		) );

	$groupOptions = array(
		array(
			'type'	 => 'select',
			'title'	 => __( 'Основная валюта', 'wp-recall' ),
			'slug'	 => 'primary_cur',
			'values' => rcl_get_currency()
		)
	);

	if ( $systems ) {

		$groupOptions[] = array(
			'type'	 => 'checkbox',
			'title'	 => __( 'Используемые платежные системы', 'wp-recall' ),
			'slug'	 => 'connect_sale',
			'values' => $systems,
			'value'	 => is_array( $group->get_value( 'connect_sale' ) ) ? $group->get_value( 'connect_sale' ) : array( $group->get_value( 'connect_sale' ) ),
			'notice' => __( 'Applied connection type', 'wp-recall' )
		);
	} else {

		$groupOptions[] = array(
			'type'		 => 'custom',
			'title'		 => __( 'Используемые платежные системы', 'wp-recall' ),
			'slug'		 => 'connect_sale',
			'content'	 => '<p style="color:red;">Похоже ни одного подключения не настроено. Скачайте <a href="https://codeseller.ru/product_tag/platezhnye-sistemy/" target="_blank">одно из доступных дополнений</a> для подключения к платежному агрегатору и настройте его</p>'
		);
	}

	$groupOptions[] = array(
		'type'		 => 'custom',
		'title'		 => __( 'Service page of payment systems', 'wp-recall' ),
		'slug'		 => 'service-pages-notice',
		'content'	 => __( '1. Создайте на своем сайте четыре страницы:<br>
				- пустую для success<br>
				- пустую для result<br>
				- одну с текстом о неудачной оплате (fail)<br>
				- одну с текстом об удачной оплате<br>
				Название и URL созданных страниц могут быть произвольными.<br>
				2. Укажите здесь какие страницы и для чего вы создали. <br>
				3. В настройках своего аккаунта платежной системы укажите URL страницы для fail, success и result', 'wp-recall' )
	);

	$groupOptions[] = array(
		'type'		 => 'custom',
		'title'		 => __( 'Страница RESULT', 'wp-recall' ),
		'slug'		 => 'page_result_pay',
		'content'	 => wp_dropdown_pages( array(
			'selected'			 => $group->get_value( 'page_result_pay' ),
			'name'				 => 'primary-rmag-options[page_result_pay]',
			'show_option_none'	 => __( 'Not selected', 'wp-recall' ),
			'echo'				 => 0 )
		)
	);

	$groupOptions[] = array(
		'type'		 => 'custom',
		'title'		 => __( 'Страница SUCCESS', 'wp-recall' ),
		'slug'		 => 'page_success_pay',
		'content'	 => wp_dropdown_pages( array(
			'selected'			 => $group->get_value( 'page_success_pay' ),
			'name'				 => 'primary-rmag-options[page_success_pay]',
			'show_option_none'	 => __( 'Not selected', 'wp-recall' ),
			'echo'				 => 0 )
		)
	);

	$groupOptions[] = array(
		'type'		 => 'custom',
		'title'		 => __( 'Страница FAIL', 'wp-recall' ),
		'slug'		 => 'page_fail_pay',
		'content'	 => wp_dropdown_pages( array(
			'selected'			 => $group->get_value( 'page_fail_pay' ),
			'name'				 => 'primary-rmag-options[page_fail_pay]',
			'show_option_none'	 => __( 'Not selected', 'wp-recall' ),
			'echo'				 => 0 )
		)
	);

	$groupOptions[] = array(
		'type'		 => 'custom',
		'title'		 => __( 'Страница успешного платежа', 'wp-recall' ),
		'slug'		 => 'page_successfully_pay',
		'content'	 => wp_dropdown_pages( array(
			'selected'			 => $group->get_value( 'page_successfully_pay' ),
			'name'				 => 'primary-rmag-options[page_successfully_pay]',
			'show_option_none'	 => __( 'Not selected', 'wp-recall' ),
			'echo'				 => 0 )
		)
	);

	$group->add_options( $groupOptions );

	/* support old options */
	global $rclOldOptionData;

	apply_filters( 'rcl_pay_child_option', '' );

	if ( $rclOldOptionData ) {

		foreach ( $rclOldOptionData as $box_id => $box ) {

			foreach ( $box['groups'] as $k => $group ) {

				$options->add_box( $k . '-old-gateway', array(
					'title' => $group['title']
				) )->add_group( $k . '-old-gateway', array(
					'title' => $group['title']
				) )->add_options( $group['options'] );
			}
		}
	}

	unset( $rclOldOptionData );
	/*	 * * */

	return $options;
}
