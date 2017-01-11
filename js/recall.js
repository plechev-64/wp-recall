var rcl_actions = [];
var rcl_filters = [];
var rcl_beats = [];
var rcl_beats_delay = 0;
var rcl_url_params = rcl_get_value_url_params();

jQuery(function($){
    rcl_do_action('rcl_init');
});

jQuery(window).load(function() {
    jQuery('body').on('drop', function (e) {
        return false;
   });
    jQuery(document.body).bind("drop", function(e){
        e.preventDefault();
    }); 
});

function rcl_do_action(action_name,args){
    
    var callbacks_action = rcl_actions[action_name];
    
    if(!callbacks_action) return false;
    
    callbacks_action.forEach(function(callback, i, callbacks_action) {
        new (window[callback])(args);
    });
}

function rcl_add_action(action_name,callback){
    if(!rcl_actions[action_name]){
        rcl_actions[action_name] = [callback];
    }else{
        var i = rcl_actions[action_name].length;
        rcl_actions[action_name][i] = callback;
    }
}

function rcl_apply_filters(filter_name,args){
    
    var callbacks_filter = rcl_filters[filter_name];
    
    if(!callbacks_filter) return args;
    
    callbacks_filter.forEach(function(callback, i, callbacks_filter) {
        args = new (window[callback])(args);
    });
    return args;
}

function rcl_add_filter(filter_name,callback){
    if(!rcl_filters[filter_name]){
        rcl_filters[filter_name] = [callback];
    }else{
        var i = rcl_filters[filter_name].length;
        rcl_filters[filter_name][i] = callback;
    }
}

function rcl_get_value_url_params(){
    var tmp_1 = new Array();
    var tmp_2 = new Array();
    var rcl_url_params = new Array();
    var get = location.search;
    if(get !== ''){
        tmp_1 = (get.substr(1)).split('&');
        for(var i=0; i < tmp_1.length; i++) {
            tmp_2 = tmp_1[i].split('=');
            rcl_url_params[tmp_2[0]] = tmp_2[1];
        }
    }
    
    return rcl_url_params;
}

function rcl_is_valid_url(url){
  var objRE = /http(s?):\/\/[-\w\.]{3,}\.[A-Za-z]{2,3}/;
  return objRE.test(url);
}

function setAttr_rcl(prmName,val){
    var res = '';
    var d = location.href.split("#")[0].split("?");  
    var base = d[0];
    var query = d[1];
    if(query) {
        var params = query.split("&");  
        for(var i = 0; i < params.length; i++) {  
                var keyval = params[i].split("=");  
                if(keyval[0] !== prmName) {  
                        res += params[i] + '&';
                }
        }
    }
    res += prmName + '=' + val;
    return base + '?' + res;
} 

function rcl_ajax_tab(e,data){

    var url = data.post.tab_url;
    var supports = data.post.supports;
    var subtab_id = data.post.subtab_id;

    if(supports && supports.indexOf('dialog')>=0){ //если вкладка поддерживает диалог
        
        if(!subtab_id){ //если загружается основная вкладка
        
            ssi_modal.show({
                className: 'rcl-dialog-tab '+data.post.tab_id,
                sizeClass: 'small',
                buttons: [{
                    label: Rcl.local.close,
                    closeAfter: true
                }],
                content: data.result
            });
        
        }else{

            var box_id = '#ssi-modalContent';
            
        }
        
    }else{
        
        rcl_update_history_url(url);
        
        if(!subtab_id)
            jQuery('.rcl-tab-button .recall-button').removeClass('active');
        
        e.addClass('active');

        var box_id = '#lk-content';

    }
    
    if(box_id){
    
        jQuery(box_id).html(data.result);
        var offsetTop = jQuery(box_id).offset().top;
        jQuery('body,html').animate({scrollTop:offsetTop -100}, 1000);
    
    }

}

function rcl_update_history_url(url){

    if(url != window.location){
        if ( history.pushState ){
            window.history.pushState(null, null, url);
        }
    }
    
}

function rcl_close_notice(e){
    jQuery(e).animate({
        opacity: 0,
        height: 'hide'
    }, 300);
}

