jQuery(function(){
	jQuery(".reglink").click(function(){ 
		position_float_form_rcl();
		jQuery('.panel_lk_recall.floatform #register-form-rcl').show();
		return false;
	});
	jQuery(".sign-button").click(function(){ 
		position_float_form_rcl();
		jQuery('.panel_lk_recall.floatform #login-form-rcl').show();
		return false;
	});
	if(get_param['action-rcl']=='login'){
		position_float_form_rcl();
		jQuery('.panel_lk_recall.floatform #login-form-rcl').show();
	}
	if(get_param['action-rcl']=='register'){
		position_float_form_rcl();
		jQuery('.panel_lk_recall.floatform #register-form-rcl').show();
	}
	if(get_param['action-rcl']=='remember'){
		position_float_form_rcl();
		jQuery('.panel_lk_recall.floatform #remember-form-rcl').show();
	}
	function position_float_form_rcl(){
		jQuery("#rcl-overlay").fadeIn(); 
		var screen_top = jQuery(window).scrollTop();
		var popup_h = jQuery('.panel_lk_recall.floatform').height();
		var window_h = jQuery(window).height();
		screen_top = screen_top + 60;
		jQuery('.panel_lk_recall.floatform').css('top', screen_top+'px').delay(100).slideDown(400);
		jQuery('.panel_lk_recall.floatform > div').hide();
	}
});