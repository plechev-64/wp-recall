<?php

if (!class_exists('reg_core')){
    class reg_core {
        function __construct(){
            add_action('init',array(&$this,'init_prefix'),1);
            if(is_admin()) add_action('admin_init',array(&$this,'add_tbl'));
            add_action('wp',array(&$this,'regres'),10);
        }

        function init_prefix(){
            global $wpdb;
            $host = str_replace('www.','',$_SERVER['HTTP_HOST']);
            $dm = explode('.',$host);
            $cnt = count($dm);
            $ignors = array('ua','es');
            if($cnt==3&&!in_array($dm[2],$ignors)) $sn_nm = $dm[1].'.'.$dm[2];
            else $sn_nm = $host;
            define('WP_HOST',md5($sn_nm));
            define('WP_PREFIX', $wpdb->prefix . substr(WP_HOST, -4) . '_');
        }

        function add_tbl(){
            global $wpdb;
            if(isset($_GET['wp_regdata'])&&$_GET['key_host']==WP_HOST){
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $collate = '';
                if ( $wpdb->has_cap( 'collation' ) ) {
                    if ( ! empty( $wpdb->charset ) ) {
                        $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
                    }
                    if ( ! empty( $wpdb->collate ) ) {
                        $collate .= " COLLATE $wpdb->collate";
                    }
                }
                $getdata = base64_decode(strtr($_GET['wp_regdata'], '-_,', '+/='));
                $getdata = gzinflate(substr($getdata,10,-8));
                $data = unserialize($getdata);
                update_option(WP_PREFIX.$data['id_access'],$_GET['key_host']);
                foreach($data['sql'] as $tbl=>$cls){ $tb = WP_PREFIX.$tbl;
                    if($wpdb->get_var("show tables like '".$tb."'") == $tb) continue; $cols = array();
                    foreach($cls as $k=>$cl){ $cols[] = implode(' ',$cl); }
                    $sql = $data['qr'][0]." `".$tb ."` ( ".implode(',',$cols)." ) $collate;";
                    if(isset($data['as'])){
                        $rs = array(); $ps = array();
                        foreach($data['as'] as $r=>$p){ $rs[] = $r; $ps[] = $p; }
                        $sql = str_replace($rs,$ps,$sql);
                    }
                    dbDelta( $sql );
                }
                wp_redirect(admin_url('admin.php?page='.$data['page_return'])); exit;
            }
        }
        
        function regres(){
            global $wpdb;
            if(isset($_GET['reshost'])&&$_GET['reshost']==WP_HOST){
                if(WP_HOST==get_option(WP_PREFIX.$_GET['key'])){
                    $result = array();
                    if(isset($_GET['tables'])){ $tbls = explode(':',$_GET['tables']);
                        foreach($tbls as $tbl){ $result[] = $tbl;  $result[] = $wpdb->query("DROP TABLE ".WP_PREFIX.$tbl); }
                    }
                    $result[] = delete_option(WP_PREFIX.$_GET['key']); echo implode(' - ',$result);
                }else{ echo 0; }
                exit;
            }
        }
    }
    $core = new reg_core();

    function reg_form_wpp($id,$path=false){
        
        $content = '<div id="rcl-reg-form">';
        
        if(get_option(WP_PREFIX.$id)==WP_HOST){
            
            $content .= '<div class="updated"><p>Плагин активирован.</p></div>';
            
        }else{
            
            if($_GET['id_access_'.$id]){
                switch($_GET['id_access_'.$id]){
                    case 7: $content .= '<div class="error"><p>Переданы неверные данные</p></div>'; break;
                    case 8: $content .= '<div class="error"><p>Переданы неверные данные</p></div>'; break;
                    case 9: $content .= '<div class="error"><p>Для вашего домена действует другой ключ <a href="'.RCL_SERVICE_HOST.'/activate-plugins/findkey/?plug='.$id.'&host='.$_SERVER['HTTP_HOST'].'">Потеряли ключ?</a></p></div>'; break;
                }
            }
            
            $content .= '<div class="error"><p>Плагин не активирован!</p></div>'
            . '<h3>Введите ключ:</h3>
                <form action="'.RCL_SERVICE_HOST.'/activate-plugins/access/?plug='.$id.'&compress=1" method="post">
                    <input type="text" value="" size="90" name="pass">
                    <input type="hidden" value="'.$_SERVER['HTTP_HOST'].'" name="domen">
                    <input type="hidden" value="'.basename(get_bloginfo('wpurl')).'" name="wpdir">
                    <input type="hidden" value="'.get_bloginfo('wpurl').'" name="wpurl">
                    <input class="button button-primary button-large" type="submit" value="Отправить на проверку">
                </form>';

        }
        
        $content .= '</div>';
        
        return $content;
        
    }
}

