jQuery(function(){
/*************************************************
Пополняем личный счет пользователя в админке
*************************************************/
	jQuery('.edit_balance').live('click',function(){
			var id_attr = jQuery(this).attr('id');
			var id_user = parseInt(id_attr.replace(/\D+/g,''));	
			var balance = jQuery('.balanceuser-'+id_user).attr('value');
			var dataString_count = 'action=edit_balance_user_recall&user='+id_user+'&balance='+balance;

			jQuery.ajax({
				type: 'POST',
				data: dataString_count,
				dataType: 'json',
				url: ajaxurl,
				success: function(data){
					if(data['otvet']==100){
						//jQuery('.balance-'+data['user']).html(data['balance']);	
						//jQuery('.balanceuser-'+data['user']).val('');
                                                alert('Баланс изменен');
					} else {
					   alert('Ошибка проверки данных.');
					}
				} 
			});				
			return false;
	});
});	