<?php
/*
    Plugin Name: WP-Recall
    Plugin URI: http://wppost.ru/?p=69
    Description: Фронт-енд профиль, система личных сообщений и рейтинг пользователей на сайте вордпресс.
    Version: 13.5.5
    Author: Plechev Andrey
    Author URI: http://wppost.ru/
    GitHub Plugin URI: https://github.com/plechev-64/wp-recall
    License:     GPLv2 or later (license.txt)
*/

/*  Copyright 2012  Plechev Andrey  (email : support {at} wppost.ru)  */

global $wpdb;
$path_parts = pathinfo(__FILE__);

if(defined( 'MULTISITE' )){
    $upload_dir = array(
        'basedir' => WP_CONTENT_DIR.'/uploads',
        'baseurl' => WP_CONTENT_URL.'/uploads'
    );
}else{
    $upload_dir = wp_upload_dir();
}

if (is_ssl()) $upload_dir['baseurl'] = str_replace( 'http://', 'https://', $upload_dir['baseurl'] );

define('VER_RCL', '13.5.5');

define('RCL_URL', plugin_dir_url( __FILE__ ));
define('RCL_PREF', $wpdb->base_prefix.'rcl_');

define('RCL_PATH', $path_parts['dirname'].'/');

define('TEMP_PATH', $upload_dir['basedir'].'/temp-rcl/');
define('TEMP_URL', $upload_dir['baseurl'].'/temp-rcl/');

define('RCL_UPLOAD_PATH', $upload_dir['basedir'].'/rcl-uploads/');
define('RCL_UPLOAD_URL', $upload_dir['baseurl'].'/rcl-uploads/');

define('RCL_TAKEPATH', WP_CONTENT_DIR.'/wp-recall/');

add_action( 'plugins_loaded', 'rcl_load_plugin_textdomain' );
function rcl_load_plugin_textdomain() {
    load_theme_textdomain('rcl', RCL_PATH.'languages/');
}

