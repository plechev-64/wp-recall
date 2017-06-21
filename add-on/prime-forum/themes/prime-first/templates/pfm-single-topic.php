<div class="prime-forum-item <?php pfm_the_topic_classes(); ?>">
    <div class="prime-forum-icon">
        <i class="fa fa-circle" aria-hidden="true"></i>
    </div>
    <div class="prime-forum-title">
        <div class="prime-general-title"><a class="" title="Перейти в топик" href="<?php pfm_the_topic_permalink(); ?>"><?php pfm_the_topic_name(); ?></a></div>
    </div>
    <?php pfm_the_forum_icons(); ?>
    <div class="prime-forum-topics">
        <span>Сообщений:</span><span><?php pfm_the_post_count(); ?></span>
    </div>
    <div class="prime-forum-activity">
        <span class="prime-forum-last-message">Последнее сообщение:</span><span class="prime-forum-time-ago"><?php pfm_time_diff_last_post(); ?> назад</span>
    </div>
</div>
