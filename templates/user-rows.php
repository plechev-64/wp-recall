<?php global $user; ?>
<div class="user-single">
    <div class="thumb-user">
        <a title="<?php rcl_user_name(); ?>" href="<?php rcl_user_url(); ?>">
            <?php rcl_user_avatar(70); ?>
        </a>
        <?php rcl_user_rayting(); ?>
    </div>

    <div class="user-content-rcl">
        <?php rcl_user_action(2); ?>
        <h3 class="user-name">
            <a href="<?php rcl_user_url(); ?>"><?php rcl_user_name(); ?></a>
        </h3>
        
        <?php rcl_user_description(); ?>
        
        <?php do_action('user_description'); ?>

    </div>

</div>