<?php global $rating; ?>
<div class="rating-single">
    <div class="rating-meta">
        <div class="meta">
            <span class="object-rating">
                <i class="fa fa-star"></i> 
                <?php echo $rating->rating_total; ?>
                <?php if($rating->time_sum) echo '(+'.$rating->time_sum.')'; ?>
            </span>
            <span class="object-title">
                <a title="<?php echo get_the_title($rating->object_id); ?>" href="<?php echo get_permalink($rating->object_id); ?>">
                    <?php echo get_the_title($rating->object_id); ?>
                </a>
            </span>
        </div>		
    </div>
</div>