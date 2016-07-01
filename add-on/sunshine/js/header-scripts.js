
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
                rcl_notice(data['error'],'error');
                return false;
            }                                   
            if(data['success']){
                
                rcl_preloader_hide();
                
                ssi_modal.show({
                    title: 'Детали пользователя',
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