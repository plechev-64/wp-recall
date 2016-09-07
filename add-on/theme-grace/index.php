<?php

// подключаем стили
if (!is_admin()):
    add_action('rcl_enqueue_scripts','lt_style',10);
endif;

function lt_style(){
    rcl_enqueue_style('grace-theme',rcl_addon_url('style.css', __FILE__));
}

// инициализируем наш скрипт
// скрипт начинает работу от 568 пикселей - высоту изображения он делает по высоте блока lk-sidebar
// при ресайзе (как ресайз так и поворот экрана) он просчитывает высоту и при уходе более 568 пикселей сбрасывает высоту ковера
add_action('rcl_enqueue_scripts', 'lt_script' );
function lt_script() {
    global $user_LK;
    if($user_LK){       
        rcl_enqueue_script('grace-script', rcl_addon_url( 'js/scripts.js', __FILE__ ), false, true);
    }
}

// объявляем поддержку загрузки аватарки, загрузку обложки, модальное окно "Подробная информация"
add_action('rcl_addons_included','lt_setup_template_options',10);
function lt_setup_template_options(){
    rcl_template_support('avatar-uploader');
    rcl_template_support('cover-uploader');
    rcl_template_support('modal-user-details');
}

// регистрируем 2 области виджетов и выводим их
function lt_sidebar_before() {
    register_sidebar(array(
        'name' => "RCL: Сайдбар над личным кабинетом",
        'id' => 'lt_sidebar_before',
        'description' => 'Выводится только в личном кабинете',
        'before_title' => '<h3 class="cab_title_before">',
        'after_title' => '</h3>',
        'before_widget' => '<div class="cabinet_sidebar_before">',
        'after_widget' => '</div>'
    ));
}
add_action('widgets_init', 'lt_sidebar_before');

add_action('rcl_area_before','lt_add_sidebar_area_before');
function lt_add_sidebar_area_before(){
    if (function_exists('dynamic_sidebar')){ dynamic_sidebar('lt_sidebar_before');}
}

function lt_sidebar_after() {
    register_sidebar(array(
        'name' => "RCL: Сайдбар под личным кабинетом",
        'id' => 'lt_sidebar_after',
        'description' => 'Выводится только в личном кабинете',
        'before_title' => '<h3 class="cab_title_after">',
        'after_title' => '</h3>',
        'before_widget' => '<div class="cabinet_sidebar_after">',
        'after_widget' => '</div>'
    ));
}
add_action('widgets_init', 'lt_sidebar_after');

add_action('rcl_area_after','lt_add_sidebar_area_after');
function lt_add_sidebar_area_after(){
    if (function_exists('dynamic_sidebar')){ dynamic_sidebar('lt_sidebar_after');}
}

// выводим обложку
add_filter('rcl_inline_styles','rcl_add_cover_inline_styles',10);
function rcl_add_cover_inline_styles($styles){
    
    if(!rcl_is_office()) return $styles;
    
    global $user_LK;
    $cover_url = get_user_meta($user_LK,'rcl_cover',1);
    if(!$cover_url) $cover_url = rcl_addon_url('img/default-cover.jpg',__FILE__);
    $styles .= '#lk-conteyner{background-image: url('.$cover_url.');}';
    return $styles;
}