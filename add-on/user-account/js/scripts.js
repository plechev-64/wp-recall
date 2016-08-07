jQuery(function($){   
    /* Пополняем личный счет пользователя */
    jQuery('body').on('click','.rcl-form-add-user-count .rcl-get-form-pay',function(){
        var id = jQuery(this).parents('.rcl-form-add-user-count').attr('id');
        rcl_preloader_show('#'+id+' .rcl-form-input');
        var dataform   = jQuery('#'+id+' form').serialize();
        var dataString = 'action=rcl_add_count_user&id_form='+id+'&'+dataform;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                rcl_preloader_hide();
                
                if(data['error']){
                    rcl_notice(data['error'],'error',10000);
                    return false;
                }
                
                if(data['otvet']==100){
                    jQuery('#'+id+' .rcl-result-box').html(data['redirectform']);
                }
            }
        });
        return false;
    });

    jQuery('body').on('click','.rcl-widget-balance .rcl-toggle-form-link',function(){
        var id = jQuery(this).parents('.rcl-widget-balance').attr('id');
        jQuery('#'+id+' .rcl-form-balance').slideToggle(200);
        return false;
    });
    
});