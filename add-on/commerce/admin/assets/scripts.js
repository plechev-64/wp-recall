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

function rcl_init_import_products(path){
    
    jQuery('#migration-manager').hide();
    
    rcl_import_products({
        'status': 'work',
        'page': 1,
        'number': 100,
        'progress': 0,
        'count': 0,
        'path': path
    });
    
}

function rcl_import_products(dataRequest){
    
    dataRequest.action = 'rcl_ajax_import_products';
    
    rcl_ajax({
        data: dataRequest,
        success: function(result){

            if(result.log)
                rcl_import_add_log(result.log);

            if(result.progress)
                rcl_import_update_progress(result.progress);

            if(result.name)
                rcl_import_update_name(result.name);

            if(result.status != 'end')
                rcl_import_products({
                    'status': result.status,
                    'page': result.page,
                    'count': result.count,
                    'progress': result.progress,
                    'number': result.number,
                    'path': result.path
                });
            
        }
    });
    
    return false;
}

function rcl_import_add_log(log){
    
    if(log.length){
        log.forEach(function(item,i){
            jQuery('#migration-log').prepend(item);
        });
    }else{
        jQuery('#migration-log').prepend(log);
    }
}

function rcl_import_update_progress(int){
    jQuery('#migration-progress').animate({
        width:int+'%'
    });
}

function rcl_import_update_name(string){
    jQuery('#migration-step').text(string);
}