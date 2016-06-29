<?php global $rcl_options,$user_LK; ?>

<?php do_action('rcl_area_before'); ?>

<div id="rcl-<?php echo $user_LK; ?>" class="wprecallblock" data-account="<?php echo $user_LK; ?>">
    <?php rcl_notice(); ?>

    <div id="lk-conteyner">
        <div class="lk-header rcl-node">
            <?php do_action('rcl_area_actions'); ?>
        </div>
        <div class="lk-sidebar">
            <div class="lk-avatar">
                <?php rcl_avatar(120); ?>
            </div>
            <div class="rcl-node">
                <?php do_action('rcl_area_counters'); ?>
            </div>
        </div>
        <div class="lk-content">
            <h2><?php rcl_username(); ?></h2>
            <div class="rcl-action">
                <?php rcl_action(); ?>
            </div>
            <div class="rcl-user-status">
                <?php rcl_status_desc(); ?>
            </div>
            <div class="rcl-content">
                <?php do_action('rcl_area_details'); ?>
            </div>
            <div class="lk-footer rcl-node">
                <?php do_action('rcl_area_extra'); ?>
            </div>
        </div>

    </div>

    <?php $class = (isset($rcl_options['buttons_place'])&&$rcl_options['buttons_place']==1)? "left-buttons":""; ?>
    <div id="rcl-tabs">
        <div id="lk-menu" class="rcl-menu <?php echo $class; ?> rcl-node">
            <?php do_action('rcl_area_menu'); ?>
        </div>
        <div id="lk-content" class="rcl-content">
            <?php do_action('rcl_area_tabs'); ?>
        </div>
    </div>
</div>

<?php do_action('rcl_area_after'); ?>

