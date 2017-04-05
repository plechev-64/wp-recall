<?php

add_action('admin_init', 'rcl_options_products');
function rcl_options_products() {
    add_meta_box( 'recall_meta', __('Wp-Recall settings','wp-recall'), 'rcl_options_box', 'products', 'normal', 'high'  );
}

add_action('admin_init', 'rcl_products_fields', 1);
function rcl_products_fields() {
    add_meta_box( 'products_fields', __('Product features','wp-recall'), 'rcl_metabox_products', 'products', 'normal','high' );
}

function rcl_metabox_products( $post ){
    global $rmag_options; 
    
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('jquery-ui-sortable');
    
    $PrVars = new Rcl_Product_Variations(array('product_id'=>$post->ID));

    $content = '<div class="rcl-product-meta">';

    $content .= '<label class="meta-title">'.__('Product price','wp-recall').'</label>';

    $content .= '<div class="meta-content">';

        $content .= '<input type="text" name="wprecall[price-products]" value="'.get_post_meta($post->ID,'price-products',1).'"> '.rcl_get_primary_currency(2);

    $content .= '</div>';

    $content .= '</div>';
    
    $content .= '<div class="rcl-product-meta">';
    
    $content .= '<label class="meta-title">'.__('Product old price','wp-recall').'</label>';

    $content .= '<div class="meta-content">';

        $content .= '<input type="text" name="wprecall[product-oldprice]" value="'.get_post_meta($post->ID,'product-oldprice',1).'"> '.rcl_get_primary_currency(2);

    $content .= '</div>';

    $content .= '</div>';
    
    if($PrVars->variations):

        $productVars = $PrVars->get_product_variations();
        
        $content .= '<div class="rcl-product-meta">';

        $content .= '<label class="meta-title">'.__('Product variations','wp-recall').'</label>';

        $content .= '<div class="meta-content">';

            $content .= '<div class="rcl-variations-list">';
            
            foreach($PrVars->variations as $variation){
                
                $content .= '<div class="variation-box">';

                    $content .= '<input type="checkbox" class="variation-checkbox" name="product-variations['.$variation['slug'].'][status]" '.checked($PrVars->product_exist_variation($variation['slug']),true,false).' value="1" id="variation-'.$variation['slug'].'"><label class="variation-title" for="variation-'.$variation['slug'].'">'.$variation['title'].'</label>';
                    
                    $content .= '<div class="variation-values">';
                    
                    if(isset($variation['field_select']))
                        $variation['values'] = rcl_edit_old_option_fields($variation['field_select'],$variation['type']);

                    foreach($variation['values'] as $k => $value){

                        $productVal = $PrVars->get_product_variation_value($variation['slug'],$value);

                        $content .= '<div class="variation-value">';
                        $content .= '<span class="variation-value-name">'.$value.'</span>';
                        $content .= '<input type="number" name="product-variations['.$variation['slug'].'][values]['.$k.'][price]" value="'.$productVal['price'].'">';
                        $content .= '<input type="hidden" name="product-variations['.$variation['slug'].'][values]['.$k.'][name]" value="'.$value.'">';
                        $content .= '</div>';
                    }

                    $content .= '</div>';

                $content .= '</div>';
                
            }

            $content .= '</div>';

        $content .= '</div>';

        $content .= '</div>';

    endif;

    $content .= '<div class="rcl-product-meta">';

    $content .= '<div class="meta-content">';

        $content .= '<input type="checkbox" name="wprecall[outsale]" '.checked(get_post_meta($post->ID,'outsale',1),1,false).' value="1"> '.__('Withdraw from sale','wp-recall');

    $content .= '</div>';

    $content .= '</div>';

    if($rmag_options['sistem_related_products']==1):

        $related = get_post_meta($post->ID, 'related_products_recall', 1); 

        $rel_prodcat = (isset($related['prodcat']))? $related['prodcat']: '';
        $rel_product_tag = (isset($related['product_tag']))? $related['product_tag']: '';

        $content .= '<div class="rcl-product-meta">';

        $content .= '<label class="meta-title">'.__('Similar and recommended products','wp-recall').'</label>';

        $content .= '<div class="meta-content">';

            $args = array(
                'show_option_none'   => __('Choose a category','wp-recall'),
                'hide_empty'         => 0,
                'echo'               => 0,
                'selected'           => $rel_prodcat,
                'hierarchical'       => 0,
                'name'               => 'wprecall[related_products_recall][prodcat]',
                'id'                 => 'name',
                'class'              => 'postform',
                'taxonomy'           => 'prodcat',
                'hide_if_empty'      => false 
            );

            $content .= wp_dropdown_categories( $args ).' - '.__('Select a product category','wp-recall');

        $content .= '</div>';

        $content .= '<div class="meta-content">';

            $args = array(
                'show_option_none'   => __('Select a tag','wp-recall'),
                'hide_empty'         => 0,
                'echo'               => 0,
                'selected'           => $rel_product_tag,
                'hierarchical'       => 0,
                'name'               => 'wprecall[related_products_recall][product_tag]',
                'id'                 => 'name',
                'class'              => 'postform',
                'taxonomy'           => 'product_tag',
                'hide_if_empty'      => false 
            );

            $content .= wp_dropdown_categories( $args ).' - '.__('select a product tag','wp-recall');

        $content .= '</div>';

        $content .= '</div>';

    endif;
    
    $args = array(
        'numberposts' => -1,
        'order'=> 'ASC',
        'post_mime_type' => 'image',
        'post_parent' => $post->ID,
        'post_status' => null,
        'post_type' => 'attachment'
    );

    $attachments = get_children( $args );

    if($attachments):
    
        $content .= '<div class="rcl-product-meta">';

        $content .= '<label class="meta-title">'.__('Images gallery','wp-recall').'</label>';

        $content .= '<div class="meta-content">';

            $gallery = explode(',',get_post_meta($post->ID, 'children_prodimage', 1));

            if($gallery){

                $sort = array();
                $new_images = array();

                foreach($attachments as $attachment){

                    $k = array_search($attachment->ID,$gallery);

                    if($k !== false){

                        $sort[$k] = $attachment;

                    }else{

                        $new_images[] = $attachment;

                    }

                }

                if($new_images){
                    foreach($new_images as $attachment){
                        $sort[] = $attachment;
                    }
                }


                $attachments = $sort;

                ksort($attachments);

            }

            $content .= '<div id="rcl-product-gallery">';

            foreach($attachments as $attachment){

                $content .= '<span class="image-gallery">';

                $content .= '<label>';

                $content .= '<span class="move-box"></span>';

                $content .= wp_get_attachment_image( $attachment->ID, array(100,100) );

                $content .= '<input type="checkbox" name="children_prodimage[]" '.checked(in_array($attachment->ID,$gallery),true,false).' value="'.$attachment->ID.'">';

                $content .= '</label>';

                $content .= '</span>';

            }

            $content .= '</div>';

            $content .= '<script>
                jQuery(function(){
                    jQuery("#rcl-product-gallery").sortable({
                        connectWith: "#rcl-product-gallery",
                        containment: "parent",
                        handle: ".move-box",
                        cursor: "move",
                        placeholder: "ui-sortable-placeholder",
                        distance: 5
                    });
                    return false;
                });
            </script>';

        $content .= '</div>';

        $content .= '</div>';
    
    endif;

    $metaBox = '<div class="rcl-products-metabox">';
    $metaBox .= apply_filters('rcl_products_custom_fields',$content,$post);
    $metaBox .= '</div>';

    $metaBox .= '<input type="hidden" name="rcl_commerce_fields_nonce" value="'.wp_create_nonce(__FILE__).'" />';
    
    echo $metaBox;

}

