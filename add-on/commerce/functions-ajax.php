<?php

add_action('wp_ajax_rcl_update_cart_content','rcl_update_cart_content');
add_action('wp_ajax_nopriv_rcl_update_cart_content','rcl_update_cart_content');
function rcl_update_cart_content(){
    
    $cartProducts = json_decode(wp_unslash($_POST['cart']));
    
    $result = array(
        'success' => true,
        'content' => rcl_get_cart($cartProducts)
    );
    
    echo json_encode($result);
    exit;
    
}

add_action('wp_ajax_rcl_add_to_cart','rcl_add_to_cart');
add_action('wp_ajax_nopriv_rcl_add_to_cart','rcl_add_to_cart');
function rcl_add_to_cart(){
    global $Cart;
    
    rcl_verify_ajax_nonce();

    $cart = apply_filters('rcl_add_to_cart_data', $_POST['cart']);
    
    if(!$cart) exit;
    
    $product_id = $cart['product_id'];
    
    if(!$product_id) exit;
    
    $args = array();
    
    $args['quantity'] = isset($cart['quantity'])? absint($cart['quantity']): false;
    $args['variations'] = isset($cart['variations'])? $cart['variations']: false;

    if(!isset($cart['isset']['variations'])){
        
        $PrVars = new Rcl_Product_Variations();
        
        if($PrVars->get_product_variations($product_id)){
            
            $cartBox = new Rcl_Cart_Button_Form(array(
                'product_id' => $product_id
            ));

            $content = '<div id="rcl-product-box" class="modal-box">';

                $content .= '<div class="product-title">';

                    $content .= get_the_title($product_id);

                $content .= '</div>';

                $content .= '<div class="product-metabox">';

                    $content .= $cartBox->cart_form(); 

                $content .= '</div>';

            $content .= '</div>';

            $result = array(
                'modal' => true,
                'content' => $content
            );

            echo json_encode($result);
            exit;
            
        }
        
    }
    
    $Cart = new Rcl_Cart();

    $Cart->add_product($product_id,$args);

    $result = array(
        'cart' => array(
            'order_price' => $Cart->order_price,
            'products_amount' => $Cart->products_amount,
            'products' => $Cart->products
        ),
        'product' => $Cart->get_product($product_id),
        'success' =>  __('Added to cart!','wp-recall').'<br>'
                    .sprintf(__('In your shopping cart: %d items','wp-recall'),$Cart->products_amount).'<br>'
                    .'<a style="text-decoration:underline;" href="'.$Cart->cart_url.'">'
                    .__('Go to cart','wp-recall')
                    .'</a>'
    );
    
    echo json_encode($result);
    exit;
    
}

add_action('wp_ajax_rcl_check_cart_data','rcl_check_cart_data');
add_action('wp_ajax_nopriv_rcl_check_cart_data','rcl_check_cart_data');
function rcl_check_cart_data(){
    global $user_ID,$rmag_options;
    
    rcl_verify_ajax_nonce();
    
    if(!$user_ID){
        
        if(!isset($_POST['user_email']) || !$_POST['user_email']){
            echo json_encode(array('error'=>__('Please fill in required fields!','wp-recall')));
            exit;
        }
        
        $buyer_register = (isset($rmag_options['buyer_register']))? $rmag_options['buyer_register']: 1;
        
        if($buyer_register){
        
            $user_email = sanitize_text_field($_POST['user_email']);

            $isEmail = is_email($user_email);
            $validName = validate_username($user_email);
            
            if(!$validName||!$isEmail){
                echo json_encode(array('error'=>__('You have entered an invalid email!','wp-recall')));
                exit;
            }
            
            if(email_exists( $user_email ) || username_exists($user_email)){
                echo json_encode(array('error'=>__('This email is already used! If this is your email, then log in and proceed with the order.','wp-recall')));
                exit;
            }
        
        }
        
    }
    
    do_action('rcl_check_cart_data');
    
    echo json_encode(array('success'=>true));
    exit;
}