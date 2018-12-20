/*jQuery(window).load(function() {
    jQuery('body').on('drop', function (e) {
        return false;
   });
    jQuery(document.body).bind("drop", function(e){
        e.preventDefault();
    }); 
});*/

var RclUploaders = [];

(function($){

	$(document).ready( function(){
            
            jQuery('body').on('drop', function (e) {
                return false;
            });
            jQuery(document.body).bind("drop", function(e){
                e.preventDefault();
            }); 

            RclUploaders.init();

            /*RclUploaders.get('rclUpload').add = function(e, data){
                console.log(data);
            };*/


	});

})(jQuery);

RclUploaders = new RclClassUploaders();

function RclClassUploaders(){
    
    this.uploaders = [];
    
    this.init = function(){

        this.uploaders.forEach(function(uploader, i){
            uploader.init();
        });

    };
    
    this.add = function(props){

        this.uploaders.push(new RclUploader(props));

    };
    
    this.get = function(uploader_id){
        
        var k = false;
        
        this.uploaders.forEach(function(uploader, i){
            
            if(uploader.uploader_id == uploader_id)
                k = i;
        });

        if(k !== false)
            return this.uploaders[k];
        
    }
    
    this.isset = function(uploader_id){
        
        var k = false;
        
        this.uploaders.forEach(function(uploader, i){
            
            if(uploader.uploader_id == uploader_id)
                k = i;
        });

        if(k !== false)
            return true;
        
        return false;
        
    }
    
}

