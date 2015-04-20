<?php global $user,$group_admin,$group; ?>
<div class="user-single">
    <div class="thumb-user">
        <a title="<?php the_user_name(); ?>" href="<?php the_user_url(); ?>">
            <?php the_user_avatar(70); ?>
        </a>
        <?php the_user_rayting(); ?>
    </div>

    <div class="user-content-rcl">
        <?php the_user_action(); ?>
        <h3 class="user-name">
            <a href="<?php the_user_url(); ?>"><?php the_user_name(); ?></a>
        </h3>

        <?php the_user_register(); ?>

        <?php the_user_comments(); ?>

        <?php the_user_posts(); ?>

        <?php the_user_description(); ?>

        <?php do_action('user_description',$user); ?>

    </div>

</div>