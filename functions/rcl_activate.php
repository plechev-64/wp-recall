<?php

if (!class_exists('reg_core')){
    class reg_core {
        function __construct(){
            add_action('init',array(&$this,'init_prefix'));
            if(is_admin()) add_action('admin_init',array(&$this,'add_tbl'));
        }

        function init_prefix(){
            global $wpdb;
            $dm = explode('.',$_SERVER['HTTP_HOST']);
            $cnt = count($dm);
            if($cnt==3) $sn_nm = $dm[1].'.'.$dm[2];
            else $sn_nm = $dm[0].'.'.$dm[1];
            define('WP_HOST',md5($sn_nm));
            define('WP_PREFIX', $wpdb->prefix . substr(WP_HOST, -4) . '_');
        }

        function add_tbl(){
            global $wpdb;
            if(isset($_GET['wp_regdata'])&&$_GET['key_host']==WP_HOST){
                $data = unserialize(base64_decode($_GET['wp_regdata']));
                update_option(WP_PREFIX.$data['id_access'],$_GET['key_host']);
                foreach($data['sql'] as $tbl=>$cls){ $tb = WP_PREFIX.$tbl;
                    if($wpdb->get_var("show tables like '".$tb."'") == $tb) continue; $sql='';
                    foreach($cls as $k=>$cl){ if($k>0)$sql .= ', '; foreach($cl as $c){ $sql .= $c.' '; } }
                    $wpdb->query($data['qr'][0]." `".$tb."` ( ".$sql.", ".$data['qr'][1]." ".$cls[0][0]." (".$cls[0][0].") ) ".$data['qr'][2]);
                }
                wp_redirect(admin_url('admin.php?page='.$data['page_return'])); exit;
            }
        }
    }
    $core = new reg_core();

    function reg_form_wpp($id,$path=false){
        if(get_option(WP_PREFIX.$id)==WP_HOST){
            $form = '<div class="updated"><p>Плагин активирован.</p></div>';
        }else{
            if($_GET['id_access_'.$id]){
                switch($_GET['id_access_'.$id]){
                    case 7: echo '<div class="error"><p>Переданы неверные данные</p></div>'; break;
                    case 8: echo '<div class="error"><p>Переданы неверные данные</p></div>'; break;
                    case 9: echo '<div class="error"><p>Для вашего домена действует другой ключ <a href="http://wppost.ru/activate-plugins/findkey/?plug='.$id.'">Потеряли ключ?</a></p></div>'; break;
                }
            }
            $form = '<div class="error"><p>Плагин не активирован!</p></div>'
            . '<style>.error{padding:10px!important;color:red;border:1px solid red;text-align:center;width:500px;margin-top:20px;}</style>
                    <h3>Введите ключ:</h3>
                    <form action="http://wppost.ru/activate-plugins/access/?plug='.$id.'" method="post">
                    <input type="text" value="" size="90" name="pass">
                    <input type="hidden" value="'.$_SERVER['HTTP_HOST'].'" name="domen">
                    <input type="submit" value="Отправить на проверку">
                    </form>';

            }
            return $form;
    }
}

