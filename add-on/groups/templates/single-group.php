<?php global $rcl_group,$rcl_group_widgets; ?>
<div class="group-sidebar">
    <div class="group-avatar">
        <?php rcl_group_thumbnail('medium'); ?>
    </div>
    <div class="sidebar-content">
        <?php rcl_group_content('sidebar'); ?>
    </div>
</div>
<div class="group-wrapper">
    <div class="group-content">
        <h1 class="group-name"><?php rcl_group_name(); ?></h1>

        <div class="group-description">
            <?php rcl_group_description(); ?>
        </div>
        <div class="group-meta">
            <p><b>Статус группы:</b> <?php rcl_group_status(); ?></p>
        </div>
        <div class="group-meta">
            <p><b>Пользователей в группе:</b> <?php rcl_group_count_users(); ?></p>
        </div>
        <?php rcl_group_content('content'); ?>
    </div>
</div>
<div class="group-footer">
    <?php rcl_group_content('footer'); ?>
</div>

