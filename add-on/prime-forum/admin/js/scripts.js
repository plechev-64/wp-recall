jQuery(document).ready(function($) {
    
    $('#prime-forum-manager .rcl-custom-field form').submit(function(e) {
       
        var form = jQuery(this);
    
        rcl_preloader_show(form);

        rcl_ajax({
            data: 'action=pfm_ajax_manager_update_data&' + form.serialize(), 
            success: function(result){
                
                if(result['update-page']){
                    location.reload();
                    return;
                }

                form.parents('li#field-' + result.id).find('.field-title').text(result['title']);
            }
        });
        
        return false;
        
    });
    
});

function pfm_delete_manager_item(e){

    if(!confirm('Вы уверены?')) return false;
    
    var item = jQuery(e).parents('.rcl-custom-field');

    rcl_ajax({
        data: {
            action: 'pfm_ajax_get_manager_item_delete_form',
            'item-type': item.data('type'),
            'item-id': item.data('slug')
        }, 
        success: function(data){

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

function pfm_manager_save_sort(typeObject){
    
    var fields = new Array;
    jQuery("#prime-forum-manager ." + typeObject + "-list ul").find(".rcl-custom-field").each(function(a,b){
        fields[a] = {
            "id": jQuery(this).attr("data-slug"),
            "parent": jQuery(this).attr("data-parent")
        }
    });

    var box = jQuery("#prime-forum-manager");

    rcl_preloader_show(box);

    var dataString = "action=pfm_ajax_update_sort_" + typeObject + "&sort=" + JSON.stringify(fields);

    jQuery.ajax({
        type: "POST", data: dataString, dataType: "json", url: ajaxurl,
        success: function(result){

            rcl_preloader_hide();

            if(result["error"]){
                rcl_notice(result["error"],"error",10000);
                return false;
            }

            rcl_notice(result["success"],"success",10000);

        }
    });
    
}