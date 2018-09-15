jQuery(function($){
    jQuery('body').on('click','a.rcl-group-link',function(){
        
        var value = jQuery(this).data('value');
        
        if(jQuery('#ssi-modalContent').size()) 
            rcl_preloader_show(jQuery('#ssi-modalContent'));
        else
            rcl_preloader_show(jQuery('#rcl-group'));
        
        var dataString = 'action=rcl_get_group_link_content&group_id='+jQuery(this).data('group')+'&callback='+jQuery(this).data('callback');
        if(value) dataString += '&value='+value;
        
        rcl_ajax({
            data: dataString
        });

        return false;
        
    });

    jQuery('body').on('click','.rcl-group-callback',function(){

        var name = jQuery(this).data('name');
        
        if(name){
            var valname = jQuery(this).parents('.group-user-option').children('[name*=\''+name+'\']').val();
        }
        
        if(jQuery('#ssi-modalContent').size()) 
            rcl_preloader_show(jQuery('#ssi-modalContent'));
        else
            rcl_preloader_show(jQuery('#rcl-group'));
        
        var objectData = {
            action: 'rcl_group_callback',
            group_id: jQuery(this).data('group'),
            callback: jQuery(this).data('callback'),
            user_id: jQuery(this).parents('.group-request').data('user')
        };
        
        if(name) objectData[name] = valname;
        
        rcl_ajax({
            data: objectData, 
            success: function(data){

                if(data['success']){
                    var type = 'success';
                } else {
                    var type = 'error';
                }

                if(data['place']=='buttons') 
                    jQuery('#options-user-'+objectData.user_id).html('<span class=\''+type+'\'>'+data[type]+'</span>');
            }
        });

        return false;
        
    });
    
    jQuery('body').on('click','.group-request .apply-request',function(){
        
        var button = jQuery(this);

        rcl_preloader_show(jQuery('#ssi-modalContent'));
        
        rcl_ajax({
            data: {
                action: 'rcl_apply_group_request',
                group_id: jQuery('#rcl-group').data('group'),
                user_id: button.parent().data('user'),
                apply: button.data('request')
            }, 
            success: function(data){

                if(data['result']){
                    button.parent().html(data['result']);
                }
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