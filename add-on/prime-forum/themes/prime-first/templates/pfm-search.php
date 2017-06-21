<div class="prime-forum-content">
    
    <?php if(pfm_have_topics()): ?>
    
        <?php pfm_page_navi(); ?>
    
        <?php while ( pfm_get_next('topic') ) : ?>
    
            <?php pfm_the_template('pfm-single-topic'); ?>
    
        <?php endwhile; ?>
    
        <?php pfm_page_navi(); ?>
    
    <?php else: ?>
    
        <?php pfm_the_notices(); ?>
    
    <?php endif; ?>
        
</div>

