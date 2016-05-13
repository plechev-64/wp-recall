<?php
/*
    Plugin Name: WP-Recall
    Plugin URI: http://codeseller.ru/?p=69
    Description: Фронт-енд профиль, система личных сообщений и рейтинг пользователей на сайте вордпресс.
    Version: 14.2.10
    Author: Plechev Andrey
    Author URI: http://codeseller.ru/
    Text Domain: wp-recall
    Domain Path: /languages
    GitHub Plugin URI: https://github.com/plechev-64/wp-recall
    License: GPLv2 or later (license.txt)
*/

/*  Copyright 2012  Plechev Andrey  (email : support {at} codeseller.ru)  */

final class WP_Recall {

	public $version = '14.2.10';

	protected static $_instance = null;

	public $session = null; //На данный момент не используется, нужно будет все сессии сюда пихать

	public $query = null; //На данный момент не используется. В дальнейшем можно будет использовать для кастомных запросов

	public $customer = null; //Тут будет хранится вся информация о пользователях (авторезированых и не авторезированных)

	/*
	 * Основной экземпляр класса WP_Recall
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Читеришь, гадёныш?' ), $this->version );
	}

	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Читеришь, гадёныш?' ), $this->version );
	}

	/*
	 * Тут происходит магия
	 * Будем возвращать методы класса WP_Recall через переменные класса.
	 */
	public function __get( $key ) {

		/*
		 * Пока что только метод для отправки писем
		 */
		if ( in_array( $key, array( 'mailer' ) ) ) {
			return $this->$key();
		}
	}

	/*
	 * Конструктор нашего WP_Recall
	 */
	public function __construct() {

                add_action('plugins_loaded', array( $this, 'load_plugin_textdomain'),10);

		$this->define_constants(); //Определяем константы.
		$this->includes(); //Подключаем все нужные файлы с функциями и классами
		$this->init_hooks(); //Тут все наши хуки

		do_action( 'wprecall_loaded' ); //Оставляем кручёк
	}

	private function init_hooks() {

            register_activation_hook( __FILE__, array( 'RCL_Install', 'install' ) );

            add_action( 'init', array( $this, 'init' ), 0 );

            if(is_admin()){
                add_action('save_post', 'rcl_postmeta_update', 0);
                add_action('admin_head','rcl_admin_scrips');
                add_action('admin_menu', 'rcl_options_panel',19);
            }else{
                 add_action('wp_enqueue_scripts', 'rcl_frontend_scripts',100);
                 add_action('wp_head','rcl_update_timeaction_user');

            }
	}

	private function define_constants() {
		global $wpdb;

            $upload_dir = $this->upload_dir();

            $this->define('VER_RCL', $this->version );

            $this->define('RCL_URL', $this->plugin_url().'/' );
            $this->define('RCL_PREF', $wpdb->base_prefix . 'rcl_' );

            $this->define('RCL_PATH', trailingslashit( $this->plugin_path() ) );

            $this->define('RCL_UPLOAD_PATH', $upload_dir['basedir'] . '/rcl-uploads/' );
            $this->define('RCL_UPLOAD_URL', $upload_dir['baseurl'] . '/rcl-uploads/' );

            $this->define('RCL_TAKEPATH', WP_CONTENT_DIR . '/wp-recall/' );
            
            $this->define('RCL_SERVICE_HOST', 'http://downloads.codeseller.ru' );
	}

	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/*
	 * Узнаём тип запроса
	 */
	private function is_request( $type ) {
            switch ( $type ) {
                case 'admin' :
                        return is_admin();
                case 'ajax' :
                        return defined( 'DOING_AJAX' );
                case 'cron' :
                        return defined( 'DOING_CRON' );
                case 'frontend' :
                        return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
            }
	}

	public function includes() {
            /*
             * Здесь подключим те фалы которые нужны глобально для плагина
             * Остальные распихаем по соответсвующим функциям
             */

            include_once 'functions/rcl_activate.php';
            require_once("functions/minify-files/minify-css.php");
            require_once('functions/enqueue-scripts.php');
            require_once('functions/rcl-cron.php');
            include_once 'functions/class-rcl-cache.php';
            include_once 'functions/class-rcl-ajax.php';
            require_once('functions/rcl_custom_fields.php');
            require_once('functions/loginform.php');
            require_once('functions/rcl_currency.php');
            require_once('functions/navi-rcl.php');
            require_once("rcl-functions.php");
            require_once("functions/deprecated.php");
            require_once("functions/shortcodes.php");
            require_once("rcl-widgets.php");           

            $this->rcl_include_addons();

            include_once('class-rcl-install.php');

            if ( $this->is_request( 'admin' ) ) {
                    $this->admin_includes();
            }

            if ( $this->is_request( 'ajax' ) ) {
                    $this->ajax_includes();
            }

            if ( $this->is_request( 'frontend' ) ) {
                    $this->frontend_includes();
            }
	}

