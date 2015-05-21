<?php

class Rcl_Payform {

    public $id_pay;
    public $summ;
    public $type;
    public $user;

    function __construct($args){
        global $user_ID;
        $this->id_pay = $args['id_pay'];
        $this->summ = $args['summ'];
        $this->type = $args['type'];
        if(!$args['user_id']) $this->user = $user_ID;
        else $this->user = $args['user_id'];
    }

    function payform(){
        global $rmag_options;
        if($rmag_options['connect_sale']==1){ //если используется робокасса
            $form = $this->robokassa();
        }
        if($rmag_options['connect_sale']==2){ //если используется интеркасса
            $form = $this->interkassa();
        }
        if($rmag_options['connect_sale']==3){ //если используется интеркасса
            $form = $this->yandexkassa();
        }
        if($rmag_options['connect_sale']==4){ //если используется единая касса
            $form = $this->walletone();
        }

        $type_p = $rmag_options['type_order_payment'];
        if($type_p==2&&$this->type==2) $form .= '<input class="pay_order recall-button" type="button" name="pay_order" data-order="'.$this->id_pay.'" value="'.__('Pay personal account','rcl').'">';

        return $form;
    }

    function walletone(){
        global $rmag_options;

        $merchant_id = $rmag_options['WO_MERCHANT_ID'];
        $secret_key = $rmag_options['WO_SECRET_KEY'];

        $formaction = 'https://wl.walletone.com/checkout/checkout/Index';

        $submit = ($this->type==1)? __('Confirm the operation','rcl'): __('Pay through payment system','rcl');

        $curs = array( 'RUB' => 643, 'UAH' => 980, 'USD' => 840, 'EUR' => 978 );
        $code_cur = (isset($curs[$rmag_options['primary_cur']]))? $curs[$rmag_options['primary_cur']]: 643;

        $fields = array(
            'WMI_MERCHANT_ID'=>$merchant_id,
            'WMI_PAYMENT_AMOUNT'=>$this->summ.'.00',
            'WMI_CURRENCY_ID'=>$code_cur,
            'WMI_PAYMENT_NO'=>$this->id_pay,
            'WMI_SUCCESS_URL'=>$rmag_options['page_success_pay'],
            'WMI_FAIL_URL'=>$rmag_options['page_fail_pay'],
            'WMI_CUSTOMER_ID'=>$this->user,
            'USER_ID'=>$this->user,
            'TYPE_PAY'=>$this->type
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

        $hidden = $this->hidden( $fields );

        $form = "<form id='form-payment-".$this->id_pay."' style='display: inline;' action='".$formaction."' method=POST>
            ".$hidden."
            <input class='recall-button' type=submit value='$submit'>
        </form>";

        return $form;
    }

    function yandexkassa(){
        global $rmag_options;

        $shopid = $rmag_options['shopid'];
        $scid = $rmag_options['scid'];

        $formaction = 'https://money.yandex.ru/eshop.xml';

        $submit = ($this->type==1)? __('Confirm the operation','rcl'): __('Pay through payment system','rcl');

        $hidden = $this->hidden(
                array(
                    'shopId'=>$shopid,
                    'scid'=>$scid,
                    'sum'=>$this->summ,
                    'orderNumber'=>$this->id_pay,
                    'customerNumber'=>$this->user,
                    'typePay'=>$this->type,
                )
            );

        $form = "<form id='form-payment-".$this->id_pay."' style='display: inline;' action='".$formaction."' method=POST>
            ".$hidden."
            <input class='recall-button' type=submit value='$submit'>
        </form>";

        return $form;
    }

    function robokassa(){
        global $rmag_options;

        $login = $rmag_options['robologin'];
        $pass1 = $rmag_options['onerobopass'];

        $crc = md5("$login:$this->summ:$this->id_pay:$pass1:Shp_item=2:shpa=$this->user:shpb=$this->type");

        if($rmag_options['robotest']==1) $formaction = 'http://test.robokassa.ru/Index.aspx';
        else $formaction = 'https://merchant.roboxchange.com/Index.aspx';

        $submit = ($this->type==1)? __('Confirm the operation','rcl'): __('Pay through payment system','rcl');

        $hidden = $this->hidden(
                array(
                    'MrchLogin'=>$login,
                    'OutSum'=>$this->summ,
                    'InvId'=>$this->id_pay,
                    'shpb'=>$this->type,
                    'shpa'=>$this->user,
                    'SignatureValue'=>$crc,
                    'Shp_item'=>'2',
                    'Culture'=>'ru'
                )
            );

        $form = "<form id='form-payment-".$this->id_pay."' style='display: inline;' action='".$formaction."' method=POST>
            ".$hidden."
            <input class='recall-button' type=submit value='$submit'>
        </form>";

        return $form;
    }

    function interkassa(){
        global $rmag_options;

        $shop_id = $rmag_options['interidshop'];
        $test = $rmag_options['interkassatest'];
        $key = $rmag_options['intersecretkey'];

        if($this->type==1) $data['ik_desc'] = __('Top up personal account','rcl');
        else if($this->type==2) $data['ik_desc'] = __('Payment for the order on the website','rcl');
        else $data['ik_desc'] = __('Other payments','rcl');

        $submit = ($this->type==1)? __('Confirm the operation','rcl'): __('Pay through payment system','rcl');

        if($test==1){
            $ik_pw_via = 'test_interkassa_test_xts';
            $data['ik_pw_via'] = $ik_pw_via;
            $test_input = "<input type='hidden' name='ik_pw_via' value='$ik_pw_via'>";
        }

        $data['ik_am'] = $this->summ;
        $data['ik_co_id'] = $shop_id;
        $data['ik_pm_no'] = $this->id_pay;
        $data['ik_x_user_id'] = $this->user;
        $data['ik_x_type'] = $this->type;

        ksort ($data, SORT_STRING);
        array_push($data, $key);
        $signStr = implode(':', $data);
        $ik_sign = base64_encode(md5($signStr, true));

        $hidden = $this->hidden(
            array(
                'ik_co_id'=>$shop_id,
                'ik_am'=>$this->summ,
                'ik_pm_no'=>$this->id_pay,
                'ik_desc'=>$data['ik_desc'],
                'ik_x_user_id'=>$this->user,
                'ik_sign'=>$ik_sign,
                'ik_x_type'=>$this->type
            )
        );

        $form = "<form id='form-payment-".$this->id_pay."' style='display: inline;' action='https://sci.interkassa.com/' method='POST'>"
                .$test_input."
                ".$hidden.""
                . "<input class='recall-button' type=submit value='$submit'>"
                . "</form>";

        return $form;
    }

    function hidden($args){
        foreach($args as $key=>$val){
            $form .= "<input type=hidden name=$key value='$val'>";
        }
        return $form;
    }
}
