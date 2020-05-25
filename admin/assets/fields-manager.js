var RclManagerFields = { };
var startDefaultbox = 0;

jQuery( function( $ ) {
	jQuery( '.rcl-fields-manager' ).on( 'change', 'select[name*="[type]"]', function() {
		rcl_manager_get_custom_field_options( this );
	} );
} );

rcl_box_default_fields_init();

jQuery( window ).scroll( function() {

	rcl_box_default_fields_init();

} );

function rcl_init_manager_fields( props ) {

	RclManagerFields = props;

}

function rcl_manager_field_switch( e ) {
	jQuery( e ).parents( '.manager-field-header' ).next( '.manager-field-settings' ).slideToggle();
}

function rcl_init_manager_sortable() {

	jQuery( ".rcl-fields-manager .fields-box" ).sortable( {
		connectWith: ".rcl-fields-manager .fields-box",
		handle: ".field-control .control-move",
		cursor: "move",
		placeholder: "ui-sortable-placeholder",
		distance: 15,
		receive: function( ev, ui ) {
			if ( jQuery( ev.target ).hasClass( "rcl-active-fields" ) )
				return true;
			if ( !ui.item.hasClass( "default-field" ) )
				ui.sender.sortable( "cancel" );
		}
	} );

	var parentGroup;
	jQuery( ".rcl-fields-manager .manager-group-areas" ).sortable( {
		connectWith: ".rcl-fields-manager .manager-group-areas",
		handle: ".rcl-areas-manager .area-move",
		cursor: "move",
		placeholder: "ui-sortable-area-placeholder",
		distance: 15,
		start: function( ev, ui ) {
			parentGroup = ui.item.parents( '.manager-group' );
		},
		stop: function( ev, ui ) {
			rcl_init_manager_group( ui.item.parents( '.manager-group' ), true );
			rcl_init_manager_group( parentGroup, true );
		}
	} );

}

function rcl_init_manager_areas_resizable() {

	jQuery( ".manager-group" ).each( function() {

		rcl_init_manager_group( jQuery( this ) );

	} );



}

function rcl_init_manager_group( group, isDefault ) {

	var container = group.find( ".manager-group-areas" );
	var areas = container.children( '.manager-area' );

	if ( isDefault ) {
		var defaultPercent = 100 / areas.length;
		areas.css( 'width', defaultPercent + '%' );
		areas.children( '.area-width' ).val( defaultPercent );
	}

	//var minWidth = (container.innerWidth())/5;
	//var maxWidth = container.innerWidth() - minWidth * (areas.length - 1);

	var sibTotalWidth;
	areas.resizable( {
		//handles: 'e',
		//minWidth: minWidth,
		//maxWidth: maxWidth,
		start: function( event, ui ) {
			sibTotalWidth = ui.originalSize.width + ui.originalElement.next().outerWidth();
			var nextCell = ui.originalElement.next();
			ui.originalElement.addClass( 'resizable-area' );
			nextCell.addClass( 'resizable-area' );
		},
		stop: function( event, ui ) {
			var cellPercentWidth = 100 * ui.originalElement.outerWidth( true ) / container.innerWidth();
			ui.originalElement.css( 'width', cellPercentWidth + '%' );
			ui.originalElement.children( '.area-width' ).val( Math.round( cellPercentWidth ) );
			ui.originalElement.removeClass( 'resizable-area' );

			var nextCell = ui.originalElement.next();
			var nextPercentWidth = 100 * nextCell.outerWidth( true ) / container.innerWidth();
			nextCell.css( 'width', nextPercentWidth + '%' );
			nextCell.children( '.area-width' ).val( Math.round( nextPercentWidth ) );
			nextCell.removeClass( 'resizable-area' );
		},
		resize: function( event, ui ) {
			ui.originalElement.next().width( sibTotalWidth - ui.size.width );

			var cellPercentWidth = 100 * ui.originalElement.outerWidth( true ) / container.innerWidth();
			ui.originalElement.children( '.area-width-content' ).text( Math.round( cellPercentWidth ) + '%' );

			var nextCell = ui.originalElement.next();
			var nextPercentWidth = 100 * nextCell.outerWidth( true ) / container.innerWidth();

			nextCell.children( '.area-width-content' ).text( Math.round( nextPercentWidth ) + '%' );
		}
	} );

}

function rcl_box_default_fields_init() {

	var manager = jQuery( '.rcl-fields-manager' );
	var box = manager.children( '.default-box' );

	if ( !box.length )
		return false;

	var structureEdit = manager.hasClass( 'structure-edit' ) ? true : false;

	var scroll = jQuery( window ).scrollTop();

	if ( !startDefaultbox ) {

		var indent = structureEdit ? -30 : 20;

		if ( scroll > box.offset().top + indent ) {
			startDefaultbox = box.offset().top + indent;
			if ( structureEdit )
				box.next().attr( 'style', 'margin-top:' + box.outerHeight( true ) + 'px' );

			box.addClass( "fixed" );
		}

	} else {

		if ( scroll < startDefaultbox ) {
			startDefaultbox = 0;
			if ( structureEdit )
				box.next().attr( 'style', 'margin-top:' + 0 + 'px' );
			box.removeClass( "fixed" );
		}

	}

}

function rcl_remove_manager_group( textConfirm, e ) {

	if ( !confirm( textConfirm ) )
		return false;

	var areasBox = jQuery( e ).parents( '.manager-group' );

	rcl_preloader_show( areasBox );

	areasBox.remove();

	return false;

}

