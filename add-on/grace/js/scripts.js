(function($){

// меню слева на 568px
	var LtMenu = $('#lk-menu');
	var RcOverlay = $('#rcl-overlay').css({'display' : ''});
	var LtMobileButton = $('.cab_lt_menu');
	
	function closeMenu(){
		if (RcOverlay.hasClass('lt_mobile_menu')){					// проверяем что это наш оверлей
			RcOverlay.fadeOut(100).removeClass('lt_mobile_menu');
		}
		LtMenu.removeClass('cab_lt_mobile');
		$('.st_menu_moved').css({'top' : ''});
	}
	
	if ($(window).width() <= 568) {									// инициализация при 568px
		LtMenu.addClass('st_menu_moved');							// добавляем класс
		$('body').append(LtMenu);									// переносим в body
		LtMobileButton.css({'display' : 'table'});
	}
	$(window).resize(function() {									// наш ресайз
		if ($(window).width() <= 568) {
			closeMenu();
			if (!LtMenu.hasClass('st_menu_moved')){					// если не имеет этого класса (чтобы не делать лишних append-ов)
				LtMenu.addClass('st_menu_moved');					// добавляем
				$('body').append(LtMenu);							// перемещаем в body
				LtMobileButton.css({'display' : 'table'});
			}
		} else {
			closeMenu();
			var LtSidebar = $('.cab_lt_sidebar');
			if (!LtSidebar.children('div').hasClass('rcl-menu')){	// чтобы не делать лишних append-ов
				LtSidebar.append(LtMenu);							// перемещаем обратно в кабинет
				LtMenu.removeClass('st_menu_moved');				// убиваем класс
				LtMobileButton.css({'display' : 'none'});
			}
		}
	});
	LtMobileButton.on('click', function () {
		var hUpButton = LtMobileButton.offset().top + 2;
		$('.st_menu_moved').css({'top' : hUpButton});
		LtMenu.toggleClass('cab_lt_mobile');
		RcOverlay.fadeToggle(100).toggleClass('lt_mobile_menu');	// добавляем наш класс оверлею. Чтоб чужой не закрывать
	});
	LtMenu.on('click',function (){ closeMenu(); });
	RcOverlay.on('click',function (){ closeMenu(); });

	
// добавляем высоту бекграунду чтобы слева вписать аву и кнопки действий
	function countHeight(){
		var hCabSidebar = $('.lk-sidebar').outerHeight();
		$('.wprecallblock #lk-conteyner').css({'min-height' : hCabSidebar});
	}
	if ($(window).width() <= 568) {
		countHeight();
	}
	$(window).resize(function() {									// при ресайзе пересчитываем высоту
		if ($(window).width() <= 568) {
			countHeight();
		} else {
			$('.wprecallblock #lk-conteyner').css({'min-height' : ''});
		}
	});
	
})(jQuery);