<?php
if (is_admin()):
	add_action('admin_head','rmag_admin_scripts');
endif;

function rmag_admin_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'rmag_admin_scripts', rcl_addon_url('js/admin.js', __FILE__) );
}

if(function_exists('rcl_register_rating_type')){
    if(!function_exists('rcl_register_rating_product_type')){
        if(!is_admin()) add_action('init','rcl_register_rating_product_type');
        if(is_admin()) add_action('admin_init','rcl_register_rating_product_type');
        function rcl_register_rating_product_type(){
                rcl_register_rating_type(array('post_type'=>'products','type_name'=>__('Products','wp-recall'),'style'=>true));
        }
    }
}

add_action('rcl_bar_setup','rcl_bar_add_cart',10);
function rcl_bar_add_cart(){
    global $CartData,$rmag_options;
    
    if(!is_user_logged_in()) return false;
    
    $amount = 0;
    if(isset($_SESSION['cart'])){
        foreach($_SESSION['cart'] as $prod_id=>$val){
            $amount += $val['number'];
        }
    }
    
    rcl_bar_add_icon('rcl-cart',
        array(
            'icon'=>'fa-shopping-cart',
            'url'=>$rmag_options['basket_page_rmag'],
            'label'=>__('Cart','wp-recall'),
            'counter'=>'<span class="cart-numbers">'.$amount.'</span>'
        )
    );
}

add_action( 'attachments_register', 'rcl_attachments_products' );
function rcl_attachments_products( $attachments ){
    $args = array(
        'label' => __('Product gallery','wp-recall'),
        'post_type' => array( 'products' ),
        'filetype' => null,
        'note' => null,
        'button_text' => __( 'Attach image or download it', 'wp-recall' ),
        'modal_text' => __( 'Attach image or download it', 'wp-recall' ),
        'fields' => array(
        )
    );

    $attachments->register( 'attachments_products', $args );
}

