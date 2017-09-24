<div class="prime-forum-item <?php pfm_the_topic_classes(); ?>">
    <?php pfm_the_topic_manager(); ?>
    <div class="prime-forum-icon">
        <i class="fa fa-circle" aria-hidden="true"></i>
    </div>
    <div class="prime-forum-title">
        <div class="prime-general-title"><a class="" title="<?php _e('Перейти в тему','wp-recall'); ?>" href="<?php pfm_the_topic_permalink(); ?>"><?php pfm_the_topic_name(); ?></a></div>
        <?php pfm_page_navi(array('type'=>'topic')); ?>
    </div>
    <?php pfm_the_forum_icons(); ?>
    <div class="prime-forum-topics">
        <span><?php _e('Сообщений','wp-recall'); ?>:</span><span><?php pfm_the_post_count(); ?></span>
    </div>
    <div class="prime-last-items">
        <span><?php _e('Последнее сообщение','wp-recall'); ?></span>
        <span><?php pfm_the_last_post(); ?></span>
    </div>
</div>
