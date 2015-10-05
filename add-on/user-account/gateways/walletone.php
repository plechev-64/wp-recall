<?php

add_action('init','rcl_add_walletone_payment');
function rcl_add_walletone_payment(){
    $pm = new Rcl_Walletone_Payment();
    $pm->register_payment('walletone');
}

class Rcl_Walletone_Payment extends Rcl_Payment{

    public $form_pay_id;

    function register_payment($form_pay_id){
        $this->form_pay_id = $form_pay_id;
        parent::add_payment($this->form_pay_id, array('class'=>get_class($this),'request'=>'WMI_PAYMENT_NO'));
        if(is_admin()) $this->add_options();
    }

    function add_options(){
        add_filter('rcl_pay_option',(array($this,'options')));
        add_filter('rcl_pay_child_option',(array($this,'child_options')));
    }

    function options($options){
        $options[$this->form_pay_id] = __('WalletOne','rcl');
        return $options;
    }

    function child_options($child){

        $opt = new Rcl_Options();

        $child .= $opt->child(
            array(
                'name'=>'connect_sale',
                'value'=>$this->form_pay_id
            ),
            array(
                $opt->title(__('Connection settings WalletOne','rcl')),
                $opt->label(__('Merchant ID','rcl')),
                $opt->option('text',array('name'=>'WO_MERCHANT_ID')),
                $opt->label(__('The secret key','rcl')),
                $opt->option('password',array('name'=>'WO_SECRET_KEY'))
            )
        );

        return $child;
    }

    function pay_form($data){
        global $rmag_options;

        $merchant_id = $rmag_options['WO_MERCHANT_ID'];
        $secret_key = $rmag_options['WO_SECRET_KEY'];

        $curs = array( 'RUB' => 643, 'UAH' => 980, 'KZT' => 398, 'USD' => 840, 'EUR' => 978 );
        $code_cur = (isset($curs[$rmag_options['primary_cur']]))? $curs[$rmag_options['primary_cur']]: 643;

        $fields = array(
            'WMI_MERCHANT_ID'=>$merchant_id,
            'WMI_PAYMENT_AMOUNT'=>$data->pay_summ.'.00',
            'WMI_CURRENCY_ID'=>$code_cur,
            'WMI_PAYMENT_NO'=>$data->pay_id,
            'WMI_SUCCESS_URL'=>get_permalink($rmag_options['page_successfully_pay']),
            'WMI_FAIL_URL'=>get_permalink($rmag_options['page_fail_pay']),
            'WMI_CUSTOMER_ID'=>$data->user_id,
            'USER_ID'=>$data->user_id,
            'TYPE_PAY'=>$data->pay_type
        );

        //Сортировка значений внутри полей
          foreach($fields as $name => $val)
          {
            if (is_array($val))
            {
               usort($val, "strcasecmp");
               $fields[$name] = $val;
            }
          }

          // Формирование сообщения, путем объединения значений формы,
          // отсортированных по именам ключей в порядке возрастания.
          uksort($fields, "strcasecmp");
          $fieldValues = "";

          foreach($fields as $value)
          {
              if (is_array($value))
                 foreach($value as $v)
                 {
                //Конвертация из текущей кодировки (UTF-8)
                    //необходима только если кодировка магазина отлична от Windows-1251
                    $v = iconv("utf-8", "windows-1251", $v);
                    $fieldValues .= $v;
                 }
             else
            {
               //Конвертация из текущей кодировки (UTF-8)
               //необходима только если кодировка магазина отлична от Windows-1251
               $value = iconv("utf-8", "windows-1251", $value);
               $fieldValues .= $value;
            }
          }

          // Формирование значения параметра WMI_SIGNATURE, путем
          // вычисления отпечатка, сформированного выше сообщения,
          // по алгоритму MD5 и представление его в Base64

          $signature = base64_encode(pack("H*", md5($fieldValues . $secret_key)));

          //Добавление параметра WMI_SIGNATURE в словарь параметров формы

          $fields["WMI_SIGNATURE"] = $signature;

        $form = parent::form($fields,$data,'https://wl.walletone.com/checkout/checkout/Index');

        return $form;
    }

    function result($data){
        global $rmag_options;

        $secret_key = $rmag_options['WO_SECRET_KEY'];

        $data->pay_summ = $_REQUEST["WMI_PAYMENT_AMOUNT"];
        $data->pay_id = $_REQUEST["WMI_PAYMENT_NO"];
        $data->user_id = $_REQUEST["USER_ID"];
        $data->pay_type = $_REQUEST["TYPE_PAY"];


        if (!isset($_REQUEST["WMI_SIGNATURE"]))
            $this->print_answer("Retry", "Отсутствует параметр WMI_SIGNATURE");

          if (!isset($_REQUEST["WMI_PAYMENT_NO"]))
            $this->print_answer("Retry", "Отсутствует параметр WMI_PAYMENT_NO");

          if (!isset($_REQUEST["WMI_ORDER_STATE"]))
            $this->print_answer("Retry", "Отсутствует параметр WMI_ORDER_STATE");

          // Извлечение всех параметров POST-запроса, кроме WMI_SIGNATURE

          foreach($_REQUEST as $name => $value)
          {
            if ($name !== "WMI_SIGNATURE") $params[$name] = $value;
          }

          // Сортировка массива по именам ключей в порядке возрастания
          // и формирование сообщения, путем объединения значений формы

          uksort($params, "strcasecmp"); $values = "";

          foreach($params as $name => $value)
          {
            //Конвертация из текущей кодировки (UTF-8)
            //необходима только если кодировка магазина отлична от Windows-1251
            //$value = iconv("utf-8", "windows-1251", $value);
            $values .= $value;
          }

          // Формирование подписи для сравнения ее с параметром WMI_SIGNATURE

          $signature = base64_encode(pack("H*", md5($values . $secret_key)));

          //Сравнение полученной подписи с подписью W1

          if ($signature == $_REQUEST["WMI_SIGNATURE"]){
            if (strtoupper($_REQUEST["WMI_ORDER_STATE"]) == "ACCEPTED"){
              // TODO: Пометить заказ, как «Оплаченный» в системе учета магазина
              if(!parent::get_pay($data)){
                  //print_answer("Ok", "Заказ #" . $_POST["WMI_PAYMENT_NO"] . " оплачен!");
                  print "WMI_RESULT=" . strtoupper("Ok") . "&";
                  print "WMI_DESCRIPTION=" .urlencode("Заказ #" . $_POST["WMI_PAYMENT_NO"] . " оплачен!");
                  parent::insert_pay($data);
              }
            }else{
              // Случилось что-то странное, пришло неизвестное состояние заказа
              $this->print_answer("Retry", "Неверное состояние ". $_REQUEST["WMI_ORDER_STATE"]);
            }
          }else{
            // Подпись не совпадает, возможно вы поменяли настройки интернет-магазина
            $this->print_answer("Retry", "Неверная подпись " . $_REQUEST["WMI_SIGNATURE"],$signature);
          }

    }

    function print_answer($result, $description,$signature=false){
      rcl_mail_payment_error($signature);
      print "WMI_RESULT=" . strtoupper($result) . "&";
      print "WMI_DESCRIPTION=" .urlencode($description);
      exit();
    }

}

