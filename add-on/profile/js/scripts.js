function rcl_check_profile_form(){
    
    var form = jQuery('form#your-profile');
    
    if(form.find('#user_pass').val()){
        
        var user_pass = form.find('#user_pass');
        var repeat_pass = form.find('#repeat_pass');
        
        if(user_pass.val() != repeat_pass.val()){
            
            user_pass.css('box-shadow','red 0px 0px 5px 1px inset');
            repeat_pass.css('box-shadow','red 0px 0px 5px 1px inset');
            
            rcl_notice(Rcl.local.no_repeat_pass,'error',10000);
            
            return false;
            
        }else{
            
            user_pass.css('box-shadow','none');
            repeat_pass.css('box-shadow','none');
            
        }
        
    }
    
    var required = true;
    var requireds = new Array;
    
    form.find(':required').each(function(){
        var i = requireds.length;
        requireds[i] = jQuery(this).attr('name');
    });

    requireds.forEach(function(name, i, requireds) {

        var field = form.find('[name="'+name+'"]');
        var type = field.attr('type');
        var value = false;

        if(type=='checkbox'){
            if(field.is(":checked")){
                value = true;
                field.next('label').css('box-shadow','none');
            }else {
                field.next('label').css('box-shadow','red 0px 0px 5px 1px inset');
            }
        }else{
            if(field.val()) value = true;
        }

        if(!value){
            field.css('box-shadow','red 0px 0px 5px 1px inset');
            required = false;
        }else{
            field.css('box-shadow','none');
        }

    });

    if(!required){
        rcl_notice(Rcl.local.requared_fields_empty,'error',10000);
        return false;
    }

    return true;
    
}