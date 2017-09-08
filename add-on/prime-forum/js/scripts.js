var PFM = {};

rcl_add_action('rcl_pre_init_ajax_editor','pfm_wrap_input_quicktags_editor');
rcl_add_action('rcl_init','pfm_wrap_input_quicktags_editor');
function pfm_wrap_input_quicktags_editor(){
    
    if(typeof QTags === 'undefined') return false;
    
    QTags.Button.prototype.html = function(idPrefix) {
        
        var active, on, wp,
                title = this.title ? ' title="' + pfm_escape( this.title ) + '"' : '',
                ariaLabel = this.attr && this.attr.ariaLabel ? ' title="' + pfm_escape( this.attr.ariaLabel ) + '"' : '',
                val = this.display ? ' value="' + pfm_escape( this.display ) + '"' : '',
                id = this.id ? ' id="' + pfm_escape( idPrefix + this.id ) + '"' : '',
                dfw = ( wp = window.wp ) && wp.editor && wp.editor.dfw;

        if ( this.id === 'fullscreen' ) {
                return '<button type="button"' + id + ' class="ed_button qt-dfw qt-fullscreen"' + title + ariaLabel + '></button>';
        } else if ( this.id === 'dfw' ) {
                active = dfw && dfw.isActive() ? '' : ' disabled="disabled"';
                on = dfw && dfw.isOn() ? ' active' : '';

                return '<button type="button"' + id + ' class="ed_button qt-dfw' + on + '"' + title + ariaLabel + active + '></button>';
        }

        return '<span id="qt_button_' + this.id + '"><input type="button"' + id + ' class="ed_button button button-small"' + title + ariaLabel + val + ' /></span>';
    };
    
}

