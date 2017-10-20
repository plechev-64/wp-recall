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

        RCL()->init();

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
        $upload_dir = rcl_get_wp_upload_dir();
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
            ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user BIGINT(20) UNSIGNED NOT NULL,
            time_action DATETIME NOT NULL,
            UNIQUE KEY id (id)
        ) $collate";
    }

    private static function create_pages() {
        global $rcl_options;

        $pages = apply_filters( 'wp_recall_pages', array(
            'lk_page_rcl' => array(
                'name'    => 'account',
                'title'   => __('Personal cabinet','wp-recall'),
                'content' => '[wp-recall]'
            ),
            'feed_page_rcl' => array(
                'name'    => 'user-feed',
                'title'   => __('FEED','wp-recall'),
                'content' => '[feed]'
            ),
            'users_page_rcl' => array(
                'name'    => 'users',
                'title'   => __('Users','wp-recall'),
                'content' => '[userlist inpage="30" orderby="time_action" template="rows" data="rating_total,comments_count,posts_count,description" filters="1" order="DESC"]'
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

        $def_addons = apply_filters( 'default_wprecall_addons', array(
            'rating-system',
            'review',
            'profile',
            'feed',
            'publicpost',
            'rcl-chat'
        ));
        
        foreach( $def_addons as $addon ) {
            rcl_activate_addon($addon);
        }
    }

    private static function create_files() {
        $upload_dir = RCL()->upload_dir();

        $files = array(
            array(
                'base'  => $upload_dir['basedir'],
                'file' 		=> 'index.html',
                'content' 	=> ''
            ),
            array(
                'base'  => RCL_TAKEPATH,
                'file' 		=> '.htaccess',
                'content' 	=> 'Options -indexes'
            ),
            array(
                'base'  => RCL_TAKEPATH,
                'file' 		=> 'index.html',
                'content' 	=> ''
            ),
            array(
                'base'  => RCL_TAKEPATH . 'add-on',
                'file' 		=> 'index.html',
                'content' 	=> ''
            ),
            array(
                'base'  => RCL_TAKEPATH . 'themes',
                'file' 		=> 'index.html',
                'content' 	=> ''
            ),
            array(
                'base'  => RCL_TAKEPATH . 'templates',
                'file' 		=> 'index.html',
                'content' 	=> ''
            ),
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

        add_role( 'need-confirm', __('Unconfirmed','wp-recall'), array(
            'read'          => false,
            'edit_posts'    => false,
            'delete_posts'  => false,
            'upload_files'  => false
            )
        );

        add_role( 'banned', __('Ban','wp-recall'), array(
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
        global $wpdb, $rcl_options,$active_addons;

        if(!isset($rcl_options['view_user_lk_rcl'])){

            $rcl_options['view_user_lk_rcl'] = 1;
            $rcl_options['view_recallbar'] = 1;
            
            //подключаем первый попавшийся шаблон ЛК
            $templates = rcl_search_templates();
            
            foreach($templates as $addon_id=>$template){
                update_option('rcl_active_template',$addon_id);
                break;
            }
           
            update_option('rcl_global_options',$rcl_options);

            //отключаем все пользователям сайта показ админ панели, если включена
            $wpdb->update(
                $wpdb->prefix.'usermeta',
                array('meta_value'=>'false'),
                array('meta_key'=>'show_admin_bar_front')
            );

            update_option('default_role','author');
            update_option('users_can_register',1);

        }else{

            //устанавливаем показ аватарок на сайте
            update_option('show_avatars', 1 );

            //производим повторную активацию всех активных дополнений плагина
            if($active_addons){
                foreach($active_addons as $addon=>$src_dir){
                    rcl_activate_addon($addon);
                }
            }          

        }

        update_option('rcl_global_options', $rcl_options );
        update_option('rcl_version',VER_RCL);

        rcl_remove_dir(RCL_UPLOAD_PATH.'js');
        rcl_remove_dir(RCL_UPLOAD_PATH.'css');

    }

}

RCL_Install::init();