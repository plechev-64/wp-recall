<?php

//Выводим категорию товара
add_filter('the_content','rcl_add_product_category',20);
function rcl_add_product_category($content){
    global $post;
    
    if($post->post_type != 'products' || doing_filter('get_the_excerpt')) return $content;
    
    $product_cat = rcl_get_product_terms($post->ID);
    
    return $product_cat.$content;
}

function rcl_get_product_terms($product_id){

    $content = rcl_get_product_term_list( $product_id, 'prodcat', __('Categories','wp-recall'), 'folder-open' );
    $content .= rcl_get_product_term_list( $product_id, 'product_tag', __('Tags','wp-recall'), 'tags');
    
    if(!$content) return false;
    
    return $content;
}

function rcl_get_product_term_list($product_id,$taxonomy,$name,$icon){
    
    $start = '<div class="product-meta">'
            . '<i class="fa fa-%s rcl-icon"></i>'
            . '<span class="meta-content-box">'
            . '<span class="meta-content">%s: ';
    $end = '</span>'
            . '</span>'
            . '</div>';
    
    $terms = get_the_term_list( $product_id, $taxonomy, sprintf($start,$icon,$name), ', ',$end);
    
    if(!$terms) return false;
    
    return $terms;
}
