<div class="prime-forum-content">
    
    <?php if(pfm_have_forums()): ?>
    
        <div class="prime-subforums">
            
            <?php while ( pfm_get_next('forum') ) : ?>

                <?php pfm_the_template('pfm-single-forum'); ?>

            <?php endwhile; ?>

        </div>
    
        <?php pfm_reset_forumdata(); ?>
    
    <?php endif; ?>
    
    <?php if(pfm_have_topics()): ?>
    
        <?php pfm_page_navi(); ?>
    
        <?php do_action('pfm_forum_loop_before'); ?>
    
        <?php while ( pfm_get_next('topic') ) : ?>
    
            <?php pfm_the_template('pfm-single-topic'); ?>
    
        <?php endwhile; ?>
    
        <?php pfm_page_navi(); ?>
    
    <?php else: ?>
    
        <?php pfm_the_notices(); ?>
    
    <?php endif; ?>
        
    <?php pfm_the_topic_form(); ?>
        
</div>

