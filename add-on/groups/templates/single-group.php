<?php global $rcl_group,$rcl_group_widgets; ?>
<?php if(rcl_is_group_area('sidebar')): ?>
    <div class="group-sidebar">
        <div class="group-avatar">
            <?php rcl_group_thumbnail('medium'); ?>
        </div>   
        <div class="sidebar-content">
            <?php rcl_group_area('sidebar'); ?>
        </div>
    </div>
<?php endif; ?>
<div class="group-wrapper">
    <div class="group-content">
        <?php if(!rcl_is_group_area('sidebar')): ?>
            <div class="group-avatar">
                <?php rcl_group_thumbnail('medium'); ?>
            </div>
        <?php endif; ?>
        <div class="group-metadata">
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
        </div>
        <?php if(rcl_is_group_area('content')) rcl_group_area('content'); ?>
    </div>
</div>
<?php if(rcl_is_group_area('sidebar')): ?>
    <div class="group-footer">
        <?php rcl_group_area('footer'); ?>
    </div>
<?php endif; ?>

