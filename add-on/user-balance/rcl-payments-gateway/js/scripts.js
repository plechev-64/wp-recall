
function rcl_load_payment_form(e){

    //получаем данные формы
    var FormFactory = new RclForm(jQuery(e).parents('form'));

    //проверяем на правильность заполнения
    if(!FormFactory.validate()) return false;

    if(e && jQuery(e).parents('.preloader-box')){
        rcl_preloader_show(jQuery(e).parents('.preloader-box'));
    }

    FormFactory.send('rcl_load_payment_form', function(result){

        jQuery('.rcl-payment-form-type-'+result.pay_type+' .rcl-payment-form-content').html(result.content);

    });

}
