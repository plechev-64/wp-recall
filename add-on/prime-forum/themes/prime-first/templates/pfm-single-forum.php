<div class="prime-forum-box">
    <div class="prime-forum-item <?php pfm_the_forum_classes(); ?>">
        <div class="prime-forum-icon">
            <i class="fa fa-folder" aria-hidden="true"></i>
        </div>
        <div class="prime-forum-title">
            <div class="prime-general-title"><a class="" title="Перейти в форум" href="<?php pfm_the_forum_permalink(); ?>"><?php pfm_the_forum_name(); ?></a></div>
            <div class="prime-forum-description"><?php pfm_the_forum_description(); ?></div>
            
            <?php if(pfm_have_subforums()): ?>         
                <div class="prime-subforums-list">
                    <?php _e('Подфорумы:'); ?> <?php pfm_subforums_list(); ?>
                </div>
            <?php endif; ?>
            
        </div>
        <?php pfm_the_forum_icons(); ?>
        <div class="prime-forum-topics">
            <span>Тем:</span><span><?php pfm_the_topic_count(); ?></span>
        </div>
        <div class="prime-last-items">
            <span>Последняя тема: <?php pfm_the_last_topic(); ?></span>
            <span>Последнее сообщение <?php pfm_the_last_post(); ?></span>
        </div>
    </div>
</div>