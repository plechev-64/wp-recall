<?php global $rating; ?>
<div class="rating-single">   
	<a title="<?php echo get_the_author_meta('display_name',$rating->object_author); ?>" href="<?php echo get_author_posts_url($rating->object_author); ?>">
		<?php echo get_avatar($rating->object_author,60); ?>
	</a>
	<div class="rating-meta">
		<div class="meta object-title">
			<a title="<?php echo get_the_author_meta('display_name',$rating->object_author); ?>" href="<?php echo get_author_posts_url($rating->object_author); ?>">
				<?php echo get_the_author_meta('display_name',$rating->object_author); ?>
			</a>
		</div>
		<div class="meta object-rating">
			<i class="fa fa-star"></i> <?php echo $rating->rating_total; ?>
		</div>
	</div>
</div>