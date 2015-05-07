<?php global $user; ?>
<div class="user-single">
    <div class="thumb-user">
        <a title="<?php rcl_user_name(); ?>" href="<?php rcl_user_url(); ?>">
            <?php rcl_user_avatar(70); ?>
            <?php rcl_user_action(); ?>
        </a>
        <?php rcl_user_rayting(); ?>
    </div>
</div>