<?php
/*  Шаблон базового дополнения PublicPost (Публикация) https://codeseller.ru/?p=7084
  Если вам нужно внести изменения в данный шаблон - скопируйте его в папку /wp-content/wp-recall/templates/
  - сделайте там в нём нужные вам изменения и он будет подключаться оттуда
  Подробно работа с шаблонами описана тут: https://codeseller.ru/?p=11632
 */
?>
<?php global $post; ?>
<div class="single-post-box">

	<?php if ( has_post_thumbnail() ): ?>
		<div class="post-thumbnail">
			<?php if ( $post->post_status != 'trash' ): ?>
				<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail(); ?></a>
			<?php else: ?>
				<?php the_post_thumbnail(); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="post-content">
		<div class="post-meta">
			<span class="post-date"><?php the_date(); ?></span>
			<span class="post-status status-<?php echo $post->post_status; ?>"><?php rcl_the_post_status(); ?></span>
		</div>
		<div class="post-title">
			<?php if ( $post->post_status != 'trash' ): ?>
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			<?php else: ?>
				<?php the_title(); ?>
			<?php endif; ?>
		</div>
		<div class="post-excerpt"><?php the_excerpt(); ?></div>
	</div>

</div>

