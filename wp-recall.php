<?php
/*
    Plugin Name: WP-Recall
    Plugin URI: http://wppost.ru/?p=69
    Description: Фронт-енд профиль, система личных сообщений и рейтинг пользователей на сайте вордпресс.
    Version: 13.7.2
    Author: Plechev Andrey
    Author URI: http://wppost.ru/
    GitHub Plugin URI: https://github.com/plechev-64/wp-recall
    License:     GPLv2 or later (license.txt)
*/

/*  Copyright 2012  Plechev Andrey  (email : support {at} wppost.ru)  */

final class WP_Recall {

	public $version = '13.7.2';

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

		$this->define_constants(); //Определяем константы.
		$this->includes(); //Подключаем все нужные файлы с функциями и классами
		$this->init_hooks(); //Тут все наши хуки

		do_action( 'wprecall_loaded' ); //Оставляем кручёк
	}

	private function init_hooks() {
		register_activation_hook( __FILE__, array( 'RCL_Install', 'install' ) );

		add_action( 'init', array( $this, 'init' ), 0 );
	}

	private function define_constants() {
		global $wpdb;

            $upload_dir = $this->upload_dir();

            $this->define('VER_RCL', $this->version );

            $this->define('RCL_URL', $this->plugin_url().'/' );
            $this->define('RCL_PREF', $wpdb->base_prefix . 'rcl_' );

            $this->define('RCL_PATH', trailingslashit( $this->plugin_path() ) );

            $this->define('TEMP_PATH', $upload_dir['basedir'] . '/temp-rcl/');
            $this->define('TEMP_URL', $upload_dir['baseurl'] . '/temp-rcl/' );

            $this->define('RCL_UPLOAD_PATH', $upload_dir['basedir'] . '/rcl-uploads/' );
            $this->define('RCL_UPLOAD_URL', $upload_dir['baseurl'] . '/rcl-uploads/' );

            $this->define('RCL_TAKEPATH', WP_CONTENT_DIR . '/wp-recall/' );
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

	}

	public function init() {
            global $wpdb,$rcl_options,$user_ID,$rcl_current_action,$rcl_user_URL;

            do_action( 'wprecall_before_init' );

            $rcl_options = get_option('primary-rcl-options');

            $this->load_plugin_textdomain();

            if ( $this->is_request( 'frontend' ) ) {
                /*
                 * RCL_Session_Handler и RCL_Customer название классов которые нужно будет подключить в $this->frontend_includes()
                             * ap: пока закомментил
                 */
                /*$session_handler = apply_filters( 'wprecall_session_handler', 'RCL_Session_Handler' );
                $customer_handler = apply_filters( 'wprecall_customer_handler', 'RCL_Customer' );

                $this->session = new $session_handler();
                $this->customer = new $customer_handler();*/

                $rcl_user_URL = get_author_posts_url($user_ID);
                $rcl_current_action = $wpdb->get_var($wpdb->prepare("SELECT time_action FROM ".RCL_PREF."user_action WHERE user='%d'",$user_ID));

            }

            do_action( 'wprecall_init' );
	}

	public function load_plugin_textdomain() {

		$locale = apply_filters( 'plugin_locale', get_locale() );

		/*
		 * Тут файлы перевода для админки
		 */
		if ( $this->is_request( 'admin' ) ) {
			load_textdomain( 'rcl', RCL_PATH . 'languages/rcl-admin-' . $locale . '.mo' );
		}

		/*
		 * Тут для фронт-энда
		 */
		load_textdomain( 'rcl', RCL_PATH . 'languages/rcl-' . $locale . '.mo' );
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
 * $
 */
$GLOBALS['wprecall'] = RCL();

require_once("rcl-functions.php");

function wp_recall(){
    rcl_include_template('cabinet.php');
}
