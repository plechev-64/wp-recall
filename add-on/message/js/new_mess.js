jQuery(document).ready( function() {			
	get_new_mess_rcl();	
});
	var num_request_mess=0;
	function get_new_mess_rcl(){
		num_request_mess++;
		if(num_request_mess==1){
			setTimeout('get_new_mess_rcl()', 10000);
			return false;
		}
		jQuery(function(){
			var mess = jQuery("#rcl-new-mess").html();
			if(mess) return false;
			var dataString_new_mess = 'action=get_new_outside_message_recall'+'&user_ID='+user_ID;	
			jQuery.ajax({
				type: 'POST',
				data: dataString_new_mess,
				dataType: 'json',
				//url: '/wp-admin/admin-ajax.php',
				url: '/wp-content/plugins/recall/add-on/message/ajax-request.php',
				success: function(data){
					if(data['recall']==100){
						jQuery('#rcl-new-mess').html(data['message_block']);
						jQuery("#privatemess").delay('500').animate({
							bottom: "10px"
						 }, 2000 );
                                                
						jQuery.ionSound.play('e-oh');
					}
				} 
			});
			/*jQuery.post("/wp-content/plugins/recall/add-on/message/check-message.php", function(check){
				if (check) {
					var dataString_new_mess = 'action=get_new_outside_message_recall&id_mess='+check;	
					jQuery.ajax({
						type: 'POST',
						data: dataString_new_mess,
						dataType: 'json',
						url: '/wp-admin/admin-ajax.php',
						success: function(data){
							if(data['recall']==100){
								jQuery('#rcl-new-mess').html(data['message_block']);
								jQuery("#privatemess").delay('500').animate({
									bottom: "10px"
								 }, 3000 );
								jQuery.ionSound.play('e-oh');
							}
						} 
					});
				}
			});*/
			return false;		
		});			
		if(global_update_num_mess) setTimeout('get_new_mess_rcl()', global_update_num_mess);      
	}