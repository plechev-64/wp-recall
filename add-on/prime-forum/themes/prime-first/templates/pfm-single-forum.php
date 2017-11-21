<div class="prime-forum-box">
    <div class="prime-forum-item <?php pfm_the_forum_classes(); ?>">
        <div class="prime-forum-icon">
            <?php pfm_the_icon('fa-folder'); ?>
        </div>
        <div class="prime-forum-title">
            <div class="prime-general-title"><a class="" title="<?php _e('Go to forum','wp-recall'); ?>" href="<?php pfm_the_forum_permalink(); ?>"><?php pfm_the_forum_name(); ?></a></div>
            <div class="prime-forum-description"><?php pfm_the_forum_description(); ?></div>
            <?php pfm_page_navi(array('type'=>'forum')); ?>
            <?php if(pfm_have_subforums()): ?>         
                <div class="prime-subforums-list">
                    <?php _e('Subforums:','wp-recall'); ?> <?php pfm_subforums_list(); ?>
                </div>
            <?php endif; ?>
            
        </div>
        <?php pfm_the_forum_icons(); ?>
        <div class="prime-forum-topics">
            <span><?php _e('Topics','wp-recall'); ?>:</span><span><?php pfm_the_topic_count(); ?></span>
        </div>
        <div class="prime-last-items">
            <span><?php _e('Last topic','wp-recall'); ?>: <?php pfm_the_last_topic(); ?></span>
            <span><?php _e('Last message','wp-recall'); ?> <?php pfm_the_last_post(); ?></span>
        </div>
    </div>
</div>