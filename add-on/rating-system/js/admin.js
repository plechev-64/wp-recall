jQuery(function(){
    jQuery('.wp-list-table .edit_rayting').click(function(){
        var id_attr = jQuery(this).attr('id');
        var id_user = parseInt(id_attr.replace(/\D+/g,''));	
        var rayting = jQuery('.raytinguser-'+id_user).attr('value');
        var dataString_count = 'action=rcl_edit_rating_user&user='+id_user+'&rayting='+rayting;

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
});	