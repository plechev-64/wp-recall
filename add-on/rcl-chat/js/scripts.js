var rcl_chat_last_activity = 0; //последняя запрос новых сообщений
var rcl_chat_beat = new Array; //массив открытых чатов
var rcl_chat_write = 0; //юзер пишет
var rcl_chat_contact_token = 0; //открытый контакт
var rcl_chat_inactive_counter = -1; //счетчик простоя пользователя
var rcl_chat_important = 0; 
var rcl_chat_max_words = 300;
var rcl_chat_sound = {};

jQuery(function($){
    
    rcl_chat_init_sound();
    
    rcl_chat_inactivity_counter();
    
    jQuery('.chat-new-messages').parents('#rcl-chat-noread-box').animateCss('tada');
    
});

function rcl_chat_init_sound(){
    
    var options = {
        sounds: ['e-oh'],
        path: Rcl.chat.sounds,
        multiPlay: false,
        volume: '0.5'
    };
    
    rcl_chat_sound = rcl_apply_filters('rcl_chat_sound_options',options);
    
    jQuery.ionSound(rcl_chat_sound);
}

function rcl_chat_inactivity_cancel(){
    rcl_chat_inactive_counter = -1;
}

function rcl_chat_inactivity_counter(){
    rcl_chat_inactive_counter++;
    setTimeout('rcl_chat_inactivity_counter()', 60000);
}

function rcl_chat_scroll_bottom(token){
    jQuery('.rcl-chat[data-token="'+token+'"] .chat-messages').scrollTop( jQuery('.rcl-chat[data-token="'+token+'"] .chat-messages').get(0).scrollHeight );
}

function rcl_reset_active_mini_chat(){
    jQuery('.rcl-noread-users .rcl-chat-user > a ').removeClass('active-chat');
}

function rcl_chat_counter_reset(form){
    form.children('.words-counter').text(Rcl.chat.words).removeAttr('style');
}

function rcl_chat_add_message(e){
    var form = jQuery(e).parents('form');
    rcl_chat_add_new_message(form);
}

function rcl_chat_clear_beat(token){
    
    var all_beats = rcl_beats;
    var all_chats = rcl_chat_beat;
    
    all_beats.forEach(function(beat, index, rcl_beats){
        if(beat.beat_name != 'rcl_chat_beat_core') return;
        if(beat.data.token != token) return;
            delete rcl_beats[index];
    });
    
    all_chats.forEach(function(chat_token, index, chats){
        if(chat_token != token) return;
            delete rcl_chat_beat[index];
    });

}

function rcl_set_active_mini_chat(e){
    rcl_reset_active_mini_chat();
    jQuery(e).addClass('active-chat').children('i').remove();
}

function rcl_init_chat(chat){
    jQuery(function($){
        
        chat = rcl_apply_filters('rcl_init_chat',chat);

        rcl_chat_scroll_bottom(chat.token);
        
        var user_id = parseInt(Rcl.user_ID);
        
        if(user_id){
            
            if(chat.file_upload)
                rcl_chat_uploader(chat.token);
        }

        rcl_chat_max_words = chat.max_words;
        
        var i = rcl_chat_beat.length;
        rcl_chat_beat[i] = chat.token;
        
        rcl_do_action('rcl_init_chat',chat);
        
    });
}

function rcl_chat_close(e){
    
    rcl_reset_active_mini_chat();
    var token = jQuery(e).parents('.rcl-mini-chat').find('.rcl-chat').data('token');
    rcl_chat_clear_beat(token);
    var minichat_box = jQuery('#rcl-chat-noread-box');
    minichat_box.removeClass('active-chat');
    minichat_box.children('.rcl-mini-chat').animateCss('fadeOutRight',function(e){
        jQuery(e).empty();
    });
    rcl_do_action('rcl_chat_close',token);
    
}

function rcl_chat_write_status(token){
    var chat = jQuery('.rcl-chat[data-token="'+token+'"]');
    var chat_status = chat.find('.chat-status');
    chat_status.css({width: 12});
    chat_status.animate({width: 35}, 1000);
    rcl_chat_write = setTimeout('rcl_chat_write_status("'+token+'")', 3000);
    
    rcl_do_action('rcl_add_chat_write',token);
    
}

