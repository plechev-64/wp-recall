var rcl_actions = [];
var rcl_filters = [];
var rcl_beats = [];
var rcl_beats_delay = 0;
var rcl_url_params = rcl_get_value_url_params();

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

function rcl_update_history_url(url){

    if(url != window.location){
        if ( history.pushState ){
            window.history.pushState(null, null, url);
        }
    }
    
}

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

function rcl_add_dynamic_field(e){
    var parent = jQuery(e).parents('.dynamic-value');
    var box = parent.parent('.dynamic-values');
    var html = parent.html();
    box.append('<span class="dynamic-value">'+html+'</span>');
    jQuery(e).attr('onclick','rcl_remove_dynamic_field(this);return false;').children('i').toggleClass("fa-plus fa-minus");
    box.children('span').last().children('input').val('').focus();
}

function rcl_remove_dynamic_field(e){
    jQuery(e).parents('.dynamic-value').remove();
}

function rcl_update_require_checkbox(e){
    var name = jQuery(e).attr('name');
    var chekval = jQuery('form input[name="'+name+'"]:checked').val();
    if(chekval) jQuery('form input[name="'+name+'"]').attr('required',false);
    else jQuery('form input[name="'+name+'"]').attr('required',true);
}

function rcl_rand( min, max ) {
    if( max ) {
            return Math.floor(Math.random() * (max - min + 1)) + min;
    } else {
            return Math.floor(Math.random() * (min + 1));
    }
}

function rcl_notice(text,type,time_close){

    time_close = time_close || false;
    
    var options = {
        text: text,
        type: type,
        time_close: time_close
    };
    
    options = rcl_apply_filters('rcl_notice_options',options);

    var notice_id = rcl_rand(1, 1000);

    var html = '<div id="notice-'+notice_id+'" class="notice-window type-'+options.type+'"><a href="#" class="close-notice"><i class="fa fa-times"></i></a>'+options.text+'</div>';	
    if(!jQuery('#rcl-notice').size()){
            jQuery('body > div').last().after('<div id="rcl-notice">'+html+'</div>');
    }else{
            if(jQuery('#rcl-notice > div').size()) jQuery('#rcl-notice > div:last-child').after(html);
            else jQuery('#rcl-notice').html(html);
    }

    if(time_close){
        setTimeout(function () {
            rcl_close_notice('#rcl-notice #notice-'+notice_id)
        }, options.time_close);
    }
}

function rcl_close_notice(e){
    jQuery(e).animate({
        opacity: 0,
        height: 'hide'
    }, 300);
}

function rcl_preloader_show(e,size){
    
    var font_size = (size)? size: 80;
    var margin = font_size/2;
    
    var options = {
        size: font_size,
        margin: margin,
        icon: 'fa-circle-o-notch',
        class: 'rcl_preloader'
    };
    
    options = rcl_apply_filters('rcl_preloader_options',options);
    
    var style = 'style="font-size:'+options.size+'px;margin: -'+options.margin+'px 0 0 -'+options.margin+'px;"';
    
    var html = '<div class="'+options.class+'"><i class="fa '+options.icon+' fa-spin" '+style+'></i></div>';
    
    if(typeof( e ) === 'string')
        jQuery(e).after(html);
    else
        e.append(html);
}

function rcl_preloader_hide(){
    jQuery('.rcl_preloader').remove();
}

function rcl_setup_datepicker_options(){
    
    jQuery.datepicker.setDefaults(jQuery.extend(jQuery.datepicker.regional["ru"]));
    
    var options = {
        monthNames: [ "Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь" ],
        dayNamesMin: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
        firstDay: 1,
        dateFormat: 'yy-mm-dd',
        yearRange: "1950:c+3",
        changeYear: true
    };
    
    options = rcl_apply_filters('rcl_datepicker_options',options);
    
    return options;
    
}

function rcl_show_datepicker(e){
    jQuery(e).datepicker(rcl_setup_datepicker_options());
    jQuery(e).datepicker( "show" );
    rcl_add_action('rcl_upload_tab','rcl_remove_datepicker_box');
}

function rcl_remove_datepicker_box(){
    jQuery('#ui-datepicker-div').remove();
}

