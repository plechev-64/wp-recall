
jQuery('.edit-price-product').click(function(){
    var id_post = jQuery(this).data('product');	
    var price = jQuery('#price-product-'+id_post).attr('value');
    var dataString_count = 'action=rcl_edit_admin_price_product&id_post='+id_post+'&price='+price;

    jQuery.ajax({
        type: 'POST',
        data: dataString_count,
        dataType: 'json',
        url: ajaxurl,
        success: function(data){
            
            if(data.error){
                alert(data.error);
            }
            
            if(data.success){
               alert(data.success);
            }
            
        } 
    });				
    return false;
});