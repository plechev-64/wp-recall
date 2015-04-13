jQuery(function(){
	var i = jQuery('#inputs_order_fields .field').size();
    jQuery('#add_order_field').click(function(){
        jQuery('<li class="menu-item menu-item-edit-active"><dl class="menu-item-bar"><dt class="menu-item-handle"><span class="item-title"><input type="text" name="order_fields_title[]" class="field" value=""/></span><span class="item-controls"><span class="item-type">Тип: <select name="type_field_'+i+'"><option value="text">Однострочное поле</option><option value="textarea">Многострочное поле</option><option value="select">Выпадающий список</option><option value="checkbox">Чекбокс</option><option value="radio">Радиокнопки</option></select></span></span></dt></dl><div class="menu-item-settings" style="display: block;"><p><input type="checkbox" name="requared_field_'+i+'" value="1"/> обязательное поле</p></div></li>').fadeIn('slow').appendTo('.order_fields');
		i++;
		return false;
    });
	
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
	jQuery('.delete-order').live('click',function(){
		if(confirm('Уверены?')){
			var idorder = jQuery(this).attr('id');
			var dataString_reg = 'action=all_delete_order_recall&idorder='+ idorder;

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
jQuery('.select_status').live('click',function(){
		var order = jQuery(this).attr('id');
		//var id_user = parseInt(id_attr.replace(/\D+/g,''));	
		var status = jQuery('#status-'+order).val();
		//alert(order+' + '+status);
		var dataString = 'action=select_status_order_recall&order='+order+'&status='+status;	
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

jQuery('.edit-price-product').live('click',function(){
			var id_post = jQuery(this).attr('product');	
			var price = jQuery('#price-product-'+id_post).attr('value');
			var dataString_count = 'action=edit_price_product_rcl&id_post='+id_post+'&price='+price;

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