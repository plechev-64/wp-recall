jQuery(function(){
    
    jQuery('form .requared-checkbox').live('click',function(){
        var name = jQuery(this).attr('name');
        var chekval = jQuery('form input[name="'+name+'"]:checked').val();
        if(chekval) jQuery('form input[name="'+name+'"]').attr('required',false);
        else jQuery('form input[name="'+name+'"]').attr('required',true);
    });

    jQuery('#message-list .author-avatar, #rcl-popup .author-avatar').live('click',function(){
            var userid = jQuery(this).attr("user_id");
            if(!userid) return false;
            var ava = jQuery(this).html();
            jQuery(".author-avatar").children().removeAttr('style');
            jQuery(this).children().css('opacity','0.4');
            jQuery("#adressat_mess").val(userid);
            jQuery("#opponent").html(ava);
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

    jQuery('#lk-menu .block_button').click(function() {      
        var url = setAttr_rcl('view',jQuery(this).attr('id'));
        if(url !== window.location){
            if ( history.pushState ){
                window.history.pushState(null, null, url);
            }
        }
        return false;
    });

    jQuery('.close-popup,#rcl-overlay').live('click',function(){
            jQuery('#rcl-overlay').fadeOut();
            jQuery('.floatform').fadeOut();
            jQuery('#rcl-popup').empty();		
            return false;
    });

    jQuery("#temp-files .thumb-foto").live('click',function(){		
            jQuery("#temp-files .thumb-foto").removeAttr("checked");
            jQuery(this).attr("checked",'checked');			
    });

    jQuery(".thumbs a").click(function(){	
                    var largePath = jQuery(this).attr("href");
                    var largeAlt = jQuery(this).attr("title");		
                    jQuery("#largeImg").attr({ src: largePath, alt: largeAlt });
                    jQuery(".largeImglink").attr({ href: largePath });		
                    jQuery("h2 em").html(" (" + largeAlt + ")"); return false;
            });	
	
    var num_field_rcl = jQuery('input .field_thumb_rcl').size() + 1;
    jQuery('#add-new-input-rcl').click(function() {
        if(num_field_rcl<5) jQuery('<tr><td><input type="radio" name="image_thumb" value="'+num_field_rcl+'"/></td><td><input type="file" class="field_thumb_rcl" name="image_file_'+num_field_rcl+'" value="" /></td></tr>').fadeIn('slow').appendTo('.inputs');
		else jQuery(this).remove();
        num_field_rcl++;
		return false;
    });
	
    jQuery('.public-post-group').live('click',function(){				
            jQuery(this).slideUp();
            jQuery(this).next().slideDown();
            return false;
    });
    jQuery('.close-public-form').live('click',function(){				
            jQuery(this).parent().prev().slideDown();
            jQuery(this).parent().slideUp();
            return false;
    });

    jQuery(".float-window-recall .close").live('click',function(){	
            jQuery(".float-window-recall").remove();
            return false; 
    });

    jQuery('.close_edit').click(function(){
        jQuery('.group_content').empty();
    });

    jQuery('.form-tab-rcl .link-tab-rcl').click(function(){
        jQuery('.form-tab-rcl').slideUp();
        if(jQuery(this).hasClass('link-login-rcl')) jQuery('#login-form-rcl').slideDown();
        if(jQuery(this).hasClass('link-register-rcl')) jQuery('#register-form-rcl').slideDown();
        if(jQuery(this).hasClass('link-remember-rcl')) jQuery('#remember-form-rcl').slideDown();
        return false; 
    });

    jQuery('.block_button').click(function(){
        if(jQuery(this).hasClass('active'))return false;
        var id = jQuery(this).attr('id');		
        jQuery("#lk-menu > a").removeClass("active");
        jQuery(".recall_content_block").removeClass("active").slideUp();
        jQuery(this).addClass("active");
        jQuery('.'+id+'_block').slideDown().addClass("active");
        return false;
    });

    jQuery('.child_block_button').live('click',function(){
        if(jQuery(this).hasClass('active'))return false;
        var id = jQuery(this).attr('id');
        var parent_id = jQuery(this).parent().parent().attr('id');
        jQuery("#"+parent_id+" .child_block_button").removeClass("active");
        jQuery("#"+parent_id+" .recall_child_content_block").removeClass("active").slideUp();
        jQuery(this).addClass("active");
        jQuery('#'+parent_id+' .'+id+'_block').slideDown().addClass("active");
        return false;
    });			

    if(get_param['action-rcl']){
        jQuery('.form-tab-rcl').slideUp();
        jQuery('#'+get_param['action-rcl']+'-form-rcl').slideDown();		
        return false; 
    }

    if(get_param['view']){		
        var id_block = get_param['view'];
        var offsetTop = jQuery("#lk-content").offset().top;
        jQuery('body,html').animate({scrollTop:offsetTop -50}, 1000);
        view_recall_content_block(id_block);
    }

    if(jQuery("#lk-menu.left-buttons").size()){
        var menu_start = jQuery("#lk-menu.left-buttons").offset().top;
        var w_start = jQuery('.wprecallblock').innerHeight();

        jQuery(window).scroll(function(){
            var w_now = jQuery('.wprecallblock').innerHeight();
            if(!w_now) return false;
            var menu_now = jQuery("#lk-menu.left-buttons").offset().top;
            var th = jQuery(this).scrollTop();
            var cont_top = jQuery("#lk-content").offset().top;
            if ((th > menu_start+90&&w_start===w_now)||(th < menu_now&&w_now>w_start)) {
                    var h = th - menu_start;
                    jQuery("#lk-menu.left-buttons").css('marginTop',h);
            }
            if(th < menu_start){
                    jQuery("#lk-menu.left-buttons").css('marginTop','0');              
            }
        });
    }

    function view_recall_content_block(id_block){
            jQuery("#lk-menu > a").removeClass("active");
            jQuery('.recall_content_block').slideUp();
            jQuery('#'+id_block).addClass("active");
            jQuery('.'+id_block+'_block').slideDown().addClass("active");
            return false;
    }

    if(jQuery.cookie('favs')){		
            favsr=jQuery.cookie('favs'); 
            favsr=favsr.split('|');
            jQuery("#favs").html('<p style="margin:0;" align="right"><a onclick="jQuery(\'#favs\').slideToggle();return false;" href="#">Закрыть</a></p>');
            for(i=1;i<favsr.length;i++){
                    favsl=favsr[i].split(',');
                    if(favsl[1]){ 
                            jQuery("#favs").append('<div><a href="'+favsl[0]+'">'+favsl[1]+'</a> [<a href="javascript://" onclick="delfav(\''+favsl[0]+'\')">x</a>]</div>');
                    }else{
                            delfav(favsl[0]);
                    }
            }
            return false;
    } else {
            jQuery("#favs").html('<p style="margin:0;" align="right"><a onclick="jQuery(\'#favs\').slideToggle();return false;" href="#">Закрыть</a></p><p class="empty"><b>Формируйте свой список интересных страниц сайта с помощью закладок!</b><br />Закладки не добавляются в ваш браузер и действуют только на этом сайте.<br />Для добавления новой закладки,<br>на нужной странице нажмите <b>В закладки</b>.<br> Помните что если очистить Cookies, то закладки тоже исчезнут.<br>Управляйте временем сохранения закладок через настройки вашего браузера для Cookies.</p>');
            return false;
    }
	
});

jQuery.fn.extend({
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

    var FileAPI = {
            debug: true
            , media: true
            , staticPath: rcl_url+'js/fileapi/FileAPI/'
    };
    var examples = [];

    var rcl_tmp = new Array();
    var rcl_tmp2 = new Array();
    var get_param = new Array();

    var get = location.search;
    if(get !== ''){
      rcl_tmp = (get.substr(1)).split('&');
      for(var i=0; i < rcl_tmp.length; i++) {
      rcl_tmp2 = rcl_tmp[i].split('=');
      get_param[rcl_tmp2[0]] = rcl_tmp2[1];
      }
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

    jQuery.cookie = function(name, value, options) {
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
                                var cookie = jQuery.trim(cookies[i]);
                                if (cookie.substring(0, name.length + 1) === (name + '=')) {
                                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                                        break;
                                }
                        }
                }
                return cookieValue;
        }
    };

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
        var empty = jQuery("#favs .empty");
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