function rcl_add_dropzone(idzone){

    jQuery(document.body).bind("drop", function(e){
        var dropZone = jQuery(idzone),
        node = e.target, 
        found = false;

        if(dropZone[0]){		
            dropZone.removeClass('in hover');
            do {               
                if (node === dropZone[0]) {
                    found = true;
                    break;
                }
                node = node.parentNode;
            } while (node != null);

            if(found){
                e.preventDefault();
            }else{			
                return false;
            }
        }
    });

    jQuery(idzone).bind('dragover', function (e) {
        var dropZone = jQuery(idzone),
                timeout = window.dropZoneTimeout;

        if (!timeout) {
                dropZone.addClass('in');
        } else {
                clearTimeout(timeout);
        }

        var found = false,
                node = e.target;

        do {
                if (node === dropZone[0]) {
                        found = true;
                        break;
                }
                node = node.parentNode;
        } while (node != null);

        if (found) {
                dropZone.addClass('hover');
        } else {
                dropZone.removeClass('hover');
        }

        window.dropZoneTimeout = setTimeout(function () {
                window.dropZoneTimeout = null;
                dropZone.removeClass('in hover');
        }, 100);
    });
}

function passwordStrength(password){
    var desc = new Array();
    desc[0] = Rcl.local.pass0;
    desc[1] = Rcl.local.pass1;
    desc[2] = Rcl.local.pass2;
    desc[3] = Rcl.local.pass3;
    desc[4] = Rcl.local.pass4;
    desc[5] = Rcl.local.pass5;
    var score   = 0;
    if (password.length > 6) score++;   
    if ( ( password.match(/[a-z]/) ) && ( password.match(/[A-Z]/) ) ) score++;
    if (password.match(/\d+/)) score++;
    if ( password.match(/.[!,@,#,$,%,^,&,*,?,_,~,-,(,)]/) ) score++;
    if (password.length > 12) score++;
    document.getElementById("passwordDescription").innerHTML = desc[score];
    document.getElementById("passwordStrength").className = "strength" + score;
}

function rcl_notice(text,type,time_close){

    time_close = time_close || false;

    var notice_id = rcl_rand(1, 1000);

    var html = '<div id="notice-'+notice_id+'" class="notice-window type-'+type+'"><a href="#" class="close-notice"><i class="fa fa-times"></i></a>'+text+'</div>';	
    if(!jQuery('#rcl-notice').size()){
            jQuery('body > div').last().after('<div id="rcl-notice">'+html+'</div>');
    }else{
            if(jQuery('#rcl-notice > div').size()) jQuery('#rcl-notice > div:last-child').after(html);
            else jQuery('#rcl-notice').html(html);
    }

    if(time_close){
        setTimeout(function () {
            rcl_close_notice('#rcl-notice #notice-'+notice_id)
        }, time_close);
    }
}

function rcl_preloader_show(e,size){
    var font_size = (size)? size: 80;
    var margin = font_size/2;
    var style = 'style="font-size:'+font_size+'px;margin: -'+margin+'px 0 0 -'+margin+'px;"';    
    jQuery(e).after('<div class="rcl_preloader"><i class="fa fa-spinner fa-pulse" '+style+'></i></div>');
}

function rcl_preloader_hide(){
    jQuery('.rcl_preloader').remove();
}

function rcl_rand( min, max ) {
    if( max ) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    } else {
        return Math.floor(Math.random() * (min + 1));
    }
}

function rcl_add_dynamic_field(e){
    var parent = jQuery(e).parents('.dynamic-value');
    var box = parent.parent('.dynamic-values');
    var html = parent.html();
    box.append('<span class="dynamic-value">'+html+'</span>');
    jQuery(e).attr('onclick','rcl_remove_dynamic_field(this);return false;').children('i').toggleClass("fa-plus fa-minus");
    box.children('span').last().children('input').val('');
}

function rcl_remove_dynamic_field(e){
    jQuery(e).parents('.dynamic-value').remove();
}

function rcl_manage_user_black_list(e,user_id){
    
    var class_i = jQuery(e).children('i').attr('class');
    
    if(class_i=='fa fa-refresh fa-spin') return false;
    
    jQuery(e).children('i').attr('class','fa fa-refresh fa-spin');
    
    var office_id = jQuery(e).parents('#rcl-office').data('account');
    
    var dataString = 'action=rcl_manage_user_black_list&user_id='+user_id;
    dataString += '&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: Rcl.ajaxurl,				
        success: function(data){
            
            jQuery(e).children('i').attr('class',class_i);

            if(data['errors']){
                jQuery.each(data['errors'], function( index, value ) {
                    rcl_notice(value[1],'error',10000);
                });
            }

            if(data['success']){
                jQuery(e).find('span').text(data['label']);
            }
            
            rcl_do_action('rcl_manage_user_black_list',{element:e,result:data});

        } 
    });
    return false;   
}

