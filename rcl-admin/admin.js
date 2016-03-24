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
    
    $("input[name='global[primary-color]']").wpColorPicker();

    $("#recall").find(".parent-select").each(function(){
        var id = $(this).attr('id');
        var val = $(this).val();
        $('#'+id+'-'+val).show();
    });

    $('.parent-select').change(function(){
        var id = $(this).attr('id');
        var val = $(this).val();
        $('.'+id).slideUp();
        $('#'+id+'-'+val).slideDown();		
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
        
	$('.update-message .update-add-on').click(function(){
            if($(this).hasClass("updating-message")) return false;
            var addon = $(this).data('addon');
            $('#'+addon+'-update .update-message').addClass('updating-message');
            var dataString = 'action=rcl_update_addon&addon='+addon;
            $.ajax({
                type: 'POST',
                data: dataString,
                dataType: 'json',
                url: ajaxurl,
                success: function(data){
                    if(data['success']==addon){					
                            $('#'+addon+'-update .update-message').toggleClass('updating-message updated-message').html('Успешно обновлено!');				
                    }
                    if(data['error']){
                        $('#'+addon+'-update .update-message').removeClass('updating-message');
                        alert(data['error']);
                    }
                } 
            });	  	
            return false;
	});

	function str_replace(search, replace, subject) {
		return subject.split(search).join(replace);
	}

        $('#rcl-notice,body').on('click','a.close-notice',function(){	
                $(this).parent().remove();
                return false;
        });

});

function rcl_update_options(){
    rcl_preloader_show('#rcl-options-form > div:last-child');
    var form = jQuery('#rcl-options-form');
    var dataString = 'action=rcl_update_options&'+form.serialize();
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: ajaxurl,
        success: function(data){
            rcl_preloader_hide();

            if(data['result']==1){
                var type = 'success';
            } else {
                var type = 'error';
            }

            rcl_notice(data['notice'],type);
        } 
    });	  	
    return false;
}

function rcl_notice(text,type){	
    var html = '<div class="notice-window type-'+type+'"><a href="#" class="close-notice"><i class="fa fa-times"></i></a>'+text+'</div>';	
    if(!jQuery('#rcl-notice').size()){
            jQuery('body > div').last().after('<div id="rcl-notice">'+html+'</div>');
    }else{
        if(jQuery('#rcl-notice > div').size()) jQuery('#rcl-notice > div:last-child').after(html);
        else jQuery('#rcl-notice').html(html);
    }
}

function rcl_preloader_show(e){
    jQuery(e).after('<div class="rcl_preloader"><i class="fa fa-spinner fa-pulse"></i></div>');
}

function rcl_preloader_hide(){
    jQuery('.rcl_preloader').remove();
}