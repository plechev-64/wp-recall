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
				LkMenu.append($('#sunshine_ext_menu ul').html());
				$('#lk-menu .hideshow').remove();
				$('#sunshine_ext_menu').remove();
				alignMenu();
			} else {
				typeButton.removeClass('horizontal-menu').addClass('vertical-menu');
				LkMenu.append($('#sunshine_ext_menu ul').html());
				$('#lk-menu .hideshow').remove();
				$('#sunshine_ext_menu').remove();
			}
		});
	} else if (typeButton.hasClass('horizontal-menu')){
		alignMenu();
		$(window).resize(function() {
			LkMenu.append($('#sunshine_ext_menu ul').html());
			$('#lk-menu .hideshow').remove();
			$('#sunshine_ext_menu').remove();
			alignMenu();
		});
	}
	
// отступ сверху до наших кнопок
	function countHeight(){
		var hUpMenu = $("#lk-menu").offset().top + 2;
		$('#sunshine_ext_menu').css({'top' : hUpMenu});
	}
	
// группировка кнопок
    function alignMenu() {
		
        var mw = LkMenu.outerWidth() - 30;                           		// ширина блока - отступ на кнопку
        var menuhtml = '';
        var totalWidth = 0;                                                 // сумма ширины всех кнопок
		
        $.each(LkMenu.children('.rcl-tab-button'), function() {
            totalWidth += $(this).children().outerWidth(true);				// считаем ширину всех кнопок с учетом отступов
            if (mw < totalWidth) {                                          // если ширина блока кнопок меньше чем сумма ширины кнопок:
                menuhtml += $('<div>').append($(this).clone()).html();
                $(this).remove();
            }
        });
        LkMenu.append(                                               		// формируем в кнопке контент
            '<span style="position:absolute;" class="rcl-tab-butt hideshow">'
             + '<a class="recall-button block_button bars" ><i class="fa fa-bars"></i></a>'
             + '</span>'
        );
		$('body').append(
			'<div id="sunshine_ext_menu"><ul>' + menuhtml + '</ul></div>'
		);
		var RcOverlay = $('#rcl-overlay').css({'display' : ''});
		var extMenu = $('#sunshine_ext_menu');
        $('#lk-menu .hideshow').click(function(event){                      // по клику на кнопке
            extMenu.toggleClass('bounce', 500);
			RcOverlay.fadeToggle(100);
			countHeight();
        });
		function closeExtMenu(){
			extMenu.removeClass('bounce');
			RcOverlay.fadeOut(100);
			extMenu.css({'top' : ''});
		}
		RcOverlay.on('click', function () {
			closeExtMenu();
		});
		$('#sunshine_ext_menu a').on('click', function () {
			closeExtMenu();
		});
		var hideshow = $('#lk-menu .rcl-tab-butt.hideshow');
        if (menuhtml == '') {                                               // если нет контента в кнопке - скрываем её
            hideshow.hide();
        } else {
            hideshow.show();
        }
    }

})(jQuery);