function rcl_update_require_checkbox(e){
    var name = jQuery(e).attr('name');
    var chekval = jQuery('form input[name="'+name+'"]:checked').val();
    if(chekval) jQuery('form input[name="'+name+'"]').attr('required',false);
    else jQuery('form input[name="'+name+'"]').attr('required',true);
}

function rcl_show_tab(id_block){
    jQuery(".rcl-tab-button .recall-button").removeClass("active");
    jQuery("#lk-content .recall_content_block").removeClass("active");
    jQuery('#tab-button-'+id_block).children('.recall-button').addClass("active");
    jQuery('#lk-content .'+id_block+'_block').addClass("active");
    return false;
}

rcl_add_action('rcl_init','rcl_init_recallbar_hover');
function rcl_init_recallbar_hover(){
    jQuery("#recallbar .menu-item-has-children").hover(function() {
        jQuery(this).children(".sub-menu").css({'visibility': 'visible'});
    }, function() {
        jQuery(this).children(".sub-menu").css({'visibility': ''});
    });
}

rcl_add_action('rcl_init','rcl_init_ajax_tab');
function rcl_init_ajax_tab(){
    jQuery('body').on('click','.rcl-ajax',function(){
        
        var e = jQuery(this);
        
        if(e.hasClass('tab-upload')) return false;
        
        rcl_do_action('rcl_before_upload_tab',e);

        var post = e.data('post');
        var tab_url = encodeURIComponent(e.attr('href'));
        var dataString = 'action=rcl_ajax&post='+post+'&tab_url='+tab_url;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', 
            data: dataString, 
            dataType: 'json', 
            url: Rcl.ajaxurl, 
            success: function(data){
                
                rcl_preloader_hide();
                
                e.removeClass('tab-upload');

                data = rcl_apply_filters('rcl_upload_tab',data);

                if(data.result.error){
                    rcl_notice(data.result.error,'error',10000);
                    return false;
                }
                
                var funcname = data.post.callback;              
                new (window[funcname])(e,data);
                
                rcl_do_action('rcl_upload_tab',{element:e,result:data});
                
            }			
        }); 
        return false;
    });
}

rcl_add_action('rcl_before_upload_tab','rcl_add_class_upload_tab');
function rcl_add_class_upload_tab(e){
    e.addClass('tab-upload');
}

rcl_add_action('rcl_before_upload_tab','rcl_add_preloader_tab');
function rcl_add_preloader_tab(e){
    rcl_preloader_show('#lk-content > div');
    rcl_preloader_show('#ssi-modalContent > div');
}

rcl_add_action('rcl_init','rcl_init_get_smilies');
function rcl_init_get_smilies(){
    jQuery(document).on({
        mouseenter: function () {
            var sm_box = jQuery(this).next();
            var block = sm_box.children();
            sm_box.show();
            if(block.html()) return false;
            block.html(Rcl.local.loading+'...');
            var dir = jQuery(this).data('dir');
            var area = jQuery(this).parent().data('area');
            var dataString = 'action=rcl_get_smiles_ajax&area='+area;
            if(dir) dataString += '&dir='+dir;
            dataString += '&ajax_nonce='+Rcl.nonce;
            jQuery.ajax({
                type: 'POST', 
                data: dataString, 
                dataType: 'json', 
                url: Rcl.ajaxurl,
                success: function(data){				
                        if(data['result']==1){
                                block.html(data['content']);
                        }else{
                                rcl_notice(Rcl.local.error,'error',10000);
                        }					
                }			
            }); 
        },
        mouseleave: function () {
            jQuery(this).next().hide();
        }
    }, "body .rcl-smiles .fa-smile-o");
}

rcl_add_action('rcl_init','rcl_init_hover_smilies');
function rcl_init_hover_smilies(){
    
    jQuery(document).on({
        mouseenter: function () {
            jQuery(this).show();           
        },
        mouseleave: function () {
            jQuery(this).hide();
        }
    }, "body .rcl-smiles > .rcl-smiles-list");

    jQuery('body').on('hover click','.rcl-smiles > img',function(){
        var block = jQuery(this).next().children();
        if(block.html()) return false;
        block.html(Rcl.local.loading+'...');
        var dir = jQuery(this).data('dir');
        var area = jQuery(this).parent().data('area');
        var dataString = 'action=rcl_get_smiles_ajax&area='+area;
        if(dir) dataString += '&dir='+dir;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', 
            data: dataString, 
            dataType: 'json', 
            url: Rcl.ajaxurl,
            success: function(data){				
                    if(data['result']==1){
                            block.html(data['content']);
                    }else{
                            rcl_notice(Rcl.local.error,'error',10000);
                    }					
            }			
        }); 
        return false;
    });
}

