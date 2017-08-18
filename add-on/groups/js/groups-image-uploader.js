jQuery(function($){
    $('#groupavatarupload').fileupload({
        dataType: 'json',
        type: 'POST',
        url: Rcl.ajaxurl,
        formData:{action:'rcl_group_avatar_upload',ajax_nonce:Rcl.nonce},
        loadImageMaxFileSize: Rcl.groups.avatar_size,
        autoUpload:true,
        imageMinWidth:150,
        imageMinHeight:150,
        disableExifThumbnail: true,
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#avatar-upload-progress').show().html('<span>'+progress+'%</span>');
        },
        submit: function (e, data) {
            var group_id = $('#groupavatarupload').parents('#rcl-group').data('group');
            data.formData = {
                group_id: group_id,
                ajax_nonce:Rcl.nonce,
                action:'rcl_group_avatar_upload'
            };
        },
        done: function (e, data) {

            if(data.result['error']){
                rcl_notice(data.result['error'],'error',10000);
                return false;
            }
            
            var image = $('#rcl-group .group-avatar img').attr('src',data.result['avatar_url']);
            image.load(function(){
                image.animateCss('zoomIn');
            });

            $('#avatar-upload-progress').hide().empty();
            rcl_notice(data.result['success'],'success',10000);

        }
    });
});


