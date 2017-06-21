<div class="prime-forum-content">
    
    <?php if(pfm_have_groups()): ?>
    
        <?php while ( pfm_get_next('group') ) : ?>
    
            <?php pfm_the_template('pfm-single-group'); ?>
    
        <?php endwhile; ?>
    
    <?php else: ?>
    
        <?php pfm_the_notices(); ?>
    
    <?php endif; ?>
</div>

