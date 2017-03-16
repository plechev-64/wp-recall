function rcl_close_votes_window(e){
    jQuery(e).parent().remove();
    return false;
}

function rcl_edit_rating(e){
    var block = jQuery(e);
    var parent = block.parents('.rcl-rating-box');
    var rating = block.data('rating');
    
    rcl_preloader_show(jQuery(e).parents('.rating-wrapper').children('.rating-value'),30);

    var dataString = 'action=rcl_edit_rating_post&rating='+rating;
    dataString += '&ajax_nonce='+Rcl.nonce;

    jQuery.ajax({
        type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
        success: function(result){
            
            rcl_preloader_hide();
            
            if(result['error']){
                rcl_notice(result['error'],'error',10000);
                return false;
            }
            
            if(result['result']==100){
                
                parent.find('.rating-value').html(result['rating']);
                
                if(!block.hasClass('user-vote')){
    
                    parent.find('.rating-vote').removeClass('user-vote');
                    
                }
                
                block.toggleClass('user-vote');

                /*var val = jQuery('#' + idbox + ' .rating-value');
                val.empty().text(result['rating']);
                if(result['rating']<0){
                    val.parent().css('color','#FF0000');
                }else{
                    val.parent().css('color','#008000');
                }
                block.parent().remove();*/
            }
            
            rcl_do_action('rcl_edit_rating',{data:rating,result:result});
            
        }
    });
    return false;
}

function rcl_get_list_votes(e){
    if(jQuery(this).hasClass('active')) return false;
    rcl_preloader_show('#tab-rating .votes-list');
    jQuery('#tab-rating a.get-list-votes').removeClass('active');
    jQuery(e).addClass('active');
    var rating = jQuery(e).data('rating');

    var dataString = 'action=rcl_view_rating_votes&rating='+rating+'&content=list-votes';
    dataString += '&ajax_nonce='+Rcl.nonce;

    jQuery.ajax({
        type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
        success: function(data){
            if(data['result']==100){
                jQuery('#tab-rating .rating-list-votes').html(data['window']);
            }else{
                rcl_notice(Rcl.local.error,'error',10000);
            }
            rcl_preloader_hide();
        }
    });
    return false;
}

function rcl_view_list_votes(e){
    jQuery('.rating-value-block .votes-window').remove();
    var block = jQuery(e);
    var rating = block.data('rating');
    
    rcl_preloader_show(jQuery(e),30);

    var dataString = 'action=rcl_view_rating_votes&rating='+rating;
    dataString += '&ajax_nonce='+Rcl.nonce;

    jQuery.ajax({
        type: 'POST', data: dataString, dataType: 'json', url: Rcl.ajaxurl,
        success: function(data){
            
            rcl_preloader_hide();
            
            if(data['result']==100){
                block.after(data['window']);
                block.next().slideDown();
            }else{
                rcl_notice(Rcl.local.error,'error',10000);
            }
        }
    });
    return false;
}