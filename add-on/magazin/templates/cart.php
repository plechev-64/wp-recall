<?
	/*Шаблон для отображения содержимого шорткода basket - полной корзины пользователя*/
	/*Данный шаблон можно разместить в папке используемого шаблона /recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $products,$product,$CartData; ?>
<div id="cart-form" class="cart-data">
	<table class="order-data">
		<tr>
			<th class="product-name">Товар</th>
			<th>Цена</th>
			<th class="product-number">Количество</th>
			<th>Сумма</th>
		</tr>
		<?php foreach($products as $product): setup_cartdata($product); ?>
			<tr id="product-<?php the_product_ID(); ?>">
				<td><a href="<?php the_product_permalink(); ?>"><?php the_product_title(); ?></a></td>
				<td><?php the_product_price(); ?></td>
				<td data-product="<?php the_product_ID(); ?>">
					<a class="edit-num add-product" href="#"><i class="fa fa-plus"></i></a>
					<a class="edit-num remove-product" href="#"><i class="fa fa-minus"></i></a>
					<span class="number-product"><?php the_product_number(); ?></span>						
				</td>
				<td class="sumprice-product"><?php the_product_summ(); ?></td>
			</tr>
		<?php endforeach; ?>
		<tr>
			<th colspan="2"></th>
			<th>Общая сумма</th>
			<th class="cart-summa"><?php echo $CartData->cart_price; ?></th>
		</tr>
	</table>
</div>
