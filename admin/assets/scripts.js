var RclFields = {};

function rcl_get_value_url_params(){
    var tmp_1 = new Array();
    var tmp_2 = new Array();
    var rcl_url_params = new Array();
    var get = location.search;
    if(get !== ''){
        tmp_1 = (get.substr(1)).split('&');
        for(var i=0; i < tmp_1.length; i++) {
            tmp_2 = tmp_1[i].split('=');
            rcl_url_params[tmp_2[0]] = tmp_2[1];
        }
    }
    
    return rcl_url_params;
}

var rcl_url_params = rcl_get_value_url_params();

jQuery(function($){

    if(rcl_url_params['options']){
        $('.wrap-recall-options').slideUp();
        $('#options-'+rcl_url_params['options']).slideDown();
        return false;
    }
    
    $('.rcl-custom-fields-box').find('.required-checkbox').each(function(){
        rcl_update_require_checkbox(this);
    });
    
    $('body').on('click','.required-checkbox',function(){
        rcl_update_require_checkbox(this);
    });
    
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
    
    $('#rcl-custom-fields-editor').on('change','.select-type-field', function (){
        rcl_get_custom_field_options(this);
    });
    
    $('#rcl-custom-fields-editor').on('click','.field-delete',function(){
        var id_item = $(this).parents('.rcl-custom-field').data('slug');
        var item = id_item;
        $(this).parents('li.rcl-custom-field').remove();
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
    
    $.cookie = function(name, value, options) {
        if (typeof value !== 'undefined') { 
                options = options || {};
                if (value === null) {
                        value = '';
                        options.expires = -1;
                }
                var expires = '';
                if (options.expires && (typeof options.expires === 'number' || options.expires.toUTCString)) {
                        var date;
                        if (typeof options.expires === 'number') {
                                date = new Date();
                                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
                        } else {
                                date = options.expires;
                        }
                        expires = '; expires=' + date.toUTCString();
                }
                var path = options.path ? '; path=' + (options.path) : '';
                var domain = options.domain ? '; domain=' + (options.domain) : '';
                var secure = options.secure ? '; secure' : '';
                document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
        } else {
                var cookieValue = null;
                if (document.cookie && document.cookie !== '') {
                        var cookies = document.cookie.split(';');
                        for (var i = 0; i < cookies.length; i++) {
                                var cookie = $.trim(cookies[i]);
                                if (cookie.substring(0, name.length + 1) === (name + '=')) {
                                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                                        break;
                                }
                        }
                }
                return cookieValue;
        }
    };

});

function rcl_add_dynamic_field(e){
    var parent = jQuery(e).parents('.dynamic-value');
    var box = parent.parent('.dynamic-values');
    var html = parent.html();
    box.append('<span class="dynamic-value">'+html+'</span>');
    jQuery(e).attr('onclick','rcl_remove_dynamic_field(this);return false;').children('i').toggleClass("fa-plus fa-minus");
    box.children('span').last().children('input').val('').focus();
}

function rcl_remove_dynamic_field(e){
    jQuery(e).parents('.dynamic-value').remove();
}

function rcl_update_require_checkbox(e){
    var name = jQuery(e).attr('name');
    var chekval = jQuery('form input[name="'+name+'"]:checked').val();
    if(chekval) jQuery('form input[name="'+name+'"]').attr('required',false);
    else jQuery('form input[name="'+name+'"]').attr('required',true);
}

function rcl_init_custom_fields(fields_type,primaryOptions,defaultOptions){
    
    RclFields = {
        'type': fields_type,
        'primary': primaryOptions,
        'default': defaultOptions
    };
    
}

function rcl_get_custom_field_options(e){
    
    var typeField = jQuery(e).val();
    var boxField = jQuery(e).parents('.rcl-custom-field');
    var slugField = boxField.data('slug');
    
    rcl_preloader_show(boxField);
    
    var dataString = 'action=rcl_get_custom_field_options&type_field='+typeField+'&post_type='+RclFields.type+'&primary_options='+RclFields.primary+'&default_options='+RclFields.default+'&slug='+slugField;
    
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: ajaxurl,
        success: function(data){
            
            rcl_preloader_hide();

            if(data['success']){
                
                boxField.find('.options-custom-field').html(data['content']);
                
            } 
            
            if(data['error']){
                rcl_notice(data['error'],'error',10000);
            }

        } 
    });
    
    return false;
    
}

function rcl_get_new_custom_field(){
    
    rcl_preloader_show(jQuery('#rcl-custom-fields-editor'));
    
    var dataString = 'action=rcl_get_new_custom_field&post_type='+RclFields.type+'&options='+RclFields.default;
    
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: ajaxurl,
        success: function(data){
            
            rcl_preloader_hide();

            if(data['success']){
                jQuery("#rcl-custom-fields-editor ul").append(data['content']);
            } 
            
            if(data['error']){
                rcl_notice(data['error'],'error',10000);
            }

        } 
    });
    
    return false;
    
}

function rcl_enable_extend_options(e){
    var extend = e.checked? 1: 0;
    jQuery.cookie('rcl_extends',extend);
    var options = jQuery('#rcl-options-form .extend-options');
    if(extend) options.show();
    else options.hide();
}

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

function rcl_preloader_show(e,size){
    
    var font_size = (size)? size: 80;
    var margin = font_size/2;
    
    var options = {
        size: font_size,
        margin: margin,
        icon: 'fa-circle-o-notch',
        class: 'rcl_preloader'
    };
    
    var style = 'style="font-size:'+options.size+'px;margin: -'+options.margin+'px 0 0 -'+options.margin+'px;"';
    
    var html = '<div class="'+options.class+'"><i class="fa '+options.icon+' fa-spin" '+style+'></i></div>';
    
    if(typeof( e ) === 'string')
        jQuery(e).after(html);
    else
        e.append(html);
}

function rcl_preloader_hide(){
    jQuery('.rcl_preloader').remove();
}

function rcl_get_option_help(elem){
    
    var help = jQuery(elem).children('.help-content');
    var title_dialog = jQuery(elem).parents('.rcl-option').children('label').text();

    var content = help.html();
    help.dialog({
        modal: true,
        dialogClass: 'rcl-help-dialog',
        resizable: false,
        minWidth: 400,
        title:title_dialog,
        open: function (e, data) {
            jQuery('.rcl-help-dialog .help-content').css({
                'display':'block',
                'min-height':'initial'
            });
        },
        close: function (e, data) {
            jQuery(elem).append('<span class="help-content">'+content+'</span>');
        }
    });
}