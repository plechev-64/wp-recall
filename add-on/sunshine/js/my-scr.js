(function($){ 
    // Скрипт группировки кнопок
    alignMenu();
    $(window).resize(function() {
        $("#lk-menu").append($("#lk-menu .hideshow ul").html());
        $("#lk-menu .hideshow").remove();
        alignMenu();
    });
    function alignMenu() {
        var mw = $("#lk-menu").outerWidth() - 30;                           // ширина блока - отступ на кнопку
        var i = -1;
        var menuhtml = '';
        var totalWidth = 0;                                                 // сумма ширины всех кнопок
        jQuery.each($("#lk-menu").children('.rcl-tab-button'), function() {
            totalWidth += $(this).children().outerWidth(true);                  // считаем ширину всех кнопок с учетом отступов
            if (mw < totalWidth) {                                          // если ширина блока кнопок меньше чем сумма ширины кнопок:
                menuhtml += $('<div>').append($(this).clone()).html();
                $(this).remove();
            }
        });
        $("#lk-menu").append(                                               // формируем в кнопке контент
            '<span  style="position:absolute;" class="rcl-tab-butt hideshow">'
             + '<a class="recall-button block_button bars" ><i class="fa fa-bars"></i></a>'
             + '<ul>' + menuhtml + '</ul></span>'
        );
        $('#lk-menu .hideshow').click(function(event){                      // по клику на кнопке
            $(this).children("ul").toggleClass("bounce");
        });
        $(document).mouseup(function (e){                                   // по клику за ее пределами
            var container = $('.rcl-tab-butt');
            if (!container.is(e.target)                                     // if the target of the click isn't the container...
                    && container.has(e.target).length === 0){               // ... nor a descendant of the container
                        $('.hideshow ul').removeClass("bounce");
            }
        });
		var hideshow = $("#lk-menu .rcl-tab-butt.hideshow");
        if (menuhtml == '') {                                               // если нет контента в кнопке - скрываем её
            hideshow.hide();
        } else {
            hideshow.show();
        }
    }
    

})(jQuery);