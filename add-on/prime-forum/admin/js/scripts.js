jQuery(document).ready(function($) {
    
    $('#prime-forum-manager .rcl-custom-field form').submit(function(e) {
       
        var form = jQuery(this);
    
        rcl_preloader_show(form);

        var dataString = 'action=pfm_ajax_manager_update_data&' + form.serialize();

        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: ajaxurl,
            success: function(result){
                
                if(result['update-page']){
                    location.reload();
                    return;
                }

                rcl_preloader_hide();

                if(result['error']){
                    rcl_notice(result['error'],'error',10000);
                    return false;
                }

                rcl_notice(result['success'],'success',10000);
                
                form.parents('li#field-' + result.id).find('.field-title').text(result['title']);
            }
        });
        
        return false;
        
    });
    
    /*$("#pfm-forums-list").hover(function() {
        
        var maxHeight = 600;
    
         var $container = $(this),
             $list = $container.find("ul"),
             height = $list.height() * 1.1,       // Снизу должно быть достаточно места
             multiplier = height / maxHeight;     // Для ускорения перемещения, если список очень длинный
        
        // Сохраняем оригинальное значение высоты контейнера, чтобы восстановить его 
        $container.data("origHeight", $container.height());
        
        // Выпадающее меню появляется точно под соответствующим пунктом родительского списка
        $list
            .show()
            .css({
                paddingTop: $container.data("origHeight")
            });
        
        // Не делаем никаких анимаций, если список короче максимального значения
        if (multiplier > 1) {
            $container
                .css({
                    height: maxHeight,
                    overflow: "hidden"
                })
                .mousemove(function(e) {
                    var offset = $container.offset();
                    var relativeY = ((e.pageY - offset.top) * multiplier) - ($container.data("origHeight") * multiplier);
                    if (relativeY > $container.data("origHeight")) {
                        $list.css("top", -relativeY + $container.data("origHeight"));
                    };
                });
        }
        
    }, function() {
    
        var $el = $(this);
        
        // Устанавливаем оригинальные настройки
        $el
            .height($(this).data("origHeight"))
            .find("ul")
            .css({ 
                top: 0
            })
            .end();
    
    });*/
    
});

function pfm_delete_manager_item(e){

    if(!confirm('Вы уверены?')) return false;
    
    var item = jQuery(e).parents('.rcl-custom-field');
    
    var itemType = item.data('type');
    var itemID = item.data('slug');
    
    var dataString = 'action=pfm_ajax_get_manager_item_delete_form&item-type='+itemType+'&item-id='+itemID;
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: ajaxurl,
        success: function(data){
            
            rcl_preloader_hide();

            if(data['error']){
                rcl_notice(data['error'],'error',3000);
            } 
            
            jQuery('body').append(data['form']);

            jQuery('#manager-deleted-form').dialog({
                modal: true,
                dialogClass: 'rcl-help-dialog',
                resizable: false,
                minWidth: 400,
                title: 'Форма удаления',
                open: function (e, data) {
                   
                },
                close: function (e, data) {
                    jQuery('#manager-deleted-form').remove();
                }
            });

        } 
    });	  	
    return false;
}