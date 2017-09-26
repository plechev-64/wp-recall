<div class="prime-forum-content">
    
    <?php if(pfm_have_posts()): ?>
    
        <?php pfm_the_topic_manager(); ?>
    
        <div class="prime-topics-header">
            <span class="topic_count"><?php pfm_the_post_count(); ?> <?php _e('reply(ies) in the topic','wp-recall'); ?></span>
            <?php pfm_page_navi(); ?>
        </div>
    
        <?php do_action('pfm_topic_loop_before'); ?>
    
        <div class="prime-posts">
    
        <?php while ( pfm_get_next('post') ) : ?>
            
            <?php pfm_the_template('pfm-single-post'); ?>

        <?php endwhile; ?>
            
        </div>
            
        <?php pfm_page_navi(); ?>
    
        <?php pfm_the_topic_manager(); ?>

    <?php else: ?>
    
        <?php pfm_the_notices(); ?>
        
    <?php endif; ?>

    <?php pfm_the_post_form(); ?>
        
</div>

