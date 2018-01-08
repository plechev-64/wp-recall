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

    /**/
    $(".wrap-recall-options").find(".parent-option").each(function(){
        $(this).find("input,select").each(function(){
            var id = $(this).attr('id');
            var val = $(this).val();
            $('.'+id+'-'+val).show();
        });
    });

    $('.parent-option select, .parent-option input').change(function(){
        var id = $(this).attr('id');
        $( '.parent-' + id ).hide();
        $( '.' + id + '-' + $(this).val() ).show();
    });
    /**/

    $("#recall").find(".parent-select").each(function(){
        var id = $(this).attr('id');
        var val = $(this).val();
        $('.child-select.'+id+'-'+val).show();
    });

    $('.wrap-recall-options .parent-select').change(function(){
        var id = $(this).attr('id');
        var val = $(this).val();
        $('.wrap-recall-options .child-select.'+id).slideUp();
        $('.wrap-recall-options .child-select.'+id+'-'+val).slideDown();		
    });
    
    $('#rcl-custom-fields-editor').on('change','.select-type-field', function (){
        rcl_get_custom_field_options(this);
    });
    
    $('#rcl-custom-fields-editor').on('click','.field-delete',function(){
        var field = $(this).parents('.rcl-custom-field');
        
        if(field.hasClass('must-meta-delete')){
            
            if(confirm($('#field-delete-confirm').text())){
                var itemID = field.data('slug');
                var val = $('#rcl-deleted-fields').val();
                if(val) itemID += ',';
                itemID += val;
                $('#rcl-deleted-fields').val(itemID);
            }
            
        }

        field.remove();
        
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
                    $('#'+addon+'-update .update-message').toggleClass('updating-message updated-message').html('Успешно обновлен!');				
                }
                if(data['error']){
                    
                    $('#'+addon+'-update .update-message').removeClass('updating-message');
                    
                    var ssiOptions = {
                        className: 'rcl-dialog-tab rcl-update-error',
                        sizeClass: 'auto',
                        title: Rcl.local.error,
                        buttons: [{
                            label: Rcl.local.close,
                            closeAfter: true
                        }],
                        content: data['error']
                    };

                    ssi_modal.show(ssiOptions);

                }
            } 
        });	  	
        return false;
    });

    $('#rcl-notice,body').on('click','a.close-notice',function(){           
        rcl_close_notice(jQuery(this).parent());
        return false;
    });
    
    jQuery('body').on('click','#rcl-addon-details .sections-menu .no-active-section',function(){
        var li = jQuery(this);
        
        li.parent().find('.active-section').each(function(){
            var tab = jQuery(this).data('tab');
            jQuery(this).removeClass('active-section');
            jQuery(this).addClass('no-active-section');
            
            var box = jQuery('#rcl-addon-details .section-content [data-box="'+tab+'"]');
            
            box.removeClass('active-box');
            box.addClass('no-active-box');
        });
        
        var tab = li.data('tab');
        
        li.removeClass('no-active-section');
        li.addClass('active-section');
        
        var box = jQuery('#rcl-addon-details .section-content [data-box="'+tab+'"]');
        
        box.removeClass('no-active-box');
        box.addClass('active-box');
        
        return false;
        
    });

});

function rcl_get_details_addon(props,e){
    
    rcl_preloader_show(jQuery(e).parents('.addon-box'));
    
    props.action = 'rcl_get_details_addon';
    
    rcl_ajax({
        data: props, 
        success: function(data){

            ssi_modal.show({
                className: 'rcl-dialog-tab rcl-addon-details',
                sizeClass: 'medium',
                title: data.title,
                buttons: [{
                    label: Rcl.local.close,
                    closeAfter: true
                }],
                content: data.content
            });

        }
    });
    
    return false;
    
}

function rcl_update_addon(props,e){
    
    var button = jQuery(e);
    
    if(button.hasClass("updating-message") || button.hasClass("updated-message")) return false;

    button.addClass('updating-message');
    
    var dataString = 'action=rcl_update_addon&addon='+props.slug;
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: ajaxurl,
        success: function(data){
            if(data['success']==props.slug){					
                button.addClass('button-disabled').toggleClass('updating-message updated-message').html('Обновлен!');				
            }
            if(data['error']){

                button.removeClass('updating-message');

                var ssiOptions = {
                    className: 'rcl-dialog-tab rcl-update-error',
                    sizeClass: 'auto',
                    title: Rcl.local.error,
                    buttons: [{
                        label: Rcl.local.close,
                        closeAfter: true
                    }],
                    content: data['error']
                };

                ssi_modal.show(ssiOptions);

            }
        } 
    });	  	
    return false;
    
}

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
    
    var sliderVals = ['runner','range'];
    
    if(jQuery.inArray( typeField, sliderVals ) >= 0 && jQuery.inArray( oldType, sliderVals ) >= 0){
        
        boxField.attr('data-type',typeField);
        return;
        
    }
    
    rcl_preloader_show(boxField);
    
    rcl_ajax({
        data: {
            action: 'rcl_get_custom_field_options',
            type_field: typeField,
            old_type: oldType,
            post_type: RclFields.type,
            primary_options: RclFields.primary,
            default_options: RclFields.default,
            slug: boxField.data('slug')
        }, 
        success: function(data){

            if(data['content']){

                boxField.find('.options-custom-field').html(data['content']);

                boxField.attr('data-type',typeField);

            } 

        }
    });
    
    return false;
    
}

function rcl_get_new_custom_field(){
    
    rcl_preloader_show(jQuery('#rcl-custom-fields-editor'));
    
    rcl_ajax({
        data: {
            action: 'rcl_get_new_custom_field',
            post_type: RclFields.type,
            primary_options: RclFields.primary,
            default_options: RclFields.default
        }, 
        success: function(data){

            if(data['content']){
                jQuery("#rcl-custom-fields-editor ul").append(data['content']);
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

    rcl_ajax({
        data: 'action=rcl_update_options&' + jQuery('#rcl-options-form').serialize()
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