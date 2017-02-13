<?php
class Rcl_Payment{

    public $pay_id; //идентификатор платежа
    public $pay_summ = 0; //сумма платежа
    public $pay_sum = 0; //сумма платежа
    public $pay_type = 2; //тип платежа. 1 - пополнение личного счета, 2 - оплата заказа
    public $pay_date; //время платежа
    public $user_id; //идентификатор пользователя
    public $pay_status; //статус платежа
    public $pay_callback;
    public $description = '';
    public $submit_value = '';
    public $box_id = '';
    public $box_class = array();
    public $box_width = 350;
    public $merchant_icon = 1;
    public $method = 'post';
    public $pay_systems = array();
    public $pay_systems_not_in = array();
    public $current_connect = '';
    public $current_step = '';
    public $page_result;
    public $page_success;
    public $page_fail;
    public $page_successfully;
    public $baggage_data = array();
    //взаимодействие с multipayeers, удалить
    public $connect;

    function __construct($args = false){
        global $user_ID,$rmag_options;
        
        if(isset($args['box_class']) && !is_array($args['box_class'])) 
        $args['box_class'] = array_map('trim',explode(',',$args['box_class']));

        if(isset($args['pay_systems']) && !is_array($args['pay_systems'])) 
            $args['pay_systems'] = array_map('trim',explode(',',$args['pay_systems']));   

        if(isset($args['pay_systems_not_in']) && !is_array($args['pay_systems_not_in'])) 
            $args['pay_systems_not_in'] = array_map('trim',explode(',',$args['pay_systems_not_in']));
        
        $args = apply_filters('rcl_payform_args',$args);

        $this->init_properties($args);

        if(!$this->pay_systems){
            
            if($this->connect){
                //взаимодействие с multipayeers, удалить
                $this->pay_systems = $this->connect;
            }else{
                $this->pay_systems = $rmag_options['connect_sale'];
 
            }
            
            if(!is_array($this->pay_systems))
                $this->pay_systems = array($this->pay_systems);

            if(!in_array('user_balance',$this->pay_systems))
                $this->pay_systems[] = 'user_balance';

        }

        if($this->pay_systems_not_in){
            
            foreach($this->pay_systems as $k=>$typeConnect){
                if(in_array($typeConnect,$this->pay_systems_not_in))
                    unset($this->pay_systems[$k]);
            }
            
        }
        
        $this->box_class[] = 'rcl-types-connects';

        if(!$this->pay_summ && $this->pay_sum)
            $this->pay_summ = $this->pay_sum;

        if(!$this->pay_id)
            $this->pay_id = current_time('timestamp');
        
        if(!$this->user_id)
            $this->user_id = $user_ID;

        if(!$this->page_result)
            $this->page_result = $rmag_options['page_result_pay'];
        
        if(!$this->page_success)
            $this->page_success = $rmag_options['page_success_pay'];
        
        if(!$this->page_fail)
            $this->page_fail = $rmag_options['page_fail_pay'];
        
        if(!$this->page_successfully)
            $this->page_successfully = $rmag_options['page_successfully_pay'];
        
        $this->baggage_data['pay_type'] = $this->pay_type;
        $this->baggage_data['user_id'] = $this->user_id;
        
        $this->baggage_data = json_encode($this->baggage_data);

    }
    
    function init_properties($args){
        $properties = get_class_vars(get_class($this));

        foreach ($properties as $name=>$val){
            if(isset($args[$name])) $this->$name = $args[$name];
        }
    }

    //удалить после перехода всех платежных систем
    function add_payment($type,$data){
        global $rcl_payments;
        $rcl_payments[$type] = (object)$data;
    }

    function payment_process($pay_system = false){
        global $post;

        add_action('rcl_success_pay',array($this,'pay_account'));
        
        //15.9.0 позже удалить, а также все обращения к insert_pay_rcl
        add_action('rcl_success_pay',array($this,'insert_pay_rcl'));

        $this->pay_date = current_time('mysql');
        
        if($post->ID == $this->page_result) 
            $this->get_result($pay_system);
        
        if($post->ID == $this->page_success) 
            $this->get_success($pay_system);
    }
    