	/*
	 * Сюда складываем все файлы для админки
	 */
	public function admin_includes() {
            require_once("rcl-admin/admin-pages.php");
            require_once("rcl-admin/tabs_options.php");
            require_once("rcl-admin/rcl-admin.php");
            require_once("rcl-admin/add-on-manager.php");
	}

	/*
	 * Сюда складываем все файлы AJAX
	 */
	public function ajax_includes() {

	}

	/*
	 * Сюда складываем все файлы для фронт-энда
	 */
	public function frontend_includes() {

            require_once('functions/recallbar.php');
            require_once("functions/rcl-frontend.php");

	}

	public function init() {
            global $wpdb,$rcl_options,$user_ID,$rcl_current_action,$rcl_user_URL;

            do_action( 'wprecall_before_init' );

            $rcl_options = get_option('rcl_global_options');
            
            if(!$user_ID){
                //тут подключаем файлы необходимые для регистрации и авторизации
                require_once('functions/register.php');
                require_once('functions/authorize.php');
                if(class_exists('ReallySimpleCaptcha')){
                    require_once('functions/captcha.php');
                }
                if(!isset($rcl_options['login_form_recall'])||!$rcl_options['login_form_recall']){
                    add_filter('wp_footer', 'rcl_login_form',99);
                    add_filter('wp_enqueue_scripts', 'rcl_floatform_scripts');
                }else{
                    add_filter('wp_enqueue_scripts', 'rcl_pageform_scripts');
                }
                
            }
            
            if(!isset($rcl_options['view_user_lk_rcl'])){
                require_once('functions/migration.php');
                //14.0.0 переименование опций плагина
                rcl_rename_plugin_options();
                $rcl_options = get_option('rcl_global_options');
            }

            if ( $this->is_request( 'frontend' ) ) {
                $rcl_user_URL = get_author_posts_url($user_ID);
                $rcl_current_action = rcl_get_time_user_action($user_ID);

            }

            do_action( 'wprecall_init' );
	}

        function rcl_include_addons(){
            global $active_addons;

            require_once("functions/rcl_addons.php");
            
            if(is_admin()){
                global $rcl_error;
                $rcl_error = (isset($_GET['error-text']))? $_GET['error-text']: '';
                register_shutdown_function('rcl_register_shutdown');
            }

            $active_addons = get_site_option('rcl_active_addons');
            
            if($active_addons){
                $addons = array();
                foreach($active_addons as $addon=>$data){
                    if(!$addon) continue;
                    if(isset($data['priority']))
                        $addons[$data['priority']][$addon] = $data;
                    else 
                        $addons[0][$addon] = $data;
                }
                
                ksort($addons);
                $unset = false;
                foreach($addons as $priority=>$adds){
                    foreach($adds as $addon=>$data){
                        if(!$addon) continue;

                        $path = untrailingslashit( $data['path'] );
                        if(file_exists($path.'/index.php')){                            
                            rcl_include_addon($path.'/index.php',$addon);
                        }else{                            
                            unset($active_addons[$addon]);
                            $unset = true;
                        }
                    }
                }
                
                if($unset) update_site_option('rcl_active_addons',$active_addons);
                
            }
        }

	public function load_plugin_textdomain() {
                load_plugin_textdomain( 'wp-recall', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	public function mailer() {
		/*
		 * TODO: Сюда добавить подключение класса отправки сообщений
		 */
	}

    public function upload_dir() {

        if( defined( 'MULTISITE' ) ) {
            $upload_dir = array(
                'basedir' => WP_CONTENT_DIR.'/uploads',
                'baseurl' => WP_CONTENT_URL.'/uploads'
            );
        } else {
            $upload_dir = wp_upload_dir();
        }

        if ( is_ssl() )
            $upload_dir['baseurl'] = str_replace( 'http://', 'https://', $upload_dir['baseurl'] );

        return apply_filters( 'wp_recall_upload_dir', $upload_dir, $this );
    }
}

/*
 * Возвращает класс WP_Recall
 * @return WP_Recall
 */
function RCL() {
    return WP_Recall::instance();
}

/*
 * Теперь у нас есть глобальная переменная $wprecall
 * Которая содержит в себе основной класс WP_Recall
 */
$GLOBALS['wprecall'] = RCL();

function wp_recall(){
    rcl_include_template('cabinet.php');
}
