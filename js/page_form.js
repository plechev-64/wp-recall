jQuery(function(){
	if(get_param['action-rcl']==='login'){
		jQuery('.panel_lk_recall.pageform #register-form-rcl').hide();
		jQuery('.panel_lk_recall.pageform #login-form-rcl').show();
	}
	if(get_param['action-rcl']==='register'){
		jQuery('.panel_lk_recall.pageform #login-form-rcl').hide();
		jQuery('.panel_lk_recall.pageform #register-form-rcl').show();
	}
	if(get_param['action-rcl']==='remember'){
		jQuery('.panel_lk_recall.pageform #login-form-rcl').hide();
		jQuery('.panel_lk_recall.pageform #remember-form-rcl').show();
	}
});