<div class="prime-forum-content">
    
    <?php if(pfm_have_forums()): ?>
    
        <div class="prime-forums prime-parent-box">
            <div class="prime-item-label"><span><?php _e('List of forums','wp-recall'); ?></span></div>
            <div class="prime-forums-list prime-loop-list">
    
                <?php pfm_page_navi(); ?>

                <?php while ( pfm_get_next('forum') ) : ?>

                     <?php pfm_the_template('pfm-single-forum'); ?>

                <?php endwhile; ?>

                <?php pfm_page_navi(); ?>
                
            </div>
        </div>
    
    <?php else: ?>
    
        <?php pfm_the_notices(); ?>
    
    <?php endif; ?>
</div>