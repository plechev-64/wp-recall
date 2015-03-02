<?php
function get_short_basket_rmag(){
	global $rmag_options;
	if($rmag_options['add_basket_button_recall']==1) add_shortcode('add-basket','add_basket_button_product');
	else add_filter('the_content','add_basket_button_product');
}
add_action('init','get_short_basket_rmag');

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
                $content .= get_button_rcl('Добавить в корзину','#',array('icon'=>false,'class'=>'add_basket','id'=>$post->ID));	
            }else{
                if($rmag_options['products_warehouse_recall']==1){ 
                    $amount = get_post_meta($post->ID, 'amount_product', 1);
                    if($amount>0||$amount==false){
                        $content .= 'Цена: '.$price.' руб. <input type="text" size="2" name="number_product" id="number_product" value="1">'
                                . get_button_rcl('Добавить в корзину','#',array('icon'=>false,'class'=>'add_basket','id'=>$post->ID));								
                    }
                }else{
                    $content .= 'Цена: '.$price.' руб. <input type="text" size="2" name="number_product" id="number_product" value="1">'
                            . get_button_rcl('Добавить в корзину','#',array('icon'=>false,'class'=>'add_basket','id'=>$post->ID));		
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
    global $rmag_options;
    $sumprice = 0;

    if(isset($_SESSION['cartdata']['summ'])) $sumprice = $_SESSION['cartdata']['summ'];
    
    $all = 0;
    if(isset($_SESSION['cart'])){
        foreach($_SESSION['cart'] as $prod_id=>$val){
            $all += $val['number'];
        }
    }

    $minibasket = '<div class="cart-icon"><i class="fa fa-shopping-cart"></i></div>'
            . '<div>В вашей корзине:</div>';	
    if($all){
	$minibasket .= '<div>Всего товаров: <span class="allprod">'.$all.'</span> шт.</div>
	<div>Общая сумма: <span class="sumprice">'.$sumprice.'</span> руб.</div>
	<a href="'.get_permalink($rmag_options['basket_page_rmag']).'">Перейти в корзину</a>';
    }else{
        $minibasket .= '<div class="empty-basket" style="text-align:center;">Пока пусто</div>';
    }
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

	$n=0;
	
	if($type=='slab'||$type=='list') $prodlist ='<div class="prodlist">';
            else  $prodlist .='<table class="prodlist">';
	
		foreach((array)$products as $product){
			$n++;
			$thumbnail_id = get_post_thumbnail_id($product->ID);
			$large_image_url = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail');
			$price = get_post_meta($product->ID, 'price-products', 1);
                  
			$post_content = strip_tags($product->post_content);
			$catlist = get_the_term_list( $product->ID, 'prodcat', 'Категория: ', ', ', '' );
			if(strlen($post_content) > $desc){
				$post_content = substr($post_content, 0, $desc);
				$post_content = preg_replace('@(.*)\s[^\s]*$@s', '\\1 ...', $post_content);
			}
			if($type=='slab'){	
				$prodlist .='<div class="prod-single slab-list">';
				$prodlist .='<a href="'.get_permalink($product->ID).'"><h3 class="title-prod">'.$product->post_title.'</h3></a>';
				$prodlist .='<div class="thumb-prod"><img src="'.$large_image_url[0].'"></div>';				
				$prodlist .='<p class="desc-prod">'.$post_content.'</p>';
                                
                                
                                if($price) $prodlist .='<h4 class="price-prod">Цена: '.$price.' руб.</h4>';
                                else $prodlist .='<h4 style="color:red;text-transform: uppercase;" class="price-prod">Бесплатно!</h4>';
                                    
                                $prodlist .= get_button_cart_rcl($product->ID);
                                
				$prodlist .='</div>';
				$cnt = $n%$inline;
				if($cnt==0) $prodlist .='<div class="clear"></div>';
			}
			
			if($type=='list'){	
				$prodlist .='<div class="prod-single list-list">'
                                        . '<div class="thumb-prod" width="110">'
                                            . '<img width="100" src="'.$large_image_url[0].'">'
                                        . '</div>'
                                        . '<div class="product-content">';
                                
                                if($price) $prodlist .='<span class="price-prod">'.$price.' руб.</span>';
				else $prodlist .='<span class="price-prod no-price">Бесплатно!</span>';
                                
                                            $prodlist .= '<a href="'.get_permalink($product->ID).'">'
                                                . '<h3 class="title-prod">'.$product->post_title.'</h3>'
                                            . '</a>'
                                            . '<p class="desc-prod">'.$post_content.'</p>';
                                            if($catlist) $prodlist .='<p class="product-category">'.$catlist.'</p>';
                                            
                                            $prodlist .= get_button_cart_rcl($product->ID);

				$prodlist .='</div>'
                                        . '</div>';
			}
			if($type=='rows'){
				 if($n%2) $prodlist .='<tr class="prod-single rows-list parne">';
                                    else  $prodlist .='<tr class="prod-single rows-list">';	
				$prodlist .='<td><a href="'.get_permalink($product->ID).'"><h3 class="title-prod">'.$product->post_title.'</h3></a></td>';								
				$prodlist .='<td><h4 class="price-prod">Цена: '.$price.' руб.</h4></td>';
                                $prodlist .= '<td>'.get_button_cart_rcl($product->ID).'</td>';
				$prodlist .='</tr>';
			}			
		}
			
		if($type=='slab'||$type=='list') $prodlist .='</div>'; 
		else $prodlist .='</table>';
		
		
	if(!$num) $prodlist .= $rclnavi->navi();
	
	return $prodlist;
	
	wp_reset_query();
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