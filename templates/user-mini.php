<?php global $user; ?>
<div class="user-single">
    <div class="thumb-user">
        <a title="<?php the_user_name(); ?>" href="<?php the_user_url(); ?>">
            <?php the_user_avatar(50); ?>
            <?php the_user_action(); ?>
        </a>
    </div>
</div>