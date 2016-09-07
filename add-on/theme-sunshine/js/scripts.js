
(function($){
	var LkMenu = $('#lk-menu');
	var typeButton = $('#rcl-office');
	var RclOverlay = $('#rcl-overlay');
	
// при ресайзе обновляем
function moveMenu() {
	LkMenu.append($('#sunshine_ext_menu ul').html());
	$('#lk-menu .hideshow').remove();
	$('#sunshine_ext_menu').remove();
}

// закрытие меню
function closeExtMenu(){
	if (RclOverlay.hasClass('sunshine_mbl_menu')){ // проверяем что это наш оверлей
		RclOverlay.fadeOut(100).removeClass('sunshine_mbl_menu');
	}
	$('#sunshine_ext_menu').removeClass('bounce').css({'top' : '','right' : ''});
}

// определяем какой тип кнопок у нас
if (typeButton.hasClass('vertical-menu')){
	if ($(window).width() <= 768) { // ширина экрана
		typeButton.removeClass('vertical-menu').addClass('horizontal-menu');
		alignMenu();
	}
	$(window).resize(function() { // действия при ресайзе окна
		if ($(window).width() <= 768) {
			typeButton.removeClass('vertical-menu').addClass('horizontal-menu');
			closeExtMenu();
			moveMenu();
			alignMenu();
		} else {
			typeButton.removeClass('horizontal-menu').addClass('vertical-menu');
			closeExtMenu();
			moveMenu();
		}
	});
} else if (typeButton.hasClass('horizontal-menu')){
	alignMenu();
	$(window).resize(function() {
		closeExtMenu();
		moveMenu();
		alignMenu();
	});
}
	
// отступ сверху-справа до наших кнопок
function menuPosition(){
	var hUpMenu = LkMenu.offset().top + 2;
	$('#sunshine_ext_menu').css({'top' : hUpMenu});
	// считаем ниже отступ когда экран у нас шире контента. Предотвращаем прижатие окна к правому краю. Теперь меню в области гамбургера
	var wRightMenu = ($(window).width() - (LkMenu.offset().left + LkMenu.outerWidth())) - 100;
	if (wRightMenu > 10) { // если у нас есть отступ и он не отрицательный - сдвигаем менюшку
		$('#sunshine_ext_menu').css({'right' : wRightMenu});
	}
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
	LkMenu.append(
		'<span style="position:absolute;" class="rcl-tab-butt hideshow">'
		 + '<a class="recall-button block_button bars" ><i class="fa fa-bars"></i></a>'
		 + '</span>'
	);
	$('body').append(													// формируем в кнопке контент
		'<div id="sunshine_ext_menu"><ul>' + menuhtml + '</ul></div>'
	);

	var hideshow = $('#lk-menu .rcl-tab-butt.hideshow');
	if (menuhtml == '') {                                               // если нет контента в кнопке - скрываем её
		hideshow.hide();
	} else {
		hideshow.show();
	}
	
	$('#lk-menu .hideshow').on('click', function () {
		$('#sunshine_ext_menu').toggleClass('bounce', 500);
		RclOverlay.fadeToggle(100).toggleClass('sunshine_mbl_menu'); // добавляем наш класс оверлею. Чтоб чужой не закрывать
		menuPosition();
	});

	RclOverlay.on('click', function () { closeExtMenu(); });
	$('#sunshine_ext_menu').on('click', function () { closeExtMenu(); });
}

})(jQuery);
