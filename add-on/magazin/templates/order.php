<?
	/*Шаблон для отображения содержимого отдельного заказа,
	также используется при формировании письма-уведомления
	о заказе и его содержимом на почту пользователя (поэтому есть указание bordercolor и border для тега table)*/
	/*Данный шаблон можно разместить в папке используемого шаблона /wp-recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $order,$product; ?>
<div id="cart-form" class="cart-data">
	<table bordercolor="сссссс" border="1" cellpadding="5" class="order-data">
		<tr>
			<th class="product-name">Товар</th>
			<th>Цена</th>
			<th class="product-number">Количество</th>
			<th>Сумма</th>
		</tr>
		<?php foreach($order->products as $product): ?>
			<tr id="product-<?php rcl_product_ID; ?>">
				<td><a href="<?php rcl_product_permalink(); ?>"><?php rcl_product_title(); ?></a></td>
				<td><?php rcl_product_price(); ?></td>
				<td align="center" data-product="<?php rcl_product_ID; ?>">
					<span class="number-product"><?php rcl_product_number(); ?></span>
				</td>
				<td class="sumprice-product"><?php rcl_product_summ(); ?></td>
			</tr>
		<?php endforeach; ?>
		<tr>
			<th colspan="2"></th>
			<th>Общая сумма</th>
			<th class="cart-summa"><?php rcl_order_price(); ?></th>
		</tr>
	</table>
</div>