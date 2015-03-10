jQuery(function(){
    jQuery.datepicker.setDefaults(jQuery.extend(jQuery.datepicker.regional["ru"]));
    jQuery(".datepicker").datepicker({
        monthNames: [ "Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь" ],
        dayNamesMin: [ "Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс" ],
        dateFormat: 'dd.mm.yy',
        yearRange: "1950:c+3",
        changeYear: true
      });
});
