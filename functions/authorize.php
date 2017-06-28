<?php
add_filter('wp_authenticate_user','rcl_chek_user_authenticate',10);
/**
 * проверяем подтверждение емейла, если такая настройка включена
 */
function rcl_chek_user_authenticate($user){

    if(isset($user->ID) && rcl_get_option('confirm_register_recall') == 1){
        
        if(rcl_is_user_role($user->ID, 'need-confirm')){

            $wp_errors = new WP_Error();
            $wp_errors->add( 'need-confirm', __('Your account is unconfirmed! Confirm your account by clicking on the link in the email','wp-recall') );
            return $wp_errors;

        }
        
    }
    
    return $user;
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

    $creds = array();
    $creds['user_login'] = $login;
    $creds['user_password'] = $pass;
    $creds['remember'] = $member;
    $userdata = wp_signon( $creds );
    
    if ( is_wp_error($userdata) ){
        $wp_errors = $userdata;
        return $wp_errors;
    }else{
        rcl_update_timeaction_user();
        wp_redirect(rcl_get_authorize_url($userdata->ID));exit;
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
    
    $redirect = false;

    if($autPage = rcl_get_option('authorize_page')){
        
        if($autPage==1) 
            $redirect = $_POST['redirect_to'];
        else if($autPage==2) 
            $redirect = rcl_get_option('custom_authorize_page');

    }
    
    if(!$redirect) 
        $redirect = get_author_posts_url($user_id);
    
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