add_action( 'init', 'rcl_register_posttype_products' );
function rcl_register_posttype_products(){

    $labels = array(
        'name' => __('Products catalog','wp-recall'),
        'singular_name' => __('Product','wp-recall'),
        'add_new' => __('Add this item','wp-recall'),
        'add_new_item' => __('Add new item','wp-recall'),
        'edit_item' => __('Edit','wp-recall'),
        'new_item' => __('New','wp-recall'),
        'view_item' => __('View','wp-recall'),
        'search_items' => __('Search','wp-recall'),
        'not_found' => __('Not found','wp-recall'),
        'not_found_in_trash' => __('Cart is empty','wp-recall'),
        'parent_item_colon' => __('Parental goods','wp-recall'),
        'menu_name' => __('Products','wp-recall')
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => false,
        'supports' => array( 'title', 'editor','custom-fields','thumbnail','comments','excerpt','author'),
        'taxonomies' => array( 'prodcat','product_tag' ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 10,
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post'
    );

    $args = apply_filters('register_data_products',$args);

    register_post_type( 'products', $args );
}

if(!function_exists('rcl_register_taxonomy_prodcat')){
    add_action( 'init', 'rcl_register_taxonomy_prodcat' );
    function rcl_register_taxonomy_prodcat() {

        $labels = array(
            'name' => __('Product categories','wp-recall'),
            'singular_name' => __('Category','wp-recall'),
            'search_items' => __('Search','wp-recall'),
            'popular_items' => __('Popular categories','wp-recall'),
            'all_items' => __('All categories','wp-recall'),
            'parent_item' => __('Parent category','wp-recall'),
            'parent_item_colon' => __('Parent category','wp-recall'),
            'edit_item' => __('Edit category','wp-recall'),
            'update_item' => __('Update category','wp-recall'),
            'add_new_item' => __('Add a new category','wp-recall'),
            'new_item_name' => __('New category','wp-recall'),
            'separate_items_with_commas' => __('Categories separated by commas','wp-recall'),
            'add_or_remove_items' => __('Add or delete a category','wp-recall'),
            'choose_from_most_used' => __('Select to use the','wp-recall'),
            'menu_name' => __('Product categories','wp-recall')
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'show_in_nav_menus' => true,
            'show_ui' => true,
            'show_tagcloud' => true,
            'hierarchical' => true,
            'rewrite' => true,
            'query_var' => true
        );

        register_taxonomy( 'prodcat', array('products'), $args );
    }
}

add_action( 'init', 'rcl_register_taxonomy_product_tag' );
function rcl_register_taxonomy_product_tag() {

    $labels = array(
        'name' => __('Product Tags','wp-recall'),
        'singular_name' => __('Product Tag','wp-recall'),
        'search_items' => __('Search','wp-recall'),
        'popular_items' => __('Popular tags','wp-recall'),
        'all_items' => __('All tags','wp-recall'),
        'parent_item' => __('Parent tag','wp-recall'),
        'parent_item_colon' => __('Parent tag','wp-recall'),
        'edit_item' => __('Edit','wp-recall'),
        'update_item' => __('Update','wp-recall'),
        'add_new_item' => __('Add a new','wp-recall'),
        'new_item_name' => __('New tag','wp-recall'),
        'separate_items_with_commas' => __('Tags separated by commas','wp-recall'),
        'add_or_remove_items' => __('Add or delete','wp-recall'),
        'choose_from_most_used' => __('Select to use the','wp-recall'),
        'menu_name' => __('Product Tags','wp-recall')
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_in_nav_menus' => true,
        'show_ui' => true,
        'show_tagcloud' => true,
        'hierarchical' => false,
        'rewrite' => true,
        'query_var' => true
    );

    register_taxonomy( 'product_tag', array('products'), $args );
}

// создаем колонку товарных категорий
add_filter('manage_edit-products_columns', 'rcl_prodcat_column', 10, 1);
function rcl_prodcat_column( $columns ){
    $columns['prodcat'] = __('Category','wp-recall');
    return $columns;
}

// заполняем колонку данными
add_filter('manage_products_posts_custom_column', 'rcl_fill_prodcat_column', 5, 2);
function rcl_fill_prodcat_column($column_name, $post_id) {
    if( $column_name != 'prodcat' )
        return;

    $cur_terms = get_the_terms( $post_id,'prodcat');
		foreach((array)$cur_terms as $cur_term){
			echo '<a href="./edit.php?post_type=products&prodcat='. $cur_term->slug .'">'. $cur_term->name .'</a><br />'  ;
		}
}
// добавляем возможность сортировать колонку
add_filter('manage_edit-products_sortable_columns', 'rcl_price_sortable_column');
function rcl_price_sortable_column($sortable_columns){
        $sortable_columns['prodcat'] = 'prodcat_prodcat';

        return $sortable_columns;
}

// создаем колонку цены
add_filter('manage_edit-products_columns', 'rcl_price_column', 10, 1);
function rcl_price_column( $columns ){
	$out = array();
    foreach((array)$columns as $col=>$name){
        if(++$i==3)
             $out['price'] = __('Price','wp-recall');
        $out[$col] = $name;
    }
    return $out;

}

// заполняем колонку цены
add_filter('manage_products_posts_custom_column', 'rcl_fill_price_column', 5, 2);
function rcl_fill_price_column($column_name, $post_id) {
    switch( $column_name ){
        case 'price':
            echo '<input type="text" id="price-product-'.$post_id.'" name="price-product" size="4" value="'.get_post_meta($post_id,'price-products',1).'"> '.rcl_get_current_type_currency($post_id).'
                <input type="button" class="recall-button edit-price-product" product="'.$post_id.'" id="product-'.$post_id.'" value="'.__('OK','wp-recall').'">';
        break;
    }
}

add_filter('manage_products_posts_columns', 'rcl_delete_column_date_product', 10, 1);
function rcl_delete_column_date_product( $columns ){
        unset($columns['date']);
        return $columns;

}

// создаем колонку наличия товара
add_filter('manage_edit-products_columns', 'rcl_availability_column', 10, 1);
function rcl_availability_column( $columns ){
    global $rmag_options;
    if($rmag_options['products_warehouse_recall']!=1) return $columns;
    $out = array();
    foreach((array)$columns as $col=>$name){
            if(++$i==3)
                $out['availability'] = __('Availability','wp-recall');
            $out[$col] = $name;
    }
    return $out;

}

// заполняем колонку наличия товара
add_filter('manage_products_posts_custom_column', 'rcl_fill_availability_column', 5, 2);
function rcl_fill_availability_column($column_name, $post_id) {
    global $rmag_options;
    if($rmag_options['products_warehouse_recall']!=1) return $column_name;

    if( $column_name != 'availability' ) return;

     if(get_post_meta($post_id, 'availability_product', 1)=='empty'){ //если товар цифровой
         echo '<span>'.__('digital goods','wp-recall').'</span>';
     }else{
        if(!get_post_meta($post_id, 'outsale', 1)){
            $amount = get_post_meta($post_id,'amount_product',1);
            $reserve = get_post_meta($post_id,'reserve_product',1);

            if($amount==0&&$amount!='') echo '<span style="color:red;">'.__('in stock','wp-recall').'</span> ';
            else echo '<span style="color:green;">'.__('in stock','wp-recall').'</span> ';

            if($amount!=false&&$amount>0) echo '<span style="color:green;">'.$amount.'</span>';
                    else if($amount<=0) echo '<span style="color:red;">'.$amount.'</span>' ;

            if($reserve) echo '<br /><span style="color:orange;">'.__('in reserve','wp-recall').' '.$reserve.'</span>';
        }else{
                echo '<span style="color:red;">'.__('withdrawn from sale','wp-recall').'</span>';
        }
     }
}


// создаем колонку миниатюр
add_filter('manage_edit-products_columns', 'rcl_thumb_column', 10, 1);
function rcl_thumb_column( $columns ){
	$out = array();
    foreach((array)$columns as $col=>$name){
        if(++$i==2)
             $out['thumb'] = __('Thumbnail','wp-recall');
        $out[$col] = $name;
    }
    return $out;

}

if (is_admin()) add_action('admin_init', 'rcl_options_products');
function rcl_options_products() {
    add_meta_box( 'recall_meta', __('Settings Wp-Recall','wp-recall'), 'rcl_options_box', 'products', 'normal', 'high'  );
}

// заполняем колонку миниатюр
add_filter('manage_products_posts_custom_column', 'rcl_fill_thumb_column', 5, 2);
function rcl_fill_thumb_column($column_name, $post_id) {
    if( $column_name != 'thumb' )
        return;
    if(get_the_post_thumbnail($post_id,'thumbnail')) $img = get_the_post_thumbnail($post_id,array(70,70)) ;

    echo '<div class="thumbnail">'.$img.'</div>';
}


add_action('admin_init', 'rcl_products_fields', 1);
function rcl_products_fields() {
    add_meta_box( 'products_fields', __('Product features','wp-recall'), 'rcl_metabox_products', 'products', 'normal','high' );
}

function rcl_metabox_products( $post ){
    global $rmag_options; ?>

    <p><?php _e('Price product','wp-recall') ?>:<br>
    <label><input type="number" name="wprecall[price-products]" value="<?php echo get_post_meta($post->ID,'price-products',1); ?>" style="width:70px" /> <?php rcl_type_currency_list($post->ID); ?></label></p>

    <?php if($rmag_options['multi_cur']){ ?>
    <p><?php _e('The course is an additional currency for the goods','wp-recall') ?>:<br>
    <label><input type="text" name="wprecall[curse_currency]" value="<?php echo get_post_meta($post->ID,'curse_currency',1); ?>" style="width:70px" /></label><br>
    <small><?php _e('The current rate of additional currency','wp-recall') ?>: <?php echo $rmag_options['curse_currency']; ?>.<br>
    <?php _e('If the goods specified its course , it will be a priority in the calculation of the price of this commodity','wp-recall') ?>.</small></p>
    <?php } ?>

    <p><?php _e('The margin on goods','wp-recall') ?>:<br>
    <label><input type="number" name="wprecall[margin_product]" value="<?php echo get_post_meta($post->ID,'margin_product',1); ?>" style="width:70px" /> (%)</label><br>
    <small><?php _e('The margin on the goods will be added to the output value of the goods','wp-recall') ?></small></p>

    <?php
    $customprice = unserialize(get_post_meta($post->ID, 'custom-price', 1));
    if($customprice){
            $cnt = count($customprice);
            for($a=0;$a<$cnt;$a++){
                    $price .= '<p id="custom-price-'.$a.'">'.__('Title','wp-recall').': <input type="text" class="title-custom-price" name="title-custom-price[]" value="'.$customprice[$a]['title'].'">
                    '.__('Price','wp-recall').': <input type="number" class="custom-price" name="custom-price[]" value="'.$customprice[$a]['price'].'">
                    <a href="#" class="delete-price" id="'.$a.'">'.__('delete','wp-recall').'</a></p>';
            }
    } ?>

    <?php if($rmag_options['products_warehouse_recall']==1){ ?>
            <h4><?php _e('Product availability','wp-recall') ?>: <?php $mark_v = get_post_meta($post->ID, 'availability_product', 1); ?></h4>
             <p><label><input type="radio" name="wprecall[availability_product]" value="" <?php checked( $mark_v, '' ); ?>/> <?php _e('in stock','wp-recall') ?></label>
             <input type="number" name="wprecall[amount_product]" value="<?php echo get_post_meta($post->ID, 'amount_product', 1); ?>" size="4"/></p>
             <p><label><input type="radio" name="wprecall[availability_product]" value="empty" <?php checked( $mark_v, 'empty' ); ?> /> <?php _e('Digital goods','wp-recall') ?></label></p>

    <?php }else{ ?>
        <p><label><input type="checkbox" name="wprecall[availability_product]" value="empty" <?php checked( get_post_meta($post->ID, 'availability_product', 1), 'empty' ); ?> /> <?php _e('Digital goods','wp-recall') ?></label></p>
    <?php } ?>


    <p><label><input type="checkbox" name="wprecall[outsale]" value="1" <?php checked( get_post_meta($post->ID, 'outsale', 1), 1 ); ?> /> <?php _e('Remove the sale','wp-recall') ?></label></p>

    <?php
    if($rmag_options['sistem_related_products']==1){

      $related = get_post_meta($post->ID, 'related_products_recall', 1); 
      
      $rel_prodcat = (isset($related['prodcat']))? $related['prodcat']: '';
      $rel_product_tag = (isset($related['product_tag']))? $related['product_tag']: '';

    echo '<h3>'.__('Similar and recommended products','wp-recall').':</h3>';
    $args = array(
            'show_option_all'    => '',
            'show_option_none'   => __('Choose category','wp-recall'),
            'orderby'            => 'name',
            'order'              => 'ASC',
            'show_last_update'   => 0,
            'show_count'         => 0,
            'hide_empty'         => 0,
            'child_of'           => 0,
            'exclude'            => '',
            'echo'               => 0,
            'selected'           => $rel_prodcat,
            'hierarchical'       => 0,
            'name'               => 'wprecall[related_products_recall][prodcat]',
            'id'                 => 'name',
            'class'              => 'postform',
            'depth'              => 0,
            'tab_index'          => 0,
            'taxonomy'           => 'prodcat',
            'hide_if_empty'      => false );

        echo '<div style="margin:10px 0;">'.wp_dropdown_categories( $args ).' - '.__('Select product category','wp-recall').'</div>';

        $args = array(
            'show_option_all'    => '',
            'show_option_none'   => __('Select tag','wp-recall'),
            'orderby'            => 'name',
            'order'              => 'ASC',
            'show_last_update'   => 0,
            'show_count'         => 1,
            'hide_empty'         => 0,
            'child_of'           => 0,
            'exclude'            => '',
            'echo'               => 0,
            'selected'           => $rel_product_tag,
            'hierarchical'       => 0,
            'name'               => 'wprecall[related_products_recall][product_tag]',
            'id'                 => 'name',
            'class'              => 'postform',
            'depth'              => 0,
            'tab_index'          => 0,
            'taxonomy'           => 'product_tag',
            'hide_if_empty'      => true );

        echo '<div style="margin:10px 0;">'.wp_dropdown_categories( $args ).' - '.__('select product tag','wp-recall').'</div>';

    }

    echo apply_filters('rcl_products_custom_fields','',$post);

    if(!class_exists( 'Attachments' )){
    $args = array(
        'numberposts' => -1,
        'order'=> 'ASC',
        'post_mime_type' => 'image',
        'post_parent' => $post->ID,
        'post_status' => null,
        'post_type' => 'attachment'
    );

    $childrens = get_children( $args );

    $postmeta = get_post_meta($post->ID, 'children_prodimage', 1);
    $value = explode(',',$postmeta);
    $count_value = count($value);
    $id_thumbnail = get_post_thumbnail_id( $post->ID );

    echo '
    <style>
    .image-prod-gallery{float: left; margin: 3px;} .prod-gallery{overflow:hidden;}
    </style>
    <h3>'.__('Images gallery','wp-recall').'</h3>

    <div class="prod-gallery">';
    if( $childrens ){
            $n=0;

            foreach((array) $childrens as $children ){

                $n++;

                for($a=0;$a<=$count_value;$a++){
                        if($value[$a]==$children->ID) $selected = ' checked=checked';
                }
                echo '<div class="image-prod-gallery"><label><img width="100" src="'.wp_get_attachment_thumb_url( $children->ID ).'" class="current">';
                echo '<input style="position: absolute; margin-left: -15px; margin-top: 85px;" type="checkbox" id="imageprod-'.$children->ID.'" name="children_prodimage[]" value="'.$children->ID.'"'.$selected.'></label></div>';


                $selected = '';

                if($id_thumbnail==$children->ID) $thumb = true;

            }
            if(!$thumb&&has_post_thumbnail($post->ID)){
                    for($a=0;$a<=$count_value;$a++){
                            if($value[$a]==$id_thumbnail) $selected = ' checked=checked';
                    }
                    echo '<div class="image-prod-gallery"><label><img width="100" src="'.wp_get_attachment_thumb_url( $id_thumbnail ).'" class="current">';
                    echo '<input style="position: absolute; margin-left: -15px; margin-top: 85px;" type="checkbox" id="imageprod-'.$id_thumbnail.'" name="children_prodimage[]" value="'.$id_thumbnail.'"'.$selected.'></label></div>';

                    $selected = '';
            }

    }else{
            if(has_post_thumbnail($post->ID)){
                    for($a=0;$a<=$count_value;$a++){
                                    if($value[$a]==$id_thumbnail) $selected = ' checked=checked';
                    }
                    echo '<div class="image-prod-gallery"><label><img width="100" src="'.wp_get_attachment_thumb_url( $id_thumbnail ).'" class="current">';
                    echo '<input style="position: absolute; margin-left: -15px; margin-top: 85px;" type="checkbox" id="imageprod-'.$id_thumbnail.'" name="children_prodimage[]" value="'.$id_thumbnail.'"'.$selected.'></label></div>';

                    $selected = '';
            }
    }
    echo '</div>';
    }

?>

	<input type="hidden" name="wpm_fields_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>" />
<?php
}

add_action('save_post', 'rmag_extra_fields_update');
function rmag_extra_fields_update( $post_id ){
    if(!isset($_POST['wpm_fields_nonce'])) return false;
    if ( !wp_verify_nonce($_POST['wpm_fields_nonce'], __FILE__) ) return false;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return false;
    if ( !current_user_can('edit_post', $post_id) ) return false;

    if(!isset($_POST['wprecall']['outsale'])) delete_post_meta($post_id, 'outsale');
    if(!isset($_POST['wprecall']['availability_product'])) delete_post_meta($post_id, 'availability_product');

    if( $_POST['children_prodimage']=='' ){
            delete_post_meta($post_id, 'children_prodimage');
    }else{
        $_POST['children_prodimage'] = array_map('trim', (array)$_POST['children_prodimage']);
        $n=0;
        foreach((array) $_POST['children_prodimage'] as $value ){
            $n++;
            if($n==1) $children_prodimage = $value;
                    else $children_prodimage .= ','.$value;
        }
        update_post_meta($post_id, 'children_prodimage', $children_prodimage);
    }

    return $post_id;
}
