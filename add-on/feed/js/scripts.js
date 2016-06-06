jQuery(function($){
    
    var feed_progress = false;
    var feed_page = 2;
    jQuery(window).scroll(function(){
        if(jQuery(window).scrollTop() + jQuery(window).height() >= jQuery(document).height() - 200 && !feed_progress) {
            var feed_load = jQuery('#rcl-feed').data('load');

            if(feed_load!=='ajax'){
                feed_progress = true;
                return false;
            }

            rcl_preloader_show('#feed-preloader > div');
            feed_progress = true;
            var feed_type = jQuery('#rcl-feed').data('feed');
            var dataString = 'action=rcl_feed_progress&paged='+feed_page+'&content='+feed_type;
            dataString += '&ajax_nonce='+Rcl.nonce;
            jQuery.ajax({
                type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
                success: function(result){

                    if(result['code']){
                        ++feed_page;
                        feed_progress = false;
                    }

                    jQuery('#rcl-feed .feed-box').last().after(result['content']);
                    rcl_preloader_hide();
                }
            });
            return false;
        }
    });

    /* Подписываемся на пользователя */
    jQuery('body').on('click','.feed-callback',function(){
        var link = jQuery(this);
        link.removeClass('feed-callback');
        var class_i = link.children('i').attr('class');
        link.children('i').attr('class','fa fa-refresh fa-spin');
        var data = link.data('feed');
        var callback = link.data('callback');
        var dataString = 'action=rcl_feed_callback&data='+data+'&callback='+callback;
        dataString += '&ajax_nonce='+Rcl.nonce;
        jQuery.ajax({
            type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
            success: function(result){
                if(result['success']){
                    var type = 'success';
                } else {
                    var type = 'error';
                }

                if(result['return']=='notice') rcl_notice(result[type],type);
                if(result['return']=='this') link.parent().html('<span class=\''+type+'\'>'+result[type]+'</span>');
                if(result['this']) link.children('span').html(result['this']);
                if(result['all']){
                    jQuery('#rcl-feed .user-link-'+data+' a').children('span').html(result['all']);
                    jQuery('#rcl-feed .feed-user-'+data).hide();
                }

                link.addClass('feed-callback');
                link.children('i').attr('class',class_i);

                rcl_preloader_hide();
            }
        });
        return false;
    });
    
});