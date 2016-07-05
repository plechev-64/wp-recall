(function($){
	var LkMenu = $('#lk-menu');
	var typeButton = $('#rcl-office');
	// определяем какой тип кнопок у нас
	if (typeButton.hasClass('vertical-menu')){
		if ($(window).width() <= 768) {										// ширина экрана
			typeButton.removeClass('vertical-menu').addClass('horizontal-menu');
			alignMenu();
		}
		$(window).resize(function() {										// действия при ресайзе окна
			if ($(window).width() <= 768) {
				typeButton.removeClass('vertical-menu').addClass('horizontal-menu');
				LkMenu.append($('#lk-menu .hideshow ul').html());
				$('#lk-menu .hideshow').remove();
				alignMenu();
			} else {
				typeButton.removeClass('horizontal-menu').addClass('vertical-menu');
				LkMenu.append($('#lk-menu .hideshow ul').html());
				$('#lk-menu .hideshow').remove();
			}
		});
	} else if (typeButton.hasClass('horizontal-menu')){
		alignMenu();
		$(window).resize(function() {
			LkMenu.append($('#lk-menu .hideshow ul').html());
			$('#lk-menu .hideshow').remove();
			alignMenu();
		});
	}
	
    // Скрипт группировки кнопок

    function alignMenu() {
		
        var mw = LkMenu.outerWidth() - 30;                           		// ширина блока - отступ на кнопку
        var i = -1;
        var menuhtml = '';
        var totalWidth = 0;                                                 // сумма ширины всех кнопок
		var RcOverlay = $('#rcl-overlay').removeClass('rcl_menu_bttn_overlay');

        $.each(LkMenu.children('.rcl-tab-button'), function() {
            totalWidth += $(this).children().outerWidth(true);				// считаем ширину всех кнопок с учетом отступов
            if (mw < totalWidth) {                                          // если ширина блока кнопок меньше чем сумма ширины кнопок:
                menuhtml += $('<div>').append($(this).clone()).html();
                $(this).remove();
            }
        });
        LkMenu.append(                                               		// формируем в кнопке контент
            '<span  style="position:absolute;" class="rcl-tab-butt hideshow">'
             + '<a class="recall-button block_button bars" ><i class="fa fa-bars"></i></a>'
             + '<ul>' + menuhtml + '</ul></span>'
        );
        $('#lk-menu .hideshow').click(function(event){                      // по клику на кнопке
            $(this).children('ul').toggleClass('bounce', 1000);
			RcOverlay.toggleClass('rcl_menu_bttn_overlay');
        });
        $(document).mouseup(function (e){                                   // по клику за ее пределами
            var container = $('.rcl-tab-butt');
            if (!container.is(e.target)                                     // if the target of the click isn't the container...
                    && container.has(e.target).length === 0){               // ... nor a descendant of the container
                        $('.hideshow ul').removeClass('bounce');
						RcOverlay.removeClass('rcl_menu_bttn_overlay');
            }
        });
		var hideshow = $('#lk-menu .rcl-tab-butt.hideshow');
        if (menuhtml == '') {                                               // если нет контента в кнопке - скрываем её
            hideshow.hide();
        } else {
            hideshow.show();
        }
    }
    

})(jQuery);