function rcl_chat_write_status_cancel(token){
    clearTimeout(rcl_chat_write);
    var chat = jQuery('.rcl-chat[data-token="'+token+'"]');
    var chat_status = chat.find('.chat-status');
    chat_status.css({width: 0});
    
    rcl_do_action('rcl_remove_chat_write',token);
}

function rcl_chat_add_new_message(form){
    
    rcl_chat_inactivity_cancel();
    
    var token = form.children('[name="chat[token]"]').val();
    var chat = jQuery('.rcl-chat[data-token="'+token+'"]');
    var message_text = form.children('textarea').val();
    
    if(!message_text.length){
        rcl_notice(Rcl.local.empty_mess,'error',10000);
        return false;
    }
    
    if(message_text.length>Rcl.chat.words){
        rcl_notice(Rcl.local.max_words,'error',10000);
        return false;
    }
    
    rcl_preloader_show('.rcl-chat .chat-form > form');
    
    var dataString = 'action=rcl_chat_add_message&'+form.serialize();
    dataString += '&office_ID='+Rcl.office_ID+'&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: Rcl.ajaxurl,
        success: function(data){
            
            rcl_preloader_hide();
            
            if(data['errors']){
                jQuery.each(data['errors'], function( index, error ) {
                    rcl_notice(error,'error',10000);
                });
            }
            
            if(data['success']){
                form.find('textarea').val('');
                
                chat.find('.chat-messages').append(data['content']).find('.chat-message').last().animateCss('flipInX');
                chat.find('.rcl-chat-uploader').show();
                chat.find('.chat-preloader-file').empty();
                
                rcl_chat_scroll_bottom(token);
                rcl_chat_counter_reset(form);
                
                rcl_do_action('rcl_chat_add_message',{token:token,result:data});
            }
        } 
    });	  	
    return false;
}

function rcl_chat_navi(e){
    
    rcl_chat_inactivity_cancel();
    
    var page = jQuery(e).data('page');
    var pager = jQuery(e).data('pager-id');
    var token = jQuery(e).parents('.rcl-chat').data('token');
    var in_page = jQuery(e).parents('.rcl-chat').data('in_page');
    rcl_preloader_show('.rcl-chat .chat-form > form');
    var dataString = 'action=rcl_get_chat_page&page='+page+'&token='+token+'&pager-id='+pager+'&in_page='+in_page+'&important='+rcl_chat_important;
    dataString += '&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: Rcl.ajaxurl,
        success: function(data){
            
            rcl_preloader_hide();
            
            if(data['errors']){
                jQuery.each(data['errors'], function( index, error ) {
                    rcl_notice(error,'error',10000);
                });
            }
            
            if(data['success']){
                
                jQuery(e).parents('.chat-messages-box').animateCss('fadeOut',function(e){
                    jQuery(e).html(data['content']).animateCss('fadeIn');
                });
                
                rcl_chat_scroll_bottom(token);
                
            }
        } 
    });	  	
    return false;
}

function rcl_get_mini_chat(e,user_id){

    if(rcl_chat_contact_token){
        rcl_chat_clear_beat(rcl_chat_contact_token);
    }
    
    rcl_preloader_show('#rcl-chat-noread-box > div');
    
    var dataString = 'action=rcl_get_chat_private_ajax&user_id='+user_id;
    dataString += '&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: Rcl.ajaxurl,				
        success: function(data){
            
            rcl_preloader_hide();

            if(data['errors']){
                jQuery.each(data['errors'], function( index, error ) {
                    rcl_notice(error,'error',10000);
                });
            }

            if(data['success']){
                var minichat_box = jQuery('#rcl-chat-noread-box');
                minichat_box.children('.rcl-mini-chat').html(data['content']).animateCss('fadeInRight');
                minichat_box.addClass('active-chat');
                rcl_chat_contact_token = data['chat_token'];
                rcl_set_active_mini_chat(e);
                rcl_chat_scroll_bottom(rcl_chat_contact_token);
            }

        } 
    });
    return false;  
}

