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
			var dataString_new_mess = 'action=get_new_outside_message'+'&user_ID='+user_ID;	
			jQuery.ajax({
				type: 'POST',
				data: dataString_new_mess,
				dataType: 'json',
				url: wpurl+'/wp-admin/admin-ajax.php',				
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
			return false;		
		});			
		if(global_update_num_mess) setTimeout('get_new_mess_rcl()', global_update_num_mess);      
	}