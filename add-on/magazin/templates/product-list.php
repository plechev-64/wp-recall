<?
	/*Шаблон для отображения содержимого шорткода [productlist] с указанием атрибута type='list'*/
	/*Данный шаблон можно разместить в папке используемого шаблона /wp-content/wp-recall/templates/ и он будет подключаться оттуда*/
?>
<?php global $post,$desc,$productlist; ?>
<div class="product" id="product-<?php the_ID(); ?>" itemscope itemtype="http://schema.org/Product">
	<a class="product-thumbnail" href="<?php the_permalink(); ?>">
		<?php the_post_thumbnail('thumbnail',array('alt'=>$post->post_title,'itemprop'=>'image')); ?>
	</a>
	<div class="product-content">
		<a class="product-title" href="<?php the_permalink(); ?>" itemprop="name">
                    <?php the_title(); ?>
                </a>
		
		<div class="product-meta">
			<?php rcl_product_excerpt(); ?>
			<?php echo rcl_get_product_category($post->ID); ?>
		</div>		
		
		<?php echo rcl_get_cart_button($post->ID); ?>
            
	</div>
</div>