<?php
add_action('wp_authenticate','rcl_chek_user_authenticate');
function rcl_chek_user_authenticate($email){
    global $rcl_options;
    if($rcl_options['confirm_register_recall']==1){
        if ( $user = get_user_by('login', $email) ){
            $user_data = get_userdata( $user->ID );
            $roles = $user_data->roles;
            $role = array_shift($roles);
            if($role=='need-confirm'){
                wp_redirect( get_bloginfo('wpurl').'?getconfirm=needed' ); exit;
            }
        }
    }
}
function rcl_get_login_user(){
	$pass = sanitize_text_field($_POST['pass-user']);
	$login = sanitize_user($_POST['login-user']);
	$member = intval($_POST['member-user']);
	$url = esc_url($_POST['referer_rcl']);

	if($pass&&$login){

		if ( $user = get_user_by('login', $login) ){
			$user_data = get_userdata( $user->ID );
			$roles = $user_data->roles;
			$role = array_shift($roles);
			if($role=='need-confirm'){
				wp_redirect(rcl_format_url($url).'action-rcl=login&error=confirm');exit;
			}
		}

                $creds = array();
                $creds['user_login'] = $login;
                $creds['user_password'] = $pass;
                $creds['remember'] = $member;
                $user = wp_signon( $creds, false );
                if ( is_wp_error($user) ){
                        wp_redirect(rcl_format_url($url).'action-rcl=login&error=failed');exit;
                }else{
                        rcl_update_timeaction_user();
                        wp_redirect(rcl_get_authorize_url($user->ID));exit;
                }
	}else{
		wp_redirect(rcl_format_url($url).'action-rcl=login&error=empty');exit;
	}
}
add_action('init', 'rcl_get_login_user_activate');
function rcl_get_login_user_activate ( ) {
  if ( isset( $_POST['submit-login'] ) ) {
	if( !wp_verify_nonce( $_POST['_wpnonce'], 'login-key-rcl' ) ) return false;
    add_action( 'wp', 'rcl_get_login_user' );
  }
}
function rcl_get_authorize_url($user_id){
	global $rcl_options;
	if($rcl_options['authorize_page']){
		if($rcl_options['authorize_page']==1) $redirect = $_POST['referer_rcl'];
		if($rcl_options['authorize_page']==2) $redirect = $rcl_options['custom_authorize_page'];
		if(!$redirect) $redirect = get_author_posts_url($user_id);
	}else{
		$redirect = get_author_posts_url($user_id);
	}
	return $redirect;
}

