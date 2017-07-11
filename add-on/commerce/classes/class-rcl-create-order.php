<?php

class Rcl_Create_Order{
    
    public $order_price = 0;
    public $order_id = 0;
    public $user_id = 0;
    public $register_data = array();
    public $product_amount = 0;
    public $order_details = array();
    public $order_status = 1;
    public $products = array();
    public $buyer_register;
    public $is_error = 0;
    
    function __construct(){
        global $rmag_options;
        
        $this->buyer_register = (isset($rmag_options['buyer_register']))? $rmag_options['buyer_register']: 1;
        
        $this->init_orderdata();
        
    }
    
    function init_orderdata(){
        global $user_ID;
        
        $Cart = new Rcl_Cart();
        
        if(!$Cart->products) return false;
        
        foreach($Cart->products as $product){
            
            $productPrice = new Rcl_Product_Price($product->product_id);
            
            $product_price = $productPrice->get_price((array)$product->variations);
            
            $this->order_price += $product_price * $product->product_amount;
            $this->products_amount += $product->product_amount;
            
            $this->products[] = array(
                'product_id' => $product->product_id,
                'product_price' => $product_price,
                'product_amount' => $product->product_amount,
                'variations' => $product->variations
            );
            
        }
        
        $this->user_id = $user_ID;
        $this->order_status = 1;
        $this->order_details = $this->get_details();
 
    }
    
    function error($code,$error){
        $this->is_error = $code;
        $wp_errors = new WP_Error();
        $wp_errors->add( $code, $error );
        return $wp_errors;
    }
    
    function insert_order(){
        
        if(!$this->user_id){
            $result = $this->register_user();
            if($this->is_error) return $result;
        }
        
        $args = array(
            'user_id' => $this->user_id,
            'order_details' => $this->order_details,
            'order_status' => $this->order_status,
            'order_price' => $this->order_price
        );

        $this->order_id = rcl_insert_order($args, $this->products);
        
        $Cart = new Rcl_Cart();
        $Cart->reset_cart();
        
        do_action('rcl_create_order', $this->order_id, $this->register_data);
        
        return $this->order_id;
        
    }

    function get_details(){

        $Cart = new Rcl_Cart_Constructor();
        
        if(!$Cart->fields) return false;
        
        $order_details = array();
        
        foreach($Cart->fields as $field){

            if($field['type'] == 'file'){
                
                $value = rcl_upload_meta_file($field,$this->user_id);
                
            }else if($field['type'] == 'agree'){
                
                $value = (isset($_POST[$field['slug']]) && $_POST[$field['slug']])? 'Принято': false;

            }else{
                
                $value = (isset($_POST[$field['slug']]))? $_POST[$field['slug']]: false;
                
            }
            
            if(!$value) continue;
            
            $order_details[] = array(
                'type' => $field['type'],
                'title' => $field['title'],
                'value' => $value
            );
            
        }
        
        return $order_details;

    }
    
    function register_user(){
        
        $user_email = sanitize_text_field($_POST['user_email']);
        $user_name = sanitize_text_field($_POST['first_name']);

        $isEmail = is_email($user_email);
        $validName = validate_username($user_email);
        
        //если разрешена регистрация покупателя
        if($this->buyer_register){
            
            if(!$validName||!$isEmail){
                return $this->error('email_invalid',__('You have entered an invalid email!','wp-recall'));
            }
            
            if(email_exists( $user_email ) || username_exists($user_email)){
                return $this->error('email_used',__('This email is already used! If this is your email, then log in and proceed with the order.','wp-recall'));
            }
            
            if(!$this->user_id){
                
                $user_password = wp_generate_password( 12, false );

                $this->register_data = array(
                    'user_pass'=>   $user_password,
                    'user_login'=>  $user_email,
                    'user_email'=>  $user_email,
                    'display_name'=>$user_name
                );

                $this->user_id = rcl_insert_user($this->register_data);

                if(!$this->user_id){
                    return $this->error('buyer_registered',__('An error occurred while registering the buyer!','wp-recall'));
                }
                
                do_action('rcl_buyer_register', $this->user_id, $this->register_data);
            
            }
            
        }else{
            
            if(!$isEmail||!$validName){
                return $this->error('email_invalid',__('You have entered an invalid email!','wp-recall'));
            }
            
            $user = get_user_by('email', $user_email);
            
            if($user){
                
                $this->user_id = $user->ID;
                
            }else{
                
                $data = array(
                    'user_pass'=>   $user_password,
                    'user_login'=>  $user_email,
                    'user_email'=>  $user_email,
                    'display_name'=>$user_name,
                    'user_nicename' => '',
                    'nickname' => $user_email,
                    'first_name' => $user_name,
                    'rich_editing' => 'true'
                );

                $this->user_id = wp_insert_user( $data );
                
            }
            
        }
        
        if(!$this->user_id) return false;

        rcl_update_profile_fields($this->user_id);

        //Сразу авторизуем пользователя, если не требуется подтверждение почты
        if($this->buyer_register && !rcl_get_option('confirm_register_recall')){

            $creds = array(
                'user_login' => $user_email,
                'user_password' => $user_password,
                'remember' => true
            );

            wp_signon( $creds );

        }
        
        return $this->user_id;
        
    }
    
}
