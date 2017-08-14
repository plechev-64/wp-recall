<?php

add_filter('the_content','rcl_add_related_products',20);
function rcl_add_related_products($content){
    global $post,$rmag_options;
    
    if($rmag_options['sistem_related_products'] !=1) return $content;

    if(!is_object($post) || $post->post_type!='products') return $content;
    
    $content .= rcl_get_related_products($post->ID);
    
    return $content;
    
}

function rcl_get_related_products($product_id){
    global $rmag_options,$post;

    $related = get_post_meta($product_id,'related_products_recall',1);

    $args = array(
        'numberposts' => $rmag_options['size_related_products'],
        'orderby'     => 'rand',
        'post_type'   => 'products',
        'exclude'     => $product_id
    );

    if($related && is_array($related)){

        foreach($related as $tax=>$id){
            if($id > 0) 
                $args['tax_query'][] = array(
                    'taxonomy'=>$tax,
                    'field'=>'id',
                    'terms'=> $id
                );
        }

    }

    $related_products = get_posts($args);

    if(!$related_products) return false;
    
    $title_related = (isset($rmag_options['title_related_products_recall']) && $rmag_options['title_related_products_recall'])? $rmag_options['title_related_products_recall']: '';
    
    $content ='<div class="rcl-related-products">';

        $content .= ($title_related)? '': '<span class="related-title">'.$title_related.'</span>';

        $content .='<div class="products-box type-slab">';

            $content .='<div class="products-list">';

            foreach($related_products as $post){ setup_postdata($post);

                $content .= rcl_get_include_template('product-slab.php',__FILE__);

            }

            wp_reset_query();

            $content .= '</div>';

        $content .= '</div>';
    
    $content .= '</div>';

    return $content;
}