function rcl_remove_manager_area( textConfirm, e ) {

	if ( !confirm( textConfirm ) )
		return false;

	var areaBox = jQuery( e ).parents( '.manager-area' );

	var areasBox = jQuery( e ).parents( '.manager-group' );

	rcl_preloader_show( areaBox );

	areaBox.remove();

	var countAreas = areasBox.find( '.manager-area' ).length;

	if ( countAreas == 2 ) {
		areasBox.find( '.manager-area .rcl-areas-manager' ).hide();
	}

	rcl_init_manager_group( areasBox, true );

	return false;

}

function rcl_manager_get_new_area( e ) {

	var areasBox = jQuery( e ).parents( '.manager-group' );

	rcl_preloader_show( areasBox );

	rcl_ajax( {
		data: {
			action: 'rcl_manager_get_new_area',
			props: RclManagerFields
		},
		success: function( data ) {

			areasBox.children( '.manager-group-areas' ).append( data.content );

			rcl_init_manager_sortable();

			rcl_init_manager_group( areasBox, true );

		}
	} );

	return false;
}

function rcl_manager_get_new_group( e ) {

	var groupsBox = jQuery( '.rcl-manager-groups' );

	rcl_preloader_show( groupsBox );

	rcl_ajax( {
		data: {
			action: 'rcl_manager_get_new_group',
			props: RclManagerFields
		},
		success: function( data ) {

			groupsBox.append( data.content );

			rcl_init_manager_sortable();

		}
	} );

	return false;
}

function rcl_manager_field_edit( e ) {

	var field = jQuery( e ).parents( '.manager-field' );

	field.toggleClass( 'settings-edit' );

	/*ssi_modal.show({
	 content: field,
	 bodyElement: true,
	 title: 'ssi-modal',
	 extendOriginalContent: true,
	 beforeShow: function(modal){
	 field.remove();
	 },
	 });*/

}

function rcl_manager_field_delete( e ) {

	var field = jQuery( e ).parents( '.manager-field' );

	if ( field.hasClass( 'must-meta-delete' ) ) {

		if ( confirm( jQuery( '#rcl-manager-confirm-delete' ).text() ) ) {
			jQuery( '.rcl-fields-manager-form' ).append( '<input type="hidden" name="deleted[]" value="' + field.data( 'id' ) + '">' );
		}

	}

	field.remove();

	return false;
}

function rcl_manager_get_custom_field_options( e ) {

	var typeField = jQuery( e ).val();
	var boxField = jQuery( e ).parents( '.manager-field' );
	var oldType = boxField.attr( 'data-type' );

	var multiVals = [ 'multiselect', 'checkbox' ];

	if ( jQuery.inArray( typeField, multiVals ) >= 0 && jQuery.inArray( oldType, multiVals ) >= 0 ) {

		boxField.attr( 'data-type', typeField );
		return;

	}

	var multiVals = [ 'radio', 'select' ];

	if ( jQuery.inArray( typeField, multiVals ) >= 0 && jQuery.inArray( oldType, multiVals ) >= 0 ) {

		boxField.attr( 'data-type', typeField );
		return;

	}

	var singleVals = [ 'date', 'time', 'email', 'number', 'url', 'dynamic', 'tel'
	];

	if ( jQuery.inArray( typeField, singleVals ) >= 0 && jQuery.inArray( oldType, singleVals ) >= 0 ) {

		boxField.attr( 'data-type', typeField );
		return;

	}

	var sliderVals = [ 'runner', 'range' ];

	if ( jQuery.inArray( typeField, sliderVals ) >= 0 && jQuery.inArray( oldType, sliderVals ) >= 0 ) {

		boxField.attr( 'data-type', typeField );
		return;

	}

	rcl_preloader_show( boxField );

	rcl_ajax( {
		/*rest: true,*/
		data: {
			action: 'rcl_manager_get_custom_field_options',
			newType: typeField,
			oldType: oldType,
			manager: RclManagerFields,
			fieldId: boxField.data( 'id' )
		},
		success: function( data ) {

			if ( data['content'] ) {

				boxField.find( '.field-secondary-options' ).replaceWith( data['content'] );

				boxField.attr( 'data-type', typeField );

			}

		}
	} );

	return false;

}

function rcl_manager_get_new_field( e ) {

	var area = jQuery( e ).parents( '.manager-area' );

	rcl_preloader_show( area );

	rcl_ajax( {
		/*rest: true,*/
		data: {
			action: 'rcl_manager_get_new_field',
			props: RclManagerFields
		},
		success: function( data ) {

			if ( data['content'] ) {
				area.find( '.fields-box' ).append( data['content'] );
				rcl_init_iconpicker();
			}

		}
	} );

	return false;

}

function rcl_manager_update_fields( newManagerId ) {

	var newManagerId = newManagerId ? newManagerId : 0;

	rcl_preloader_show( jQuery( '.rcl-fields-manager' ) );

	rcl_ajax( {
		/*rest: {action: 'rcl_update_fields'},*/
		data: 'action=rcl_manager_update_fields&copy=' + newManagerId + '&' + jQuery( '.rcl-fields-manager-form' ).serialize()
	} );

	return false;
}

function rcl_manager_copy_fields( newManagerId ) {

	rcl_manager_update_fields( newManagerId );

	return false;
}
