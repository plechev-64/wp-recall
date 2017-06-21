<div class="prime-forum-content">
    
    <?php if(pfm_have_forums()): ?>
    
        <?php pfm_page_navi(); ?>
    
        <?php while ( pfm_get_next('forum') ) : ?>
    
             <?php pfm_the_template('pfm-single-forum'); ?>
    
        <?php endwhile; ?>
    
        <?php pfm_page_navi(); ?>
    
    <?php else: ?>
    
        <?php pfm_the_notices(); ?>
    
    <?php endif; ?>
</div>