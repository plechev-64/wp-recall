function rcl_zoom_avatar(e){
    var link = jQuery(e);
    var src = link.data('zoom');
    ssi_modal.show({
        sizeClass: 'auto',
        className: 'rcl-user-avatar-zoom',
        content: '<div id="rcl-preview"><img class=aligncenter src=\''+src+'\'></div>'
    });
}

function rcl_get_user_info(element){
    
    rcl_preloader_show('#lk-conteyner > div');

    var post_id = jQuery(element).data('post');
    
    var dataString = 'action=rcl_get_user_details&user_id='+jQuery(element).parents('.wprecallblock').data('account');
    dataString += '&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
        type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
        success: function(data){                                   
            if(data['error']){
                rcl_preloader_hide();
                rcl_notice(data['error'],'error',10000);
                return false;
            }                                   
            if(data['success']){
                
                rcl_preloader_hide();
                
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

function rcl_update_profile(){
    rcl_preloader_show('#tab-profile > form');
    var form = jQuery('#tab-profile form');
    var dataString = 'action=rcl_edit_profile&'+form.serialize();
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: Rcl.ajaxurl,
        success: function(data){
            
            rcl_preloader_hide();
            
            if(data['error']){
                rcl_notice(data['error'],'error',10000);
            }
            if(data['success']){
                rcl_notice(data['success'],'success',10000);
                location.href = data['redirect_url'];
            }
        } 
    });	  	
    return false;
}