rcl_add_action('rcl_init','rcl_init_click_smilies');
function rcl_init_click_smilies(){
    jQuery("body").on("click",'.rcl-smiles-list img',function(){
        var alt = jQuery(this).attr("alt");
        var area = jQuery(this).parents(".rcl-smiles").data("area");
        jQuery("#"+area).val(jQuery("#"+area).val()+" "+alt+" ");
    });
}

rcl_add_action('rcl_init','rcl_init_close_popup');
function rcl_init_close_popup(){
    jQuery('#rcl-popup,.floatform').on('click','.close-popup',function(){
        rcl_hide_float_login_form();
        jQuery('#rcl-overlay').fadeOut();
        jQuery('#rcl-popup').empty();		
        return false;
    });
}

rcl_add_action('rcl_init','rcl_init_click_overlay');
function rcl_init_click_overlay(){
    jQuery('#rcl-overlay').click(function(){
        rcl_hide_float_login_form();
        jQuery('#rcl-overlay').fadeOut();
        jQuery('#rcl-popup').empty();		
        return false;
    });
}

rcl_add_action('rcl_init','rcl_init_click_float_window');
function rcl_init_click_float_window(){
    jQuery(".float-window-recall").on('click','.close',function(){	
        jQuery(".float-window-recall").remove();
        return false; 
    });
}

rcl_add_action('rcl_init','rcl_init_loginform_shift_tabs');
function rcl_init_loginform_shift_tabs(){
    jQuery('.form-tab-rcl .link-tab-rcl').click(function(){
        jQuery('.form-tab-rcl').hide();
        
        if(jQuery(this).hasClass('link-login-rcl')) 
            rcl_show_login_form_tab('login');
        
        if(jQuery(this).hasClass('link-register-rcl'))
            rcl_show_login_form_tab('register');
        
        if(jQuery(this).hasClass('link-remember-rcl'))
            rcl_show_login_form_tab('remember');
        
        return false; 
    });
}

rcl_add_action('rcl_init','rcl_init_check_url_params');
function rcl_init_check_url_params(){
    
    var options = {
        scroll:1
    };
    
    options = rcl_apply_filters('rcl_options_url_params',options);

    if(rcl_url_params['tab']){		

        if(options.scroll){
            var offsetTop = jQuery("#lk-content").offset().top;
            jQuery('body,html').animate({scrollTop:offsetTop -50}, 1000);
        }
        
        var id_block = rcl_url_params['tab'];
        rcl_show_tab(id_block);
    }
    
}

rcl_add_action('rcl_init','rcl_init_update_requared_checkbox');
function rcl_init_update_requared_checkbox(){
    
    jQuery('.public_block form.edit-form').find('.requared-checkbox').each(function(){
        rcl_update_require_checkbox(this);
    });
    
    jQuery('body').on('click','.requared-checkbox',function(){
        rcl_update_require_checkbox(this);
    });
    
}

rcl_add_action('rcl_init','rcl_init_close_notice');
function rcl_init_close_notice(){
    jQuery('#rcl-notice,body').on('click','a.close-notice',function(){           
        rcl_close_notice(jQuery(this).parent());
        return false;
    });
}

rcl_add_action('rcl_init','rcl_init_cookie');
function rcl_init_cookie(){
    
    jQuery.cookie = function(name, value, options) {
        if (typeof value !== 'undefined') { 
            options = options || {};
            if (value === null) {
                value = '';
                options.expires = -1;
            }
            var expires = '';
            if (options.expires && (typeof options.expires === 'number' || options.expires.toUTCString)) {
                var date;
                if (typeof options.expires === 'number') {
                    date = new Date();
                    date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
                } else {
                    date = options.expires;
                }
                expires = '; expires=' + date.toUTCString();
            }
            var path = options.path ? '; path=' + (options.path) : '';
            var domain = options.domain ? '; domain=' + (options.domain) : '';
            var secure = options.secure ? '; secure' : '';
            document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
        } else {
            var cookieValue = null;
            if (document.cookie && document.cookie !== '') {
                var cookies = document.cookie.split(';');
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = jQuery.trim(cookies[i]);
                    if (cookie.substring(0, name.length + 1) === (name + '=')) {
                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                        break;
                    }
                }
            }
            return cookieValue;
        }
    };
    
}

