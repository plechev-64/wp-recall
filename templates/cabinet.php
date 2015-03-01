<?php global $rcl_options,$user_LK; ?>

<?php rcl_before(); ?>

<div id="rcl-<?php echo $user_LK; ?>" class="wprecallblock">
    <?php rcl_notify(); ?>
     
    <div id="lk-conteyner">
        <div class="lk-header">
            <?php rcl_header(); ?>
        </div>
        <div class="lk-sidebar">
            <div class="lk-avatar">
                <?php rcl_avatar(120); ?>
            </div>
            <?php rcl_sidebar(); ?>
        </div>
        <div class="lk-content">
            <h2><?php rcl_username(); ?></h2>
            <div class="status">
                <?php rcl_action(); ?>
            </div>
            <?php rcl_status_desc(); ?>
            <?php rcl_content(); ?>
        </div>
        <div class="lk-footer">
            <?php rcl_footer(); ?>
        </div>
    </div>
        
    <?php $class = (isset($rcl_options['buttons_place'])&&$rcl_options['buttons_place']==1)? "left-buttons":""; ?>
    <div id="lk-menu" class="rcl-menu <?php echo $class; ?>">
        <?php rcl_buttons(); ?>
    </div>	
    <div id="lk-content" class="rcl-content">
        <?php rcl_tabs(); ?>
    </div>
</div>

<?php rcl_after(); ?>

