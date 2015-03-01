jQuery(document).ready( function() {

    jQuery('#add_search_field').click(function() {
		var html_li = jQuery('#inputs_search_fields ul li').last().html();
        jQuery('<li class="menu-item menu-item-edit-active">'+html_li+'</li>').fadeIn('slow').appendTo('#sortable');
		jQuery('#inputs_search_fields ul li').last().children('.menu-item-settings').show();
		return false;
    });
	
	jQuery('.searchfield-submitdelete').live('click',function() {
		jQuery(this).parent().parent().parent().remove();
		return false;
    });
	
	jQuery('.searchfield-item-edit').live('click',function() {
		jQuery(this).parent().parent().parent().next().slideToggle();
		return false;
    });
	
});