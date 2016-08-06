<?php
add_action('wp_authenticate','rcl_chek_user_authenticate');
/**
 * проверяем подтверждение емейла, если такая настройка включена
 * 
 * @param string $email емейл юзера на проверку
 */
function rcl_chek_user_authenticate($email){
    global $rcl_options;
    $confirm = (isset($rcl_options['confirm_register_recall']))? $rcl_options['confirm_register_recall']: 0;
    if($confirm==1){
        if ( $user = get_user_by('login', $email) ){
            if(rcl_is_user_role($user->ID, 'need-confirm')){
                if($rcl_options['login_form_recall']==2){
                    wp_safe_redirect( 'wp-login.php?checkemail=confirm' );
                }else{
                    wp_redirect( get_bloginfo('wpurl').'?action-rcl=login&error=confirm' );
                }
                exit;
            }
        }
    }
}

/**
 * авторизация пользователя
 */
function rcl_get_login_user(){
    global $wp_errors;

    $pass = sanitize_text_field($_POST['user_pass']);
    $login = sanitize_user($_POST['user_login']);
    $member = (isset($_POST['rememberme']))? intval($_POST['rememberme']): 0;
    $url = esc_url($_POST['redirect_to']);

    $wp_errors = new WP_Error();

    if(!$pass||!$login){
        $wp_errors->add( 'rcl_login_empty', __('Fill in the required fields!','wp-recall') );
        return $wp_errors;
    }

    if ( $user = get_user_by('login', $login) ){
        if(rcl_is_user_role($user->ID, 'need-confirm')){
            $wp_errors->add( 'rcl_login_confirm', __('Your email is not confirmed!','wp-recall') );
            return $wp_errors;
        }
    }

    $creds = array();
    $creds['user_login'] = $login;
    $creds['user_password'] = $pass;
    $creds['remember'] = $member;
    $user = wp_signon( $creds, false );
    if ( is_wp_error($user) ){
        $wp_errors = $user;
        return $wp_errors;
    }else{
        rcl_update_timeaction_user();
        wp_redirect(rcl_get_authorize_url($user->ID));exit;
    }

}

//принимаем данные для авторизации пользователя с формы wp-recall
add_action('init', 'rcl_get_login_user_activate');
function rcl_get_login_user_activate ( ) {
    if ( isset( $_POST['submit-login'] ) ) {
        if( !wp_verify_nonce( $_POST['_wpnonce'], 'login-key-rcl' ) ) return false;
        add_action( 'wp', 'rcl_get_login_user' );
    }
}

/**
 * получаем путь на возврат пользователя после авторизации
 * 
 * @param int $user_id идентификатор пользователя
 */
function rcl_get_authorize_url($user_id){
    global $rcl_options;
    if(isset($rcl_options['authorize_page'])&&$rcl_options['authorize_page']){
        if($rcl_options['authorize_page']==1) $redirect = $_POST['redirect_to'];
        if($rcl_options['authorize_page']==2) $redirect = $rcl_options['custom_authorize_page'];
        if(!$redirect) $redirect = get_author_posts_url($user_id);
    }else{
        $redirect = get_author_posts_url($user_id);
    }
    return $redirect;
}

if(function_exists('limit_login_add_error_message'))
add_action('rcl_login_form_head', 'rcl_limit_login_add_error_message');

function rcl_limit_login_add_error_message() {
    global $wp_errors, $limit_login_my_error_shown;

    if (!should_limit_login_show_msg() || $limit_login_my_error_shown) {
            return;
    }

    $msg = limit_login_get_message();

    if ($msg != '') {
        $limit_login_my_error_shown = true;
        $wp_errors->errors['rcl_limit_login'][] = $msg;
    }

    return;
}

