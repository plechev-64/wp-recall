<?php global $rating; ?>
<div class="user-single">
    <div class="thumb-user">
        <a title="<?php echo get_the_author_meta('display_name',$rating->user_id); ?>" href="<?php echo get_author_posts_url($rating->user_id); ?>">
            <?php echo get_avatar($rating->user_id,70); ?>
        </a>
        <?php echo rcl_rating_block(array('value'=>$rating->rating_total)); ?>
    </div>
</div>