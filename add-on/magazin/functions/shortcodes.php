<?php
function get_short_basket_rmag(){
	global $rmag_options;
	if($rmag_options['add_basket_button_recall']==1) add_shortcode('add-basket','add_basket_button_product');
	else add_filter('the_content','add_basket_button_product');
}
add_action('wp','get_short_basket_rmag');

//кнопку добавления заказа на странице товара
function add_basket_button_product($content){
global $post,$rmag_options;

	if($post->post_type!=='products') return $content; 
        
        $metas = get_postmetas($post->ID);
        
        $price = $metas['price-products'];
        $outsale = $metas['outsale'];
        
        $content .= '<div class="price-basket-product">';
        
        if(!$outsale){
            if($metas['availability_product']=='empty'){ //если товар цифровой
                if($price) $content .= 'Цена: '.$price.' руб. <input type="text" size="2" name="number_product" id="number_product" value="1">';
                else $content .= 'Бесплатно ';
                $content .= get_button_rcl('Добавить в корзину','#',array('icon'=>false,'class'=>'add_basket','attr'=>'data-product='.$post->ID));	
            }else{
                if($rmag_options['products_warehouse_recall']==1){ 
                    $amount = get_post_meta($post->ID, 'amount_product', 1);
                    if($amount>0||$amount==false){
                        $content .= 'Цена: '.$price.' руб. <input type="text" size="2" name="number_product" id="number_product" value="1">'
                                . get_button_rcl('Добавить в корзину','#',array('icon'=>false,'class'=>'add_basket','attr'=>'data-product='.$post->ID));								
                    }
                }else{
                    $content .= 'Цена: '.$price.' руб. <input type="text" size="2" name="number_product" id="number_product" value="1">'
                            . get_button_rcl('Добавить в корзину','#',array('icon'=>false,'class'=>'add_basket','attr'=>'data-product='.$post->ID));		
                }
            }
        }
	//if(get_post_meta($post->ID,'price-products',1)!==''){ 
		
		/*if($rmag_options['products_warehouse_recall']!=1||get_post_meta($post->ID, 'availability_product', 1)!='empty'){ 
                if($rmag_options['products_warehouse_recall']!=1){ 
			$amount = get_post_meta($post->ID, 'amount_product', 1);
			if($amount>0||$amount==false){
				$price .= '<input type="text" size="2" name="number_product" id="number_product" value="1">'
                                        . get_button_rcl('Добавить в корзину','#',array('icon'=>false,'class'=>'add_basket','id'=>$post->ID));								
			}
		}*/
		$content .= '</div>';
	//}
	
	/*$customprice = unserialize(get_post_meta($post->ID, 'custom-price', 1));
	if($customprice){
		$cnt = count($customprice);
		for($a=0;$a<$cnt;$a++){												
			$price .= '<div class="price-basket-product">'.$customprice[$a]['title'].' - '.$customprice[$a]['price'].'р. <input type="text" size="2" name="number_custom_product" id="number-custom-product-'.$a.'" value="1"><input type="button" name="'.$a.'" class="recall-button add_basket" id="'.$post->ID.'" value="Добавить в корзину"></div>';
		}
	}*/
	
	return $content;
}

function shortcode_mini_basket() {
    global $rmag_options,$CartData;
    $sumprice = 0;

    if(isset($_SESSION['cartdata']['summ'])) $sumprice = $_SESSION['cartdata']['summ'];
    
    $all = 0;
    if(isset($_SESSION['cart'])){
        foreach($_SESSION['cart'] as $prod_id=>$val){
            $all += $val['number'];
        }
    }
	
	$CartData = (object)array(
		'numberproducts'=>$all,
		'cart_price'=>$sumprice,
		'cart_url'=>$rmag_options['basket_page_rmag'],
		'cart'=> $_SESSION['cart']
	);

    $minibasket = get_include_template_rcl('cart-mini.php',__FILE__);

    return $minibasket;
}
add_shortcode('minibasket', 'shortcode_mini_basket');

add_action( 'widgets_init', 'widget_minibasket' );
function widget_minibasket() {
	register_widget( 'Widget_minibasket' );
}

class Widget_minibasket extends WP_Widget {

