jQuery(function(){
    
    jQuery('#rcl-cart').on('click','.confirm_order',function(){
        
        var dataString = 'action=rcl_confirm_order&'+jQuery('#rcl-cart').serialize();
        dataString += '&ajax_nonce='+Rcl.nonce;
        rcl_preloader_show('#cart-form > div');
        
        jQuery.ajax({
            type: 'POST',
            data: dataString,
            dataType: 'json',
            url: Rcl.ajaxurl,
            success: function(data){
                rcl_preloader_hide();

                if(data.errors){
                    jQuery.each(data.errors, function( index, value ) {
                        rcl_notice(value,'error',10000);
                    });

                    if(data['code']==10){
                        jQuery('#rcl-cart-notice').html(data['html']);
                    }

                    return false;
                }

                //jQuery('#rcl-cart').submit();

                jQuery('#rcl-cart-notice').html(data['success']);
                jQuery('#rcl-cart .rcl-cart-fields').remove();
                jQuery('#rcl-cart .add_remove').empty();

            }
        });
        
        return false;
        
    });
    
});

/* Удаляем заказ пользователя в корзину */
function rcl_trash_order(e,data){       
    jQuery('#manage-order, table.order-data').remove();
    jQuery('#rcl-cart-notice').html(data.result);
}

/* Увеличиваем количество товара в большой корзине */
function rcl_cart_add_product(e){
    rcl_preloader_show('#cart-form > table');
    var id_post = jQuery(e).parent().data('product');
    var number = 1;
    var dataString = 'action=rcl_add_cart&id_post='+ id_post+'&number='+ number;
    dataString += '&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
    type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
    success: function(data){
        rcl_preloader_hide();
        
        if(data['error']){
            rcl_notice(data['error'],'error',10000);
            return false;
        }
        
        if(data['recall']==100){
            jQuery('.cart-summa').text(data['data_sumprice']);
            jQuery('#product-'+data['id_prod']+' .sumprice-product').text(data['sumproduct']);
            jQuery('#product-'+data['id_prod']+' .number-product').text(data['num_product']);
            jQuery('.cart-numbers').text(data['allprod']);
        }
        
    }
    });
    return false;
}

/* Уменьшаем товар количество товара в большой корзине */
function rcl_cart_remove_product(e){
    rcl_preloader_show('#cart-form > table');
    var id_post = jQuery(e).parent().data('product');
    var number = 1;
    if(number>0){
        var dataString = 'action=rcl_remove_product_cart&id_post='+ id_post+'&number='+ number;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                rcl_preloader_hide();

                if(data['error']){
                    rcl_notice(data['error'],'error',10000);
                    return false;
                }

                if(data['recall']==100){
                    jQuery('.cart-summa').text(data['data_sumprice']);
                    jQuery('#product-'+data['id_prod']+' .sumprice-product').text(data['sumproduct']);

                    var numprod = data['num_product'];
                    if(numprod>0){
                        jQuery('#product-'+data['id_prod']+' .number-product').text(data['num_product']);
                    }else{
                        var numberproduct = 0;
                        jQuery('#product-'+data['id_prod']).remove();
                    }
                    if(data['allprod']==0) jQuery('.confirm').remove();

                    jQuery('.cart-numbers').text(data['allprod']);
                }

            }
        });
    }
    return false;
}

/* Кладем товар в малую корзину */
function rcl_add_cart(e){            
    var id_post = jQuery(e).data('product');
    rcl_preloader_show('#product-'+id_post+' > div');
    var id_custom_prod = jQuery(e).attr('name');
    if(id_custom_prod){
        var number = jQuery('#number-custom-product-'+id_custom_prod).val();
    }else{
        var number = jQuery('#number_product').val();
    }
    var dataString = 'action=rcl_add_minicart&id_post='+id_post+'&number='+number+'&custom='+id_custom_prod;
    dataString += '&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
        type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
        success: function(data){
            rcl_preloader_hide();

            if(data['error']){
                rcl_notice(data['error'],'error',10000);
                return false;
            }

            if(data['recall']==100){
                rcl_close_notice('#rcl-notice > div');
                jQuery('.empty-basket').replaceWith(data['empty-content']);
                jQuery('.cart-summa').html(data['data_sumprice']);
                jQuery('.cart-numbers').html(data['allprod']);
                rcl_notice(data['success'],'success');
            }

        }
    });
    return false;
}

function rcl_pay_order_private_account(e){
    var idorder = jQuery(e).data('order');
    var dataString = 'action=rcl_pay_order_private_account&idorder='+ idorder;
    dataString += '&ajax_nonce='+Rcl.nonce;
    
    rcl_preloader_show('#cart-form > table');
    
    jQuery.ajax({
        type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
        success: function(data){
            rcl_preloader_hide();
            
            if(data['error']){
                rcl_notice(data['error'],'error',10000);
                return false;
            }
            
            if(data['otvet']==100){
                jQuery('.order_block').find('.pay_order').each(function() {
                        if(jQuery(e).attr('name')==data['idorder']) jQuery(e).remove();
                });
                jQuery('#rcl-cart-notice').html(data['recall']);
                jQuery('.usercount').html(data['count']);
                jQuery('.order-'+data['idorder']+' .remove_order').remove();
                jQuery('#manage-order').remove();
            }
        }
    });
    return false;
}