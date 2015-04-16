<?
	/*Шаблон для отображения содержимого шорткода productslist с указанием атрибута type='rows'*/
	/*Данный шаблон можно разместить в папке используемого шаблона /recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $post; ?>
<tr class="prod-single rows-list">
	<td>
		<a href="<?php the_permalink(); ?>">
			<h3 class="title-prod"><?php the_title(); ?></h3>
		</a>
	</td>							
	<td>
		<h4 class="price-prod">Цена: <?php echo get_price($post->ID); ?></h4>
	</td>
	<td>
		<?php echo get_button_cart_rcl($post->ID); ?>
	</td>
</tr>
