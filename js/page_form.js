jQuery(function($){
	if(rcl_url_params['action-rcl']==='login'){
		$('.panel_lk_recall.pageform #register-form-rcl').hide();
		$('.panel_lk_recall.pageform #login-form-rcl').show();
	}
	if(rcl_url_params['action-rcl']==='register'){
		$('.panel_lk_recall.pageform #login-form-rcl').hide();
		$('.panel_lk_recall.pageform #register-form-rcl').show();
	}
	if(rcl_url_params['action-rcl']==='remember'){
		$('.panel_lk_recall.pageform #login-form-rcl').hide();
		$('.panel_lk_recall.pageform #remember-form-rcl').show();
	}
});