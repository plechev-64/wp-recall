	var tmp_1 = new Array();
	var tmp_2 = new Array();
	var get_param = new Array();
	var get = location.search;
	if(get !== ''){
	  tmp_1 = (get.substr(1)).split('&');
	  for(var i=0; i < tmp_1.length; i++) {
	  tmp_2 = tmp_1[i].split('=');
	  get_param[tmp_2[0]] = tmp_2[1];
	  }
	}

jQuery(function($){
    

    $("#recall").find(".parent-select").each(function(){
        var name = $(this).attr('name');
        var val = $(this).val();
        $('#'+name+'-'+val).show();
    });

    $('.parent-select').change(function(){
        var name = $(this).attr('name');
        var val = $(this).val();
        $('.'+name).slideUp();
        $('#'+name+'-'+val).slideDown();		
    });

    $('.profilefield-item-edit').click(function() {
        var id_button = $(this).attr('id');
        var id_item = str_replace('edit-','settings-',id_button);	
        $('#'+id_item).slideToggle();
        return false;
    });
    
    $('.field-delete').click(function() {
        var id_item = $(this).attr('id');
        var item = id_item;
        $('#item-'+id_item).remove();
        var val = $('#deleted-fields').val();
        if(val) item += ',';
        item += val;
        $('#deleted-fields').val(item);
        return false;
    });
        
    $('body').on('change','.typefield', function (){
        var val = $(this).val();
        var id = $(this).parent().parent().parent().parent().attr('id');
        if(val!='select'&&val!='radio'&&val!='checkbox'&&val!='agree'&&val!='file'){
                $('#'+id+' .field-select').attr('disabled',true);
        }else{ 
            if($('#'+id+' .field-select').size()){
                $('#'+id+' .field-select').attr('disabled',false);
            }else{
                $('#'+id+' .place-sel').prepend('перечень вариантов разделять знаком #<br><textarea rows="1" style="height:50px" class="field-select" name="field[field_select][]"></textarea>');
            }

        }
    });    
    
    $('#add_public_field').on('click','input',function() {
        var html = $(".public_fields ul li").last().html();
        $(".public_fields ul").append('<li class="menu-item menu-item-edit-active">'+html+'</li>');
        return false;
    });
	
	$('#recall .title-option').click(function(){  
                if($(this).hasClass('active')) return false;
		$('.wrap-recall-options').hide();
                $('#recall .title-option').removeClass('active');
                $(this).addClass('active');
		$(this).next('.wrap-recall-options').show();
		return false;
	});
	
	if(get_param['options']){
		$('.wrap-recall-options').slideUp();
		$('#options-'+get_param['options']).slideDown();
		return false;
	}
	
	/*$('.type_field').live('change',function(){
		var type = $(this).val();
		var slug = $(this).attr('id');
		if(type==='text'||type==='textarea'){
			$('#content-'+slug+' textarea').remove();
			return false;
		}
		if($('#content-'+slug+' textarea').attr('name')) return false;				
		var dataString = 'action=rcl_data_type_profile_field&type='+type+'&slug='+slug;

		$.ajax({
			type: 'POST',
			data: dataString,
			dataType: 'json',
			url: ajaxurl,
			success: function(data){
				if(data['result']===100){					
					$('#content-'+slug+' .first-chek').before(data['content']);				
				}else{
					alert('Ошибка!');
				}
			} 
		});	  	
		return false;
	});*/
        
	$('.update-message .update-add-on').click(function(){
            var addon = $(this).data('addon');				
            var dataString = 'action=rcl_update_addon&addon='+addon;

            $.ajax({
                type: 'POST',
                data: dataString,
                dataType: 'json',
                url: ajaxurl,
                success: function(data){
                    if(data['success']==addon){					
                            $('#'+addon+'-update .update-message').html('Успешно обновлено!');				
                    }
                    if(data['error']){
                        alert(data['error']);
                    }
                } 
            });	  	
            return false;
	});
	
	function str_replace(search, replace, subject) {
		return subject.split(search).join(replace);
	}
});