    //15.9.0 позже удалить, а также все обращения к insert_pay_rcl
    function insert_pay_rcl($data){
        do_action('insert_pay_rcl',$data);
    }

    function get_result($pay_system){
        global $rmag_options,$rcl_payments;
        
        if(!$pay_system) 
            $pay_system = $rmag_options['connect_sale'];
        
        $this->current_connect = $pay_system;
        $this->current_step = 'result';

        if(isset($rcl_payments[$pay_system])){
            
            $className = $rcl_payments[$pay_system];
            
            $obj = new $className->class;
            $method = 'result';
            $obj->$method($this);
            
        }else{
            return false;
        }
    }

    function get_success($pay_system){
        global $rmag_options,$rcl_payments;
        
        if(!$pay_system) 
            $pay_system = $rmag_options['connect_sale'];
        
        $this->current_connect = $pay_system;
        $this->current_step = 'success';

        if(isset($rcl_payments[$pay_system])){
            
            $className = $rcl_payments[$pay_system];
            
            $obj = new $className->class;
            $method = 'success';
            $obj->$method();
            
        }else{
            return false;
        }

        if($this->get_pay()){
            wp_redirect(get_permalink($this->page_successfully)); exit;
        } else {
            wp_die(__('No record of the payment in the database was found','wp-recall'));
        }
    }

    function get_pay($data){
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM ".RMAG_PREF ."pay_results WHERE payment_id = '%s' AND user_id = '%d'",$data->pay_id,$data->user_id));
    }

    function insert_pay($data){
        global $wpdb;

        $data->pay_status = $wpdb->insert( RMAG_PREF .'pay_results',
            array(
                'payment_id' => $data->pay_id,
                'user_id' => $data->user_id,
                'pay_amount' => $data->pay_summ,
                'time_action' => $data->pay_date,
                'pay_system' => $data->current_connect,
                'pay_type' => $data->pay_type
            )
        );

        if(!$data->pay_status) exit;
        
        $data->baggage_data = ($data->baggage_data)? json_decode(wp_unslash($data->baggage_data)): false;

        do_action('rcl_success_pay',$data);
        
        if($data->pay_status)
            do_action('payment_rcl',$data->user_id,$data->pay_summ,$data->pay_id,$data->pay_type);

    }

    function get_form($args = false){

        global $rcl_payments;

        $box_id = ($this->box_id)? 'id="'.$this->box_id.'"': '';
        
        $content = '<div class="'.implode(' ',$this->box_class).'" '.$box_id.' style="max-width:'.$this->box_width.'px">';
        
        foreach($this->pay_systems as $type){
            
            if($type == 'user_balance'){
                
                if($this->pay_type == 1)
                    continue;
                
                $content .= $this->personal_account_pay_form($this->pay_id,
                    array(
                        'pay_type' => $this->pay_type,
                        'pay_id' => $this->pay_id,
                        'pay_summ' => $this->pay_summ,
                        'pay_callback' => $this->pay_callback,
                        'baggage_data' => json_decode($this->baggage_data)
                    )
                );
                
                continue;
                
            }

            if(isset($rcl_payments[$type])){
                 $connect = $rcl_payments[$type];
                 $class = $connect->class;
                 $this->connect = array(
                     'name'=>$connect->name,
                     'image'=>$connect->image
                 );
                 $obj = new $class;
                 $method = 'pay_form';
                 $content .= $obj->$method($this);
             }else{
                 $content .= '<div class="error"><p class="error">'.__('Error! Connection to payment aggregator not set.','wp-recall').'</p></div>';
             }
        
        }
        
        $content .= '</div>';
        
        return $content;
        
    }

