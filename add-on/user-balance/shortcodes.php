<?php

add_shortcode('rcl-paybutton','rcl_get_pay_form');
add_shortcode('rcl-pay-form','rcl_get_pay_form');
function rcl_get_pay_form($attr){

    $payment = new Rcl_Payment($attr);
    
    $content = '<div class="rcl-payment-buttons">';
    
    $content .= $payment->get_form();
    
    $content .= '</div>';

    return $content;
}

add_shortcode('rcl-usercount','rcl_shortcode_usercount');
function rcl_shortcode_usercount(){
    return rcl_get_html_usercount();
}

add_shortcode('rcl-form-balance','rcl_form_user_balance');
function rcl_form_user_balance($attr=false){
    global $user_ID,$rcl_payments,$rmag_options;
    
    if(!$user_ID) return '<p align="center">'.__("please log in to make a payment",'wp-recall').'</p>';

    extract(shortcode_atts(array(
        'idform' => rand(1,1000)
    ),
    $attr));
    
    $form = array(
        'fields' => array('<input class=value-user-count name=count type=number value=>'),
        'notice' => '',
        'submit' => '<input class="rcl-get-form-pay recall-button" type=submit value=Отправить>'
    );
    
    if(!is_array($rmag_options['connect_sale'])&&isset($rcl_payments[$rmag_options['connect_sale']])){
        $connect = $rcl_payments[$rmag_options['connect_sale']];
        $background = (isset($connect->image))? 'style="background:url('.$connect->image.') no-repeat center;"': '';       
        $form['notice'] = '<span class="form-notice">'
                        . '<span class="thumb-connect" '.$background.'></span> '.__('Payment via','wp-recall').' '
                        .$connect->name
                        .'</span>';
    }
    
    $form = apply_filters('rcl_user_balance_form',$form);
    
    if(!is_array($form['fields'])) return false;
    
    $content = '<div class=rcl-form-add-user-count id=rcl-form-balance-'.$idform.'>
                    <p class="form-balance-notice">'.__("Enter the amount to top up",'wp-recall').'</p>
                    <form class=rcl-form-input>';
                        foreach($form['fields'] as $field){
                            $content .= '<span class="form-field">'.$field.'</span>';
                        }
                        if(isset($form['notice'])&&$form['notice']) 
                            $content .= '<span class="form-field">'.$form['notice'].'</span>';
                        $content .= '<span class="form-submit">'.$form['submit'].'</span>'
                    .'</form>
                    <div class=rcl-result-box></div>
                </div>';
                        
    return $content;
}

