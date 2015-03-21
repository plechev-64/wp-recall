<?php
add_action('wp','get_init_filters_actions_rcl');
function get_init_filters_actions_rcl(){
    global $rcl_options,$user_ID;

    if (!is_admin()):
        add_action('wp_enqueue_scripts', 'output_style_scripts_recall');
        add_filter('get_comment_author_url', 'add_link_author_in_page');					
        add_action('wp_head','hidden_admin_panel');

        if(!$user_ID){
            if(!$rcl_options['login_form_recall']) add_filter('wp_footer', 'login_form_rcl',99);
            if($rcl_options['login_form_recall']==1) add_filter('wp_enqueue_scripts', 'script_page_form_recall');
            else if(!$rcl_options['login_form_recall']) add_filter('wp_enqueue_scripts', 'script_float_form_recall');
        }
    endif;
    
    add_action('wp_head','rcl_update_timeaction_user');       
    add_action('before_delete_post', 'delete_attachments_with_post_rcl');

}

add_filter('get_avatar','custom_avatar_recall', 1, 5);
if(is_admin()):
    add_action('save_post', 'recall_postmeta_update', 0);
    add_action('admin_head','output_script_style_admin_recall');
    add_action('admin_menu', 'wp_recall_options_panel',19);
endif;

function script_page_form_recall(){
    wp_enqueue_script( 'page_form_recall', RCL_URL.'js/page_form.js' );
}

function script_float_form_recall(){
    wp_enqueue_script( 'float_form_recall', RCL_URL.'js/float_form.js' );
}

function add_sortable_scripts(){
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('jquery-ui-sortable');
}

function add_resizable_scripts(){   
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('jquery-ui-resizable');
}

function add_datepicker_scripts(){
    wp_enqueue_style( 'datepicker', RCL_URL.'js/datepicker/style.css' );   
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script( 'custom-datepicker', RCL_URL.'js/datepicker/datepicker-init.js', array('jquery-ui-datepicker') );
}

function add_bxslider_scripts(){
    wp_enqueue_style( 'bx-slider', RCL_URL.'js/jquery.bxslider/jquery.bxslider.css' );   
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'bx-slider', RCL_URL.'js/jquery.bxslider/jquery.bxslider.min.js' );
    wp_enqueue_script( 'custom-bx-slider', RCL_URL.'js/slider.js', array('bx-slider'));
}
	
function output_style_scripts_recall(){
	global $rcl_options,$user_LK,$user_ID;
	if(!isset($rcl_options['font_icons']))  $rcl_options['font_icons']=1;
	wp_enqueue_style( 'fileapi_static', RCL_URL.'js/fileapi/statics/main.css' );
	if($user_ID==$user_LK) wp_enqueue_style( 'fileapi_jcrop', RCL_URL.'js/fileapi/jcrop/jquery.Jcrop.min.css' );
	//wp_enqueue_style( 'bx-slider-css', RCL_URL.'js/jquery.bxslider/jquery.bxslider.css' );
        
	//if($rcl_options['font_icons']==1){
        if( wp_style_is( 'font-awesome' ) ) wp_deregister_style('font-awesome');
        wp_enqueue_style( 'font-awesome', RCL_URL.'fonts/font-awesome.min.css', array(), '4.3.0' );
	//}
        
	if(isset($rcl_options['minify_css'])&&$rcl_options['minify_css']==1){
		if($rcl_options['custom_scc_file_recall']!=''){
			wp_enqueue_style( 'style_custom_rcl', $rcl_options['custom_scc_file_recall'] );
		}else{		
			wp_enqueue_style( 'style_recall', TEMP_URL.'css/minify.css' );
		}
	}else{	
            $css_ar = array('lk','recbar','regform','slider','users','style');
            foreach($css_ar as $name){wp_enqueue_style( 'style_'.$name, RCL_URL.'css/'.$name.'.css' );}		
	}
	if($rcl_options['color_theme']){
            $dirs   = array(RCL_PATH.'css/themes',TEMPLATEPATH.'/recall/themes');
            foreach($dirs as $dir){
                if(!file_exists($dir.'/'.$rcl_options['color_theme'].'.css')) continue;
                wp_enqueue_style( 'theme_rcl', path_to_url_rcl($dir.'/'.$rcl_options['color_theme'].'.css') );
            }
        }
	
	wp_enqueue_script( 'jquery' );
	//wp_enqueue_script( 'bx-slider', RCL_URL.'js/jquery.bxslider/jquery.bxslider.js' );
        
	if($user_ID) wp_enqueue_script( 'rangyinputs', RCL_URL.'js/rangyinputs.js' );
        
	wp_enqueue_script( 'recall_recall', RCL_URL.'js/recall.js', array(), VER_RCL );
	if(!file_exists(TEMP_PATH.'scripts/header-scripts.js')){
		$rcl_addons = new rcl_addons();
		$rcl_addons->get_update_scripts_file_rcl();
	}
	wp_enqueue_script( 'temp-scripts-recall', TEMP_URL.'scripts/header-scripts.js', array(), VER_RCL );	
}

function output_script_style_admin_recall(){
    wp_enqueue_style( 'admin_recall', RCL_URL.'css/admin.css' );
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'primary_script_admin_recall', RCL_URL.'js/admin.js', array(), VER_RCL );	
}

function fileapi_footer_scripts() {
    global $user_ID;
    if(!$user_ID) return false;
    if(file_exists(TEMP_PATH.'scripts/footer-scripts.js')){
        wp_enqueue_script( 'jquery' );	
        wp_enqueue_script( 'FileAPI-min', RCL_URL.'js/fileapi/FileAPI/FileAPI.min.js', array(), VER_RCL, true );
        wp_enqueue_script( 'mousewheel-js', RCL_URL.'js/fileapi/FileAPI/FileAPI.exif.js', array(), VER_RCL, true );
        wp_enqueue_script( 'fileapi-pack-js', RCL_URL.'js/fileapi/jquery.fileapi.js', array(), VER_RCL, true );
        wp_enqueue_script( 'Jcrop-buttons-js', RCL_URL.'js/fileapi/jcrop/jquery.Jcrop.min.js', array(), VER_RCL, true );
        wp_enqueue_script( 'modal-thumbs-js', RCL_URL.'js/fileapi/statics/jquery.modal.js', array(), VER_RCL, true );
        wp_enqueue_script( 'footer-js-recall', TEMP_URL.'scripts/footer-scripts.js', array(), VER_RCL, true );
    }
}

