jQuery(function($){   
    /* Пополняем личный счет пользователя */
    jQuery('body').on('click','.rcl-form-add-user-count .rcl-get-form-pay',function(){
        var id = jQuery(this).parents('.rcl-form-add-user-count').attr('id');
        rcl_preloader_show('#'+id+' .rcl-form-input');
        var dataform   = jQuery('#'+id+' form').serialize();
        var dataString = 'action=rcl_add_count_user&'+dataform;
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

function rcl_pay_order_user_balance(e,data){
    
    var pay_id = data.pay_id;
    var pay_type = data.pay_type;
    var pay_summ = data.pay_summ;
    var baggage_data = JSON.stringify(data.baggage_data);
    
    rcl_preloader_show(jQuery('.rcl-payment-buttons'));
    
    var dataString = 'action=rcl_pay_order_user_balance&pay_id='+pay_id+'&pay_type='+pay_type+'&pay_summ='+pay_summ+'&baggage_data='+baggage_data;
    dataString += '&ajax_nonce='+Rcl.nonce;
    
    jQuery.ajax({
        type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
        success: function(data){
            
            rcl_preloader_hide();

            if(data['error']){
                
                rcl_notice(data['error'],'error',10000);
                return false;
                
            }

            if(data['success']){

               if(data['redirect']){
                    document.location.href = data['redirect'];
                }
                
            }
            
            rcl_do_action('rcl_pay_order_user_balance',data);
        }
    });
    return false;
    
}