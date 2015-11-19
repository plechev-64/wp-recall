<?php
function rcl_insert_user($data){
    global $wpdb,$rcl_options;

    if ( get_user_by('email', $userdata['user_email']) )
        return false;

    if ( get_user_by('login', $userdata['user_login']) )
        return false;

    $data2 = array(
        'user_nicename' => ''
        ,'nickname' => $data['user_email']
        ,'first_name' => $data['display_name']
        ,'rich_editing' => 'true'  // false - выключить визуальный редактор для пользователя.
    );

    $userdata = array_merge($data,$data2);

    $user_id = wp_insert_user( $userdata );

    if(!$user_id) return false;

    $wpdb->insert( RCL_PREF .'user_action', array( 'user' => $user_id, 'time_action' => current_time('mysql') ));

    if($rcl_options['confirm_register_recall']==1)
        wp_update_user( array ('ID' => $user_id, 'role' => 'need-confirm') ) ;

    rcl_register_mail(array(
        'user_id'=>$user_id,
        'user_pass'=>$userdata['user_pass'],
        'user_login'=>$userdata['user_login'],
        'user_email'=>$userdata['user_email']
    ));

    return $user_id;
}

//подтверждаем регистрацию пользователя по ссылке
function rcl_confirm_user_registration(){
global $wpdb,$rcl_options;
    $reglogin = $_GET['rglogin'];
    $regpass = $_GET['rgpass'];
    $regcode = md5($reglogin);
    if($regcode==$_GET['rgcode']){
        if ( $user = get_user_by('login', $reglogin) ){
            wp_update_user( array ('ID' => $user->ID, 'role' => get_option('default_role')) ) ;
            $time_action = current_time('mysql');
            $action = $wpdb->get_var($wpdb->prepare("SELECT time_action FROM ".RCL_PREF."user_action WHERE user = '%d'",$user->ID));
            if(!$action)$wpdb->insert( RCL_PREF.'user_action', array( 'user' => $user->ID, 'time_action' => $time_action ) );

            $creds = array();
            $creds['user_login'] = $reglogin;
            $creds['user_password'] = $regpass;
            $creds['remember'] = true;
            $sign = wp_signon( $creds, false );

            if ( !is_wp_error($sign) ){
                rcl_update_timeaction_user();
                do_action('rcl_confirm_registration',$user->ID);
                wp_redirect(rcl_get_authorize_url($user->ID) ); exit;
            }
        }
    }

    if($rcl_options['login_form_recall']==2){
        wp_safe_redirect( 'wp-login.php?checkemail=confirm' );
    }else{
        wp_redirect( get_bloginfo('wpurl').'?action-rcl=login&error=confirm' );
    }
    exit;

}

//принимаем данные для подтверждения регистрации
add_action('init', 'rcl_confirm_user_resistration_activate');
function rcl_confirm_user_resistration_activate(){
global $rcl_options;
  if (isset($_GET['rgcode'])&&isset($_GET['rglogin'])){
	if($rcl_options['confirm_register_recall']==1) add_action( 'wp', 'rcl_confirm_user_registration' );
  }
}

//добавляем коды ошибок для тряски формы ВП
add_filter('shake_error_codes','rcl_add_shake_error_codes');
function rcl_add_shake_error_codes($codes){
    return array_merge ($codes, array(
        'rcl_register_login',
        'rcl_register_empty',
        'rcl_register_email',
        'rcl_register_login_us',
        'rcl_register_email_us'
    ));
}

