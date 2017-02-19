<?php global $rating; ?>
<div class="rating-single">
	<div class="object-rating">
		<i class="fa fa-star"></i> 
		<span class="rtng-ttl"><?php echo $rating->rating_total; ?></span>
		<span class="rtng-time"><?php if($rating->days_value_sum) echo '('.$rating->days_value_sum.')'; ?></span>
	</div>
	<span class="object-title">
		<a title="<?php echo get_the_title($rating->object_id); ?>" href="<?php echo get_permalink($rating->object_id); ?>">
			<?php echo get_the_title($rating->object_id); ?>
		</a>
	</span>
</div>