	function Widget_minibasket() {
		$widget_ops = array( 'classname' => 'widget-minibasket', 'description' => __('Корзина','rcl') );		
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'widget-minibasket' );		
		$this->WP_Widget( 'widget-minibasket', __('Корзина','rcl'), $widget_ops, $control_ops );
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
		$defaults = array( 'title' => __('Корзина','rcl'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Заголовок','rcl'); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
	<?php
	}
}

function shortcode_page_basket() {
    include_once 'rcl_cart.php';
    $form = new Rcl_Cart();
    return $form->cart();
}
add_shortcode('basket', 'shortcode_page_basket');

add_shortcode('productlist','short_product_list');

function short_product_list($atts, $content = null){
	global $post,$wpdb,$rmag_options;
			
	extract(shortcode_atts(array(
            'num' => false,
            'inpage' => 10,
            'type' => 'list',
            'inline' => 3,
            'cat' => false,
            'desc'=> 200,
            'tag'=> false,
            'include' => false,
            'orderby'=> 'post_date',
            'order'=> 'DESC',
            'author'=>false
	),
	$atts));
        
	if(!$num){ 
		$count_prod = $wpdb->get_var("SELECT COUNT(ID) FROM ".$wpdb->prefix."posts WHERE post_type='products' AND post_status='publish'");	
	}else{
                $count_prod = false;
		$inpage = $num;
	}
	
	$rclnavi = new RCL_navi($inpage,$count_prod,'&filter='.$orderby);

	if($cat) 
	$args = array(
	'numberposts'     => $inpage,
	'offset'          => $rclnavi->offset,
        'orderby'         => $orderby,
        'order'           => $order,
        'author'           => $author,
        'post_type'       => 'products',
	'tag'             => $tag,
	'include'         => $include,
	'tax_query' 	  => array(
            array(
                    'taxonomy'=>'prodcat',
                    'field'=>'id',
                    'terms'=> explode(',',$cat)
                    )
            )  
	);
	else
		$args = array(
		'numberposts'     => $inpage,
		'offset'          => $rclnavi->offset,
		'category'        => '',
		'orderby'         => $orderby,
		'order'           => $order,
                'author'           => $author,
		'include'         => $include,
		'tag'			  => $tag,
		'exclude'         => '',
		'meta_key'        => '',
		'meta_value'      => '',
		'post_type'       => 'products',
		'post_mime_type'  => '',
		'post_parent'     => '',
		'post_status'     => 'publish'	  
		);

	$products = get_posts($args);
	
	if(!$products) return false;

	$n=0;
	
	$block = ($type=='rows')? 'table': 'div';
	
	$prodlist .='<'.$block.' class="prodlist">';
	
	foreach($products as $post){ setup_postdata($post);
		$n++;
		$prodlist .= get_include_template_rcl('list-'.$type.'.php',__FILE__);
		if($type=='slab'){
			$cnt = $n%$inline;
			if($cnt==0) $prodlist .='<div class="clear"></div>';
		}			
	}
	wp_reset_query();
		
	$prodlist .='</'.$block.'>';
				
	if(!$num) $prodlist .= $rclnavi->navi();
	
	return $prodlist;
}

add_shortcode('pricelist', 'shortcode_pricelist_recall');
function shortcode_pricelist_recall($atts, $content = null){
	global $post;
	
	extract(shortcode_atts(array(
	'catslug' => '',
	'tagslug'=> '',
	'catorder'=>'id',
	'prodorder'=>'post_date'
	),
	$atts));		
	
	if($catslug) 
	$args = array(
	'numberposts'     => -1,
    'orderby'         => $prodorder,
    'order'           => '',
    'post_type'       => 'products',
	'tag'			  => $tagslug,
	'include'         => $include,
	'tax_query' 	  => array(
							array(
								'taxonomy'=>'prodcat',
								'field'=>'slug',
								'terms'=> $catslug
								)
							)  
	);
	else
	$args = array(
    'numberposts'     => -1,
    'orderby'         => $prodorder,
    'order'           => '',
	'tag'			  => $tagslug,
    'exclude'         => '',
    'meta_key'        => '',
    'meta_value'      => '',
    'post_type'       => 'products',
    'post_mime_type'  => '',
    'post_parent'     => '',
    'post_status'     => 'publish'	  
	);
	
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

                        if( has_term( '', 'post_tag', $product->ID ) ){
                                $tags = get_the_terms( $product->ID, 'post_tag' );  
                                foreach((array)$tags as $tag){  
                                        $tags_prod .= $tag->name;  
                                } 
                        }

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

add_shortcode('slider-products','slider_products_rcl');
function slider_products_rcl($atts, $content = null){
	
    extract(shortcode_atts(array(
	'num' => 5,
	'cat' => '',
	'exclude' => false,
	'orderby'=> 'post_date',
	'title'=> true,
	'desc'=> 280,
        'order'=> 'DESC'
	),
    $atts));
	
    return slider_rcl(array(
        'type'=>'products',
        'tax'=>'prodcat',
        'term'=>$cat,
        'desc'=>$desc,
        'title'=>$title,
        'exclude'=>$exclude,
        'order'=>$order,
        'orderby'=>$orderby
    ));
    
}
?>