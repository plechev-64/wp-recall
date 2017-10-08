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
            
            rcl_ajax({
                data: {
                    action: 'rcl_feed_progress',
                    paged: feed_page,
                    content: jQuery('#rcl-feed').data('feed'),
                    custom: jQuery('#rcl-feed').data('custom')
                }, 
                success: function(result){

                    if(result['code']){
                        ++feed_page;
                        feed_progress = false;
                    }

                    jQuery('#rcl-feed .feed-box').last().after(result['content']);

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
        
        var userID = link.data('feed');

        rcl_ajax({
            data: {
                action: 'rcl_feed_callback',
                data: userID,
                callback: link.data('callback')
            }, 
            success: function(result){

                if(result['success']){
                    var type = 'success';
                } else {
                    var type = 'error';
                }

                if(result['return']=='this') link.parent().html('<span class=\''+type+'\'>'+result[type]+'</span>');
                if(result['this']) link.children('span').html(result['this']);
                if(result['all']){
                    jQuery('#rcl-feed .user-link-'+userID+' a').children('span').html(result['all']);
                    jQuery('#rcl-feed .feed-user-'+userID).hide();
                }

                link.addClass('feed-callback');
                link.children('i').attr('class',class_i);
            }
        });

        return false;
    });
    
});