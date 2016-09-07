<div id="lk-conteyner">
    <?php do_action('rcl_area_top'); ?>
    <div class="cab_lt_line">
        <div class="cab_lt_title">
            <h2><?php rcl_username(); ?></h2>
            <div class="rcl-action"><?php rcl_action(); ?></div>
        </div>
        <div class="cab_bttn_lite">
            <?php do_action('rcl_area_counters'); ?>
        </div>
    </div>
</div>

<div class="cab_lt_sidebar">
    <div class="lk-sidebar">
        <div class="lk-avatar">
            <?php rcl_avatar(200); ?>
        </div>
        <div class="cab_bttn">
            <?php do_action('rcl_area_actions'); ?>
            <a class="cab_lt_menu recall-button" style="display: none;"><i class="fa fa-angle-double-right" aria-hidden="true"></i></a>
        </div>
    </div>
    
        <?php do_action('rcl_area_menu'); ?>
    
</div>

<div id="rcl-tabs">
    
        <?php do_action('rcl_area_tabs'); ?>
    
</div>