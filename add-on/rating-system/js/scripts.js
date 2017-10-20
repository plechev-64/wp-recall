function rcl_close_votes_window(e){
    jQuery(e).parent().remove();
    return false;
}

function rcl_edit_rating(e){
    
    var block = jQuery(e);
    var parent = block.parents('.rcl-rating-box');

    rcl_preloader_show(jQuery(e).parents('.rating-wrapper').children('.rating-value'),30);
    
    rcl_ajax({
        data: {
            action: 'rcl_edit_rating_post',
            rating: block.data('rating')
        }, 
        success: function(result){

            if(result['result']==100){

                parent.find('.rating-value').html(result['rating']);

                if(!block.hasClass('user-vote')){

                    parent.find('.rating-vote').removeClass('user-vote');

                }

                block.toggleClass('user-vote');

            }

            block.animateCss('zoomIn');
            
            if(result.replace_box){
                
                parent.replaceWith(result.replace_box);
                
            }

            rcl_do_action('rcl_edit_rating',{
                data:block.data('rating'),
                result:result
            });
        }
    });

    return false;
}

function rcl_get_list_votes(e){
    
    if(jQuery(this).hasClass('active')) return false;
    
    rcl_preloader_show('#tab-rating .votes-list');
    
    jQuery('#tab-rating a.get-list-votes').removeClass('active');
    jQuery(e).addClass('active');
    
    rcl_ajax({
        data: {
            action: 'rcl_view_rating_votes',
            rating: jQuery(e).data('rating'),
            content: 'list-votes'
        }, 
        success: function(data){
            
            if(data['content'])
                jQuery('#tab-rating .rating-list-votes').html(data['content']);
            
        }
    });

    return false;
}

function rcl_view_list_votes(e){
    
    jQuery('.rating-value-block .votes-window').remove();
    
    var block = jQuery(e);
    
    rcl_preloader_show(jQuery(e),30);
    
    rcl_ajax({
        data: {
            action: 'rcl_view_rating_votes',
            rating: block.data('rating')
        }, 
        success: function(data){
            block.after(data['content']);
            block.next().slideDown();
        }
    });

    return false;
}