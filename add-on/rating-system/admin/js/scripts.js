jQuery(function(){
    jQuery('.wp-list-table .edit_rayting').click(function(){

        var id_user = parseInt(jQuery(this).attr('id').replace(/\D+/g,''));	
        
        rcl_ajax({
            data: {
                action: 'rcl_edit_rating_user',
                user: id_user,
                rayting: jQuery('.raytinguser-'+id_user).attr('value')
            }
        });
				
        return false;
    });
});	