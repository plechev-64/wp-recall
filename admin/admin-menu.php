<?php

add_action('admin_menu', 'rcl_admin_menu',19);
function rcl_admin_menu(){
    
    $need_update = get_option('rcl_addons_need_update');
    
    $templates = array(); $addons = array();
    
    if($need_update){
        foreach($need_update as $addon_id=>$data){
            if(isset($data['template'])) $templates[] = $addon_id;
            else $addons[] = $addon_id;
        }
    }

    $cnt_t = $templates? count($templates): 0;
    $cnt_a = $addons? count($addons): 0;
    
    $notice_all = ($cnt_all=$cnt_a+$cnt_t)? ' <span class="update-plugins count-'.$cnt_all.'"><span class="plugin-count">'.$cnt_all.'</span></span>': '';
    $notice_t = ($cnt_t)? ' <span class="update-plugins count-'.$cnt_t.'"><span class="plugin-count">'.$cnt_t.'</span></span>': '';
    $notice_a = ($cnt_a)? ' <span class="update-plugins count-'.$cnt_a.'"><span class="plugin-count">'.$cnt_a.'</span></span>': '';
    
    add_menu_page(__('WP-RECALL','wp-recall').$notice_all, __('WP-RECALL','wp-recall').$notice_all, 'manage_options', 'manage-wprecall', 'rcl_dashboard');
    add_submenu_page( 'manage-wprecall', __('Dashboard','wp-recall'), __('Dashboard','wp-recall'), 'manage_options', 'manage-wprecall', 'rcl_dashboard');
    add_submenu_page( 'manage-wprecall', __('SETTINGS','wp-recall'), __('SETTINGS','wp-recall'), 'manage_options', 'rcl-options', 'rcl_global_options');
    add_submenu_page( 'manage-wprecall', __('Repository','wp-recall'), __('Repository','wp-recall'), 'manage_options', 'rcl-repository', 'rcl_repository_page');
    $hook = add_submenu_page( 'manage-wprecall', __('Add-ons','wp-recall').$notice_a, __('Add-ons','wp-recall').$notice_a, 'manage_options', 'manage-addon-recall', 'rcl_render_addons_manager');
    add_action( "load-$hook", 'rcl_add_options_addons_manager' );
    $hook = add_submenu_page( 'manage-wprecall', __('Templates','wp-recall').$notice_t, __('Templates','wp-recall').$notice_t, 'manage_options', 'manage-templates-recall', 'rcl_render_templates_manager');
    add_action( "load-$hook", 'rcl_add_options_templates_manager' );
    add_submenu_page( 'manage-wprecall', __('Tabs manager','wp-recall'), __('Tabs manager','wp-recall'), 'manage_options', 'rcl-tabs-manager', 'rcl_admin_tabs_manager');
}

function rcl_dashboard(){
    /** Load WordPress dashboard API */
    require_once(ABSPATH . 'wp-admin/includes/dashboard.php');
    
    wp_enqueue_script( 'dashboard' );
    
    do_action('rcl_add_dashboard_metabox',get_current_screen());
    
    require_once 'pages/dashboard.php';
}

//Настройки плагина в админке
function rcl_global_options(){
    require_once 'pages/options.php';
}

function rcl_repository_page(){
    require_once 'pages/repository.php';
}

function rcl_admin_tabs_manager(){
    require_once 'pages/tabs-manager.php';
}

function rcl_render_addons_manager(){
    require_once 'pages/addons-manager.php';
}

function rcl_render_templates_manager(){
    require_once 'pages/themes-manager.php';
}
