<?php global $user_ID,$post; ?>

<div id="feed-comment-<?php echo $post->comment_post_ID; ?>" class="feedcomment">

    <div class="feed-author-avatar">
            <a href="<?php echo get_author_posts_url($post->user_id); ?>"><?php echo get_avatar($post->user_id,50); ?></a>
    </div>

    <?php if($post->parent_comment): ?>
            <h3 class="feed-title">
                    <?php _e('комментарий к публикации','rcl'); ?>:
                    <a href="<?php echo get_comment_link( $post->comment_ID ); ?>"><?php echo $post->parent_comment; ?></a>
            </h3>
    <?php else: ?>
            <h4 class="recall-comment"><?php _e('in reply to your comment','rcl'); ?></h4>
    <?php endif; ?>

    <small><?php echo mysql2date('d.m.Y H:i', $post->comment_date); ?></small>

    <?php $comment_content = apply_filters('comment_text',$post->comment_content,$post); ?>

    <div class="feed-content"><?php echo $comment_content; ?></div>
    <?php if($post->user_id!=$user_ID): ?>
            <p align="right"><a target="_blank" href="<?php echo get_comment_link( $post->comment_ID ); ?>">Ответить</a></p>
    <?php endif; ?>

</div>