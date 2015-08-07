<?php global $user_ID,$comment; ?>

<div id="feed-comment-<?php echo $comment->comment_post_ID; ?>" class="feedcomment">

    <div class="feed-author-avatar">
            <a href="<?php echo get_author_posts_url($comment->user_id); ?>"><?php echo get_avatar($comment->user_id,50); ?></a>
    </div>

    <?php if($comment->parent_comment): ?>
            <h3 class="feed-title">
                    <?php _e('comment for publication','rcl'); ?>:
                    <a href="<?php echo get_comment_link( $comment->comment_ID ); ?>"><?php echo $comment->parent_comment; ?></a>
            </h3>
    <?php else: ?>
            <h4 class="recall-comment"><?php _e('in reply to your comment','rcl'); ?></h4>
    <?php endif; ?>

    <small><?php echo mysql2date('d.m.Y H:i', $comment->comment_date); ?></small>

    <?php $comment_content = apply_filters('comment_text',$comment->comment_content,$comment); ?>

    <div class="feed-content"><?php echo $comment_content; ?></div>
    <?php if($comment->user_id!=$user_ID): ?>
            <p align="right"><a target="_blank" href="<?php echo get_comment_link( $comment->comment_ID ); ?>">Ответить</a></p>
    <?php endif; ?>

</div>