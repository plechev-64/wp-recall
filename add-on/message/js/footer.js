jQuery(function($){
    jQuery('#lk-content').on('click','.link-file-rcl',function(){
        jQuery(this).parent().text('Removes the file from the server');
    });
    
    if(!Rcl.private.sort){
        var div = jQuery('#resize-content');
        div.scrollTop( div.get(0).scrollHeight );

        var chatHeight = 'chatHeight';
        var chatNow = jQuery.cookie(chatHeight);
        if(chatNow != null)
            jQuery('#resize-content,#resize').css('height', chatNow + 'px');
        jQuery('#resize').resizable( {
            alsoResize: '#resize-content',
            stop: function(event, ui) {
                chatNow = jQuery('#resize-content').height();
                jQuery.cookie(chatHeight, chatNow);
            }
        });
    }

    jQuery('#upload-private-message').fileupload({
        dataType: 'json',
        type: 'POST',
        url: Rcl.ajaxurl,
        formData:{
            action:'rcl_message_upload',
            talker:jQuery('input[name="adressat_mess"]').val(),
            online:jQuery('input[name="online"]').val(),
            ajax_nonce:Rcl.nonce
        },
        loadImageMaxFileSize: Rcl.private.filesize_mb*1024*1024,
        autoUpload:true,
        progressall: function (e, data){
            var progress = parseInt(data.loaded / data.total * 100, 10);
            jQuery('#upload-box-message .progress-bar').show().css('width',progress+'px');
        },
        change:function (e, data) {
            if(data.files[0]['size']>Rcl.private.filesize_mb*1024*1024){
                rcl_notice('Exceeds the maximum size for a picture! Max. '+Rcl.private.filesize_mb+' MB','error');
                return false;
            }
        },
        done: function (e, data) {
            var result = data.result;
            if(result['recall']==100){
                var text = 'The file was sent successfully.';
            }
            if(result['recall']==150){
                var text = 'You have exceeded the limit on the number of uploaded files. Wait until the files sent previously will be accepted.';
            }
            
            var rcl_replace = "<div class='public-post message-block file'><div class='content-mess'><p style='margin-bottom:0px;' class='time-message'><span class='time'>"+result['time']+"</span></p><p class='balloon-message'>"+text+"</p></div></div>";
            var rcl_newmess = "<div class='new_mess'></div>";

            if(Rcl.private.sort){
                rcl_replace = rcl_newmess+rcl_replace;
            }else{
                rcl_replace += rcl_newmess;
            }
            
            jQuery('#message-list .new_mess').replaceWith(rcl_replace);
            jQuery('#upload-box-message .progress-bar').hide();
            
            if(!Rcl.private.sort){
                var div = jQuery('#resize-content');
                div.scrollTop( div.get(0).scrollHeight );
            }
        }
    });
    
});