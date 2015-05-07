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

jQuery(document).ready( function() {
    

    jQuery("#recall").find(".parent-select").each(function(){
        var name = jQuery(this).attr('name');
        var val = jQuery(this).val();
        jQuery('#'+name+'-'+val).show();
    });

    jQuery('.parent-select').change(function(){
        var name = jQuery(this).attr('name');
        var val = jQuery(this).val();
        jQuery('.'+name).slideUp();
        jQuery('#'+name+'-'+val).slideDown();		
    });

    jQuery('.profilefield-item-edit').click(function() {
        var id_button = jQuery(this).attr('id');
        var id_item = str_replace('edit-','settings-',id_button);	
        jQuery('#'+id_item).slideToggle();
        return false;
    });
    
    jQuery('.field-delete').click(function() {
        var id_item = jQuery(this).attr('id');
        var item = id_item;
        jQuery('#item-'+id_item).remove();
        var val = jQuery('#deleted-fields').val();
        if(val) item += ',';
        item += val;
        jQuery('#deleted-fields').val(item);
        return false;
    });
        
    jQuery('body').on('change','.typefield', function (){
        var val = jQuery(this).val();
        var id = jQuery(this).parent().parent().parent().parent().attr('id');
        if(val!='select'&&val!='radio'&&val!='checkbox'){
                jQuery('#'+id+' .field-select').attr('disabled',true);
        }else{ 
            if(jQuery('#'+id+' .field-select').size()){
                jQuery('#'+id+' .field-select').attr('disabled',false);
            }else{
                jQuery('#'+id+' .place-sel').prepend('перечень вариантов разделять знаком #<br><textarea rows="1" style="height:50px" class="field-select" name="field[field_select][]"></textarea>');
            }

        }
    });    
    
    jQuery('#add_public_field').live('click',function() {
        var html = jQuery(".public_fields ul li").last().html();
        jQuery(".public_fields ul").append('<li class="menu-item menu-item-edit-active">'+html+'</li>');
        return false;
    });
	
	jQuery('#recall .title-option').click(function(){  
                if(jQuery(this).hasClass('active')) return false;
		jQuery('.wrap-recall-options').hide();
                jQuery('#recall .title-option').removeClass('active');
                jQuery(this).addClass('active');
		jQuery(this).next('.wrap-recall-options').show();
		return false;
	});
	
	if(get_param['options']){
		jQuery('.wrap-recall-options').slideUp();
		jQuery('#options-'+get_param['options']).slideDown();
		return false;
	}
	
	jQuery('.type_field').live('change',function(){
		var type = jQuery(this).val();
		var slug = jQuery(this).attr('id');
		if(type==='text'||type==='textarea'){
			jQuery('#content-'+slug+' textarea').remove();
			return false;
		}
		if(jQuery('#content-'+slug+' textarea').attr('name')) return false;				
		var dataString = 'action=rcl_data_type_profile_field&type='+type+'&slug='+slug;

		jQuery.ajax({
			type: 'POST',
			data: dataString,
			dataType: 'json',
			url: ajaxurl,
			success: function(data){
				if(data['result']===100){					
					jQuery('#content-'+slug+' .first-chek').before(data['content']);				
				}else{
					alert('Ошибка!');
				}
			} 
		});	  	
		return false;
	});
	
	
	function str_replace(search, replace, subject) {
		return subject.split(search).join(replace);
	}
});