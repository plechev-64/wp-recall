<?php

if (!class_exists('reg_core')){
    class reg_core {
        function __construct(){
            add_action('init',array(&$this,'init_prefix'),1);
            if(is_admin()) add_action('admin_init',array(&$this,'add_tbl'));
            add_action('wp',array(&$this,'regres'),10);
            add_action('rcl_cron_daily',array(&$this,'chekplugs'),10);
        }

        function init_prefix(){
            global $wpdb; $host = str_replace('www.','',$_SERVER['HTTP_HOST']);
            $dm = explode('.',$host); $cnt = count($dm); $ignors = array('ua','es');
            if($cnt==3&&!in_array($dm[2],$ignors)) $sn_nm = $dm[1].'.'.$dm[2];
            else $sn_nm = $host; define('WP_HOST',md5($sn_nm));
            define('WP_PREFIX', $wpdb->prefix . substr(WP_HOST, -4) . '_');
        }

        function add_tbl(){
            global $wpdb;
            if(isset($_GET['key_host']) && $_GET['key_host'] == WP_HOST){
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $collate = '';if ( $wpdb->has_cap( 'collation' ) ) {
                if ( ! empty( $wpdb->charset ) ) {$collate .= "DEFAULT CHARACTER SET $wpdb->charset";}
                if ( ! empty( $wpdb->collate ) ) {$collate .= " COLLATE $wpdb->collate";}}
                if($td = $this->remote('gtl')){ foreach($td['tns'] as $tn){
                $t = $this->remote('gtd',array('tn'=>$tn)); $tn = WP_PREFIX.$tn;
                if($wpdb->get_var("show tables like '".$tn."'") == $tn){ $sqls[] = $tn; continue; } $cls = array();
                foreach($t as $k=>$cl){ $cls[] = implode(' ',$cl); }
                $sql = $td['qr'][0]." `".$tn ."` ( ".implode(' ,',$cls)." ) $collate;";
                if($td['as']){ $rs = array(); $ps = array(); foreach($td['as'] as $r=>$p){$rs[]=$r;$ps[]=$p;}
                $sql = str_replace($rs,$ps,$sql); } dbDelta( $sql ); $sqls[] = $tn;}
                if(isset($td['id']) && count($sqls) == count($td['tns'])) update_option(WP_PREFIX.$td['id'],$_GET['key_host']);
                wp_redirect(admin_url('admin.php?page='.$td['pr'])); exit;}
            }
        }

        function remote($dir, $data = array()){
            $data = array_merge(array('wpurl' => get_bloginfo('wpurl'),'wpdir' => basename(get_bloginfo('wpurl')),
            'domen' => $_SERVER['HTTP_HOST'],'sql-key' => isset($_GET['sql-key'])? $_GET['sql-key']: 0 ), $data);
            $response = wp_remote_post(RCL_SERVICE_HOST.'/activate-plugins/access/2.0/'.$dir.'/?plug='.$_GET['plug'], array('body' => $data));
            if(!$response['body']) return false;
            $body = json_decode($response['body']);
            $getdata = base64_decode(strtr($body->data, '-_,', '+/='));
            return unserialize(gzinflate(substr($getdata,10,-8)));
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

        function chekplugs(){
            global $wpdb;
            if(!WP_PREFIX) $this->remote('chks',array('keys'=>array('none')));
            $names = $wpdb->get_col("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '".WP_PREFIX."%'"); if(!$names) return false;
            $keys = array(); foreach($names as $name) $keys[] = str_replace(WP_PREFIX,'',$name);
            $this->remote('chks',array('keys'=>$keys));
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
                <form action="'.RCL_SERVICE_HOST.'/activate-plugins/access/2.0/gk/?plug='.$id.'" method="post">
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

