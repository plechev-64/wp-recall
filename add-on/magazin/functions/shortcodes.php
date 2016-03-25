<?php
function rcl_get_shortcode_cart(){
	global $rmag_options;
	if(isset($rmag_options['add_basket_button_recall'])&&$rmag_options['add_basket_button_recall']==1)
            add_shortcode('add-basket','rcl_add_cart_button');
	else
            add_filter('the_content','rcl_add_cart_button');
}
add_action('wp','rcl_get_shortcode_cart');

//кнопку добавления заказа на странице товара
function rcl_add_cart_button($content){
global $post,$rmag_options;

	if($post->post_type!=='products') return $content;

        $metas = rcl_get_postmeta_array($post->ID);

        $price = $metas['price-products'];
        $outsale = (isset($metas['outsale']))?$metas['outsale']:false;

        $button = '<div id="product-'.$post->ID.'" class="single-cart-button">'
                . '<div class="price-basket-product">';

        if(!$outsale){
            if($metas['availability_product']=='empty'){ //если товар цифровой
                if($price) $button .= 'Цена: '.rcl_get_price($post->ID).' <input type="text" size="2" name="number_product" id="number_product" value="1">';
                else $button .= 'Бесплатно ';
                $button .= rcl_get_button('Добавить в корзину','#',array('icon'=>false,'class'=>'add_basket','attr'=>'onclick="rcl_add_cart(this);return false;" data-product='.$post->ID));
            }else{
                if($rmag_options['products_warehouse_recall']==1){
                    $amount = get_post_meta($post->ID, 'amount_product', 1);
                    if($amount>0||$amount==false){
                        $button .= 'Цена: '.rcl_get_price($post->ID).' <input type="text" size="2" name="number_product" id="number_product" value="1">'
                                . rcl_get_button('Добавить в корзину','#',array('icon'=>false,'class'=>'add_basket','attr'=>'onclick="rcl_add_cart(this);return false;" data-product='.$post->ID));
                    }
                }else{
                    $button .= 'Цена: '.rcl_get_price($post->ID).' <input type="text" size="2" name="number_product" id="number_product" value="1">'
                            . rcl_get_button('Добавить в корзину','#',array('icon'=>false,'class'=>'add_basket','attr'=>'onclick="rcl_add_cart(this);return false;" data-product='.$post->ID));
                }
            }
        }

        $button .= '</div>'
                . '</div>';

        $button = apply_filters('cart_button_product_page',$button);

        $content .= $button;

	return $content;
}

function rcl_shortcode_minicart() {
    global $rmag_options,$CartData;
    $sumprice = 0;

    if(isset($_SESSION['cartdata']['summ'])) $sumprice = $_SESSION['cartdata']['summ'];

    $all = 0;
    if(isset($_SESSION['cart'])){
        foreach($_SESSION['cart'] as $prod_id=>$val){
            $all += $val['number'];
        }
    }

    $cart = (isset($_SESSION['cart']))? $_SESSION['cart']: false;

	$CartData = (object)array(
		'numberproducts'=>$all,
		'cart_price'=>$sumprice,
		'cart_url'=>$rmag_options['basket_page_rmag'],
		'cart'=> $cart
	);
        
    $minibasket = rcl_get_include_template('cart-mini.php',__FILE__);

    return $minibasket;
}
add_shortcode('minibasket', 'rcl_shortcode_minicart');

add_action( 'widgets_init', 'rcl_widget_minicart' );
function rcl_widget_minicart() {
	register_widget( 'Widget_minibasket' );
}

class Widget_minibasket extends WP_Widget {

