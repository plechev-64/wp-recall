function rcl_get_value_url_params(){
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
    
    return get_param;
}

var rcl_url_params = rcl_get_value_url_params();

jQuery(function($){

    if(rcl_url_params['options']){
        $('.wrap-recall-options').slideUp();
        $('#options-'+rcl_url_params['options']).slideDown();
        return false;
    }
    
    $("input[name='global[primary-color]']").wpColorPicker({
        defaultColor: '#4c8cbd'
    });

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
    
    $("#rcl-custom-fields-editor").on('click','.add-field-button',function() {
        var html = $("#rcl-custom-fields-editor ul li").last().html();
        $("#rcl-custom-fields-editor ul").append('<li class="rcl-custom-field new-field">'+html+'</li>');
        return false;
    });
    
    $('#rcl-custom-fields-editor').on('change','.typefield', function (){
        var val = $(this).val();
        var parent = $(this).parents('.rcl-custom-field');
        var textarea = parent.find('.field-select');
        
        var option_box = parent.find('.secondary-settings');
        var placeholder = parent.find('.placeholder-field');
        
        if(val!='select'&&val!='multiselect'&&val!='radio'&&val!='checkbox'&&val!='agree'&&val!='file'){
            textarea.attr('disabled',true);
            if(!placeholder.size())
                option_box.prepend('<div class="field-option placeholder-field"><input type="text" name="field[placeholder][]"><br>placeholder</div>');
        }else{ 
            
            parent.find('.placeholder-field').remove();
            
            if(textarea.size()){              
                textarea.attr('disabled',false);
            }else{
                option_box.prepend('<span class="textarea-notice"></span><textarea rows="1" style="height:50px" class="field-select" name="field[field_select][]"></textarea>');
            }

            var notice_box = option_box.children('.textarea-notice');
            
            if(val=='agree'){
                notice_box.text('Укажите текст ссылки на соглашение');
            }else if(val=='file'){
                notice_box.text('Разрешенные типы файлов разделяются запятой, например: pdf, zip, jpg');
            }else{
                notice_box.text('Перечень вариантов разделять знаком #');
            }

        }
    });
    
    $('#rcl-custom-fields-editor .field-delete').click(function(){
        var id_item = $(this).parents('.rcl-custom-field').data('slug');
        var item = id_item;
        $('#field-'+id_item).remove();
        var val = $('#rcl-deleted-fields').val();
        if(val) item += ',';
        item += val;
        $('#rcl-deleted-fields').val(item);
        return false;
    });
    
    $('#rcl-custom-fields-editor').on('click','.field-edit',function() {
        $(this).parents('.rcl-custom-field').find('.field-settings').slideToggle();	
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
        rcl_close_notice(jQuery(this).parent());
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

            rcl_notice(data['notice'],type,3000);
        } 
    });	  	
    return false;
}

function rcl_rand( min, max ) {
    if( max ) {
            return Math.floor(Math.random() * (max - min + 1)) + min;
    } else {
            return Math.floor(Math.random() * (min + 1));
    }
}

function rcl_close_notice(e){
    jQuery(e).animate({
        opacity: 0,
        height: 'hide'
    }, 300);
}

function rcl_notice(text,type,time_close){
        
    time_close = time_close || false;

    var notice_id = rcl_rand(1, 1000);

    var html = '<div id="notice-'+notice_id+'" class="notice-window type-'+type+'"><a href="#" class="close-notice"><i class="fa fa-times"></i></a>'+text+'</div>';	
    if(!jQuery('#rcl-notice').size()){
            jQuery('body > div').last().after('<div id="rcl-notice">'+html+'</div>');
    }else{
            if(jQuery('#rcl-notice > div').size()) jQuery('#rcl-notice > div:last-child').after(html);
            else jQuery('#rcl-notice').html(html);
    }

    if(time_close){
        setTimeout(function () {
            rcl_close_notice('#rcl-notice #notice-'+notice_id)
        }, time_close);
    }
}

function rcl_preloader_show(e){
    jQuery(e).after('<div class="rcl_preloader"><i class="fa fa-spinner fa-pulse"></i></div>');
}

function rcl_preloader_hide(){
    jQuery('.rcl_preloader').remove();
}