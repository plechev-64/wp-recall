<?php
/*
This script uses original idea Dasari Srinivas
http://blog.sodhanalibrary.com/2014/01/responsive-menu-or-navigation-bar-with.html
идея скрипта группировок кнопок принадлежит ему.
*/

// над и под кабинетом и внутри регистрируем 3 зоны виджетов dynamic_sidebar
// верю что пользователям пригодятся - учитывая что дизайн этого шаблона на всю ширину страницы
?>

    <div id="lk-conteyner">
        <div class="cab_header">
            <?php do_action('rcl_area_top'); ?>
        </div>
        <div class="cab_content">
            <div class="cab_center">
                <div class="lk-sidebar">
                    <?php rcl_avatar(200); ?>
                </div>
                <div class="cab_title">
                    <h2><?php rcl_username(); ?></h2>
                    <div class="rcl-action"><?php rcl_action(); ?></div>
                </div>
            </div>

            <div class="cab_footer">
                <div class="cab_bttn">
                    <?php do_action('rcl_area_actions'); ?>
                </div>
                <div class="cab_bttn_lite">
                    <?php do_action('rcl_area_counters'); ?>
                </div>
            </div>
        </div>
    </div>

    <div id="rcl-tabs">
        
            <?php do_action('rcl_area_menu'); ?>

        <?php if(is_active_sidebar('cab_15_sidebar')){ // если в сайтбаре(виджете) есть контент выводим и контент и сайтбар обернутыми в div cab_content_blk ?>
        <div class="cab_content_blk">
            
                <?php do_action('rcl_area_tabs'); ?>

            <div class="cab_sidebar">
                <?php if (function_exists('dynamic_sidebar')){ dynamic_sidebar('cab_15_sidebar');} ?>
            </div>
        </div>
        <?php } else { // если нет - выводим только контент и не оборачиваем в див ?>
            
                <?php do_action('rcl_area_tabs'); ?>

        <?php } ?>
    </div>

