<?php

class Rcl_Payment_Form extends Rcl_Payment_Core {

	public $ids			 = array();
	public $ids__not_in;
	public $pay_systems	 = array(); //old
	public $pay_systems_not_in; //old
	public $gateways	 = array();
	public $pay_id;
	public $pay_type;
	public $user_id;
	public $pay_summ	 = 0;
	public $baggage_data = array();
	public $description;
	public $currency;
	public $submit_value;
	public $amount_type	 = 'number';
	public $amount_min	 = 1;
	public $amount_max	 = false;
	public $amount_step	 = 1;
	public $default		 = 1;

	function __construct( $args = array() ) {
		global $rmag_options, $user_ID;

		rcl_dialog_scripts();

		if ( $args ) {
			$this->init_properties( $args );
		}

		if ( $this->pay_systems )
			$this->ids = $this->pay_systems;

		if ( $this->pay_systems_not_in )
			$this->ids__not_in = $this->pay_systems_not_in;

		$checkSystems	 = is_array( $rmag_options['connect_sale'] ) ? $rmag_options['connect_sale'] : array( $rmag_options['connect_sale'] );
		$checkSystems[]	 = 'user_balance';

		if ( !$this->ids ) {

			$this->ids = $checkSystems;
		} else {

			if ( !is_array( $this->ids ) )
				$this->ids = array_map( 'trim', explode( ',', $this->ids ) );

			foreach ( $this->ids as $k => $typeConnect ) {
				if ( !in_array( $typeConnect, $checkSystems ) )
					unset( $this->ids[$k] );
			}
		}

		if ( $this->ids__not_in ) {

			if ( !is_array( $this->ids__not_in ) )
				$this->ids__not_in = array_map( 'trim', explode( ',', $this->ids__not_in ) );

			foreach ( $this->ids as $k => $typeConnect ) {
				if ( in_array( $typeConnect, $this->ids__not_in ) )
					unset( $this->ids[$k] );
			}
		}

		if ( !$this->pay_id )
			$this->pay_id = current_time( 'timestamp' );

		if ( !$this->currency )
			$this->currency = (isset( $rmag_options['primary_cur'] ) && $rmag_options['primary_cur'] != 'RUB') ? $rmag_options['primary_cur'] : 'RUB';

		if ( !$this->user_id )
			$this->user_id = $user_ID;

		if ( !$this->description )
			$this->description = __( 'Платеж от', 'wp-recall' ) . ' ' . get_the_author_meta( 'user_email', $this->user_id );

		$this->pay_summ = round( str_replace( ',', '.', $this->pay_summ ), 2 );

		$this->baggage_data['pay_type']	 = $this->pay_type;
		$this->baggage_data['user_id']	 = $this->user_id;

		$this->baggage_data = base64_encode( json_encode( $this->baggage_data ) );

		$this->setup_gateways();
	}

	function setup_gateways() {
		global $rcl_gateways;

		if ( !$rcl_gateways )
			return false;

		if ( !$this->ids ) {
			$this->gateways = $rcl_gateways;
		} else {

			foreach ( $rcl_gateways->gateways as $id => $gateWay ) {

				if ( !in_array( $id, $this->ids ) )
					continue;

				$this->gateways[$id] = Rcl_Gateways()->gateway( $id );
			}
		}
	}

	function get_form() {

		if ( !$this->gateways )
			return false;

		$content = '<div class="rcl-payment-forms rcl-payment-buttons">';

		$styles = '';
		foreach ( $this->gateways as $id => $gateway ) {

			$content .= '<div class="rcl-payment-form rcl-payment-button rcl-payment-form-type-' . $this->pay_type . '" data-gateway-id="' . $id . '">';
			$content .= $gateway->get_form( $this );
			$content .= '</div>';

			if ( $gateway->icon ) {
				$styles .= '<style>.rcl-payment-form[data-gateway-id="' . $id . '"]{'
					. 'background-image:url(' . $gateway->icon . ');'
					. '}</style>';
			}
		}

		$content .= $styles;
		$content .= '</div>';

		return $content;
	}

	function get_custom_amount_form() {

		$fields = array(
			array(
				'type'			 => $this->amount_type,
				'slug'			 => 'pay_summ',
				'title'			 => __( 'Сумма платежа', 'wp-recall' ),
				'required'		 => true,
				'value_min'		 => $this->amount_min,
				'value_max'		 => $this->amount_type == 'runner' && !$this->amount_max ? 100 : false,
				'value_step'	 => $this->amount_step,
				'placeholder'	 => 0
			)
		);

		if ( $this->gateways ) {
			$values	 = array();
			$styles	 = '';
			foreach ( $this->gateways as $id => $gateway ) {

				$values[$id] = $gateway->label;

				if ( $gateway->icon ) {
					$styles .= '<style>.rcl-payment-form .rcl-field-gateway_id .rcl-radio-box[data-value="' . $id . '"]{'
						. 'background-image:url(' . $gateway->icon . ');'
						. '}</style>';
				}
			}

			$keys	 = array_keys( $values );
			$default = $keys[0];

			$fields[] = array(
				'type'		 => 'radio',
				'slug'		 => 'gateway_id',
				'title'		 => __( 'Система платежа', 'wp-recall' ),
				'default'	 => $default,
				'values'	 => $values
			);
		}

		$fields[] = array(
			'type'	 => 'hidden',
			'slug'	 => 'pay_type',
			'value'	 => $this->pay_type
		);

		$fields[] = array(
			'type'	 => 'hidden',
			'slug'	 => 'description',
			'value'	 => $this->description
		);

		$content = '<div class="rcl-payment-form rcl-payment-form-type-' . $this->pay_type . '">';

		$content .= rcl_get_form( array(
			'method'	 => 'post',
			'fields'	 => $fields,
			'submit'	 => __( 'Получить ссылку на оплату', 'wp-recall' ),
			//'onclick' => 'rcl_load_payment_form(this);return false;',
			'onclick'	 => 'rcl_send_form_data("rcl_load_payment_form",this);return false;'
			) );

		$content .= '<div class="rcl-payment-form-content"></div>';
		$content .= $styles;
		$content .= '</div>';

		return $content;
	}

}
