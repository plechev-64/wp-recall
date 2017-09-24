<div class="prime-forum-content">
    
    <?php if(pfm_have_topics()): ?>
    
        <div class="prime-topics prime-parent-box">
            <div class="prime-item-label"><span><?php _e('The archive of the author','wp-recall'); ?></span></div>
            <div class="prime-topics-list prime-loop-list">
    
            <?php pfm_page_navi(); ?>

            <?php while ( pfm_get_next('topic') ) : ?>

                <?php pfm_the_template('pfm-single-topic'); ?>

            <?php endwhile; ?>

            <?php pfm_page_navi(); ?>
                
            </div>
        </div>
    
    <?php else: ?>
    
        <?php pfm_the_notices(); ?>
    
    <?php endif; ?>
        
</div>

