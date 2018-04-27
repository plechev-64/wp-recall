<?php

function rcl_get_product_gallery($product_id, $size = 'rcl-product-thumb'){
    
    $image_ids = array();
    
    if(has_post_thumbnail($product_id)){
        
        $image_ids[] = get_post_thumbnail_id($product_id);
        
    }
    
    $attach_ids = get_post_meta($product_id, 'children_prodimage', 1);

    if ($attach_ids) {
        
        $image_ids = array_unique(array_merge($image_ids, explode(',', $attach_ids)));
        
    }
    
    $content = rcl_get_image_gallery(array(
        'id' => 'rcl-product-gallery-'.$product_id,
        'attach_ids' => $image_ids,
        'width' => 350,
        'height' => 350,
        'slides' => array(
            'slide' => $size,
            'full' => 'large'
        ),
        'navigator' => array(
            'thumbnails' => array(
                'width' => 50,
                'height' => 50,
                'arrows' => true
            )
        )
    ));
    
    return $content;
    
}

