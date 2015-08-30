jQuery(function(){
	
    jQuery('#add-custom-price').click(function(){
        jQuery('<p>Заголовок: <input type="text" class="title-custom-price" name="title-custom-price[]" value=""> Цена: <input type="text" class="custom-price" name="custom-price[]" value=""></p>').fadeIn('slow').appendTo('#custom-price-list');
		return false;
    });
	jQuery('.delete-price').click(function() {
		var id_item = jQuery(this).attr('id');
		jQuery('#custom-price-'+id_item).remove();
		return false;
	});
	
});

/*************************************************
Удаляем заказ пользователя воопще)
*************************************************/
	jQuery('.delete-order').click(function(){
		if(confirm('Уверены?')){
			var idorder = jQuery(this).attr('id');
			var dataString_reg = 'action=rcl_all_delete_order&idorder='+ idorder;

			jQuery.ajax({
			type: 'POST',
			data: dataString_reg,
			dataType: 'json',
			url: ajaxurl,
			success: function(data){
				if(data['otvet']==100){
					jQuery('#row-'+data['idorder']).remove();
				}else{
					alert('Ошибка при удалении заказа!');
				}
			} 
			});	  	
			return false;
		}
	});
/*************************************************
Меняем статус заказа в админке
*************************************************/	
jQuery('.select_status').click(function(){
    var order = jQuery(this).attr('id');
    //var id_user = parseInt(id_attr.replace(/\D+/g,''));	
    var status = jQuery('#status-'+order).val();
    //alert(order+' + '+status);
    var dataString = 'action=rcl_edit_order_status&order='+order+'&status='+status;	
    jQuery.ajax({
    type: 'POST',
    data: dataString,
    dataType: 'json',
    url: ajaxurl,
        success: function(data){
            if(data['otvet']==100){
                    jQuery('.change-'+data['order']).empty().html(data['status']);				
            } else {
               alert('Смена статуса не удалась.');
            }
        } 
    });	  	
    return false;
});

jQuery('.edit-price-product').click(function(){
    var id_post = jQuery(this).attr('product');	
    var price = jQuery('#price-product-'+id_post).attr('value');
    var dataString_count = 'action=rcl_edit_price_product&id_post='+id_post+'&price='+price;

    jQuery.ajax({
        type: 'POST',
        data: dataString_count,
        dataType: 'json',
        url: ajaxurl,
        success: function(data){
            if(data['otvet']==100){
                    alert('Данные сохранены!');
            } else {
               alert('Ошибка!');
            }
        } 
    });				
    return false;
});