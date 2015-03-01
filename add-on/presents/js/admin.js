jQuery(document).ready(function($){
    var tbframe_interval;
	var value;
	var button;
	var i=0;
    $('.add_present_rcl').live('click',function() {
		button = $(this);
        tb_show('', 'media-upload.php?type=image&TB_iframe=true&width=800');
        tbframe_interval = setInterval(function() {
			$('#TB_iframeContent').contents().find('.subsubsub').remove();
			$('#TB_iframeContent').contents().find('.savesend .button').val('Добавить подарок');
			value = $('#TB_iframeContent').contents().find('.open .url .field .urlfield').val();		
		}, 2000);

        return false;
    });

    window.send_to_editor = function(html) {	
        clearInterval(tbframe_interval);
		button.parent().children('.present_url').val(value);
        tb_remove();		
    };
	
	$('.add_button_present').live('click',function() {
		var html = $(this).parent().html();
		$(this).remove();
		$("#presents_manage_rcl").append('<div class="clear">'+html+'</div>');
		return false;
    });
});