function rcl_init_field_file(field_id){
    
    var field = jQuery("#"+field_id);
    var form = field.parents('form');
    
    form.attr("enctype","multipart/form-data");
    
    form.submit(function(event){
        
        var error = false;
        
        field.each(function(){
            
            var maxsize = jQuery(this).data("size");
            var fileInput = jQuery(this)[0];
            var file = fileInput.files[0];
            var accept = fileInput.accept.split(',');
            
            if(!file) return;
            
            if(accept){

                var fileType = false;
                
                if(file.type){
                
                    for(var i in accept){
                        if(accept[i] == file.type){
                            fileType = true;
                            return;
                        }
                    }
                
                }
                
                if(!fileType){
                    
                    var exts = jQuery(this).data("ext").split(',');
                    var filename = file.name;
                    
                    for(var i in exts){
                        if(filename.indexOf('.'+exts[i]) + 1) {
                            fileType = true;
                            return;
                        }
                    }
                    
                }
                
                if(!fileType){
                    rcl_preloader_hide();
                    rcl_notice("Некорректный тип файла!",'error',5000);
                    error = true;
                    return;
                }
                
            }
            
            var filesize = file.size/1024/1024;
            
            if(filesize > maxsize){
                jQuery(this).parent().css("border","1px solid red").css("padding","2px");
                rcl_preloader_hide();
                rcl_notice("Размер файла превышен!",'error',5000);
                error = true;
                return;
            }else{
                jQuery(this).parent().removeAttr("style");
            }
            
        });
        
        if(error){
            return false;
        }
        
    });
    
}

function rcl_init_field_maxlength(fieldID){
    
    var field = jQuery('#'+fieldID);
    var maxlength = field.attr('maxlength');
    
    if(!field.parent().find('.maxlength').size()){
        
        if(field.val()){ 
            maxlength = maxlength - field.val().length;
        }
        
        field.after('<span class="maxlength">'+maxlength+'</span>');
    }
    
    field.on('keyup', function(){
        var maxlength = jQuery(this).attr('maxlength');
        if(!maxlength) return false;
        var word = jQuery(this);
        var count = maxlength - word.val().length;
        jQuery(this).next().text(count);
        if(word.val().length > maxlength) word.val(word.val().substr(0, maxlength));
    });
}

function rcl_init_ajax_editor(id,options){
    
    if(typeof QTags === 'undefined') return false;
    
    rcl_do_action('rcl_pre_init_ajax_editor',{id:id,options:options});

    var qt_options = {
        id: id,
        buttons: (options.qt_buttons)? options.qt_buttons: "strong,em,link,block,del,ins,img,ul,ol,li,code,more,close"
    };
    
    QTags( qt_options );
    
    QTags._buttonsInit();

    if(options.tinymce){
        
        tinyMCEPreInit.qtInit[id] = qt_options;
        
        tinyMCEPreInit.mceInit[id] = {
            body_class: id,
            selector: '#'+id,
            menubar: false,
            skin: "lightgray",
            theme: 'modern',
            toolbar1: "formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv",
            toolbar2: "strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help",
            wpautop: true
        };
        
        tinymce.init(tinyMCEPreInit.mceInit[id]);   
        tinyMCE.execCommand('mceAddEditor', false, id); 
        
        switchEditors.go(id, 'html');
    }

}

function rcl_setup_quicktags(newTags){
    
    if(typeof QTags === 'undefined') return false;
    
    newTags.forEach(function(tagArray, i, newTags) {

        QTags.addButton( 
            tagArray[0], 
            tagArray[1], 
            tagArray[2], 
            tagArray[3], 
            tagArray[4], 
            tagArray[5], 
            tagArray[6]
        );

    });
    
}

rcl_add_action('rcl_pre_init_ajax_editor','rcl_add_ajax_quicktags');
function rcl_add_ajax_quicktags(editor){

    if(typeof Rcl === 'undefined' || !Rcl.QTags) return false;
    
    rcl_setup_quicktags(Rcl.QTags);

}

rcl_add_action('rcl_footer','rcl_add_quicktags');
function rcl_add_quicktags(){

    if(typeof Rcl === 'undefined' || !Rcl.QTags) return false;
    
    rcl_setup_quicktags(Rcl.QTags);

}