function rcl_chat_words_count(e,elem){
    
    evt = e || window.event;
    
    var key = evt.keyCode;
        
    if(key == 13&&evt.ctrlKey){
        var form = jQuery(elem).parents('form');
        rcl_chat_add_new_message(form);
        return false;
    }
    
    var words = jQuery(elem).val();
    var counter = rcl_chat_max_words - words.length;
    var color;

    if(counter > (rcl_chat_max_words-1)) return false;
    
    if(counter<0){
        jQuery(elem).val(words.substr(0, (rcl_chat_max_words-1)));
        return false;
    }
    
    if(counter>150) color = 'green';
    else if(50<counter&&counter<150) color = 'orange';
    else if(counter<50) color = 'red';
    
    jQuery(elem).next('.words-counter').css('color', color).text(counter);
}

function rcl_chat_remove_contact(e,chat_id){
    rcl_preloader_show('.rcl-chat-contacts');
    
    var contact = jQuery(e).parents('.contact-box').data('contact');
    
    var dataString = 'action=rcl_chat_remove_contact&chat_id='+chat_id;
    dataString += '&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: Rcl.ajaxurl,				
        success: function(data){
            
            rcl_preloader_hide();

            if(data['errors']){
                jQuery.each(data['errors'], function( index, error ) {
                    rcl_notice(error,'error',10000);
                });
            }

            if(data['success']){
                jQuery('[data-contact="'+contact+'"]').remove();
                
                rcl_do_action('rcl_chat_remove_contact',chat_id);
                
            }

        } 
    });
    return false; 
}

function rcl_chat_message_important(message_id){
    rcl_preloader_show('.chat-message[data-message="'+message_id+'"] > div');
    var dataString = 'action=rcl_chat_message_important&message_id='+message_id;
    dataString += '&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: Rcl.ajaxurl,				
        success: function(data){
            
            rcl_preloader_hide();

            if(data['errors']){
                jQuery.each(data['errors'], function( index, error ) {
                    rcl_notice(error,'error',10000);
                });
            }

            if(data['success']){
                jQuery('.chat-message[data-message="'+message_id+'"]').find('.message-important').toggleClass('active-important');
            }

        } 
    });
    return false; 
}

function rcl_chat_important_manager_shift(e,status){
    
    rcl_preloader_show('.rcl-chat');
    
    rcl_chat_important = status;
    
    var token = jQuery(e).parents('.rcl-chat').data('token');
    
    var dataString = 'action=rcl_chat_important_manager_shift&token='+token+'&status_important='+status;
    dataString += '&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: Rcl.ajaxurl,				
        success: function(data){
            
            rcl_preloader_hide();

            if(data['errors']){
                jQuery.each(data['errors'], function( index, error ) {
                    rcl_notice(error,'error',10000);
                });
            }

            if(data['success']){
                
                jQuery(e).parents('.chat-messages-box').animateCss('fadeOut',function(e){
                    jQuery(e).html(data['content']).animateCss('fadeIn');
                });
                
                rcl_chat_scroll_bottom(token);
                
            }

        } 
    });
    return false; 
}

function rcl_chat_delete_message(message_id){
    
    rcl_preloader_show('.chat-message[data-message="'+message_id+'"] > div');
    
    var dataString = 'action=rcl_chat_ajax_delete_message&message_id='+message_id;
    dataString += '&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: Rcl.ajaxurl,				
        success: function(data){
            
            rcl_preloader_hide();

            if(data['errors']){
                jQuery.each(data['errors'], function( index, error ) {
                    rcl_notice(error,'error',10000);
                });
            }

            if(data['success']){
                jQuery('.chat-message[data-message="'+message_id+'"]').animateCss('flipOutX',function(e){
                    jQuery(e).remove();
                });
                
                rcl_do_action('rcl_chat_delete_message',message_id);
                
            }

        } 
    });
    return false; 
}

function rcl_chat_delete_attachment(e,attachment_id){
    
    rcl_preloader_show('.chat-form > form');
    
    var dataString = 'action=rcl_chat_delete_attachment&attachment_id='+attachment_id;
    dataString += '&ajax_nonce='+Rcl.nonce;
    jQuery.ajax({
        type: 'POST',
        data: dataString,
        dataType: 'json',
        url: Rcl.ajaxurl,				
        success: function(data){
            
            rcl_preloader_hide();

            if(data['errors']){
                jQuery.each(data['errors'], function( index, error ) {
                    rcl_notice(error,'error',10000);
                });
            }

            if(data['success']){
                var form = jQuery(e).parents('form');
                form.find('.rcl-chat-uploader').show();
                form.find('.chat-preloader-file').empty();
            }

        } 
    });
    return false; 
}

