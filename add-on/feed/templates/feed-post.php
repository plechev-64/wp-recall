<?php global $rcl_feed; ?>

<div class="feed-header">

    <?php if($rcl_feed->is_options) rcl_feed_options(); ?>

    <div class="feed-author-avatar">
        <a href="<?php echo get_author_posts_url($rcl_feed->feed_author); ?>">
            <?php echo get_avatar($rcl_feed->feed_author,40); ?>
        </a>
    </div>
    <div class="feed-author-name">
        <a href="<?php echo get_author_posts_url($rcl_feed->feed_author); ?>">
            <?php echo get_the_author_meta('display_name',$rcl_feed->feed_author); ?>
        </a>
    </div>
    <div class="feed-date">
        <?php echo mysql2date('j F Y H:i',$rcl_feed->feed_date); ?>
    </div>
</div>

<div class="feed-content">

    <?php if($rcl_feed->feed_title): ?>
        <h3 class="feed-title"><?php rcl_feed_title(); ?></h3>
     <?php endif; ?>

    <?php rcl_feed_content(); ?>

</div>