function rcl_activate(){
    global $wpdb,$rcl_options;

    init_global_rcl();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    load_theme_textdomain('rcl', RCL_PATH.'languages/');

    $upload_dir = rcl_get_wp_upload_dir();

    if(!file_exists($upload_dir['basedir'])){
        mkdir($upload_dir['basedir']);
        chmod($upload_dir['basedir'], 0755);
    }

    if(!file_exists(RCL_TAKEPATH)){
        mkdir(RCL_TAKEPATH);
        chmod(RCL_TAKEPATH, 0755);

        $dirs = array('add-on','themes','templates');

        foreach($dirs as $dir){
            mkdir(RCL_TAKEPATH.$dir);
            chmod(RCL_TAKEPATH, 0755);
        }
    }

    $table4 = RCL_PREF."user_action";
    if($wpdb->get_var("show tables like '". $table4 . "'") != $table4) {
	   $wpdb->query("CREATE TABLE IF NOT EXISTS `". $table4 . "` (
		  ID bigint (20) NOT NULL AUTO_INCREMENT,
		  user INT(20) NOT NULL,
		  time_action DATETIME NOT NULL,
		  UNIQUE KEY id (id)
		) DEFAULT CHARSET=utf8;");
	}

	if(!isset($rcl_options['view_user_lk_rcl'])){

            if(!file_exists(RCL_UPLOAD_PATH)){
                mkdir(RCL_UPLOAD_PATH);
                chmod(RCL_UPLOAD_PATH, 0755);
            }

            $rcl_options = array(
                'view_user_lk_rcl' => 1,
                'color_theme' => 'blue',
                'lk_page_rcl' => wp_insert_post(array('post_title'=>__('Personal office','rcl'),'post_content'=>'[wp-recall]','post_status'=>'publish','post_author'=>1,'post_type'=>'page','post_name'=>'account'))
            );

            wp_insert_post(array('post_title'=>__('Users','rcl'),'post_content'=>'[userlist]','post_status'=>'publish','post_author'=>1,'post_type'=>'page','post_name'=>'users'));

            wp_insert_post(array('post_title'=>__('FEED','rcl'),'post_content'=>'[feed]','post_status'=>'publish','post_author'=>1,'post_type'=>'page','post_name'=>'user-feed'));

            $active_addons = get_site_option('active_addons_recall');
            $def_addons = array('rating-system','review','profile','feed','publicpost','message');
            foreach($def_addons as $addon){
                    $path = RCL_PATH.'add-on/'.$addon.'/index.php';
                    if ( false !== strpos($path, '\\') ) $path = str_replace('\\','/',$path);
                    $active_addons[$addon]['src'] = $path;
                    $install_src = RCL_PATH.'add-on/'.$addon.'/activate.php';
                    $index_src = RCL_PATH.'add-on/'.$addon.'/index.php';
                    if(file_exists($install_src)) include($install_src);
                    if(file_exists($index_src)) include($index_src);
            }
            update_site_option('active_addons_recall',$active_addons);

            $no_action_users = $wpdb->get_results("SELECT COUNT(us.ID) FROM ".$wpdb->prefix."users AS us WHERE us.ID NOT IN (SELECT ua.user FROM ".RCL_PREF."user_action AS ua)");

            if($no_action_users){
                            $wpdb->query("
                                    INSERT INTO ".RCL_PREF."user_action( user, time_action )
                                    SELECT us.ID, us.user_registered
                                    FROM ".$wpdb->prefix."users AS us
                                    WHERE us.ID NOT IN ( SELECT user FROM ".RCL_PREF."user_action )
                            ");
            }

            $wpdb->update(
                    $wpdb->prefix.'usermeta',
                    array('meta_value'=>'false'),
                    array('meta_key'=>'show_admin_bar_front')
            );

            $roledata = array(
                    'need-confirm' => array(
                            'name'=>__('Unconfirmed','rcl'),
                            'cap'=>array('read' => false, 'edit_posts' => false, 'delete_posts' => false, 'upload_files' => false)
                    ),
                    'banned' => array(
                            'name'=>__('Ban','rcl'),
                            'cap'=>array('read' => false, 'edit_posts' => false, 'delete_posts' => false, 'upload_files' => false)
                    )
            );

            foreach($roledata as $key=>$role){
                    remove_role($key);
                    add_role($key, $role['name'], $role['cap']);
            }

            update_option('default_role','author');

	}else{

            if(file_exists(TEMP_PATH)){
                rename(TEMP_PATH,RCL_UPLOAD_PATH);

                rcl_rename_media_dir();

            }else{
                if(!file_exists(RCL_UPLOAD_PATH)){
                    mkdir(RCL_UPLOAD_PATH);
                    chmod(RCL_UPLOAD_PATH, 0755);
                }
            }

            rcl_update_avatar_data();
            rcl_update_old_feeds();
            update_option('show_avatars',1);
	}

	$rcl_options['footer_url_recall']=1;
	update_option('primary-rcl-options',$rcl_options);

    //rcl_update_dinamic_files();
}
register_activation_hook(__FILE__,'rcl_activate');

function rcl_uninstall() {
    /*delete_option('custom_orders_field');
            delete_option('custom_profile_field');
            delete_option('custom_profile_search_form');
            delete_option('custom_public_fields_1');
            delete_option('custom_saleform_fields');
            delete_option('primary-rcl-options');
            delete_option('active_addons_recall');*/
    wp_clear_scheduled_hook('rcl_daily_addon_update');
    wp_clear_scheduled_hook('days_garbage_file_rcl');
}
register_uninstall_hook(__FILE__, 'rcl_uninstall');

//определяем глобальные переменные
function init_global_rcl(){
	global $wpdb;
	global $user_ID;
	global $rcl_current_action;
	global $rcl_user_URL;
	global $rcl_options;

	$rcl_options = get_option('primary-rcl-options');

	$upload_dir = rcl_get_wp_upload_dir();

        if(!file_exists(RCL_UPLOAD_PATH)&&file_exists(TEMP_PATH)){
            rename(TEMP_PATH,RCL_UPLOAD_PATH);
            rcl_rename_media_dir();
        }

        if(!is_dir($upload_dir['basedir'])){
            mkdir($upload_dir['basedir']);
            chmod($upload_dir['basedir'], 0755);
        }

	$rcl_user_URL = get_author_posts_url($user_ID);
	$rcl_current_action = $wpdb->get_var($wpdb->prepare("SELECT time_action FROM ".RCL_PREF."user_action WHERE user='%d'",$user_ID));
}
add_action('init','init_global_rcl',1);

function init_user_lk(){
    global $wpdb,$user_LK,$rcl_userlk_action,$rcl_options,$user_ID;

    $user_LK = false;
    $userLK = false;
    $get='user';

    if(isset($rcl_options['link_user_lk_rcl'])&&$rcl_options['link_user_lk_rcl']!='') $get = $rcl_options['link_user_lk_rcl'];
    if(isset($_GET[$get])) $userLK = $_GET[$get];

    if(!$userLK){
        if($rcl_options['view_user_lk_rcl']==1){
                $post_id = url_to_postid($_SERVER['REQUEST_URI']);
                if($rcl_options['lk_page_rcl']==$post_id) $user_LK = $user_ID;
        }else {
            if(isset($_GET['author'])) $user_LK = $_GET['author'];
            else{
                $url = (isset($_SERVER['SCRIPT_URL']))? $_SERVER['SCRIPT_URL']: $_SERVER['REQUEST_URI'];
                $url = preg_replace('/\?.*/', '', $url);
                $url_ar = explode('/',$url);
                foreach($url_ar as $key=>$u){
                    if($u!='author') continue;
                    $nicename = $url_ar[$key+1];
                    break;
                }
                if(!$nicename) return false;
                $user_LK = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->prefix."users WHERE user_nicename='%s'",$nicename));
            }
        }
    }else{
	$user_LK = $userLK;
    }

    if($user_LK){
        $rcl_userlk_action = $wpdb->get_var($wpdb->prepare("SELECT time_action FROM ".RCL_PREF."user_action WHERE user='%d'",$user_LK));
        rcl_fileapi_scripts();
    }
}
if(!is_admin()) add_action('init','init_user_lk',2);

