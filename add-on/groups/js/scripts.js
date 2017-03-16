jQuery(function($){
    jQuery('body').on('click','a.rcl-group-link',function(){
        var callback = jQuery(this).data('callback');
        var group_id = jQuery(this).data('group');
        var value = jQuery(this).data('value');

        var dataString = 'action=rcl_get_group_link_content&group_id='+group_id+'&callback='+callback;
        if(value) dataString += '&value='+value;
        dataString += '&ajax_nonce='+Rcl.nonce;
        
        if(jQuery('#ssi-modalContent').size()) 
            rcl_preloader_show(jQuery('#ssi-modalContent'));
        else
            rcl_preloader_show(jQuery('#rcl-group'));
        
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data){
                    
                    if(jQuery('#ssi-modalContent').size()) ssi_modal.close();
                    
                    ssi_modal.show({
                        className: 'rcl-dialog-tab group-dialog',
                        sizeClass: 'small',
                        buttons: [{
                            label: Rcl.local.close,
                            closeAfter: true
                        }],
                        content: data
                    });

                } else {
                    rcl_notice('Error','error',10000);
                }
                rcl_preloader_hide();
            }
        });
        return false;
    });

    jQuery('body').on('click','.rcl-group-callback',function(){
        var callback = jQuery(this).data('callback');
        var group_id = jQuery(this).data('group');
        var name = jQuery(this).data('name');
        if(name){
            var valname = jQuery(this).parents('.group-user-option').children('[name*=\''+name+'\']').val();
        }
        var user_id = jQuery(this).parents('.group-request').data('user');
        var dataString = 'action=rcl_group_callback&group_id='+group_id+'&callback='+callback+'&user_id='+user_id;
        dataString += '&ajax_nonce='+Rcl.nonce;
        if(name) dataString += '&'+name+'='+valname;
        
        if(jQuery('#ssi-modalContent').size()) 
            rcl_preloader_show(jQuery('#ssi-modalContent'));
        else
            rcl_preloader_show(jQuery('#rcl-group'));
        
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                
                if(data['success']){
                    var type = 'success';
                } else {
                    var type = 'error';
                }

                if(data['place']=='notice') 
                    rcl_notice(data[type],type,10000);
                
                if(data['place']=='buttons') 
                    jQuery('#options-user-'+user_id).html('<span class=\''+type+'\'>'+data[type]+'</span>');

                rcl_preloader_hide();
            }
        });
        return false;
    });
    
    jQuery('body').on('click','.group-request .apply-request',function(){
        var button = jQuery(this);
        var user_id = button.parent().data('user');
        var apply = button.data('request');
        var group_id = jQuery('#rcl-group').data('group');
        var dataString = 'action=rcl_apply_group_request&group_id='+group_id+'&user_id='+user_id+'&apply='+apply;
        dataString += '&ajax_nonce='+Rcl.nonce;
        
        rcl_preloader_show(jQuery('#ssi-modalContent'));
        
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data){

                    button.parent().html(data['result']);

                } else {
                    rcl_notice('Error','error');
                }
                rcl_preloader_hide();
            }
        });
        return false;
    });

    var func = function(e){

        var rclGroup = jQuery('#rcl-group');

        /* если верстка шаблона single-group.php не содержит эти классы - останавливаем:*/
        if (!rclGroup.children('.group-sidebar').length || !rclGroup.children('.group-wrapper').length) return false; 

        var sidebar = jQuery('.group-sidebar');

        var hUpSidebar = sidebar.offset().top; /* высота до сайтбара*/
        var hSidebar = sidebar.height(); /* высота сайтбара*/
        var hWork = hUpSidebar + hSidebar - 30; /* общая высота при которой будет работать скрипт*/
        var scrolled = jQuery(this).scrollTop(); /* позиция окна от верха*/
        var hBlock = jQuery('#rcl-group').height(); /* высота всего блока*/


        if (hBlock < (hWork + 55)) return false; /* если в группе нет контента - не выполняем. 55 - это отступ на group-admin-panel*/


        if( scrolled > hWork && !jQuery('.group-wrapper').hasClass('collapsexxx') ) {			/* вниз, расширение блока*/
            jQuery('.group-wrapper').addClass('collapsexxx');
            jQuery('.group-sidebar').addClass('hideexxx');
            sidebar.css({'height' : hSidebar,'width':'0','min-width':'0','padding':'0'});
        }
        if( scrolled < (hWork - 200) && jQuery('.group-wrapper').hasClass('collapsexxx') ) {		/* вверх, сужение блока   */
            jQuery('.group-wrapper').removeClass('collapsexxx');
            jQuery('.group-sidebar').removeClass('hideexxx');
            sidebar.css({'width' : '','min-width':'','padding':''});
        }

    };
    jQuery(window).scroll(func).resize(func);

});

function rcl_more_view(e){
    var link = jQuery(e);
    var icon = link.children('i');
    link.parent().children('div').slideToggle();
    icon.toggleClass('fa-plus-square-o fa-minus-square-o');
}