	function Widget_minibasket() {
            $widget_ops = array( 'classname' => 'widget-minibasket', 'description' => __('Cart','wp-recall') );
            $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'widget-minibasket' );
            parent::__construct( 'widget-minibasket', __('Cart','wp-recall'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_title', $instance['title'] );

		if ( !isset($count_user) ) $count_user = 12;

		echo $before_widget;

		if ( $title ) echo $before_title . $title . $after_title;

		echo do_shortcode('[minibasket]');
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['count_user'] = $new_instance['count_user'];
		$instance['page_all_users'] = $new_instance['page_all_users'];
		return $instance;
	}

	function form( $instance ) {
		$defaults = array( 'title' => __('Cart','wp-recall'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title','wp-recall'); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
	<?php
	}
}

add_shortcode('basket', 'rcl_shortcode_cart');
function rcl_shortcode_cart() {
    include_once 'rcl_cart.php';
    $form = new Rcl_Cart();
    return $form->cart();
}

add_shortcode('productlist','rcl_shortcode_productlist');
function rcl_shortcode_productlist($atts, $content = null){
    global $post,$wpdb,$rmag_options,$productlist,$user_ID;

    extract(shortcode_atts(array(
        'num' => false,
        'inpage' => 10,
        'type' => 'list',
        'width' => 150,
        'cat' => false,
        'desc'=> 200,
        'tag'=> false,
        'include' => false,
        'orderby'=> 'post_date',
        'order'=> 'DESC',
        'author'=>false
    ),
    $atts));
    
    $productlist = $atts;

    if(!$num){
        $count_prod = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->prefix."posts WHERE post_type='%s' AND post_status='%s'",'products','publish'));
    }else{
        $count_prod = false;
        $inpage = $num;
    }

    $rclnavi = new RCL_navi($inpage,$count_prod,'&filter='.$orderby);

    $args = array(
        'numberposts'     => $inpage,
        'offset'          => $rclnavi->offset,
        'orderby'         => $orderby,
        'order'           => $order,
        'author'          => $author,
        'post_type'       => 'products',
        'include'         => $include
    );
    
    if($cat){
        $args['tax_query'][] = array(
                                    'taxonomy'=>'prodcat',
                                    'field'=>'id',
                                    'terms'=> explode(',',$cat)
                                );
    }

    if($tag){
        $args['tax_query'][] = array(
                                    'taxonomy'=>'product_tag',
                                    'field'=>'id',
                                    'terms'=> explode(',',$tag)
                                );
    }

    $rcl_cache = new Rcl_Cache();
        
    if(!$user_ID&&$rcl_cache->is_cache){

        $file = $rcl_cache->get_file(json_encode($args));

        if(!$file->need_update){
            return $rcl_cache->get_cache();
        }

    }

    $products = get_posts($args);

    if(!$products) return false;

    $prodlist ='<div class="products-box type-'.$type.'">
                    <div class="products-list">';

    foreach($products as $post){ setup_postdata($post);
        $prodlist .= rcl_get_include_template('product-'.$type.'.php',__FILE__);
    }

    wp_reset_query();

    $prodlist .='</div>'
            . '</div>';

    if(!$num) $prodlist .= $rclnavi->navi();
    
    if(!$user_ID&&$rcl_cache->is_cache){
        $rcl_cache->update_cache($prodlist);
    }

    return $prodlist;
}

add_shortcode('pricelist', 'rcl_shortcode_pricelist');
function rcl_shortcode_pricelist($atts, $content = null){
    global $post;

    extract(shortcode_atts(array(
        'catslug' => '',
        'tagslug'=> '',
        'catorder'=>'id',
        'prodorder'=>'post_date'
    ),
    $atts));

    $args = array(
        'numberposts'     => -1,
        'orderby'         => $prodorder,
        'order'           => '',
        'post_type'       => 'products',
        'tag'             => $tagslug,
        'include'         => $include            
    );

    if($cat){
        $args['tax_query'][] = array(
                                    'taxonomy'=>'prodcat',
                                    'field'=>'id',
                                    'terms'=> explode(',',$catslug)
                                );
    }

    if($tag){
        $args['tax_query'][] = array(
                                    'taxonomy'=>'product_tag',
                                    'field'=>'id',
                                    'terms'=> explode(',',$tagslug)
                                );
    }

    $products = get_posts($args);

    $catargs = array(
        'orderby'      => $catorder
        ,'order'        => 'ASC'
        ,'hide_empty'   => true
        ,'slug'         => $catslug
        ,'hierarchical' => false
        ,'pad_counts'   => false
        ,'get'          => ''
        ,'child_of'     => 0
        ,'parent'       => ''
    );

    $prodcats = get_terms('prodcat', $catargs);

    $n=0;

    $pricelist ='<table class="pricelist">
            <tr><td>№</td><td>Наименование товара</td><td>Метка товара</td><td>Цена</td></tr>';
    foreach((array)$prodcats as $prodcat){

        $pricelist .='<tr><td colspan="4" align="center"><b>'.$prodcat->name.'</b></td></tr>';

        foreach((array)$products as $product){

            if( has_term($prodcat->term_id, 'prodcat', $product->ID)){

            $n++;
            
            $tags_prod = get_the_term_list( $product->ID, 'product_tag', '', ', ' );
            
            $pricelist .='<tr>';
            $pricelist .='<td>'.$n.'</td>';
            $pricelist .='<td><a target="_blank" href="'.get_permalink($product->ID).'">'.$product->post_title.'</a>';
            $pricelist .='<td>'.$tags_prod.'</td>';
            $pricelist .='<td>'.get_post_meta($product->ID, 'price-products', 1).' руб</td>';
            $pricelist .='</tr>';

            }
            unset ($tags_prod);
        }

        $n=0;

    }

    $pricelist .='</table>';

    return $pricelist;

}

add_shortcode('slider-products','rcl_slider_products');
function rcl_slider_products($atts, $content = null){

    extract(shortcode_atts(array(
	'num' => 5,
	'cat' => '',
	'exclude' => false,
	'orderby'=> 'post_date',
	'title'=> true,
	'desc'=> 280,
        'order'=> 'DESC',
        'size'=> '9999,300'
	),
    $atts));

    return rcl_slider(array(
        'type'=>'products',
        'tax'=>'prodcat',
        'num' => $num,
        'term'=>$cat,
        'desc'=>$desc,
        'title'=>$title,
        'exclude'=>$exclude,
        'order'=>$order,
        'orderby'=>$orderby,
        'size'=> $size
    ));

}
?>