jQuery(document).ready(function($) {
    /*var tbframe;
	var wpds_orig_send_to_editor = window.send_to_editor;
    jQuery('#add_thumbnail_rcl').live('click',function() {
		send_to = true;
        tb_show('', '/wp-admin/media-upload.php?type=image&amp;TB_iframe=true');
        tbframe = setInterval(function() {jQuery('#TB_iframeContent').contents().find('.savesend .button').val('Использовать как миниатюру');}, 2000);
		
		window.send_to_editor = function(html) {			
			clearInterval(tbframe);
			img = jQuery(html).find('img').andSelf().filter('img');
			imgurl = img.attr('src');
			imgclass = img.attr('class');
			idimg = parseInt(imgclass.replace(/\D+/g,''));
			jQuery("#thumbnail_rcl").html('<span class="delete"></span><img width="100" height="100" src="'+imgurl+'"><input type="hidden" name="thumb" value="'+idimg+'">');
			tb_remove();
			window.send_to_editor = wpds_orig_send_to_editor;
		};
		
        return false;
    });

    jQuery('#thumbnail_rcl .delete').live('click',function() {		
		jQuery('#thumbnail_rcl').empty();
		return false;
    });*/
	
	

});

function rcl_add_editor_box(e,type,idbox,content){
	rcl_preloader_show(e);
	var dataString = 'action=rcl_add_editor_box';
	if(type) dataString += '&type='+type;
	if(idbox) dataString += '&idbox='+idbox;
	
	jQuery.ajax({
		type: 'POST', 
		data: dataString, 
		dataType: 'json', 
		url: wpurl+'wp-admin/admin-ajax.php',
		success: function(data){
			if(!data['content']){
				rcl_notice('Ошибка!','error');
				return false;
			}
			var editor = jQuery(e).parents('.rcl-public-editor');
			editor.children('.rcl-editor-content').append(data['content']);
			if(content) jQuery('#rcl-upload-'+idbox).html(content);
			rcl_preloader_hide();
			return true;
		}			
	}); 
	return false;
}
	
function rcl_delete_editor_box(e){
	var box = jQuery(e).parents('.rcl-content-box');
	var content = box.find('[name*="post_content"]').val();
	if(content){
		if(!confirm('Are you sare?')) return false;
	}

	box.remove();
	return false;
}

function rcl_delete_post(element){
	if(confirm('Действительно удалить?')){
		var post_id = jQuery(element).data('post');
		var dataString = 'action=rcl_ajax_delete_post&post_id='+post_id;
		jQuery.ajax({
			type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',
			success: function(data){
				if(data['result']==100){
					jQuery('#'+data['post_type']+'-'+post_id).remove();
					rcl_notice('Материал успешно удален!','success');
				}else{
					rcl_notice('Удаление не удалось!','error');
					return false;
				}
			}
		});
	}
}

function rcl_edit_post(element){
	var id_contayner = 'rcl-popup-content';
	var contayner = jQuery( '#'+id_contayner );
	jQuery('body > div').last().after('<div id=\''+id_contayner+'\' title=\'Быстрое редактирование\'></div>');
	contayner.dialog({
		modal: true,
		resizable: false,
		width:500,
		close: function (e, data) {
			jQuery( this ).dialog( 'close' );
			contayner.remove();
		},
		open: function (e, data){
			var post_id = jQuery(element).data('post');
			var dataString = 'action=rcl_get_edit_postdata&post_id='+post_id;
			jQuery.ajax({
				type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',
				success: function(data){
					if(data['result']==100){
						contayner.html(data['content']);
					}else{
						rcl_notice('Не удалось получить данные!','error');
						return false;
					}
				}
			});				
		},
		buttons: [{
		  text: "Сохранить",
		  icons: {
			primary: "ui-icon-disk"
		  },
		  click: function() {
			var postdata   = jQuery('#'+id_contayner+' form').serialize();
			var dataString = 'action=rcl_edit_postdata&'+postdata;

			jQuery.ajax({
			type: 'POST', data: dataString, dataType: 'json', url: wpurl+'wp-admin/admin-ajax.php',
			success: function(data){
				if(data['result']==100){
					rcl_notice('Публикация обновлена!','success');
				} else {
					rcl_notice('Ошибка редактирования!','error');
					return false;
				}
			}
			});
		  }
		},
		{
		  text: "Закрыть",
		  icons: {
			primary: "ui-icon-closethick"
		  },
		  click: function() {
			jQuery( this ).dialog( "close" );
		  }
		}]
	});
}

