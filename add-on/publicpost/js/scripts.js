jQuery(document).ready(function($) {
    var tbframe;
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
    });

});
