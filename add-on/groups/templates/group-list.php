<?php global $rcl_group; ?>
<div class="single-group">
    <div class="group-avatar">
        <a href="<?php rcl_group_permalink(); ?>">
            <?php rcl_group_thumbnail(array(100,100)); ?>
        </a>
    </div>
    <div class="group-row">
        <span class="group-name"><a href="<?php rcl_group_permalink(); ?>"><?php rcl_group_name(); ?></a></span>
        <span class="group-status group-meta"><?php rcl_group_status(); ?></span>
        <span class="group-users group-meta"><?php _e('Users','wp-recall') ?>: <?php rcl_group_count_users(); ?></span>
        <span class="group-posts-counter group-meta"><?php _e('Posts','wp-recall') ?>: <?php rcl_group_post_counter(); ?></span>
    </div>
</div>