function RclUploader(props){
    
    this.uploader_id = props.uploader_id;
    this.input = jQuery("#rcl-uploader-input-" + this.uploader_id);
    this.button = this.input.parent(".rcl-uploader-button");
    this.options = props;
    
    this.getFormData = function(uploader){
        if(!uploader)
            uploader = this;
        
        var formData = {options:JSON.stringify(uploader.options)};
        
        formData.action = uploader.options.action;
        formData.ajax_nonce = Rcl.nonce;
        
        return formData;
        
    };
    
    this.init = function(){
        
        if(this.options.dropzone)
            rcl_init_dropzone(jQuery("#rcl-dropzone-" + this.uploader_id));
        
        var uploader_id = this.options.uploader_id;
        var uploader = this;
        
        options = {
            dataType: 'json',
            type: 'POST',
            url: Rcl.ajaxurl,
            dropZone: this.options.dropzone? jQuery("#rcl-dropzone-" + this.uploader_id): false,
            formData: this.getFormData(uploader),
            loadImageMaxFileSize: this.options.max_size * 1024,
            autoUpload: this.options.auto_upload,
            singleFileUploads:false,
            limitMultiFileUploads: this.options.max_files,
            imageMinWidth:this.options.min_width,
            imageMinHeight:this.options.min_height,
            imageMaxWidth: 1920,
            imageMaxHeight: 1080,
            imageCrop: false,
            imageForceResize: false,
            previewCrop: false,
            previewThumbnail: true,
            previewCanvas: true,
            previewMaxWidth: 900,
            previewMaxHeight: 900,
            disableExifThumbnail: true,
            progressall: function(e, data){
                RclUploaders.get(uploader_id).progressall(e, data);
            },
            processstart: function(e, data){
                RclUploaders.get(uploader_id).processstart(e, data);
            },
            processdone: function(e, data){
                RclUploaders.get(uploader_id).processdone(e, data);
            },
            processfail: function(e, data){
                RclUploaders.get(uploader_id).processfail(e, data);
            },
            add: function(e, data){
                RclUploaders.get(uploader_id).add(e, data);
            },
            submit: function(e, data){
                RclUploaders.get(uploader_id).submit(e, data);
            },
            done: function(e, data){
                RclUploaders.get(uploader_id).done(e, data);
            }
        };
        
        this.input.fileupload(options);
        
    };
    
    this.processstart = function(e, data){
        
    };
    
    this.processdone = function(e, data){
        
    };
    
    this.processfail = function(e, data){
        
    };
    
    this.progressall = function(e, data){
        /*var progress = parseInt(data.loaded / data.total * 100, 10);
        jQuery('#avatar-upload-progress').show().html('<span>'+progress+'%</span>');*/
    };
    
    this.add = function (e, data) {
        
        var uploader = this;
        var options = uploader.options;
        
        var errors = [];
        
        var inGalleryNow = jQuery('#rcl-upload-gallery-' + uploader.uploader_id + ' .gallery-attachment').length;  
        
        jQuery.each(data.files, function (index, file) {
            
            inGalleryNow++;
                                   
            if(file.size > options.max_size * 1024){
                errors.push('Превышен размер загружаемого файла. Макс: ' + options.max_size + 'Kb');
            }  
            
        });
        
        if(inGalleryNow > options.max_files){
            errors.push('Превышено количество загруженных файлов. Макс: ' + options.max_files);
        }
        
        errors = this.filterErrors(errors, data.files, uploader);

        /*jQuery.each(data.files, function (index, file) {
            
            var reader = new FileReader();

            reader.readAsDataURL(file);

            reader.onload = (function(theFile) { 
                var image = new Image();
                image.src = theFile.target.result;

                image.onload = function() {

                    if(this.width < options.min_width || this.height < options.min_height){
                        console.log(1);
                        errors.push('Выберите изображение большего размера');
                    }
                };
                
            });
            
        });*/
        
        if(errors.length){
            errors.forEach(function(error, i){
                rcl_notice(error, 'error', 10000);
            });
            return false;
        }
        
        if(options.crop != 0 && options.multiple == 0){
            
            return this.crop(e, data);
        }
        
        if (options.auto_upload == 1) {
            data.process().done(function () {
                data.submit();
            });
        }
        
    };
    
    this.filterErrors = function(errors, files, uploader){
        return errors;
    };
    
    this.submit = function(e, data){
        
        this.buttonLoading(true);
        
        if(this.options.crop){
            return this.submitCrop(e, data);
        }
        
    };
    
    this.done = function(e, data){
        
        rcl_preloader_hide();
        
        this.buttonLoading(false);
        
        if(data.result['error']){
            rcl_notice(data.result['error'],'error',10000);
            return false;
        }
        
        if(data.result['success']){
            rcl_notice(data.result['success'],'success',10000);
        }
        
        var uploader = this;
        
        jQuery.each(data.result['uploads'], function(index, file) {
            
            uploader.appendInGallery(file, uploader);
            
        });

        this.afterDone(e, data);
        
        jQuery('#rcl-preview').remove();
        
    };
    
    this.appendInGallery = function(file){
        
        if(file['html']){
            jQuery('#rcl-upload-gallery-' + this.uploader_id).append(file['html']).last().animateCss('flipInX');
        }
    };
    
    this.afterDone = function(e, data){

    };
    
    this.crop = function(e, data){
        
        var uploader = this;
        var crop = uploader.options.crop;
        var minWidthCrop = uploader.options.min_width;
        var minHeightCrop = uploader.options.min_height;

        jQuery.each(data.files, function (index, file) {
            
            jQuery('#rcl-preview').remove();
            
            var maxSize = parseInt(uploader.options.max_size);
            
            if(file.size > maxSize * 1024){
                rcl_notice('Превышен максимальный размер файла. Макс:' + ' ' + maxSize + 'Kb','error',10000);
                return false;
            }

            var reader = new FileReader();
            reader.onload = function(event) {
                var jcrop_api;
                var imgUrl = event.target.result;
                
                var maxWidth = window.innerWidth * 0.9;
                var maxHeight = window.innerHeight * 0.9;

                jQuery('body > div').last().after('<div id=rcl-preview><img style="max-width:'+maxWidth+'px;max-height:'+maxHeight+'px;" src="'+imgUrl+'"></div>');

                var image = jQuery('#rcl-preview img');

                image.load(function() {
                    
                    var img = jQuery(this);
                    var cf = 1;
                    
                    if(img[0].naturalWidth > img.width()){
                        cf = img.width()/img[0].naturalWidth;
                    }
                    
                    minWidthCrop *= cf;
                    minHeightCrop *= cf;
                    
                    var height = img.height();
                    var width = img.width();
                    var jcrop_api;
                    
                    img.Jcrop({
                        aspectRatio: (typeof crop.ratio != 'undefined')? crop.ratio: 1,
                        minSize:[minWidthCrop,minHeightCrop],
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
                            label: 'Ok',
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
        
    };
    
    this.submitCrop = function(e, data){
        
        data.formData = this.getFormData();
        
        var image = jQuery('#rcl-preview img');
        
        if (parseInt(image.data('w'))){

            var width = image.data('width');
            var height = image.data('height');
            var x = image.data('x');
            var y = image.data('y');
            var w = image.data('w');
            var h = image.data('h');

            data.formData.crop_data = [x,y,w,h];
            data.formData.image_size = [width,height];
               
        }
        
    }
    
    this.buttonLoading = function(status){
        if(status)
            this.button.addClass('rcl-bttn__loading');
        else
            this.button.removeClass('rcl-bttn__loading');
    }
    
}

function rcl_init_uploader(props){
    RclUploaders.add(props);
}

function rcl_init_dropzone(dropZone){

    jQuery(document.body).bind("drop", function(e){
        var node = e.target, found = false;

        if(dropZone[0]){		
            dropZone.removeClass('in hover');
            do {               
                if (node === dropZone[0]) {
                    found = true;
                    break;
                }
                node = node.parentNode;
            } while (node != null);

            if(found){
                e.preventDefault();
            }else{			
                return false;
            }
        }
    });

    dropZone.bind('dragover', function (e) {
        var timeout = window.dropZoneTimeout;

        if (!timeout) {
            dropZone.addClass('in');
        } else {
            clearTimeout(timeout);
        }

        var found = false, node = e.target;

        do {
            if (node === dropZone[0]) {
                found = true;
                break;
            }
            node = node.parentNode;
        } while (node != null);

        if (found) {
                dropZone.addClass('hover');
        } else {
                dropZone.removeClass('hover');
        }

        window.dropZoneTimeout = setTimeout(function () {
                window.dropZoneTimeout = null;
                dropZone.removeClass('in hover');
        }, 100);
    });
}

function rcl_delete_attachment(attachment_id, post_id, e){

    if(e)
        rcl_preloader_show(jQuery(e).parents('.gallery-attachment'));
    
    var objectData = {
        action: 'rcl_ajax_delete_attachment',
        post_id: post_id,
        attach_id: attachment_id
    };

    rcl_ajax({
        rest: true,
        data: objectData, 
        success: function(data){

            jQuery('.gallery-attachment-' + attachment_id).animateCss('flipOutX',function(e){
                jQuery(e).remove();
            });

        }
    });

    return false;
}

