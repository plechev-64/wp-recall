<?php
require_once 'cover-uploader.php';

if (!is_admin()):
    add_action('rcl_enqueue_scripts','rcl_cab_15_scripts',10);
endif;

function rcl_cab_15_scripts(){   
    rcl_enqueue_style('cab_15',rcl_addon_url('style.css', __FILE__));
}

// инициализируем наши скрипты
add_action('rcl_enqueue_scripts', 'cab_15_script_load' );
function cab_15_script_load() {
    global $rcl_options, $user_LK,$user_ID;

    if($user_LK){
        rcl_enqueue_script('theme-header', rcl_addon_url( 'js/header-scripts.js', __FILE__ ), false, true);
        if($rcl_options['buttons_place']==0) {
            // мы в кабинете и выбраны горизонтальные кнопки ("Сверху")
            rcl_enqueue_script('cab_15_scrpt', rcl_addon_url( 'js/my-scr.js', __FILE__ ), false, true);
        }
        if($user_LK==$user_ID){
            rcl_crop_scripts();
            rcl_enqueue_script('cover-uploder', rcl_addon_url( 'js/cover-uploader.js', __FILE__ ), false, true);
        }
    }

}

add_filter('rcl_init_js_variables','rcl_init_js_office',10);
function rcl_init_js_office($data){
    global $user_LK,$user_ID;
    
    if(!$user_LK) return $data;
    
    if($user_LK==$user_ID){
        $data['theme']['cover_size'] = 2;
        $data['local']['upload_size_avatar'] = sprintf(__('Exceeds the maximum size for a picture! Max. %s MB','wp-recall'),2);
        $data['local']['title_image_upload'] = __('The image being loaded','wp-recall');
    }
    
    $data['local']['title_user_info'] = __('Detailed information','wp-recall');
    
    return $data;
}

// регистрируем 3 области виджетов
function cab_15_sidebar() {
    register_sidebar(array(
        'name' => "RCL: Виджет контента личного кабинета",
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
        'name' => "RCL: Виджет над личным кабинетом",
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
        'name' => "RCL: Виджет под личным кабинетом",
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
    global $user_LK;
    $cover_url = get_user_meta($user_LK,'rcl_cover',1);
    if(!$cover_url) $cover_url = rcl_addon_url('img/default-cover.jpg',__FILE__);
    $styles .= '#lk-conteyner{background-image: url('.$cover_url.');}';
    return $styles;
}

add_filter('after-avatar-rcl','rcl_add_user_info_button',10);
function rcl_add_user_info_button($content){
    rcl_dialog_scripts();

    $content .= '<a title="'.__('User info','wp-recall').'" onclick="rcl_get_user_info(this);return false;" class="cab_usr_info" href="#"><i class="fa fa-info-circle"></i></a>';

    return $content;
}

add_action('rcl_area_top','rcl_add_cover_uploader_button',10);
function rcl_add_cover_uploader_button(){
    global $user_ID,$user_LK;
    if($user_ID&&$user_ID==$user_LK){
        echo '<span class="cab_cover_upl">
            <span class="fa fa-camera" title="Загрузите обложку">
                <input type="file" id="rcl-cover-upload" accept="image/*" name="cover-file">
            </span>
        </span>';
    }
}

add_action('wp_ajax_rcl_get_user_details','rcl_get_user_details',10);
add_action('wp_ajax_nopriv_rcl_get_user_details','rcl_get_user_details',10);
function rcl_get_user_details(){
    global $user_LK, $rcl_blocks;
    $user_LK = $_POST['user_id'];
    
    if (!class_exists('Rcl_Blocks')) 
        include_once RCL_PATH.'functions/class-rcl-blocks.php';

    $content = '<div id="rcl-user-details">';
    
    $content .= '<div class="rcl-user-avatar">';
    
    $content .= get_avatar($user_LK,300);
    
    $avatar = get_user_meta($user_LK,'rcl_avatar',1);

    if($avatar){
        if(is_numeric($avatar)){
            $image_attributes = wp_get_attachment_image_src($avatar);
            $url_avatar = $image_attributes[0];
        }else{
            $url_avatar = $avatar;
        }
        $content .= '<a title="'.__('Zoom avatar','wp-recall').'" data-zoom="'.$url_avatar.'" onclick="rcl_zoom_avatar(this);return false;" class="rcl-avatar-zoom" href="#"><i class="fa fa-search-plus"></i></a>';
        
    }
    
    $content .= '</div>';
    
    $desc = get_the_author_meta('description',$user_LK);
    if($desc) 
        $content .= '<div class="ballun-status">'
        . '<p class="status-user-rcl">'.nl2br(esc_textarea($desc)).'</p>'
        . '</div>';
    
    if($rcl_blocks&&(isset($rcl_blocks['details'])||isset($rcl_blocks['content']))){
        
        $details = isset($rcl_blocks['details'])? $rcl_blocks['details']: array();
        $old_output = isset($rcl_blocks['content'])? $rcl_blocks['content']: array();

        $details = array_merge($details,$old_output);
        
        foreach($details as $block){
            $Rcl_Blocks = new Rcl_Blocks($block);
            $content .= $Rcl_Blocks->get_block($user_LK);
        }
    
    }
    
    $content .= '</div>';
    
    $result['content'] = $content;
    $result['success'] = 1;
    echo json_encode($result); exit;
}

