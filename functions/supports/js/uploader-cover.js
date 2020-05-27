jQuery( function( $ ) {

	RclUploaders.get( 'rcl_cover' ).afterDone = function( e, data ) {

		jQuery( '#lk-conteyner' ).css( 'background-image', 'url(' + data.result.uploads[0].src + ')' ).animateCss( 'fadeIn' );

		rcl_notice( 'Изображение загружено', 'success', 10000 );

		rcl_do_action( 'rcl_success_upload_cover', data );

	};

	RclUploaders.get( 'rcl_cover' ).animateLoading = function( status ) {

		if ( status )
			rcl_preloader_show( jQuery( '#lk-conteyner' ) );
		else
			rcl_preloader_hide();

	};

} );
