function rcl_zoom_avatar(e){
    var link = jQuery(e);
    var src = link.data('zoom');
    jQuery('body > div').last().after('<div id=\'rcl-preview\'><img class=aligncenter src=\''+src+'\'></div>');
    jQuery( '#rcl-preview img' ).load(function() {
        jQuery( '#rcl-preview' ).dialog({
            modal: true,
            dialogClass: 'rcl-zoom-avatar',
            draggable: false,
            imageQuality: 1,
            resizable: false,
            width:355,
            close: function (e, data) {
                jQuery( this ).dialog( 'close' );
                jQuery( '#rcl-preview' ).remove();
            }
        });
    });
}


