<?php

/**
 * Created by PhpStorm.
 * Author: Maksim Martirosov
 * Date: 05.10.2015
 * Time: 19:45
 * Project: wp-recall
 */
class RCL_Install {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'init_global' ) );

        add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
    }


    public static function install() {
        global $rcl_options;

        if( ! defined( 'RCL_INSTALLING' ) ) {
            define( 'RCL_INSTALLING', true );
        }

        //FIXME: Разобратся с этими глобальными. Нужны ли они тут вообще, пока не понятно.
        self::init_global();

        self::create_tables();
        self::create_roles();

        if( ! isset( $rcl_options[ 'view_user_lk_rcl' ] ) ) {
            self::create_pages();
            self::add_addons();
        }

        self::any_functions();

        self::create_files();
    }

    public static function init_global() {
        global $wpdb, $user_ID, $rcl_current_action, $rcl_user_URL, $rcl_options;

        $upload_dir = rcl_get_wp_upload_dir();

        /*Убрать данную проверку позже*/
        if(!file_exists(RCL_UPLOAD_PATH)&&file_exists(TEMP_PATH)){
            rename(TEMP_PATH,RCL_UPLOAD_PATH);
            rcl_rename_media_dir();
        }

        wp_mkdir_p(($upload_dir['basedir']));
    }

    public static function create_tables() {
        global $wpdb;

        $wpdb->hide_errors();

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta( self::get_schema() );
    }

    private static function get_schema() {
        global $wpdb;

        $collate = '';

        $user_action_table = RCL_PREF . 'user_action';

        if ( $wpdb->has_cap( 'collation' ) ) {
            if ( ! empty( $wpdb->charset ) ) {
                $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if ( ! empty( $wpdb->collate ) ) {
                $collate .= " COLLATE $wpdb->collate";
            }
        }

        return "
        CREATE TABLE IF NOT EXISTS `". $user_action_table . "` (
		  ID bigint (20) NOT NULL AUTO_INCREMENT,
		  user INT(20) NOT NULL,
		  time_action DATETIME NOT NULL,
		  UNIQUE KEY id (id)
		  ) $collate
        ";
    }

    private static function create_pages() {
        global $rcl_options;
        $pages = apply_filters( 'wp_recall_pages', array(
            'lk_page_rcl' => array(
                'name'    => 'account',
                'title'   => __('Personal office','rcl'),
                'content' => '[wp-recall]'
            ),
            'feed_page_rcl' => array(
                'name'    => 'user-feed',
                'title'   => __('FEED','rcl'),
                'content' => '[feed]'
            ),
            'users_page_rcl' => array(
                'name'    => 'users',
                'title'   => __('Users','rcl'),
                'content' => '[userlist]'
            ),
        ) );

        foreach( $pages as $key => $page ) {
            if( is_array( $page ) ) {
                $page_id = wp_insert_post(
                    array(
                        'post_title'    => $page['title'],
                        'post_content'  => $page['content'],
                        'post_name'     => $page['name'],
                        'post_status'   => 'publish',
                        'post_author'   => 1,
                        'post_type'     => 'page'
                    )
                );

                $rcl_options[$key] = $page_id;
            }
        }
    }

    private static function add_addons() {

        $active_addons = get_site_option('active_addons_recall');
        $def_addons = apply_filters( 'default_wprecall_addons', array(
            'rating-system',
            'review',
            'profile',
            'feed',
            'publicpost',
            'message'
        ));

        foreach( $def_addons as $addon ) {

            $path = RCL()->plugin_path() . "/add-on/{$addon}/index.php";

            if ( false !== strpos($path, '\\') ) $path = str_replace('\\','/',$path);

            $active_addons[$addon]['src'] = $path;

            $install_src = RCL()->plugin_path() . "/add-on/{$addon}/activate.php";
            $index_src = RCL()->plugin_path() . "/add-on/{$addon}/index.php";

            /*
             * Поменяли file_exists на is_readable что загружать файлы не только если они существуют, но и доступны для чтения.
             */
            if( is_readable( $install_src ) ) include( $install_src );
            if( is_readable( $index_src) ) include($index_src);
        }

        update_site_option('active_addons_recall',$active_addons);
    }

    private static function create_files() {
        $upload_dir = RCL()->upload_dir();

        $files = array(
            /*array(
                'base'  => $upload_dir['basedir'],
                'file' 		=> '.htaccess',
                'content' 	=> 'deny from all'
            ),*/
            array(
                'base'  => $upload_dir['basedir'],
                'file' 		=> 'index.html',
                'content' 	=> ''
            ),
            array(
                'base'  => RCL_TAKEPATH,
                'file' 		=> '.htaccess',
                'content' 	=> 'deny from all'
            ),
            array(
                'base'  => RCL_TAKEPATH,
                'file' 		=> 'index.html',
                'content' 	=> ''
            ),
            array(
                'base'  => RCL_TAKEPATH . 'add-on',
                'file' 		=> '.htaccess',
                'content' 	=> 'deny from all'
            ),
            array(
                'base'  => RCL_TAKEPATH . 'add-on',
                'file' 		=> 'index.html',
                'content' 	=> ''
            ),
            array(
                'base'  => RCL_TAKEPATH . 'themes',
                'file' 		=> '.htaccess',
                'content' 	=> 'deny from all'
            ),
            array(
                'base'  => RCL_TAKEPATH . 'themes',
                'file' 		=> 'index.html',
                'content' 	=> ''
            ),
            array(
                'base'  => RCL_TAKEPATH . 'templates',
                'file' 		=> '.htaccess',
                'content' 	=> 'deny from all'
            ),
            array(
                'base'  => RCL_TAKEPATH . 'templates',
                'file' 		=> 'index.html',
                'content' 	=> ''
            ),
            /*array(
                'base'  => RCL_UPLOAD_PATH,
                'file' 		=> '.htaccess',
                'content' 	=> 'deny from all'
            ),*/
            array(
                'base'  => RCL_UPLOAD_PATH,
                'file' 		=> 'index.html',
                'content' 	=> ''
            )
        );

        foreach ( $files as $file ) {
            if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
                if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
                    fwrite( $file_handle, $file['content'] );
                    fclose( $file_handle );
                }
            }
        }
    }

    public static function create_roles() {

        if (!class_exists('WP_Roles')) {
            return;
        }

        add_role( 'need-confirm', __('Unconfirmed','rcl'), array(
            'read'          => false,
            'edit_posts'    => false,
            'delete_posts'  => false,
            'upload_files'  => false
            )
        );

        add_role( 'banned', __('Ban','rcl'), array(
                'read'          => false,
                'edit_posts'    => false,
                'delete_posts'  => false,
                'upload_files'  => false
            )
        );
    }

    public static function remove_roles() {
        if ( ! class_exists( 'WP_Roles' ) ) {
            return;
        }

        remove_role( 'need-confirm' );
        remove_role( 'banned' );
    }

    /**
     * Удаляем таблицы если удалён блог (для мультисайтов)
     * @param  array $tables
     * @return array
     */
    public static function wpmu_drop_tables( $tables ) {
        $tables[] = RCL_PREF . 'user_action';
        return $tables;
    }

    /*
     * Сюда решил сложить не понятные для меня функции при установки плагина
     * В дальнейшем нужно переопределить зависимости и переписать тут всё
     */
    private static function any_functions() {
        global $wpdb, $rcl_options;

        if(!isset($rcl_options['view_user_lk_rcl'])){

            //проверяем данные об активности пользователя на сайте, если данных нет, то устанавливаем последнюю активность юзера на момент регистрации
            $no_action_users = $wpdb->get_results("SELECT COUNT(us.ID) FROM ".$wpdb->prefix."users AS us WHERE us.ID NOT IN (SELECT ua.user FROM ".RCL_PREF."user_action AS ua)");
            if($no_action_users){
                $wpdb->query("
                                    INSERT INTO ".RCL_PREF."user_action( user, time_action )
                                    SELECT us.ID, us.user_registered
                                    FROM ".$wpdb->prefix."users AS us
                                    WHERE us.ID NOT IN ( SELECT user FROM ".RCL_PREF."user_action )
                            ");
            }

            //отключаем все пользователям сайта показ админ панели, если включена
            $wpdb->update(
                $wpdb->prefix.'usermeta',
                array('meta_value'=>'false'),
                array('meta_key'=>'show_admin_bar_front')
            );

            update_option('default_role','author');

        }else{

            //устанавливаем показ аватарок на сайте
            update_option('show_avatars', 1 );

            //устанавливаем показ ссылки на сайт автора плагина
            $rcl_options['footer_url_recall'] = 1;
            update_option('primary-rcl-options', $rcl_options );

            /*Ниже функции модифицикации данных плагина при обновлении плагина с более ранних версий*/

            //переименование temp-rcl на rcl-upload и данных юзеров использующих эту папку
            rcl_rename_media_dir();
            //изменение путей до загруженных в качестве аватарок изображений
            rcl_update_avatar_data();
            //обновление данных фида пользователей
            rcl_update_old_feeds();

        }

    }

}

RCL_Install::init();