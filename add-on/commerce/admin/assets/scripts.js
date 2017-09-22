jQuery(function($){
    jQuery('.edit-price-product').click(function(){

        var id_post = jQuery(this).data('product');
        
        rcl_ajax({
            data: {
                action: 'rcl_edit_admin_price_product',
                id_post: id_post,
                price: jQuery('#price-product-'+id_post).attr('value')
            }
        });

        return false;

    });
});