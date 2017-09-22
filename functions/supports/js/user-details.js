function rcl_zoom_avatar(e){
    var link = jQuery(e);
    var src = link.data('zoom');
    ssi_modal.show({
        sizeClass: 'auto',
        className: 'rcl-user-avatar-zoom',
        content: '<div id="rcl-preview"><img class=aligncenter src=\''+src+'\'></div>'
    });
    jQuery('.rcl-user-avatar-zoom .ssi-modalWindow').animateCss('zoomIn');
}

function rcl_get_user_info(element){
    
    rcl_preloader_show('#lk-conteyner > div');
    
    rcl_ajax({
        data: {
            action: 'rcl_return_user_details',
            user_id: jQuery(element).parents('.wprecallblock').data('account')
        },
        success: function(data){  
            
            if(data['content']){

                ssi_modal.show({
                    title: Rcl.local.title_user_info,
                    sizeClass: 'auto',
                    className: 'rcl-user-getails',
                    buttons: [{
                        label: Rcl.local.close,
                        closeAfter: true
                    }],
                    content: '<div id="rcl-popup-content">'+data['content']+'</div>'
                });
                
            }
        }
    });

}