function pfm_escape( text ) {
    text = text || '';
    text = text.replace( /&([^#])(?![a-z1-4]{1,8};)/gi, '&#038;$1' );
    return text.replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' ).replace( /'/g, '&#039;' );
}

function pfm_getSelectedText(){
    var text = "";
    if (window.getSelection) {
        text = window.getSelection();
    }else if (document.getSelection) {
        text = document.getSelection();
    }else if (document.selection) {
        text = document.selection.createRange().text;
    }
    return text.toString();
}

function pfm_ajax_action(object,e){
    
    if(object['confirm']){
        if(!confirm(object['confirm'])) return false;
    }
    
    if(e && jQuery(e).parents('.preloader-box')){
        rcl_preloader_show(jQuery(e).parents('.preloader-box'));
    }else{
    
        if(object.item_type == 'post'){
            rcl_preloader_show(jQuery('#topic-post-'+object['item_id']));
            rcl_preloader_show(jQuery('#post-manager'));
        }else{
            rcl_preloader_show(jQuery('#prime-forum'));
        }
    
    }
    
    if(object.method == 'get_post_excerpt'){
        object.excerpt = pfm_getSelectedText();
    }
    
    if(object['serialize_form']){
        object.formdata = jQuery('#'+object['serialize_form']).serialize();
    }
    
    object.group_id = Rcl.PForum.group_id;
    object.forum_id = Rcl.PForum.forum_id;
    object.topic_id = Rcl.PForum.topic_id;
    
    object.action = 'pfm_ajax_action';
    object.ajax_nonce = Rcl.nonce;
    
    jQuery.ajax({
        type: 'POST', data: object, dataType: 'json', url: Rcl.ajaxurl,
        success: function(data){
            
            if(data['url-redirect']){

                var url = data['url-redirect'].split('#');

                if(window.location.href == url[0]){
                    location.reload();
                }else{
                    location.replace(data['url-redirect']);
                }

                return;
            }
            
            if(data['update-page']){
                location.reload();
                return;
            }
            
            rcl_preloader_hide();

            if(data['error']){
                rcl_notice(data['error'],'error',10000);
                return false;
            }
            
            if(data['current_url']){
                rcl_update_history_url(data['current_url']);
            }
            
            if(data['dialog']){
                
                if(jQuery('#ssi-modalContent').size()) ssi_modal.close();
                
                var ssiOptions = {
                    className: 'rcl-dialog-tab forum-manager-dialog' + (data['dialog-class']? ' '+data['dialog-class']: ''),
                    sizeClass: data['dialog-width']? data['dialog-width']: 'auto',
                    buttons: [{
                        label: Rcl.local.close,
                        closeAfter: true
                    }],
                    content: data['content']
                };
                
                if(data['title'])
                    ssiOptions.title = data['title'];
                
                ssi_modal.show(ssiOptions);
                
            }else{

                if(data['content']){
                    
                    if(data['append']){
                        
                        if(object['method'] == 'post_create'){
                            
                            jQuery('#prime-forum .prime-posts .new-post').removeClass('new-post');
                            jQuery('#editor-action_post_create').val('');
                            jQuery('#prime-topic-form-box input[name="pfm-data[form_load]"]').val(data['form_load']);
                            
                            //var offsetTop = jQuery('#topic-post-'+data['post_id']).offset().top;
                            //jQuery('body,html').animate({scrollTop:offsetTop - 160}, 1000);
                            
                            pfm_animate_new_posts(data['content']);
                            
                            PFM.last_beat = data.form_load;
                            
                            if(!rcl_exist_beat('pfm_topic_beat')){
                                rcl_add_beat("pfm_topic_beat",30,{topic_id:data.topic_id,start_beat:data.form_load});
                            }
                            
                        }else{
                            jQuery(data['append']).append(data['content']);
                        }
                        
                    }else if(data['place-id']){
                        
                        if(object['method'] == 'get_post_excerpt'){             
                            jQuery(data['place-id']).insertAtCaret(data['content']);
                        }else{
                            jQuery(data['place-id']).text(data['content']);
                        }

                        var offsetTop = jQuery(data['place-id']).offset().top;
                        jQuery('body,html').animate({scrollTop:offsetTop - 100}, 1000);
                        
                    }else{
                        jQuery('#post-manager').html(data['content']);
                    }
                }

            }
            
            if(data['remove-item']){
                jQuery('#' + data['remove-item']).slideUp();
            }

            if(data['dialog-close']){
                ssi_modal.close();
            }
            
            rcl_do_action('pfm_ajax_action_success',{result: data, object: object});

        }
    });
    
}

function pfm_animate_new_posts(contents){
    if(!contents.length) return false;
    var content = contents.shift();
    jQuery('#prime-forum .prime-posts').append(content).find('.prime-post').last().animateCss('slideInUp',function(e){
        jQuery(e).addClass('new-post');
        pfm_animate_new_posts(contents);
    });
}

function pfm_spoiler(e){
    var link = jQuery(e);
    var icon = link.children('i');
    link.parent().children('div').slideToggle();
    icon.toggleClass('fa-plus-square-o fa-minus-square-o');
}

function pfm_topic_beat(initData){
    
    if(!PFM.last_beat)
        PFM.last_beat = initData.start_beat;

    var beat = {
        action:     'pfm_topic_beat',
        success:    'pfm_topic_beat_success',
        data: {
            last_beat: PFM.last_beat,
            topic_id: initData.topic_id,
            forum_id: initData.forum_id,
            group_id: initData.group_id
        }
    };
    
    return beat;
    
}

function pfm_topic_beat_success(result){
    
    PFM.last_beat = result.last_beat;
    
    jQuery('#prime-topic-form-box input[name="pfm-data[form_load]"]').val(result.last_beat);
    
    jQuery('#prime-forum .prime-visitors .visitors-list').html(result.visitors);
    
    if(result.content){
    
        jQuery('#prime-forum .prime-posts .new-post').removeClass('new-post');

        pfm_animate_new_posts(result.content);
    
    }
    
}