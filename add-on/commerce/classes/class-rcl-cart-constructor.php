<?php

class Rcl_Cart_Constructor {
    
    public $fields = array();
    public $order_id = false;

    function __construct() {
        
        if(isset($_GET['order-id']))
            $this->order_id = intval($_GET['order-id']);
        
        $this->init_fields();

    }
    
    function get_cart($cartProducts = false){

        $content = '<div id="rcl-order">';
        
        if($this->order_id){
            
            global $rclOrder;
            
            $rclOrder = rcl_get_order($this->order_id);
            
            $content .= rcl_get_include_template('order.php',__FILE__);
            
        }else{
            
            $Cart = new Rcl_Cart(array('cart_products' => $cartProducts));
            
            if(!$Cart->products_amount){

                $content .= '<p>'.__('Your shopping cart is empty','wp-recall').'.</p>';

            }else{

                $content .= rcl_get_include_template('cart.php',__FILE__);

                $content .= $this->get_form_fields();

            }
        
        }

        $content .= '</div>';

        return $content;
        
    }
    
    function get_form_fields(){

        $content = '<div class="cart-fields">';

            $content .= '<form id="rcl-order-form" method="post" action="">';
            
            if($this->fields){
                
                $CF = new Rcl_Custom_Fields();
                
                $content .= '<div class="cart-fields-title">'.__('To place an order fill out the form below','wp-recall').'</div>';
            
                $content .= '<table class="table-fields rcl-form">';
                
                foreach($this->fields as $field){
                    
                    if(!isset($field['value_in_key'])) $field['value_in_key'] = true;

                    $required = ($field['required'] == 1)? '<span class="required">*</span>': '';

                    $content .= '<tr class="cart-field">'
                                .'<td class="field-title">'
                                    . '<label>'.$CF->get_title($field).' '.$required.'</label>'
                                . '</td>'
                                .'<td class="field-input">'
                                    .$CF->get_input($field)
                                .'</td>'
                                .'</tr>';
                }

                $content .= '</table>';
            
            }
            
            $content .= '<div class="submit-box">'
                        . '<a href="#" class="recall-button" onclick="rcl_cart_submit();return false;"><i class="fa fa-shopping-bag" aria-hidden="true"></i>'.__('Checkout','wp-recall').'</a>'
                        . '<input type="hidden" name="rcl-commerce-action" value="new-order">'
                    . '</div>';
            
            $content .= '</form>';
        
        $content .= '</div>';
        
        return $content;
        
    }
    
    function init_fields(){
        global $user_ID;
        
        if(!$user_ID)
            $this->init_guest_fields();

        $fields = ($cartFields = $this->get_cart_fields())? array_merge($this->fields, $cartFields): $this->fields;
        
        $this->fields = apply_filters('rcl_cart_fields', $fields);

    }
    
    function init_guest_fields(){
        
        $this->fields = $this->get_profile_fields();
        
        $fields = array();
        
        $fields[] = array(
            'title' => __('Enter your E-mail','wp-recall'),
            'slug' => 'user_email',
            'required' => 1,
            'type' => 'text'
        );

        if(!$this->exist_field('first_name')){
            
            $fields[] = array(
                'title' => __('Your name','wp-recall'),
                'slug' => 'first_name',
                'required' => 1,
                'type' => 'text'
            );
            
        }
        
        $this->fields = $this->fields? array_merge($fields,$this->fields): $fields;

    }
    
    function get_profile_fields(){
        
        $profileFields = rcl_get_profile_fields();
        
        if(!$profileFields) return false;

        $fields = array();

        foreach($profileFields as $field){

            if(!isset($field['order']) || $field['order'] != 1) continue;

            $fields[] = $field;

        }
        
        return $fields;
        
    }
    
    function exist_field($meta_key){
        
        foreach($this->fields as $field){
            
            if($field['slug'] == $meta_key)
                return true;
            
        }
        
        return false;
        
    }
    
    function get_cart_fields(){
        
        $cartFields = get_option( 'rcl_cart_fields' );
        
        if(!$cartFields) return false;
        
        return $cartFields;
        
    }

}
