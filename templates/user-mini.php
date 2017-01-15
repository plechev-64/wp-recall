<?php global $rcl_user,$rcl_users_set; ?>
<div class="user-single" data-user-id="<?php echo $rcl_user->ID; ?>">
    <div class="thumb-user">
        <a title="<?php rcl_user_name(); ?>" href="<?php rcl_user_url(); ?>">
            <?php rcl_user_avatar(50); ?>
            <?php rcl_user_action(); ?>
        </a>
    </div>
</div>