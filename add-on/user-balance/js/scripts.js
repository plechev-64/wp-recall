jQuery(function($){   
    /* Пополняем личный счет пользователя */
    jQuery('body').on('click','.rcl-form-add-user-count .rcl-get-form-pay',function(){
        
        var id = jQuery(this).parents('.rcl-form-add-user-count').attr('id');
        
        rcl_preloader_show('#'+id+' .rcl-form-input');

        var dataString = 'action=rcl_add_count_user&' + jQuery('#'+id+' form').serialize();
        
        rcl_ajax({
            data: dataString,
            success: function(data){

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

    rcl_preloader_show(jQuery('.rcl-payment-buttons'));
    
    rcl_ajax({
        data: {
            action: 'rcl_pay_order_user_balance',
            pay_id: data.pay_id,
            pay_type: data.pay_type,
            pay_summ: data.pay_summ,
            description: data.description,
            baggage_data: JSON.stringify(data.baggage_data)
        }
    });

    return false;
    
}