<?php

add_shortcode('rcl-paybutton','rcl_get_pay_form');
add_shortcode('rcl-pay-form','rcl_get_pay_form');
function rcl_get_pay_form($attr){
    
    $form_type = (isset($attr['form_type']) && $attr['form_type'])? $attr['form_type']: 'frozen';

    if($form_type == 'dynamic'){
        
        return rcl_form_user_balance($attr);
        
    }

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
        'form_id' => rand(1,1000),
        'pay_type' => 1,
        'default_summ' => 0,
        'box_width' => 300,
        'merchant_icon' => 1,
        'submit_value' => __('Make payment','wp-recall'),
        'description' => __("Top up personal account from",'wp-recall').' '.get_the_author_meta('user_email',$user_ID)
    ),
    $attr));
    
    $form = array(
        'fields' => array(
            '<input class=value-user-count name=pay_summ type=number value="'.$default_summ.'">',
            '<input name=pay_type type=hidden value="'.$pay_type.'">',
            '<input name=merchant_icon type=hidden value="'.$merchant_icon.'">',
            '<input name=submit_value type=hidden value="'.$submit_value.'">',
            '<input name=description type=hidden value="'.$description.'">'
        ),
        'notice' => '',
        'submit' => '<input class="rcl-get-form-pay recall-button" type=submit value="'.__('Submit','wp-recall').'">'
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
    
    $content = '<div class=rcl-form-add-user-count id=rcl-form-balance-'.$form_id.' style="max-width:'.$box_width.'px;">
                    <p class="form-balance-notice">'.__("Enter the amount",'wp-recall').'</p>
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

