<?php

if ( !is_admin() ):
	add_action( 'rcl_enqueue_scripts', 'rcl_payments_scripts_init', 10 );
endif;
function rcl_payments_scripts_init() {
	rcl_enqueue_script( 'rcl-payments-scripts', rcl_addon_url( 'js/scripts.js', __FILE__ ) );
}
