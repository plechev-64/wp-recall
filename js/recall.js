jQuery(window).load(function() {
	jQuery(document.body).bind("drop", function(e){
		e.preventDefault();
	});
});

var get_param = new Array();

function init_location_data(){
	var rcl_tmp = new Array();
	var rcl_tmp2 = new Array();	

	var get = location.search;
	if(get !== ''){
	  rcl_tmp = (get.substr(1)).split('&');
	  for(var i=0; i < rcl_tmp.length; i++) {
	  rcl_tmp2 = rcl_tmp[i].split('=');
	  get_param[rcl_tmp2[0]] = rcl_tmp2[1];
	  }
	}
	return get_param;
}

jQuery(function($){
	
	init_location_data();
	
	$('a.close-notice').live('click',function(){	
		$(this).parent().remove();
		return false;
	});
	
	$('.rcl-smiles > img').live('click hover',function(){
            var block = $(this).next().children();
            if(block.html()) return false;
            block.html('Загрузка...');
            var dir = $(this).data('dir');
            var area = $(this).parent().data('area');
            var dataString = 'action=rcl_get_smiles_ajax&area='+area;
            if(dir) dataString += '&dir='+dir;
            $.ajax({
                type: 'POST', 
                data: dataString, 
                dataType: 'json', 
                url: wpurl+'wp-admin/admin-ajax.php',
                success: function(data){				
                        if(data['result']==1){
                                block.html(data['content']);
                        }else{
                                rcl_notice('Ошибка!','error');
                        }					
                }			
            }); 
            return false;
	});
	
	$(".rcl-smiles-list img").live("click",function(){
		var alt = $(this).attr("alt");
		var area = $(this).parents(".rcl-smiles").data("area");
		$("#"+area).val($("#"+area).val()+" "+alt+" ");
	});
    
    $('form .requared-checkbox').live('click',function(){
        var name = $(this).attr('name');
        var chekval = $('form input[name="'+name+'"]:checked').val();
        if(chekval) $('form input[name="'+name+'"]').attr('required',false);
        else $('form input[name="'+name+'"]').attr('required',true);
    });

    $('#message-list .author-avatar, #rcl-popup .author-avatar').live('click',function(){
            var userid = $(this).attr("user_id");
            if(!userid) return false;
            var ava = $(this).html();
            $(".author-avatar").children().removeAttr('style');
            $(this).children().css('opacity','0.4');
            $("#adressat_mess").val(userid);
            $("#opponent").html(ava);
    //return false;
    });

    function setAttr_rcl(prmName,val){
        var res = '';
        var d = location.href.split("#")[0].split("?");  
        var base = d[0];
        var query = d[1];
        if(query) {
                var params = query.split("&");  
                for(var i = 0; i < params.length; i++) {  
                        var keyval = params[i].split("=");  
                        if(keyval[0] !== prmName) {  
                                res += params[i] + '&';
                        }
                }
        }
        res += prmName + '=' + val;
        return base + '?' + res;
    } 

    $('.rcl-menu .block_button, #lk-conteyner .block_button').click(function() {      
        var url = setAttr_rcl('view',$(this).attr('id'));
        if(url !== window.location){
            if ( history.pushState ){
                window.history.pushState(null, null, url);
            }
        }
        return false;
    });

    $('.close-popup,#rcl-overlay').live('click',function(){
            $('#rcl-overlay').fadeOut();
            $('.floatform').fadeOut();
            $('#rcl-popup').empty();		
            return false;
    });

    $("#temp-files .thumb-foto").live('click',function(){		
            $("#temp-files .thumb-foto").removeAttr("checked");
            $(this).attr("checked",'checked');			
    });

    $(".thumbs a").click(function(){	
                    var largePath = $(this).attr("href");
                    var largeAlt = $(this).attr("title");		
                    $("#largeImg").attr({ src: largePath, alt: largeAlt });
                    $(".largeImglink").attr({ href: largePath });		
                    $("h2 em").html(" (" + largeAlt + ")"); return false;
            });	
	
    var num_field_rcl = $('input .field_thumb_rcl').size() + 1;
    $('#add-new-input-rcl').click(function() {
        if(num_field_rcl<5) $('<tr><td><input type="radio" name="image_thumb" value="'+num_field_rcl+'"/></td><td><input type="file" class="field_thumb_rcl" name="image_file_'+num_field_rcl+'" value="" /></td></tr>').fadeIn('slow').appendTo('.inputs');
		else $(this).remove();
        num_field_rcl++;
		return false;
    });
	
    $('.public-post-group').live('click',function(){				
            $(this).slideUp();
            $(this).next().slideDown();
            return false;
    });
    $('.close-public-form').live('click',function(){				
            $(this).parent().prev().slideDown();
            $(this).parent().slideUp();
            return false;
    });

    $(".float-window-recall .close").live('click',function(){	
            $(".float-window-recall").remove();
            return false; 
    });

    $('.close_edit').click(function(){
        $('.group_content').empty();
    });

    $('.form-tab-rcl .link-tab-rcl').click(function(){
        $('.form-tab-rcl').hide();
        if($(this).hasClass('link-login-rcl')) $('#login-form-rcl').show();
        if($(this).hasClass('link-register-rcl')) $('#register-form-rcl').show();
        if($(this).hasClass('link-remember-rcl')) $('#remember-form-rcl').show();
        return false; 
    });

    $('.block_button').click(function(){
        if($(this).hasClass('active'))return false;
        var id = $(this).attr('id');		
        $(".rcl-menu .recall-button, #lk-conteyner .block_button").removeClass("active");
        $(".recall_content_block").removeClass("active").slideUp();
        $(this).addClass("active");
        $('.'+id+'_block').slideDown().addClass("active");
        return false;
    });

    $('.child_block_button').live('click',function(){
        if($(this).hasClass('active'))return false;
        var id = $(this).attr('id');
        var parent_id = $(this).parent().parent().attr('id');
        $("#"+parent_id+" .child_block_button").removeClass("active");
        $("#"+parent_id+" .recall_child_content_block").removeClass("active").slideUp();
        $(this).addClass("active");
        $('#'+parent_id+' .'+id+'_block').slideDown().addClass("active");
        return false;
    });			

    if(get_param['action-rcl']){
        $('.form-tab-rcl').slideUp();
        $('#'+get_param['action-rcl']+'-form-rcl').slideDown();		
        return false; 
    }

    if(get_param['view']){		
        var id_block = get_param['view'];
        var offsetTop = $("#lk-content").offset().top;
        $('body,html').animate({scrollTop:offsetTop -50}, 1000);
        view_recall_content_block(id_block);
    }
	
	function view_recall_content_block(id_block){
            $(".rcl-menu .recall-button").removeClass("active");
            $(".rcl-content .recall_content_block").removeClass("active");
            //$('.recall_content_block').slideUp();
            $('#'+id_block).addClass("active");
            $('.'+id_block+'_block').addClass("active");
            return false;
    }

    if($("#lk-menu.left-buttons").size()){
        var menu_start = $("#lk-menu.left-buttons").offset().top;
        var w_start = $('.wprecallblock').innerHeight();

        $(window).scroll(function(){
            var w_now = $('.wprecallblock').innerHeight();
            if(!w_now) return false;
            var menu_now = $("#lk-menu.left-buttons").offset().top;
            var th = $(this).scrollTop();
            var cont_top = $("#lk-content").offset().top;
            if ((th > menu_start+90&&w_start===w_now)||(th < menu_now&&w_now>w_start)) {
                    var h = th - menu_start;
                    $("#lk-menu.left-buttons").css('marginTop',h);
            }
            if(th < menu_start){
                    $("#lk-menu.left-buttons").css('marginTop','0');              
            }
        });
    }

	$.fn.extend({
		insertAtCaret: function(myValue){
			return this.each(function(i) {
				if (document.selection) {
					// Для браузеров типа Internet Explorer
					this.focus();
					var sel = document.selection.createRange();
					sel.text = myValue;
					this.focus();
				}
				else if (this.selectionStart || this.selectionStart == '0') {
					// Для браузеров типа Firefox и других Webkit-ов
					var startPos = this.selectionStart;
					var endPos = this.selectionEnd;
					var scrollTop = this.scrollTop;
					this.value = this.value.substring(0, startPos)+myValue+this.value.substring(endPos,this.value.length);
					this.focus();
					this.selectionStart = startPos + myValue.length;
					this.selectionEnd = startPos + myValue.length;
					this.scrollTop = scrollTop;
				} else {
					this.value += myValue;
					this.focus();
				}
			})
		}
	});

	$.cookie = function(name, value, options) {
        if (typeof value !== 'undefined') { 
                options = options || {};
                if (value === null) {
                        value = '';
                        options.expires = -1;
                }
                var expires = '';
                if (options.expires && (typeof options.expires === 'number' || options.expires.toUTCString)) {
                        var date;
                        if (typeof options.expires === 'number') {
                                date = new Date();
                                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
                        } else {
                                date = options.expires;
                        }
                        expires = '; expires=' + date.toUTCString();
                }
                var path = options.path ? '; path=' + (options.path) : '';
                var domain = options.domain ? '; domain=' + (options.domain) : '';
                var secure = options.secure ? '; secure' : '';
                document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
        } else {
                var cookieValue = null;
                if (document.cookie && document.cookie !== '') {
                        var cookies = document.cookie.split(';');
                        for (var i = 0; i < cookies.length; i++) {
                                var cookie = $.trim(cookies[i]);
                                if (cookie.substring(0, name.length + 1) === (name + '=')) {
                                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                                        break;
                                }
                        }
                }
                return cookieValue;
        }
    };
	
	if($.cookie('favs')){		
            favsr=$.cookie('favs'); 
            favsr=favsr.split('|');
            $("#favs").html('<p style="margin:0;" align="right"><a onclick="jQuery(\'#favs\').slideToggle();return false;" href="#">Закрыть</a></p>');
            for(i=1;i<favsr.length;i++){
                    favsl=favsr[i].split(',');
                    if(favsl[1]){ 
                            $("#favs").append('<div><a href="'+favsl[0]+'">'+favsl[1]+'</a> [<a href="javascript://" onclick="delfav(\''+favsl[0]+'\')">x</a>]</div>');
                    }else{
                            delfav(favsl[0]);
                    }
            }
            return false;
    } else {
            $("#favs").html('<p style="margin:0;" align="right"><a onclick="jQuery(\'#favs\').slideToggle();return false;" href="#">Закрыть</a></p><p class="empty"><b>Формируйте свой список интересных страниц сайта с помощью закладок!</b><br />Закладки не добавляются в ваш браузер и действуют только на этом сайте.<br />Для добавления новой закладки,<br>на нужной странице нажмите <b>В закладки</b>.<br> Помните что если очистить Cookies, то закладки тоже исчезнут.<br>Управляйте временем сохранения закладок через настройки вашего браузера для Cookies.</p>');
            return false;
    }
	
	

});

