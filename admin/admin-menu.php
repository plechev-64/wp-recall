<?php

add_action('_admin_menu', 'rcl_init_update_notice', 10);
function rcl_init_update_notice(){
    global $rcl_update_notice;
    
    $rcl_update_notice = array();
    
    $need_update = get_option('rcl_addons_need_update');
    
    if(!$need_update) return false;
    
    foreach($need_update as $addon_id => $data){
        
        if(isset($data['template'])){
            $update_notice['templates'][] = $addon_id; continue;
        }
        
        if(isset($data['custom-manager']) && $data['custom-manager'] == 'prime-forum'){
            $update_notice['prime-forum'][] = $addon_id; continue;
        }
        
        $update_notice['addons'][] = $addon_id;
        
    }

    $rcl_update_notice = apply_filters('rcl_update_notice', $update_notice);

}

add_action('admin_menu', 'rcl_admin_menu',19);
function rcl_admin_menu(){
    global $rcl_update_notice;

    $cnt_t = isset($rcl_update_notice['templates'])? count($rcl_update_notice['templates']): 0;
    $cnt_a = isset($rcl_update_notice['addons'])? count($rcl_update_notice['addons']): 0;

    $notice_t = ($cnt_t)? ' <span class="update-plugins count-'.$cnt_t.'"><span class="plugin-count">'.$cnt_t.'</span></span>': '';
    $notice_a = ($cnt_a)? ' <span class="update-plugins count-'.$cnt_a.'"><span class="plugin-count">'.$cnt_a.'</span></span>': '';
    
    add_menu_page(__('WP-RECALL','wp-recall'), __('WP-RECALL','wp-recall'), 'manage_options', 'manage-wprecall', 'rcl_dashboard');
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