function rcl_chat_uploader(token){
    jQuery('.rcl-chat-uploader input[type="file"]').fileupload({
        dataType: 'json',
        type: 'POST',
        url: Rcl.ajaxurl,
        formData:{
            action:'rcl_chat_upload',
            ajax_nonce:Rcl.nonce
        },
        autoUpload:true,
        progressall: function (e, data){
            //var progress = parseInt(data.loaded / data.total * 100, 10);
            //jQuery('#upload-box-message .progress-bar').show().css('width',progress+'px');
        },
        change:function (e, data) {
            
            if(data.files[0]['size']>Rcl.chat.file_size*1024*1024){
                rcl_notice(Rcl.local.upload_size_chat,'error',10000);
                return false;
            }
            
            rcl_preloader_show('.chat-form > form');
            
        },
        done: function (e, data) {
            
            rcl_preloader_hide();
            
            var form = jQuery(e.target).parents('form');
            var preloader = form.find('.chat-preloader-file');
            var uploader = form.find('.rcl-chat-uploader');
            
            var result = data.result;
            
            if(result['error']){
                rcl_notice(result['error'],'error',10000);
                return false;
            }
            
            if(result['success']){
                preloader.html('<a href="#" class="chat-delete-attachment" onclick="rcl_chat_delete_attachment(this,'+result['attachment_id']+');return false;"><i class="fa fa-times" aria-hidden="true"></i></a>'+result['icon_html']+result['input_html']);
                uploader.hide();
                
                rcl_do_action('rcl_chat_upload',data);
                
            }

        }
    });
}

function rcl_chat_shift_contact_panel(){
    
    var box = jQuery('#rcl-chat-noread-box');
    
    if(box.hasClass('active-chat')) 
        return true;
    
    var view = (jQuery.cookie('rcl_chat_contact_panel')==1)? 0: 1;
    
    if(view){
        box.toggleClass('hidden-contacts').animateCss('slideInUp');
    }else{
        box.animateCss('slideOutDown',function(e){
            jQuery(e).toggleClass('hidden-contacts');
        });
    }
    
    jQuery.cookie('rcl_chat_contact_panel', view, { expires: 30, path: '/'});
    
    return false;
}

rcl_add_action('rcl_init_chat','rcl_chat_init_beat');
function rcl_chat_init_beat(chat){
    var delay = (chat.delay!=0)? chat.delay: Rcl.chat.delay,chat;
    rcl_add_beat('rcl_chat_beat_core',delay,chat);
}

function rcl_chat_beat_core(chat){
    
    if(chat.timeout == 1){
        if(rcl_chat_inactive_counter>=Rcl.chat.inactivity){
            console.log('inactive:'+rcl_chat_inactive_counter);
            return false;
        }
    }
    
    var chatBox = jQuery('.rcl-chat[data-token="'+chat.token+'"]');
        
    var chat_form = chatBox.find('form');

    var beat = {
        action:     'rcl_chat_get_new_messages',
        success:    'rcl_chat_beat_success',
        data:{
            last_activity: rcl_chat_last_activity,
            token: chat.token,
            user_write: (chat_form.find('textarea').val())? 1: 0
        }
    };
    
    return beat;

}

function rcl_chat_beat_success(data){
    
    var chat = jQuery('.rcl-chat[data-token="'+data.token+'"]');
    
    var user_write = 0;
    chat.find('.chat-users').html('');
    rcl_chat_write_status_cancel(data.token);

    if(data['errors']){
        jQuery.each(data['errors'], function( index, error ) {
            rcl_notice(error,'error',10000);
        });
    }

    if(data['success']){

        rcl_chat_last_activity = data['current_time'];

        if(data['users']){
            jQuery.each(data['users'], function( index, data ) {
                chat.find('.chat-users').append(data['link']);
                if(data['write']==1) user_write = 1;
            });
        }

        if(data['content']){
            jQuery.ionSound.play(rcl_chat_sound.sounds[0]);
            chat.find('.chat-messages').append(data['content']);
            rcl_chat_scroll_bottom(data.token);
        }else{
            if(user_write) 
                rcl_chat_write_status(data.token);
        }
    }

    rcl_do_action('rcl_chat_get_messages',{token:data.token,result:data});
    
}