function rcl_init_upload_box(idbox){
	
	rcl_add_dropzone('#rcl-upload-'+idbox);
		
	var cntFiles = 0;
	
	jQuery('#rcl-upload-'+idbox+' .rcl-box-uploader').fileupload({
		dataType: 'json',
		type: 'POST',
		singleFileUploads:false,
		url: wpurl+'wp-admin/admin-ajax.php',
		formData:{action:'rcl_upload_box'},
		dropZone: jQuery('#rcl-upload-'+idbox),
		/*progressall: function (e, data) {
			var progress = parseInt(data.loaded / data.total * 100, 10);
			jQuery('.short-loader').html('<span>'+progress+'%</span>');
		},*/
		change: function (e, data){				
			rcl_preloader_show('#rcl-upload-'+idbox);
		},
		done: function (e, data) {				
			if(data.result['error']){
				rcl_notice(data.result['error'],'error');
				return false;
			}
			var id = idbox;
			jQuery.each(data.files, function (index, file) {
				if(cntFiles>=1){
					id++;
					rcl_add_editor_box('#rcl-upload-'+idbox,'image',id,data.result[cntFiles]['content']);					
				}else{
					jQuery('#rcl-upload-'+idbox).html(data.result[cntFiles]['content']);
				}
				cntFiles++;
			});
			rcl_preloader_hide();
		}
	});
	
}

function rcl_preview(e){

	var submit = jQuery(e);
	var formblock = submit.parents('form');
	var required = true;
	
	formblock.find(':required').each(function(){
		if(!jQuery(this).val()){
			jQuery(this).css('box-shadow','0px 0px 1px 1px red');
			required = false;
		}else{
			jQuery(this).css('box-shadow','none');
		}
	});
	
	if(!required){
		rcl_notice('Заполните все обязательные поля!','error');
		return false;
	}
	
	var iframe = jQuery("#contentarea_ifr").contents().find("#tinymce").html();
	if(iframe) formblock.find('textarea[name="post_content"]').html(iframe);
	submit.attr('disabled',true).val('Идет отправка, подождите...');
	var string   = formblock.serialize();
	//if(iframe) string += '&post_content='+iframe;
	var dataString = 'action=rcl_preview_post&'+string;
	jQuery.ajax({
		type: 'POST', 
		data: dataString, 
		dataType: 'json', 
		url: wpurl+'wp-admin/admin-ajax.php',
		success: function(data){
			
			if(data['error']){
				rcl_notice(data['error'],'error');
				submit.attr('disabled',false).val('Предпросмотр');
				return false;
			}
			
			if(data['content']){
				formblock.children().last('div').after(data['content']);
				var height = jQuery("#rcl-preview").height()+100;
				jQuery("#rcl-preview").parent().height(height);
				var offsetTop = jQuery("#rcl-preview").offset().top;
				jQuery('body,html').animate({scrollTop:offsetTop -50}, 500);
				submit.attr('disabled',false).val('Предпросмотр');
				return true;
			}
			
			submit.attr('disabled',false).val('Предпросмотр');
			rcl_notice('Возникла ошибка публикации','error');
			
		}
	}); 
	return false;

}

function rcl_preview_close(e){
	var preview = jQuery(e).parents('#rcl-preview');
	preview.parent().removeAttr('style');
	var offsetTop = preview.offset().top;
	jQuery('body,html').animate({scrollTop:offsetTop -50}, 500);
	preview.remove();	
}