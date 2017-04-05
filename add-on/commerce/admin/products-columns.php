<?php

// создаем колонки товаров
add_filter('manage_edit-products_columns', 'rcl_init_product_columns', 10);
function rcl_init_product_columns( $columns ){
    global $rmag_options;
    
    $out = array();
    foreach($columns as $col=>$name){
        
        if(++$i==2){
            $out['product-thumbnail']   = __('Thumbnail','wp-recall');
        }
        
        if($i==3){
            $out['product-price']       = __('Price','wp-recall');
        }

        $out[$col] = $name;
        
    }
    
    $out['product-category']    = __('Category','wp-recall');

    return $out;
    
}

// заполняем колонку данными
add_filter('manage_products_posts_custom_column', 'rcl_add_data_product_columns', 10, 2);
function rcl_add_data_product_columns($column_name, $post_id) {
    
    switch($column_name){
        
        case 'product-thumbnail': 
            
            $thumbnail = '';
            
            if(get_the_post_thumbnail($post_id,'thumbnail')) 
                $thumbnail = get_the_post_thumbnail($post_id, array(70,70));

            echo '<div class="thumbnail">'.$thumbnail.'</div>';
            
            break;
            
        case 'product-category': 
            
            $terms = get_the_terms( $post_id,'prodcat');
            
            if($terms){
                $content = array();
                foreach($terms as $term){
                    $content[] = '<a href="'. admin_url('edit.php?post_type=products&prodcat='. $term->slug) .'">'. $term->name .'</a>'  ;
                }
                echo implode(', ',$content);
            }
            
            break;
            
        case 'product-price': 
            
            echo '<input type="text" id="price-product-'.$post_id.'" name="price-product" size="4" value="'.get_post_meta($post_id,'price-products',1).'"> '.rcl_get_current_type_currency($post_id).'
                <input type="button" class="recall-button edit-price-product" data-product="'.$post_id.'" id="product-'.$post_id.'" value="'.__('OK','wp-recall').'">';
            
            break;
        
        case 'product-availability': 
            
            if(get_post_meta($post_id, 'availability_product', 1)=='empty'){ //если товар цифровой
                
                echo '<span>'.__('digital goods','wp-recall').'</span>';
                
            }else{
                
               if(!get_post_meta($post_id, 'outsale', 1)){
                   
                    $amount = get_post_meta($post_id,'amount_product',1);
                    $reserve = get_post_meta($post_id,'reserve_product',1);

                    if($amount==0&&$amount!='') 
                       echo '<span style="color:red;">'.__('in stock','wp-recall').'</span> ';
                    else 
                       echo '<span style="color:green;">'.__('in stock','wp-recall').'</span> ';

                    if($amount!=false&&$amount>0) 
                        echo '<span style="color:green;">'.$amount.'</span>';
                    else if($amount<=0) 
                        echo '<span style="color:red;">'.$amount.'</span>' ;

                    if($reserve) 
                        echo '<br /><span style="color:orange;">'.__('in reserve','wp-recall').' '.$reserve.'</span>';

                }else{

                     echo '<span style="color:red;">'.__('withdrawn from sale','wp-recall').'</span>';

                }
            }
            
            break;
    }

}

// добавляем возможность сортировать колонку
add_filter('manage_edit-products_sortable_columns', 'rcl_price_sortable_column');
function rcl_price_sortable_column($sortable_columns){
    $sortable_columns['product-category'] = 'product-category_product-category';
    return $sortable_columns;
}

add_filter('manage_products_posts_columns', 'rcl_delete_column_date_product', 10, 1);
function rcl_delete_column_date_product( $columns ){
    unset($columns['date']);
    return $columns;
}

