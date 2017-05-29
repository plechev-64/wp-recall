<?php

add_filter('the_content','rcl_add_product_box',10);
function rcl_add_product_box($content){
    global $post, $rmag_options;
    
    if($post->post_type != 'products' || doing_filter('get_the_excerpt')) return $content;
    
    if(doing_filter('the_content')){
        
        $productCart = (isset($rmag_options['cart_button_single_page']))? $rmag_options['cart_button_single_page']: array('top','bottom');
        
        if(!in_array('top',$productCart)) return $content;
        
    }
    
    $content = rcl_get_product_box($post->ID).$content;
    
    return $content;
}

function rcl_get_product_box($product_id){
    
    $cartBox = new Rcl_Cart_Button_Form(array(
        'product_id' => $product_id
    ));
    
    $content = '<div id="rcl-product-box">';

        if(get_post_meta($product_id, 'recall_slider', 1)){
            
            $content .= '<div class="product-gallery">';

                $content .= rcl_get_product_gallery($product_id);

            $content .= '</div>';
            
        }
        
        $content .= '<div class="product-metabox">';

            $content .= $cartBox->cart_form(); 
            
        $content .= '</div>';
    
    $content .= '</div>';
    
    return $content;
    
}

