<?php global $rating; ?>
<div class="rating-single">   
	<a title="<?php echo get_the_author_meta('display_name',$rating->user_id); ?>" href="<?php echo get_author_posts_url($rating->user_id); ?>">
		<?php echo get_avatar($rating->user_id,60); ?>
	</a>
	<div class="rating-meta">
		<div class="meta object-title">
			<a title="<?php echo get_the_author_meta('display_name',$rating->user_id); ?>" href="<?php echo get_author_posts_url($rating->user_id); ?>">
				<?php echo get_the_author_meta('display_name',$rating->user_id); ?>
			</a>
		</div>
		<div class="meta object-rating">
			<i class="fa fa-star"></i> <?php echo $rating->rating_total; ?>
		</div>
	</div>
</div>