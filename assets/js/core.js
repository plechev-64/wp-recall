var rcl_actions = [];
var rcl_filters = [];
var rcl_beats = [];
var rcl_beats_delay = 0;
var rcl_url_params = rcl_get_value_url_params();

jQuery.fn.extend({
    animateCss: function (animationNameStart,functionEnd) {
        var animationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
        this.addClass('animated ' + animationNameStart).one(animationEnd, function() {
            jQuery(this).removeClass('animated ' + animationNameStart);

            if(functionEnd){
                if(typeof functionEnd == 'function'){
                    functionEnd(this);
                }else{
                    jQuery(this).animateCss(functionEnd);
                }
            }
        });
        return this;
    }
});

function rcl_do_action(action_name){
    
    var callbacks_action = rcl_actions[action_name];
    
    if(!callbacks_action) return false;
    
    var args = [].slice.call(arguments, 1);
    
    callbacks_action.forEach(function(callback, i, callbacks_action) {
        window[callback].apply(this, args);
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

function rcl_apply_filters(filter_name){
    
    var args = [].slice.call(arguments, 1);
    
    var callbacks_filter = rcl_filters[filter_name];
    
    if(!callbacks_filter) return args[0];

    callbacks_filter.forEach(function(callback, i, callbacks_filter) {
        args[0] = window[callback].apply(this, args);
    });
    
    return args[0];
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

    var html = '<div id="notice-'+notice_id+'" class="notice-window type-'+options.type+'"><a href="#" class="close-notice"><i class="rcli fa-times"></i></a>'+options.text+'</div>';	
    if(!jQuery('#rcl-notice').size()){
            jQuery('body > div').last().after('<div id="rcl-notice">'+html+'</div>');
    }else{
        if(jQuery('#rcl-notice > div').size()) jQuery('#rcl-notice > div:last-child').after(html);
        else jQuery('#rcl-notice').html(html);
    }
    
    jQuery('#rcl-notice > div').last().animateCss('slideInLeft');

    if(time_close){
        setTimeout(function () {
            rcl_close_notice('#rcl-notice #notice-'+notice_id)
        }, options.time_close);
    }
}

function rcl_close_notice(e){
    jQuery(e).animateCss('flipOutX',function(e){
        jQuery(e).hide();
    });
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
    
    var html = '<div class="'+options.class+'"><i class="rcli '+options.icon+' fa-spin" '+style+'></i></div>';
    
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
    jQuery("#"+field_id).parents('form').attr("enctype","multipart/form-data");
}

function rcl_init_runner(props){

    var box = jQuery( '#rcl-runner-' + props.id );
    
    box.children('.rcl-runner-box').slider({
        value : parseInt(props.value),
        min : parseInt(props.min),
        max : parseInt(props.max),
        step : parseInt(props.step),
        create: function( event, ui ) {
            var value = box.children('.rcl-runner-box').slider( 'value' );
            box.children('.rcl-runner-value').text( value );
            box.children('.rcl-runner-field').val( value );
        },
        slide: function( event, ui ) {
            box.find('.rcl-runner-value').text( ui.value );
            box.find('.rcl-runner-field').val( ui.value );
        }
    });
}

function rcl_init_range(props){

    var box = jQuery( '#rcl-range-' + props.id );
    
    box.children('.rcl-range-box').slider({
        range: true,
        values : [ parseInt(props.values[0]), parseInt(props.values[1]) ],
        min : parseInt(props.min),
        max : parseInt(props.max),
        step : parseInt(props.step),
        create: function( event, ui ) {
            var values = box.children('.rcl-range-box').slider( 'values' );
            box.children('.rcl-range-value').text( values[0] + ' - ' + values[1]);
            box.children('.rcl-range-min').val( values[0] );
            box.children('.rcl-range-max').val( values[1] );
        },
        slide: function( event, ui ) {
            box.children('.rcl-range-value').text( ui.values[0] + ' - ' + ui.values[1] );
            box.find('.rcl-range-min').val( ui.values[0] );
            box.find('.rcl-range-max').val( ui.values[1] );
        }
    });
}

function rcl_init_color(id, props){
    jQuery("#"+id).wpColorPicker(props);
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
        tinyMCE.execCommand('mceAddEditor', true, id); 
        
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

function rcl_proccess_ajax_return(result){
    
    var methods = {
        redirect: function(url){
            
            var urlData = url.split('#');

            if(window.location.origin + window.location.pathname === urlData[0]){
                location.reload();
            }else{
                location.replace(url);
            }
            
        },
        reload: function(){
            location.reload();
        },
        current_url: function(url){
            rcl_update_history_url(url);
        },
        dialog: function(dialog){
            
            if(dialog.content){
            
                if(jQuery('#ssi-modalContent').size()) ssi_modal.close();

                var ssiOptions = {
                    className: 'rcl-dialog-tab ' + (dialog.class? ' ' + dialog.class: ''),
                    sizeClass: dialog.size? dialog.size: 'auto',
                    content: dialog.content,
                    buttons: []
                };
                
                if(dialog.buttons){
                    ssiOptions.buttons = dialog.buttons;
                }
                
                var buttonClose = true;
                
                if ('buttonClose' in dialog) {
                    buttonClose = dialog.buttonClose;
                }
                
                if(buttonClose){
                    
                    ssiOptions.buttons.push({
                        label: Rcl.local.close,
                        closeAfter: true
                    });
                    
                }
                
                if ('onClose' in dialog) {
                    ssiOptions.onClose = function(m){
                        window[dialog.onClose[0]].apply(this, dialog.onClose[1]);
                    };
                }
                
                if(dialog.title)
                    ssiOptions.title = dialog.title;
                
                ssi_modal.show(ssiOptions);
            
            }
            
            if(dialog.close){
                ssi_modal.close();
            }
            
        }
    };
    
    for(var method in result){                  
        if(methods[method]){
            methods[method](result[method]);
        }
    }
    
}

function rcl_ajax(prop){
    
    if(prop.data.ask){
        if(!confirm(prop.data.ask)){
            rcl_preloader_hide();
            return false;
        }
    }
      
    if(typeof Rcl != 'undefined'){
        if(typeof prop.data === 'string'){
            prop.data += '&ajax_nonce=' + Rcl.nonce;
        }else if(typeof prop.data === 'object'){
            prop.data.ajax_nonce = Rcl.nonce;
        }
    }
    
    var action = false;
    if(typeof prop.data === 'string'){
        var propData = prop.data.split('&');
        var propObj={};
        for(var key in propData)
        {
            propObj[propData[key].split("=")[0]] = propData[key].split("=")[1];
        }
        action = propObj.action;
    }else if(typeof prop.data === 'object'){
        action = prop.data.action;
    }
    
    jQuery.ajax({
        type: 'POST', 
        data: prop.data, 
        dataType: 'json', 
        url: (typeof ajaxurl !== 'undefined')? ajaxurl: Rcl.ajaxurl,
        success: function(result, post){
            
            if(!result){
                rcl_notice(Rcl.local.error, 'error', 5000);
                return false;
            }
            
            if(result.error || result.errors){
                
                rcl_preloader_hide();
                
                if(result.errors){
                    jQuery.each(result.errors, function( index, error ) {
                        rcl_notice(error, 'error', 5000);
                    });
                }else{
                    rcl_notice(result.error, 'error', 5000);
                }

                if(prop.error)
                    prop.error(result);
                
                return false;
                
            }
            
            if(!result.preloader_live){
                rcl_preloader_hide();
            }
            
            if(result.success){
                rcl_notice(result.success, 'success', 5000);
            }
            
            if(result.warning){
                rcl_notice(result.warning, 'warning', 5000);
            }

            rcl_do_action('rcl_ajax_success', result);
            
            if(prop.success){
                
                prop.success(result);
                
            }else{
                
                rcl_proccess_ajax_return(result);
                
            }

            rcl_do_action(action, result);
            
        }
    });
    
}

function rcl_send_form_data(action,e){
    
    var form = jQuery(e).parents('form');
    
    if(!rcl_check_form(form)) return false;
    
    if(e && jQuery(e).parents('.preloader-parent')){
        rcl_preloader_show(jQuery(e).parents('.preloader-parent'));
    }
    
    rcl_ajax({
        data: form.serialize() + '&action=' + action
    });
  
}

function rcl_check_form(form){

    var rclFormFactory = new RclForm(form);
    
    return rclFormFactory.validate();
    
}

function rcl_add_beat(beat_name,delay,data){
    
    delay = (delay < 10)? 10: delay;
    
    var data = (data)? data: false;
    
    var i = rcl_beats.length;

    rcl_beats[i] = {
        beat_name: beat_name,
        delay :delay,
        data: data
    };
    
}

function rcl_remove_beat(beat_name){
    
    if(!rcl_beats) return false;
    
    var remove = false;
    var all_beats = rcl_beats;

    all_beats.forEach(function(beat, index, all_beats){
        if(beat.beat_name != beat_name) return;
            delete rcl_beats[index];
            remove = true;
    });
    
    return remove;
    
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

function rcl_init_table(table_id){
    
    jQuery('#' + table_id).on('click','.rcl-table__cell-must-sort',function(){
        
        jQuery('#' + table_id).find('.rcl-table__cell-must-sort, .rcl-table__cell-sort').removeClass('rcl-table__cell-current-sort');
        
        var sortCell = jQuery(this);
        
        var sortby = sortCell.data('sort');
        var route = sortCell.attr('data-route');
        
        sortCell.addClass('rcl-table__cell-current-sort');
        jQuery('#' + table_id).find('[data-'+sortby+'-value]').addClass('rcl-table__cell-current-sort');
        
        var list = jQuery('#' + table_id + ' .rcl-table__row-must-sort');
        
        list.sort(function(a, b){
            var aVal = jQuery(a).find('[data-'+sortby+'-value]').data(sortby+'-value');
            var bVal = jQuery(b).find('[data-'+sortby+'-value]').data(sortby+'-value');
            //if(isNaN(aVal)) 
            if(route == 'asc')
                return (aVal < bVal) - (aVal > bVal); //по возрастанию
            else 
                return (aVal > bVal) - (aVal < bVal); //по убыванию
        });
        
        sortCell.attr('data-route', (route == 'desc'? 'asc': 'desc'));
        
        jQuery('#' + table_id + ' .rcl-table__row-must-sort').remove();

        list.each(function(i,e){
            jQuery('#' + table_id + ' .rcl-table__row-header').after(jQuery(this));
        });

    });
    
}

function RclForm(form){
    
    this.form = form;
    this.errors = {};
    
    this.validate = function(){
        
        var valid = true;

        for(var objKey in this.checkForm){

            var chekObject = this.checkForm[objKey];

            if(!chekObject.isValid.call(this)){
                
                valid = false;

                break;

            }
            
        };
        
        if(this.errors){
            for(var k in this.errors){
                this.showError(this.errors[k]);
            };
        }
        
        return valid;
        
    };
    
    this.addChekForm = function(id, data){
        this.checkForm[id] = data;
    };
    
    this.addChekFields = function(id, data){
        this.checkFields[id] = data;
    };
    
    this.addError = function(id, error){
        this.errors[id] = error;
    };
    
    this.shake = function(shakeBox){
        shakeBox.css('box-shadow','red 0px 0px 5px 1px inset').animateCss('shake');
    };
    
    this.noShake = function(shakeBox){
        shakeBox.css('box-shadow','none');
    };
    
    this.showError = function(error){
        rcl_notice(error, 'error', 10000);
    };
    
    this.checkForm = {
        
        checkFields: {

            isValid: function(){
                
                var valid = true;
                var parent = this;

                this.form.find('input,select,textarea').each(function(){

                    var field = jQuery(this);
                    var typeField = field.attr('type');

                    if(field.tagName && field.tagName.toLowerCase() == 'textarea') {
                        typeField = 'textarea';
                    }

                    for(var objKey in parent.checkFields){

                        var chekObject = parent.checkFields[objKey];

                        if(chekObject.types.length && jQuery.inArray( typeField, chekObject.types ) < 0){
                            continue;
                        }

                        var shakeBox = (typeField == 'checkbox')? field.next('label'): field;

                        if(!chekObject.isValid(field)){

                            parent.shake(shakeBox);
                            parent.addError(objKey, chekObject.errorText());
                            valid = false;
                            return;

                        }else{
                            parent.noShake(shakeBox);
                        }

                    };

                });

                return valid;
                
            }

        }
        
    };
    
    this.checkFields = {
        
        required: {
            
            types: [],

            isValid: function(field){
                
                var required = true;

                if(!field.is(":required")) return required;

                var value = false;

                if(field.attr('type') == 'checkbox'){
                    if(field.is(":checked")){
                        value = true;
                    }
                }else{
                    if(field.val()) value = true;
                }

                if(!value){
                    required = false;
                }

                return required;
                
            },
            
            errorText: function(){
                return Rcl.errors.required;
            }


        },
        numberRange: {
            
            types: ['number'],
            
            isValid: function(field){
                var range = true;

                var val = field.val();
                
                if(val === '') return true;
                
                val = parseInt(val);
                var min = parseInt(field.attr('min'));
                var max = parseInt(field.attr('max'));
                
                if(min != 'undefined' && min > val || max != 'undefined' && max < val){
                    range = false;
                }

                return range;
            },
            
            errorText: function(){
                return Rcl.errors.number_range;
            }

        },
        pattern: {
            
            types: ['text', 'tel'],
            
            isValid: function(field){
                
                var val = field.val();
                
                if(!val) return true;

                var pattern = field.attr('pattern');
                
                if(!pattern) return true;

                var re = new RegExp(pattern);
                
                return re.test(val);
            },
            
            errorText: function(){
                return Rcl.errors.pattern;
            }

        },
        fileMaxSize: {
            
            types: ['file'],
            
            isValid: function(field){
                
                var valid = true;
                
                field.each(function(){
            
                    var maxsize = jQuery(this).data("size");
                    var fileInput = jQuery(this)[0];
                    var file = fileInput.files[0];

                    if(!file) return;

                    var filesize = file.size/1024/1024;

                    if(filesize > maxsize){
                        valid = false;
                        return;
                    }

                });
                
                return valid;
            },
            
            errorText: function(){
                return Rcl.errors.file_max_size;
            }

        },
        fileAccept: {
            
            types: ['file'],
            
            isValid: function(field){
                
                var valid = true;
                
                field.each(function(){

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
                            valid = false;
                            return;
                        }

                    }

                });
                
                return valid;
            },
            
            errorText: function(){
                return Rcl.errors.file_accept;
            }

        }
    };
    
    this.send = function(action, success){

        if(!this.validate()) return false;

        rcl_preloader_show(form);
        
        var sendData = {
            data: form.serialize() + '&action=' + action
        };
        
        if(success){
            sendData.success = success;
        }

        rcl_ajax(sendData);

    };

}