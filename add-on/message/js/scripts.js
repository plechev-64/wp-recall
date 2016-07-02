jQuery(function($){
    
    jQuery('#private-smiles').hover(
        function(){
          jQuery('#private-smiles .smiles').show();
        },
        function(){
          jQuery('#private-smiles .smiles').hide();
        }
    );

    jQuery('#rcl-new-mess').on('click','.view-form',function(){
        jQuery('#rcl-new-mess .prmess').slideDown();
        jQuery(this).slideUp();
        return false;
    });

    jQuery('.delete_old_message').delay(60000).fadeOut();

    function count_word_in_message(word){
        var count = Rcl.private.words - word.val().length;
        return count;
    }

    function get_color_count_word(count){
        var color;
        if(count>150) color = 'green';
        if(count<150) color = 'orange';
        if(count<50) color = 'red';
        return color;
    }

    jQuery('#lk-content').on('keyup','#content_mess',function(){
        var word = jQuery(this);
        count = count_word_in_message(word);
        color = get_color_count_word(count);
        jQuery('#count-word').css('color', color).text(count);
        if(word.val().length > (Rcl.private.words-1))
        word.val(word.val().substr(0, (Rcl.private.words-1)));
    });

    jQuery('#rcl-new-mess').on('keyup','#minicontent_mess',function(){
        var word = jQuery(this);
        count = count_word_in_message(word);
        color = get_color_count_word(count);
        jQuery('#minicount-word').css('color', color).text(count);
        if(word.val().length > (Rcl.private.words-1))
        word.val(word.val().substr(0, (Rcl.private.words-1)));
    });

    jQuery.ionSound({
        sounds: ['e-oh','water_droplet'],
        path: Rcl.private.sounds,
        multiPlay: false,
        volume: '0.5'
    });

    /* Добавление личного сообщения */
    function add_private_message_recall(){
        var content_mess = encodeURIComponent(jQuery('#content_mess').attr('value'));
        var widget = jQuery('#widget-mess').attr('value');
        var adressat_mess = jQuery('#adressat_mess').attr('value');
        if(adressat_mess=='0'){
                rcl_notice('Выберите собеседника!','error'); return false;
        }
        var online = jQuery('#online').attr('value');
        max_sec_update_rcl = 0;
        jQuery('#content_mess').attr('value', '');
        if(content_mess){
                var dataString = 'action=add_private_message_recall&content_mess='+content_mess+'&adressat_mess='+adressat_mess+'&online='+online+'&widget='+widget+'&user_ID='+Rcl.user_ID;
                dataString += '&ajax_nonce='+Rcl.nonce;
        }else{
                return false;
        }
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data['recall']==100){
                    jQuery('.new_mess').replaceWith(data['message_block']);
                    if(!Rcl.private.sort){
                        var div = jQuery('#resize-content');
                        div.scrollTop( div.get(0).scrollHeight );
                    }
                }
                if(data['recall']==200){
                        jQuery('#privatemess').html(data['message_block']).fadeOut(5000);
                }
            }
        });
        return false;
    }
    
    jQuery('#lk-content').on('click','.addmess',function(){
        var content_text = jQuery('#content_mess').val();
        if(content_text) add_private_message_recall();
        return false;
    });

    ctrl = false;

    function breakText() {
        var caret = jQuery('#content_mess').getSelection().start;
        jQuery('#content_mess').insertText('\r\n', caret, false).setSelection(caret+1, caret+1);
    }

    jQuery('#content_mess').keydown(function(event){
        switch (event.which) {
              case 13: return false;
              case 17: ctrl = true;
        }
    });

    jQuery('#content_mess').keyup(function(event){
        var content_text = jQuery('#content_mess').val();
        switch (event.which) {
            case 13:
              if (ctrl){
              if(content_text)
                    add_private_message_recall();
                    return false;
              }
              breakText();
            break;
            case 17: ctrl = false;
        }
    });

    function add_private_minimessage_recall(){
        var content_mess = jQuery('#minicontent_mess').attr('value');
        var widget = jQuery('#widget-mess').attr('value');
        var adressat_mess = jQuery('#miniadressat_mess').attr('value');
        if(content_mess){
            var dataString = 'action=add_private_message_recall&content_mess='+content_mess+'&adressat_mess='+adressat_mess+'&widget='+widget+'&user_ID='+Rcl.user_ID;
            dataString += '&ajax_nonce='+Rcl.nonce;
        }else{
            return false;
        }
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data['recall']==200){
                    jQuery('#privatemess').html(data['message_block']).fadeOut(5000);
                    jQuery('#rcl-new-mess').delay(2000).queue(function () {jQuery('#rcl-new-mess').empty();jQuery('#rcl-new-mess').dequeue();});
                }
            }
        });
        return false;
    }
    
    jQuery('#rcl-new-mess').on('click','.miniaddmess',function(){
    var content_text = jQuery('#rcl-new-mess #minicontent_mess').val();
    if(content_text)
        add_private_minimessage_recall();
    });

    ctrl = false;

    function minibreakText() {
      var caret = jQuery('#minicontent_mess').getSelection().start;
      jQuery('#minicontent_mess').insertText('\r\n', caret, false).setSelection(caret+1, caret+1);
    }

    jQuery('#minicontent_mess').keydown(function(event){
        switch (event.which) {
          case 13: return false;
          case 17: ctrl = true;
        }
    });

    /* Отмечаем сообщение как прочтенное */
    jQuery('#rcl-new-mess').on('click','.close-mess-window',function(){
        var id_mess = parseInt(jQuery(this).attr('id').replace(/\D+/g,''));
        var dataString = 'action=close_new_message_recall&id_mess='+id_mess+'&user_ID='+Rcl.user_ID;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                    if(data['recall']==100){
                            jQuery('#privatemess').html(data['message_block']).fadeOut(5000);
                            jQuery('#rcl-new-mess').delay(2000).queue(function () {jQuery('#rcl-new-mess').empty();jQuery('#rcl-new-mess').dequeue();});
                    } else {
                            rcl_notice(Rcl.local.error,'error',10000);
                    }
            }
        });
        return false;
    });
    
    /* Добавление в черный список */
    jQuery('#lk-conteyner').on('click','#manage-blacklist',function(){
        var user_id = jQuery(this).data('contact');
        var dataString = 'action=manage_blacklist_recall&user_id='+user_id;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data['otvet']==100){
                    jQuery('#manage-blacklist').replaceWith(data['content']);
                } else {
                    rcl_notice(Rcl.local.error,'error',10000);
                }
            }
        });
        return false;
    });
            
    jQuery('#lk-content').on('click','.remove_black_list',function(){
        var id_user = jQuery(this).data('contact');
        var dataString = 'action=remove_ban_list_rcl&id_user='+id_user+'&user_ID='+Rcl.user_ID;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data['otvet']==100){
                         jQuery('.history-'+data['id_user']).remove();
                } else {
                        rcl_notice(Rcl.local.error,'error',10000);
                }
            }
        });
        return false;
    });
            
    /* Удаление истории переписки */
    jQuery('#lk-content').on('click','.del_history',function(){
        var id_user = jQuery(this).data('contact');
        var dataString = 'action=delete_history_private_recall&id_user='+id_user+'&user_ID='+Rcl.user_ID;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data['otvet']==100){
                         jQuery('.history-'+data['id_user']).remove();
                } else {
                        rcl_notice(Rcl.local.error,'error',10000);
                }
            }
        });
        return false;
    });

    /* Получаем старые сообщения в переписке */
    jQuery('#lk-content').on('click','.old_message',function(){
        rcl_preloader_show('#tab-privat > div');
        block_mess++;
        var dataString = 'action=get_old_private_message_recall&block_mess='+block_mess+'&old_num_mess='+old_num_mess+'&user='+user_old_mess+'&user_ID='+Rcl.user_ID;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data['recall']==100){
                        jQuery('.old_mess_block').replaceWith(data['message_block']);
                        old_num_mess = data['num_mess_now'];
                }
                rcl_preloader_hide();
            }
        });
        return false;
    });

    jQuery('#lk-content').on('click','#get-important-rcl',function(){
        
        rcl_preloader_show('#tab-privat > div');
        
        if(jQuery(this).hasClass('important')){
            jQuery(this).removeClass('important').text(Rcl.local.all_correspond);
            var type = 0;
            if(block_mess) block_mess = 1;
        }else{
            jQuery(this).addClass('important').text(Rcl.local.important_notice);
            var type = 1;
        }
        
        var userid = jQuery('.wprecallblock').data('account');
        var dataString = 'action=get_important_message_rcl&user='+userid+'&type='+type+'&user_ID='+Rcl.user_ID;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data['recall']==100){
                        jQuery('#message-list').html(data['content']);
                }
                rcl_preloader_hide();
            }
        });
        return false;
    });

    jQuery('#lk-content').on('click','#tab-privat .sec_block_button',function(){
        if(jQuery(this).hasClass('active'))return false;
        rcl_preloader_show('#tab-privat > div');
        var days = jQuery(this).attr('data');
        jQuery('.correspond .sec_block_button').removeClass('active');
        jQuery(this).addClass('active');
        var dataString = 'action=get_interval_contacts_rcl&days='+days+'&user_ID='+Rcl.user_ID;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data['recall']==100){
                        jQuery('.correspond #contact-lists').html(data['message_block']);
                } else {
                        rcl_notice(Rcl.local.error,'error',10000);
                }
                rcl_preloader_hide();
            }
        });
        return false;
    });
            
    jQuery('#lk-content').on('click','#get-all-contacts',function(){
        var dataString = 'action=get_interval_contacts_rcl&days=0&user_ID='+user_ID;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data['recall']==100){
                    jQuery('#rcl-overlay').fadeIn();
                    jQuery('#rcl-popup').html('<a href=# class=close-popup></a>'+data['message_block']);
                    var screen_top = jQuery(window).scrollTop();
                    var popup_h = jQuery('#rcl-popup').height();
                    var window_h = jQuery(window).height();
                    screen_top = screen_top + 60;
                    jQuery('#rcl-popup').css('top', screen_top+'px').delay(100).slideDown(400);
                }else{
                    rcl_notice(Rcl.local.error,'error',10000);
                }
            }
        });
        return false;
    });
    
    jQuery('#lk-content').on('click','#tab-privat .important',function(){
        update_important_rcl(jQuery(this).attr('idmess'));
        return false;
    });
    
    function update_important_rcl(id_mess){
        var dataString = 'action=update_important_rcl&id_mess='+id_mess+'&user_ID='+Rcl.user_ID;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(data){
                if(data['res']==100) jQuery('#message-'+id_mess+' .important').addClass('active');
                if(data['res']==200) jQuery('#message-'+id_mess+' .important').removeClass('active');
            }
        });
        return false;
    }
    
});