function rcl_more_view(e){
    var link = jQuery(e);
    var icon = link.children('i');
    link.parent().children('div').slideToggle();
    icon.toggleClass('fa-plus-square-o fa-minus-square-o');   
}

function rcl_add_dropzone(idzone){

	jQuery(document.body).bind("drop", function(e){
		var dropZone = jQuery(idzone),
		node = e.target, 
		found = false;
		
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

	jQuery(idzone).bind('dragover', function (e) {
		var dropZone = jQuery(idzone),
			timeout = window.dropZoneTimeout;
			
		if (!timeout) {
			dropZone.addClass('in');
		} else {
			clearTimeout(timeout);
		}
		
		var found = false,
			node = e.target;
			
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

    function passwordStrength(password){
        var desc = new Array();
        desc[0] = "Очень слабый";
        desc[1] = "Слабый";
        desc[2] = "Лучше";
        desc[3] = "Средний";
        desc[4] = "Надёжный";
        desc[5] = "Сильный";
        var score   = 0;
        if (password.length > 6) score++;   
        if ( ( password.match(/[a-z]/) ) && ( password.match(/[A-Z]/) ) ) score++;
        if (password.match(/\d+/)) score++;
        if ( password.match(/.[!,@,#,$,%,^,&,*,?,_,~,-,(,)]/) ) score++;
        if (password.length > 12) score++;
        document.getElementById("passwordDescription").innerHTML = desc[score];
        document.getElementById("passwordStrength").className = "strength" + score;
    }

    

    cookiepar={expires: 9999, path: '/'} // Все закладки общие

    function addfav(title,url) {
        title=title || document.title; url=url || document.location.href; if(title.length>20){
                title=title.substr(0,99)+'..';
        }
        if(jQuery("#favs a[href='"+url+"']").length>0){
                jQuery("#add_bookmarks").html('Страница уже есть в закладках').slideDown().delay(1000).fadeOut(1000);		
                return false;
        }
        if(jQuery.cookie('favs')){
                jQuery.cookie('favs',jQuery.cookie('favs')+'|'+url+','+title,cookiepar);
        } else {
                jQuery.cookie('favs','|'+url+','+title,cookiepar);
        }
        jQuery("#add_bookmarks").html('Закладка добавлена!').slideDown().delay(2000).fadeOut(1000);

        if(jQuery("#favs").text()==='У вас пока нет закладок') {
               jQuery("#favs").html(' ');
        }
        var empty =jQuery("#favs .empty");
        if(empty) jQuery("#favs .empty").remove();
        title=title.split('|');
        jQuery("#favs").append('<div style="display:none" id="newbk"><a href="'+url+'">'+title[0]+'</a> [<a href="javascript://" onclick="delfav(\''+url+'\')">x</a>]</div>');
        jQuery("#newbk").fadeIn('slow').attr('id','');
    }
	
    function delfav(url){
        jQuery("#favs a[href='"+url+"']").parent().fadeOut('slow',function(){
                jQuery(this).empty().remove(); 
                if(jQuery("#favs").html().length<2){
                        jQuery("#favs").html('У вас нет закладок');
                }
        });
        nfavs=''; 
        dfavs=jQuery.cookie('favs');
        dfavs=dfavs.split('|');
        for(i=0;i<dfavs.length;i++){
                if(dfavs[i].split(',')[0]===url){
                        dfavs[i]='';
                }
                if(dfavs[i]!==''){
                        nfavs+='|'+dfavs[i];
                }
        }
        jQuery.cookie('favs',nfavs,cookiepar);
    }
	
	function rcl_notice(text,type){	
		var html = '<div class="notice-window type-'+type+'"><a href="#" class="close-notice"><i class="fa fa-times"></i></a>'+text+'</div>';	
		if(!jQuery('#rcl-notice').size()){
			jQuery('body > div').last().after('<div id="rcl-notice">'+html+'</div>');
		}else{
			if(jQuery('#rcl-notice > div').size()) jQuery('#rcl-notice > div:last-child').after(html);
			else jQuery('#rcl-notice').html(html);
		}
		
		/*var first = jQuery('#rcl-notice > div:first-child');
		setTimeout(function() {
			first.animate({height: 0}, 1000).remove();
		}, 3000);*/
	}
	
	function rcl_preloader_show(e){
		jQuery(e).after('<div class="rcl_preloader"></div>');
	}
	
	function rcl_preloader_hide(){
		jQuery('.rcl_preloader').remove();
	}