    function form($fields,$data,$formaction){
        global $rmag_options,$user_ID;
        
        $fields = apply_filters('rcl_pay_form_fields',$fields,$data);

        if($data->submit_value)
            $submit_value = $data->submit_value;
        else
            $submit_value = __('Pay via','wp-recall').' "'.$data->connect['name'].'"';

        $background = (isset($data->connect['image']) && $data->merchant_icon )? 'style="background-image: url('.$data->connect['image'].');"': '';
        
        $class_icon = ($background)? 'exist-merchant-icon': '';
        
        $form = '<div class="rcl-pay-form">';

        $form .= "<div class='rcl-pay-button'>";
        
        if($user_ID){
            
            $form .= "<form action='".$formaction."' method=$data->method>"
            . $this->get_hiddens( $fields )
            . "<span class='rcl-connect-submit $class_icon' $background>"
                . "<input class='recall-button' type=submit value='$submit_value'>"
            . "</span>"
            . "</form>";
            
        }else{
            
            $form .= "<span class='rcl-connect-submit $class_icon' $background>"
                . "<a class='recall-button rcl-login' href=".rcl_get_loginform_url('login').">$submit_value</a>"
            . "</span>";
            
        }
        
        $form .= "</div>";

        $form .= '</div>';

        return $form;
    }
    
    function personal_account_pay_form($pay_id, $args = array()){
        
        $pay_callback = (isset($args['pay_callback']))? $args['pay_callback']: 'rcl_pay_order_user_balance';
        $submit = (isset($args['submit']))? $args['submit']: __('Pay from personal account','wp-recall');
        
        $data = ($args)? json_encode($args): 'false';
        
        $form .= '<div class="rcl-pay-form">';

        $form .= '<div class="rcl-pay-button">'
                    . '<span class="rcl-connect-submit exist-merchant-icon">'
                        . '<i class="fa fa-credit-card" aria-hidden="true"></i>'
                        . "<input class=recall-button type=button name=pay_order onclick='".$pay_callback."(this,".$data.");return false;' data-order=$pay_id value='$submit'>"
                    . '</span>'
                . '</div>';

        $form .= '</div>';
        
        return $form;
    }

    function get_hiddens($args){
        foreach($args as $key=>$val){
            $form .= "<input type=hidden name=$key value='$val'>";
        }
        return $form;
    }
    
    //пополнение баланса пользователя
    function pay_account($data){

        if($data->pay_type!=1) return false;

        $oldcount = rcl_get_user_balance($data->user_id);

        if($oldcount) $newcount = $oldcount + $data->pay_summ;
        else $newcount = $data->pay_summ;

        rcl_update_user_balance($newcount,$data->user_id,__('Top up personal account','wp-recall'));

    }

}

function rcl_mail_payment_error($hash=false,$other=false){
    global $rmag_options,$post;
    
    if($other){
        foreach($other as $k=>$v){
            $textmail .= $k.' - '.$v.'<br>';
        }
    }

    foreach($_REQUEST as $key=>$R){
        $textmail .= $key.' - '.$R.'<br>';
    }

    if($hash){
        $textmail .= 'Cформированный хеш - '.$hash.'<br>';
        $title = 'Неудачная оплата';
    }else{
        $title = 'Данные платежа';
    }

    $textmail .= 'Текущий пост - '.$post->ID.'<br>';
    $textmail .= 'RESULT - '.$rmag_options['page_result_pay'].'<br>';
    $textmail .= 'SUCCESS - '.$rmag_options['page_success_pay'].'<br>';

    $email = $rmag_options['admin_email_magazin_recall'];
    if(!$email) $email = get_option( 'admin_email' );

    rcl_mail($email, $title, $textmail);
}

function rcl_payments(){
    global $rmag_options,$rcl_payments;

    if(!isset($rmag_options['connect_sale'])||!$rmag_options['connect_sale']) return false;
    if(!isset($rcl_payments[$rmag_options['connect_sale']])||is_array($rmag_options['connect_sale'])) return false;

    if (isset($_REQUEST[$rcl_payments[$rmag_options['connect_sale']]->request])){
        $payment = new Rcl_Payment();
        $payment->payment_process();
    }
}
add_action('wp', 'rcl_payments',10);