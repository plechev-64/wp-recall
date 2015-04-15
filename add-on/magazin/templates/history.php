<?
	/*Шаблон для отображения содержимого истории заказов пользователя*/
	/*Данный шаблон можно разместить в папке используемого шаблона /recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $orders,$order,$user_ID; ?>
<div class="order-data">
	<table>
		<tr>
			<th>Номер заказа</th>
			<th>Дата заказа</th>
			<th>Количество товаров</th>
			<th>Сумма</th>
			<th>Статус заказа</th>
		</tr>
		<?php foreach($orders as $data){ setup_orderdata($data); ?>
			<tr>
				<td>
					<a href="<?php echo get_redirect_url_rcl(get_author_posts_url($user_ID),'order'); ?>&order-id=<?php the_order_ID(); ?>">
						<?php the_order_ID(); ?>
					</a>
				</td>
				<td><?php the_order_date(); ?></td>
				<td><?php the_number_products(); ?></td>
				<td><?php the_order_price(); ?></td>
				<td><?php the_order_status(); ?></td>
			</tr>
		<?php } ?>
		<tr>
			<th colspan="5"></th>
		</tr>							
	</table>
</div>
