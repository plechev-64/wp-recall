var RclFields = {};

jQuery(function($){
    
    rcl_init_cookie();

    if(rcl_url_params['rcl-addon-options']){
        $('.wrap-recall-options').hide();
        $('#recall .title-option').removeClass('active');
        $('#options-'+rcl_url_params['rcl-addon-options']).show();
        $('#title-'+rcl_url_params['rcl-addon-options']).addClass('active');
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
    
    $('.rcl-custom-fields-box').on('click','.field-edit',function() {
        $(this).parents('.field-header').next('.field-settings').slideToggle();	
        return false;
    });
	
    $('#recall').on('click','.title-option',function(){  
        
        if($(this).hasClass('active')) return false;
        
        var titleSpan = $(this);
        
        var addonId = titleSpan.data('addon');
        var url = titleSpan.data('url');

        rcl_update_history_url(url);
        
        $('.wrap-recall-options').hide();
        $('#recall .title-option').removeClass('active');
        titleSpan.addClass('active');
        titleSpan.next('.wrap-recall-options').show();
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

    $('#rcl-notice,body').on('click','a.close-notice',function(){           
        rcl_close_notice(jQuery(this).parent());
        return false;
    });

});

function rcl_update_history_url(url){

    if(url != window.location){
        if ( history.pushState ){
            window.history.pushState(null, null, url);
        }
    }
    
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
    var oldType = boxField.attr('data-type');
    
    var multiVals = ['multiselect','checkbox','radio'];

    if(jQuery.inArray( typeField, multiVals ) >= 0 && jQuery.inArray( oldType, multiVals ) >= 0){
        
        boxField.attr('data-type',typeField);
        return;
        
    }
    
    var singleVals = ['date','time','email','number','url','dynamic','tel'];
    
    if(jQuery.inArray( typeField, singleVals ) >= 0 && jQuery.inArray( oldType, singleVals ) >= 0){
        
        boxField.attr('data-type',typeField);
        return;
        
    }
    
    var textVals = ['text','textarea'];
    
    if(jQuery.inArray( typeField, textVals ) >= 0 && jQuery.inArray( oldType, textVals ) >= 0){
        
        boxField.attr('data-type',typeField);
        return;
        
    }
    
    rcl_preloader_show(boxField);
    
    var dataString = 'action=rcl_get_custom_field_options'
            +'&type_field='+typeField
            +'&old_type='+oldType
            +'&post_type='+RclFields.type
            +'&primary_options='+RclFields.primary
            +'&default_options='+RclFields.default
            +'&slug='+slugField;
    
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: ajaxurl,
        success: function(data){
            
            rcl_preloader_hide();

            if(data['success']){
                
                boxField.find('.options-custom-field').html(data['content']);
                
                boxField.attr('data-type',typeField);
                
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
    
    var dataString = 'action=rcl_get_new_custom_field'
            +'&post_type='+RclFields.type
            +'&primary_options='+RclFields.primary
            +'&default_options='+RclFields.default;
    
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