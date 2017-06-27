<?php

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_cab_15_scripts',10);
endif;

function rcl_cab_15_scripts(){   
    rcl_enqueue_style('cab_15',rcl_addon_url('style.css', __FILE__));
}

// инициализируем наши скрипты
add_action('rcl_enqueue_scripts', 'cab_15_script_load' );
function cab_15_script_load() {
    global $user_LK;
    if($user_LK){       
        rcl_enqueue_script('theme-scripts', rcl_addon_url( 'js/scripts.js', __FILE__ ),false,true);
    }
}

add_action('rcl_addons_included','rcl_setup_template_options',10);
function rcl_setup_template_options(){
    rcl_template_support('avatar-uploader');
    rcl_template_support('cover-uploader');
    rcl_template_support('modal-user-details');
}

// регистрируем 3 области виджетов
function cab_15_sidebar() {
    register_sidebar(array(
        'name' => "RCL: Сайдбар контента личного кабинета",
        'id' => 'cab_15_sidebar',
        'description' => 'Выводится только в личном кабинете. Справа от контента (сайдбар)',
        'before_title' => '<h3 class="cabinet_sidebar_title">',
        'after_title' => '</h3>',
        'before_widget' => '<div class="cabinet_sidebar">',
        'after_widget' => '</div>'
    ));
}
add_action('widgets_init', 'cab_15_sidebar');

function cab_15_sidebar_before() {
    register_sidebar(array(
        'name' => "RCL: Сайдбар над личным кабинетом",
        'id' => 'cab_15_sidebar_before',
        'description' => 'Выводится только в личном кабинете',
        'before_title' => '<h3 class="cab_title_before">',
        'after_title' => '</h3>',
        'before_widget' => '<div class="cabinet_sidebar_before">',
        'after_widget' => '</div>'
    ));
}
add_action('widgets_init', 'cab_15_sidebar_before');

function cab_15_sidebar_after() {
    register_sidebar(array(
        'name' => "RCL: Сайдбар под личным кабинетом",
        'id' => 'cab_15_sidebar_after',
        'description' => 'Выводится только в личном кабинете',
        'before_title' => '<h3 class="cab_title_after">',
        'after_title' => '</h3>',
        'before_widget' => '<div class="cabinet_sidebar_after">',
        'after_widget' => '</div>'
    ));
}
add_action('widgets_init', 'cab_15_sidebar_after');

add_action('rcl_area_before','rcl_add_sidebar_area_before');
function rcl_add_sidebar_area_before(){
    if (function_exists('dynamic_sidebar')){ dynamic_sidebar('cab_15_sidebar_before');}
}

add_action('rcl_area_after','rcl_add_sidebar_area_after');
function rcl_add_sidebar_area_after(){
    if (function_exists('dynamic_sidebar')){ dynamic_sidebar('cab_15_sidebar_after');}
}

// корректирующие стили
add_filter('rcl_inline_styles','rcl_add_cover_inline_styles',10);
function rcl_add_cover_inline_styles($styles){
    
    if(!rcl_is_office()) return $styles;
    
    global $user_LK;
    $cover_url = get_user_meta($user_LK,'rcl_cover',1);
    if(!$cover_url) $cover_url = rcl_addon_url('img/default-cover.jpg',__FILE__);
    $styles .= '#lk-conteyner{background-image: url('.$cover_url.');}';
    return $styles;
}

add_filter('rcl_inline_styles','rcl_add_colors_inline_styles',10);
function rcl_add_colors_inline_styles($styles){
    
    if(!rcl_is_office()) return $styles;
    
    $lca_hex = rcl_get_option('primary-color'); // достаем оттуда наш цвет
    list($r, $g, $b) = sscanf($lca_hex, "#%02x%02x%02x"); 

    $rp = round($r * 0.90);
    $gp = round($g * 0.90);
    $bp = round($b * 0.90);

    $styles .= '
    #lk-menu a:hover {
        background: rgba('.$rp.', '.$gp.', '.$bp.', 1);
    }
    #lk-menu a.active:hover {
        background: rgba('.$r.', '.$g.', '.$b.', 0.4);
    }';
	
    return $styles;
}