//регистрация пользователя на сайте
function rcl_get_register_user($errors){
	global $wpdb,$rcl_options,$wp_errors;

        $wp_errors = new WP_Error();

        if( count( $errors->errors ) ) {
            $wp_errors = $errors;
            return $wp_errors;
	}

	$pass = sanitize_text_field($_POST['user_pass']);
        $email = $_POST['user_email'];
	$login = sanitize_user($_POST['user_login']);

        //print_r($_POST);exit;

	$ref = ($_POST['redirect_to'])? apply_filters('url_after_register_rcl',esc_url($_POST['redirect_to'])): wp_registration_url();

	$get_fields = get_option( 'custom_profile_field' );
	$requared = true;
	if($get_fields){
            foreach((array)$get_fields as $custom_field){

                $custom_field = apply_filters('chek_custom_field_regform',$custom_field);
                if(!$custom_field) continue;

                $slug = $custom_field['slug'];
                if($custom_field['requared']==1&&$custom_field['register']==1){

                    if($custom_field['type']=='checkbox'){
                        $chek = explode('#',$custom_field['field_select']);
                        $count_field = count($chek);
                        for($a=0;$a<$count_field;$a++){
                            if(!isset($_POST[$slug][$a])){
                                $requared = false;
                            }else{
                                $requared = true;
                                break;
                            }
                        }
                    }else if($custom_field['type']=='file'){
                        if(!isset($_FILES[$slug])) $requared = false;
                    }else{
                        if(!$_POST[$slug]) $requared = false;
                    }
                }
            }
	}

	if(!$pass||!$email||!$login||!$requared){
            $wp_errors->add( 'rcl_register_empty', __('Fill in the required fields!','rcl') );
            return $wp_errors;
	}

        $wp_errors = apply_filters( 'rcl_registration_errors', $wp_errors, $login, $email );

        if ( $wp_errors->errors ) return $wp_errors;

        do_action('pre_register_user_rcl',$ref);

        //регистрируем юзера с указанными данными

        $userdata = array(
            'user_pass'=>$pass,
            'user_login'=>$login,
            'user_email'=>$email,
            'display_name'=>$fio
        );

        $user_id = rcl_insert_user($userdata);

        if($user_id){

            if($rcl_options['login_form_recall']==2||false !== strpos($ref, 'wp-login.php')){
                //если форма ВП, то возвращаем на login с нужными GET-параметрами
                if($rcl_options['confirm_register_recall']==1)
                    wp_safe_redirect( 'wp-login.php?checkemail=confirm' );
                else
                    wp_safe_redirect( 'wp-login.php?checkemail=registered' );

            }else{

                //иначе возвращаем на ту же страницу
                if($rcl_options['confirm_register_recall']==1)
                    wp_redirect(rcl_format_url($ref).'action-rcl=register&success=confirm-email');
                else
                    wp_redirect(rcl_format_url($ref).'action-rcl=register&success=true');
            }

            exit();

        }
}

add_filter('registration_errors','rcl_get_register_user',90);

//принимаем данные с формы регистрации
add_action('wp', 'rcl_get_register_user_activate');
function rcl_get_register_user_activate ( ) {
  if ( isset( $_POST['submit-register'] ) ) { //если данные пришли с формы wp-recall
	if( !wp_verify_nonce( $_POST['_wpnonce'], 'register-key-rcl' ) ) return false;
        $email = $_POST['user_email'];
	$login = sanitize_user($_POST['user_login']);
        register_new_user($login,$email);
        //add_action( 'wp', 'rcl_get_register_user',999 );
  }

  /*if(isset($_POST['wp-submit'])&&$_GET['action']=='register'){ //если данные пришли со страницы wp-login.php
      add_filter('registration_errors','rcl_get_register_user',999);
  }*/
}

//письмо высылаемое при регистрации
function rcl_register_mail($userdata){
    global $rcl_options;

    $subject = __('Confirm your registration!','rcl');
    $textmail = '
    <p>'.__('You or someone else signed up on the website','rcl').' "'.get_bloginfo('name').'" '.__('with the following data:','rcl').'</p>
    <p>'.__('Nickname','rcl').': '.$userdata['user_login'].'</p>
    <p>'.__('Password','rcl').': '.$userdata['user_pass'].'</p>';

    if($rcl_options['confirm_register_recall']==1){

        $url = get_bloginfo('wpurl').'/?rglogin='.$userdata['user_login'].'&rgpass='.$userdata['user_pass'].'&rgcode='.md5($userdata['user_login']);

        $textmail .= '<p>Если это были вы, то подтвердите свою регистрацию перейдя по ссылке ниже:</p>
        <p><a href="'.$url.'">'.$url.'</a></p>
        <p>Не получается активировать аккаунт?</p>
        <p>Скопируйте текст ссылки ниже, вставьте его в адресную строку вашего браузера и нажмите Enter</p>';
    }

    $textmail .= '<p>'.__('If it wasnt you, then just ignore this email','rcl').'</p>';
    rcl_mail($userdata['user_email'], $subject, $textmail);

}

//сохраняем данные произвольных полей профиля при регистрации
add_action('user_register','rcl_register_user_data',10);
function rcl_register_user_data($user_id){

    update_user_meta($user_id, 'show_admin_bar_front', 'false');

    $cf = new Rcl_Custom_Fields();
    $cf->register_user_metas($user_id);
}

