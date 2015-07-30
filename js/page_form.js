jQuery(function($){
	if(get_param['action-rcl']==='login'){
		$('.panel_lk_recall.pageform #register-form-rcl').hide();
		$('.panel_lk_recall.pageform #login-form-rcl').show();
	}
	if(get_param['action-rcl']==='register'){
		$('.panel_lk_recall.pageform #login-form-rcl').hide();
		$('.panel_lk_recall.pageform #register-form-rcl').show();
	}
	if(get_param['action-rcl']==='remember'){
		$('.panel_lk_recall.pageform #login-form-rcl').hide();
		$('.panel_lk_recall.pageform #remember-form-rcl').show();
	}
});