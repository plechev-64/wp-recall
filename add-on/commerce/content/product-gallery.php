<?php

add_action('wp','rcl_init_gallery_product');
function rcl_init_gallery_product(){
    global $post;
    
    if(!$post || $post->post_type != 'products') return false;
    
    rcl_bxslider_scripts();
    
}

function rcl_add_gallery_product($content){
    global $post;
    
    $content = rcl_get_product_gallery($post->ID).$content;
    
    return $content;
}

function rcl_get_product_gallery($product_id, $size = 'rcl-product-thumb'){
    
    $image_ids = get_post_meta($product_id, 'children_prodimage', 1);
    
    if ($image_ids) {
        
        $image_ids = explode(',', $image_ids);
        
    }else if(has_post_thumbnail($product_id)){
        
        $image_ids[] = get_post_thumbnail_id($product_id);
        
    }

    //if(count($image_ids) > 1) 
        rcl_bxslider_scripts();
    
    $content = '<div class="rcl-product-gallery">';
    
    $content .= '<ul class="images-list" id="image-list-'.$product_id.'">';
    
    if(!$image_ids){

        $content .= '<li>';

        $content .= '<img src="'.rcl_addon_url('img/empty-gallery.jpg',__FILE__).'">';

        $content .= '</li>';
        
    }else{
    
        foreach($image_ids as $image_id){

            $url_large = wp_get_attachment_image_src( $image_id, 'large' );

            $content .= '<li>';

            $content .= '<a href="'.$url_large[0].'" class="fancybox">'.wp_get_attachment_image( $image_id, $size ).'</a>';

            $content .= '</li>';

        }
        
    }

    $content .= '</ul>';
    
    if(count($image_ids) > 1){
        
        $content .= '<div class="product-slider-pager">';
        
        foreach($image_ids as $k => $image_id){
            
            $content .= '<a data-slide-index="'.$k.'" href="#">'.wp_get_attachment_image( $image_id, array(50,50) ).'</a>';
            
        }
                
        $content .= '</div>';
        
    }
    
    $content .= '</div>';
    
    if(count($image_ids) > 1) 
        $content .= '<script>rcl_init_product_slider("image-list-'.$product_id.'");</script>';
    
    return $content;
    
}

