jQuery(function($){ 
    rcl_cover_uploader();
});

function rcl_cover_uploader(){
    jQuery('#rcl-cover-upload').fileupload({
        dataType: 'json',
        type: 'POST',
        url: Rcl.ajaxurl,
        formData:{action:'rcl_cover_upload',ajax_nonce:Rcl.nonce},
        loadImageMaxFileSize: Rcl.profile.cover_size*1024*1024,
        autoUpload:false,
        previewMaxWidth: 900,
        previewMaxHeight: 900,
        imageMinWidth:200,
        imageMinHeight:200,
        disableExifThumbnail: true,
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            jQuery('#avatar-upload-progress').show().html('<span>'+progress+'%</span>');
        },
        add: function (e, data) {
            if(!data.form) return false;
            jQuery.each(data.files, function (index, file) {
                
                jQuery('#rcl-preview').remove();

                if(file.size>Rcl.profile.cover_size*1024*1024){
                    rcl_notice(Rcl.local.upload_size_cover,'error',10000);
                    return false;
                }

                var reader = new FileReader();
                reader.onload = function(event) {
                    var jcrop_api;
                    var imgUrl = event.target.result;
                    
                    jQuery('body > div').last().after('<div id=rcl-preview><img src="'+imgUrl+'"></div>');
                    
                    var image = jQuery('#rcl-preview img');
                    
                    image.load(function() {
                        var img = jQuery(this);
                        var height = img.height();
                        var width = img.width();
                        var jcrop_api;
                        img.Jcrop({
                            minSize:[200,200],
                            onSelect:function(c){
                                img.attr('data-width',width).attr('data-height',height).attr('data-x',c.x).attr('data-y',c.y).attr('data-w',c.w).attr('data-h',c.h);
                            }
                        },function(){
                            jcrop_api = this;
                        });
                        
                        ssi_modal.show({
                            sizeClass: 'auto',
                            title: Rcl.local.title_image_upload,
                            className: 'rcl-hand-uploader',
                            buttons: [{
                                className: 'btn btn-primary',
                                label: Rcl.local.upload,
                                closeAfter: true,
                                method: function () {
                                    data.submit();
                                }
                            }, {
                                className: 'btn btn-danger',
                                label: Rcl.local.close,
                                closeAfter: true,
                                method: function () {
                                    jcrop_api.destroy();
                                }
                            }],
                            content: jQuery('#rcl-preview'),
                            extendOriginalContent:true
                        });

                    });

                };

                reader.readAsDataURL(file);

            });
        },
        submit: function (e, data) {
            var image = jQuery('#rcl-preview img');
            if (parseInt(image.data('w'))){
                var src = image.attr('src');
                var width = image.data('width');
                var height = image.data('height');
                var x = image.data('x');
                var y = image.data('y');
                var w = image.data('w');
                var h = image.data('h');
                data.formData = {
                    coord: x+','+y+','+w+','+h,
                    image: width+','+height,
                    action:'rcl_cover_upload',
                    ajax_nonce:Rcl.nonce
                };
            }
        },
        done: function (e, data) {
            if(data.result['error']){
                rcl_notice(data.result['error'],'error',10000);
                return false;
            }
            jQuery('#lk-conteyner').css('background-image','url('+data.result['cover_url']+')').animateCss('fadeIn');
            jQuery('#avatar-upload-progress').hide().empty();
            jQuery( '#rcl-preview' ).remove();
            rcl_notice(data.result['success'],'success',10000);
            
            rcl_do_action('rcl_success_upload_cover', data);
        }
    });
}
