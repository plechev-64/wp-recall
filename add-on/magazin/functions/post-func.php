<?php
//Меняем цену в админке
if (is_admin()):
	add_action('init', 'rcl_edit_price_product_admin_activate');
endif;

function rcl_edit_price_product_admin(){

	$priceprod = $_GET['priceprod'];
	$count = count($priceprod);

	for($a=0;$a<=$count;$a++){
		if($priceprod[$a]) update_post_meta(intval($_GET['product'][$a]), 'price-products', intval($priceprod[$a]));
		if($_GET['amountprod'][$a]!='') update_post_meta(intval($_GET['product'][$a]), 'amount_product', intval($_GET['amountprod'][$a]));
	}
}

function rcl_edit_price_product_admin_activate ( ) {
  if ( isset( $_GET['priceprod'] ) ) {
    add_action( 'wp', 'rcl_edit_price_product_admin' );
  }
}
?>