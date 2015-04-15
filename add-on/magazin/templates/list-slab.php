<?
	/*Шаблон для отображения содержимого шорткода productslist с указанием атрибута type='slab',
	а также при выводе рекомендуемых товаров*/
	/*Данный шаблон можно разместить в папке используемого шаблона /recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $post; ?>
<div class="prod-single slab-list">
	<a href="<?php the_permalink(); ?>">
		<h3 class="title-prod"><?php the_title(); ?></h3>
	</a>
	<?php if ( has_post_thumbnail()) : ?>
		<div class="thumb-prod" width="110">
			<?php the_post_thumbnail( 'thumbnail' ); ?>
		</div>
	<?php endif; ?>			
	<p class="desc-prod">
		<?php the_product_excerpt(); ?>
	</p>
	
	<?php echo get_price($post->ID); ?>
		
	<?php echo get_button_cart_rcl($post->ID); ?>
				
</div>
