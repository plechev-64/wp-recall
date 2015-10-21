<?php
//проверка подтверждения емейла юзера при авторизации
add_action('wp_authenticate','rcl_chek_user_authenticate');
function rcl_chek_user_authenticate($email){
    global $rcl_options;
    if($rcl_options['confirm_register_recall']==1){
        if ( $user = get_user_by('login', $email) ){
            $user_data = get_userdata( $user->ID );
            $roles = $user_data->roles;
            $role = array_shift($roles);
            if($role=='need-confirm'){
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

//авторизация пользователя
function rcl_get_login_user(){
        global $wp_errors;

	$pass = sanitize_text_field($_POST['user_pass']);
	$login = sanitize_user($_POST['user_login']);
	$member = intval($_POST['rememberme']);
	$url = esc_url($_POST['redirect_to']);

        $wp_errors = new WP_Error();

	if(!$pass||!$login){
            $wp_errors->add( 'rcl_login_empty', __('Fill in the required fields!','rcl') );
            return $wp_errors;
        }

        if ( $user = get_user_by('login', $login) ){
                $user_data = get_userdata( $user->ID );
                $roles = $user_data->roles;
                $role = array_shift($roles);
                if($role=='need-confirm'){
                    $wp_errors->add( 'rcl_login_confirm', __('Your email is not confirmed!','rcl') );
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

//получаем путь на возврат пользователя после авторизации
function rcl_get_authorize_url($user_id){
	global $rcl_options;
	if($rcl_options['authorize_page']){
		if($rcl_options['authorize_page']==1) $redirect = $_POST['redirect_to'];
		if($rcl_options['authorize_page']==2) $redirect = $rcl_options['custom_authorize_page'];
		if(!$redirect) $redirect = get_author_posts_url($user_id);
	}else{
		$redirect = get_author_posts_url($user_id);
	}
	return $redirect;
}

