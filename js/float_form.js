jQuery(function($){
	$(".rcl-register").click(function(){ 
		position_float_form_rcl();
		$('.panel_lk_recall.floatform #register-form-rcl').show();
		return false;
	});
	$(".rcl-login").click(function(){ 
		position_float_form_rcl();
		$('.panel_lk_recall.floatform #login-form-rcl').show();
		return false;
	});
	if(get_param['action-rcl']=='login'){
		position_float_form_rcl();
		$('.panel_lk_recall.floatform #login-form-rcl').show();
	}
	if(get_param['action-rcl']=='register'){
		position_float_form_rcl();
		$('.panel_lk_recall.floatform #register-form-rcl').show();
	}
	if(get_param['action-rcl']=='remember'){
		position_float_form_rcl();
		$('.panel_lk_recall.floatform #remember-form-rcl').show();
	}
	function position_float_form_rcl(){
		$("#rcl-overlay").fadeIn(); 
		var screen_top = $(window).scrollTop();
		var popup_h = $('.panel_lk_recall.floatform').height();
		var window_h = $(window).height();
		screen_top = screen_top + 60;
		$('.panel_lk_recall.floatform').css('top', screen_top+'px').delay(100).slideDown(400);
		$('.panel_lk_recall.floatform > div').hide();
	}
});