function wp_recall(){
    rcl_include_template('cabinet.php');
}

function rcl_buttons(){
    global $user_LK; $content = '';
    echo apply_filters( 'the_button_wprecall', $content, $user_LK );
}

function rcl_tabs(){
    global $user_LK; $content = '';
    echo apply_filters( 'the_block_wprecall', $content, $user_LK);
}

function rcl_before(){
    global $user_LK; $content = '';
    echo apply_filters( 'rcl_before_lk', $content, $user_LK );
}

function rcl_after(){
    global $user_LK; $content = '';
    echo apply_filters( 'rcl_after_lk', $content, $user_LK );
}

function rcl_header(){
    global $user_LK; $content = '';
    echo apply_filters('rcl_header_lk',$content,$user_LK);
}

function rcl_sidebar(){
    global $user_LK; $content = '';
    echo apply_filters('rcl_sidebar_lk',$content,$user_LK);
}

function rcl_content(){
    global $user_LK; $content = '';
    $content = apply_filters('rcl_content_lk',$content,$user_LK);
    echo $content;
}

function rcl_footer(){
    global $user_LK; $content = '';
    echo apply_filters('rcl_footer_lk',$content,$user_LK);
}

function rcl_action(){
    global $rcl_userlk_action;
    $last_action = rcl_get_useraction($rcl_userlk_action);
    $class = (!$last_action)? 'online': 'offline';
    $status = '<div class="status_user '.$class.'"><i class="fa fa-circle"></i></div>';
    if($last_action) $status .= __('not online','rcl').' '.$last_action;
    echo $status;
}

function rcl_avatar($size=120){
    global $user_LK; $after='';
    echo '<div id="rcl-contayner-avatar">';
	echo '<span class="rcl-user-avatar">'.get_avatar($user_LK,$size).'</span>';
	echo apply_filters('after-avatar-rcl',$after,$user_LK);
	echo '</div>';

}

function rcl_status_desc(){
    global $user_LK;
    $desc = get_the_author_meta('description',$user_LK);
    if($desc) echo '<div class="ballun-status">'
        //. '<span class="ballun"></span>'
        . '<p class="status-user-rcl">'.nl2br(esc_textarea($desc)).'</p>'
        . '</div>';
}

function rcl_username(){
    global $user_LK;
    echo get_the_author_meta('display_name',$user_LK);
}

function rcl_notice(){
    $notify = '';
    $notify = apply_filters('notify_lk',$notify);
    if($notify) echo '<div class="notify-lk">'.$notify.'</div>';
}

require_once("rcl-functions.php");
