<div class="prime-group-box">
    
    <div class="prime-forum-item <?php pfm_the_group_classes(); ?>">
        <div class="prime-forum-icon">
            <?php pfm_the_icon('fa-database'); ?>
        </div>
        <div class="prime-forum-title">
            <div class="prime-general-title"><a class="" title="<?php _e('Go to group','wp-recall'); ?>" href="<?php pfm_the_group_permalink(); ?>"><?php pfm_the_group_name(); ?></a></div>
            <div class="prime-forum-description"><?php pfm_the_group_description(); ?></div>
        </div>
        <div class="prime-forum-topics">
            <span>Форумов:</span><span><?php pfm_the_forum_count(); ?></span>
        </div>
    </div>
    
    <?php if(pfm_get_option('view-forums-home')): ?>
    
        <div class="prime-forums-list prime-loop-list prime-child-forums">

            <?php while ( pfm_get_next('forum') ) : ?>

                 <?php pfm_the_template('pfm-single-forum'); ?>

            <?php endwhile; ?>

        </div>
    
    <?php endif; ?>

</div>
