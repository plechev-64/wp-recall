jQuery(function($){   
    /* Пополняем личный счет пользователя */
    jQuery('.rcl-form-add-user-count').on('click','.rcl-get-form-pay',function(){
        var id = jQuery(this).parents('.rcl-form-add-user-count').attr('id');
        rcl_preloader_show('#'+id+' .rcl-form-input');
        var dataform   = jQuery('#'+id+' form').serialize();
        var dataString = 'action=rcl_add_count_user&id_form='+id+'&'+dataform;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                rcl_preloader_hide();
                if(data['otvet']==100){
                    jQuery('#'+id+' .rcl-result-box').html(data['redirectform']);
                } else {
                   rcl_notice(Rcl.local.error,'error');
                }
            }
        });
        return false;
    });

    jQuery('.rcl-widget-balance').on('click','.rcl-toggle-form-link',function(){
        var id = jQuery(this).parents('.rcl-widget-balance').attr('id');
        jQuery('#'+id+' .rcl-form-balance').slideToggle(200);
        return false;
    });
    
});