rcl_add_action('rcl_slider','rcl_init_footer_slider');
function rcl_init_footer_slider(){
    rcl_add_action('rcl_footer','rcl_init_slider');
}

function rcl_init_slider(){
    var slider_options = eval("("+Rcl.slider+")");
    jQuery('.rcl-gallery').bxSlider({ pagerCustom: '#bx-pager' });    
    jQuery('.slider-rcl').bxSlider( slider_options );    
    jQuery('.slider-products').bxSlider({ auto:true, pause:10000 });
}

rcl_add_action('rcl_login_form','rcl_init_login_form');
function rcl_init_login_form(type_form){
    
    if(rcl_url_params['action-rcl']){
        jQuery('.panel_lk_recall.floatform > div').hide();
    }
    
    if(type_form=='floatform'){
    
        jQuery(".rcl-register").click(function(){ 
            rcl_show_float_login_form();
            rcl_show_login_form_tab('register',type_form);
            return false;
        });
        
        jQuery(".rcl-login").click(function(){ 
            rcl_show_float_login_form();
            rcl_show_login_form_tab('login',type_form);
            return false;
        });
        
        if(rcl_url_params['action-rcl']){
            rcl_show_float_login_form();
        }

    }else{
        
        if(rcl_url_params['action-rcl']==='login'){
            jQuery('.panel_lk_recall.'+type_form+' #register-form-rcl').hide();
        }

        if(rcl_url_params['action-rcl']==='register'){
            jQuery('.panel_lk_recall.'+type_form+' #login-form-rcl').hide();
        }

        if(rcl_url_params['action-rcl']==='remember'){
            jQuery('.panel_lk_recall.'+type_form+' #login-form-rcl').hide();
        }
        
    }
    
    if(rcl_url_params['action-rcl']){
        rcl_show_login_form_tab(rcl_url_params['action-rcl'],type_form);
    }
    
}

function rcl_show_login_form_tab(tab,type_form){    
    type_form = (!type_form)? '' : '.'+type_form;
    jQuery('.panel_lk_recall'+type_form+' #'+tab+'-form-rcl').show();
}

function rcl_show_float_login_form(){
    jQuery('.panel_lk_recall.floatform > div').hide();
    rcl_setup_position_float_form();
    jQuery('.panel_lk_recall.floatform').show();
}

function rcl_hide_float_login_form(){
    jQuery('.panel_lk_recall.floatform').fadeOut().children('.form-tab-rcl').hide();
}

function rcl_setup_position_float_form(){
    jQuery("#rcl-overlay").fadeIn(); 
    var screen_top = jQuery(window).scrollTop();
    var popup_h = jQuery('.panel_lk_recall.floatform').height();
    var window_h = jQuery(window).height();
    screen_top = screen_top + 60;
    jQuery('.panel_lk_recall.floatform').css('top', screen_top+'px');
}

function rcl_add_beat(beat_name,delay){
    
    var i = rcl_beats.length;

    rcl_beats[i] = {
        beat_name:beat_name,
        delay:delay
    };
    
}

function rcl_exist_beat(beat_name){
    
    if(!rcl_beats) return false;
    
    var exist = false;
    
    rcl_beats.forEach(function(beat, index, rcl_beats){
        if(beat.beat_name != beat_name) return;
            exist = true;
    });
    
    return exist;
    
}

rcl_add_action('rcl_footer','rcl_beat');
function rcl_beat(){
    
    var beats = rcl_apply_filters('rcl_beats',rcl_beats);

    var DataBeat = rcl_get_actual_beats_data(beats);
    
    DataBeat = JSON.stringify(DataBeat);
    
    if(rcl_beats_delay && DataBeat != '[]'){

        var dataString = 'action=rcl_beat&databeat='+DataBeat;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', 
            data: dataString, 
            dataType: 'json', 
            url: Rcl.ajaxurl, 
            success: function(data){

                data.forEach(function(result, i, data) {
                    new (window[result['success']])(result['result']);
                });

            }			
        }); 
    
    }
    
    rcl_beats_delay++;
    
    setTimeout('rcl_beat()', 1000);
}

function rcl_get_actual_beats_data(beats){
    
    var beats_actual = [];
    
    if(beats){

        beats.forEach(function(beat, i, beats) {
            var rest = rcl_beats_delay%beat.delay;
            if(rest == 0){

                var object = new (window[beat.beat_name])();

                object = rcl_apply_filters('rcl_beat_' + beat.beat_name,object);

                var k = beats_actual.length;
                beats_actual[k] = object;
            }
        });
    
    }

    return beats_actual;
    
}
