
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