add_action('save_post_products', 'rcl_commerce_fields_update', 10, 3);
function rcl_commerce_fields_update( $post_id, $post, $update ){

    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) 
        return false;

    if(!isset($_POST['rcl_commerce_fields_nonce'])) 
        return false;

    if ( !wp_verify_nonce($_POST['rcl_commerce_fields_nonce'], __FILE__) ) 
        return false;

    if ( !current_user_can('edit_post', $post_id) ) 
        return false;
    
    $POST = $_POST;

    if (!isset($POST['product-variations'])) {
        
        delete_post_meta($post_id, 'product-variations');
        
    }else{
        
        $variations = array();
        foreach($POST['product-variations'] as $varSlug => $var){
            
            if(!isset($var['status']) || !$var['status']) continue;
            
            $variations[] = array(
                'slug' => $varSlug,
                'values' => $var['values']
            );
            
        }
        
        if($variations)
            update_post_meta($post_id, 'product-variations', $variations);
        else
            delete_post_meta($post_id, 'product-variations');
        
    }

    if (!isset($POST['wprecall']['outsale'])) {
        delete_post_meta($post_id, 'outsale');
    }

    if (!isset($POST['wprecall']['availability_product'])) {
        delete_post_meta($post_id, 'availability_product');
    }

    if( !isset($POST['children_prodimage']) || !$POST['children_prodimage']){
        
        delete_post_meta($post_id, 'children_prodimage');
        
    }else{

        update_post_meta($post_id, 'children_prodimage', implode(',',array_map('trim', $POST['children_prodimage'])));
        
    }

    return $post_id;
}

