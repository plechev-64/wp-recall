<?php
//Меняем цену в админке
if (is_admin()):
	add_action('init', 'edit_price_product_admin_recall_activate');
endif;

function edit_price_product_admin_recall(){

	$priceprod = $_GET['priceprod'];
	$count = count($priceprod);

	for($a=0;$a<=$count;$a++){
		if($priceprod[$a]) update_post_meta($_GET['product'][$a], 'price-products', $priceprod[$a]);
		if($_GET['amountprod'][$a]!='') update_post_meta($_GET['product'][$a], 'amount_product', $_GET['amountprod'][$a]);
	}
}

function edit_price_product_admin_recall_activate ( ) {
  if ( isset( $_GET['priceprod'] ) ) {
    add_action( 'wp', 'edit_price_product_admin_recall' );
  }
}
?>