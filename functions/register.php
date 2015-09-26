<?php
function rcl_confirm_user_registration(){
global $wpdb;
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

            if ( is_wp_error($sign) ){
                    wp_redirect( get_bloginfo('wpurl').'?getconfirm=needed' ); exit;
            }else{
                rcl_update_timeaction_user();

                do_action('rcl_confirm_registration',$user->ID);

                wp_redirect(rcl_get_authorize_url($user->ID) ); exit;
            }
        }
    }else{
        wp_redirect( get_bloginfo('wpurl').'?getconfirm=needed' ); exit;
    }
}
add_action('init', 'rcl_confirm_user_resistration_activate');
function rcl_confirm_user_resistration_activate(){
global $rcl_options;
  if (isset($_GET['rgcode'])&&isset($_GET['rglogin'])){
	if($rcl_options['confirm_register_recall']==1) add_action( 'wp', 'rcl_confirm_user_registration' );
  }
}

function rcl_get_register_user(){
	global $wpdb,$rcl_options;
	$pass = sanitize_text_field($_POST['pass-user']);
	$email = sanitize_email($_POST['email-user']);
	$login = sanitize_user($_POST['login-user']);

        //print_r($_POST);exit;

	$ref = apply_filters('url_after_register_rcl',esc_url($_POST['referer-rcl']));

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
		wp_redirect(rcl_format_url($ref).'action-rcl=register&error=empty');exit;
	}

	$res_email = email_exists( $email );
	$res_login = username_exists($login);
	$correctemail = is_email($email);
	$valid = validate_username($login);
	if($res_login||$res_email||!$correctemail||!$valid){
		if(!$valid){
			wp_redirect(rcl_format_url($ref).'action-rcl=register&error=login');exit;
		}
		if($res_login){
			wp_redirect(rcl_format_url($ref).'action-rcl=register&error=login-us');exit;
		}
		if($res_email){
			wp_redirect(rcl_format_url($ref).'action-rcl=register&error=email-us');exit;
		}
		if(!$correctemail){
			wp_redirect(rcl_format_url($ref).'action-rcl=register&error=email');exit;
		}

	}else{

            do_action('pre_register_user_rcl',$ref);

            $fio='';
            $userdata = array(
                'user_pass' => $pass
                ,'user_login' => $login
                ,'user_nicename' => ''
                ,'user_email' => $email
                ,'display_name' => $fio
                ,'nickname' => $login
                ,'first_name' => $fio
                ,'rich_editing' => 'true'
            );
            $user_id = wp_insert_user( $userdata );
	}

        if($user_id){

            $wpdb->insert( RCL_PREF .'user_action', array( 'user' => $user_id, 'time_action' => '' ));

            rcl_register_mail(array('user_id'=>$user_id,'password'=>$pass,'login'=>$login,'email'=>$email));

            if($rcl_options['confirm_register_recall']==1) wp_redirect(rcl_format_url($ref).'action-rcl=register&success=confirm-email');
            else wp_redirect(rcl_format_url($ref).'action-rcl=register&success=true');

            exit;

        }
}

function rcl_register_mail($userdata){
    global $rcl_options;

    $subject = __('Confirm your registration!','rcl');
    $textmail = '
    <p>'.__('You or someone else signed up on the website','rcl').' "'.get_bloginfo('name').'" '.__('with the following data:','rcl').'</p>
    <p>'.__('Nickname','rcl').': '.$userdata['login'].'</p>
    <p>'.__('Password','rcl').': '.$userdata['password'].'</p>';

    if($rcl_options['confirm_register_recall']==1){
            $url = get_bloginfo('wpurl').'/?rglogin='.$userdata['login'].'&rgpass='.$userdata['password'].'&rgcode='.md5($userdata['login']);
            wp_update_user( array ('ID' => $userdata['user_id'], 'role' => 'need-confirm') ) ;
            $res['recall']='<p style="text-align:center;color:green;">Регистрация завершена!<br />Для подтверждения регистрации перейдите по ссылке в письме, высланном на указанную вами почту.</p>';
            $textmail .= '<p>Если это были вы, то подтвердите свою регистрацию перейдя по ссылке ниже:</p>
            <p><a href="'.$url.'">'.$url.'</a></p>
            <p>Не получается активировать аккаунт?</p>
            <p>Скопируйте текст ссылки ниже, вставьте его в адресную строку вашего браузера и нажмите Enter</p>';
    }

    $textmail .= '<p>'.__('If it wasnt you, then just ignore this email','rcl').'</p>';
    rcl_mail($userdata['email'], $subject, $textmail);

}

add_action('user_register','rcl_register_user_data',10);
function rcl_register_user_data($user_id){

    update_user_meta($user_id, 'show_admin_bar_front', 'false');

    $cf = new Rcl_Custom_Fields();
    $cf->register_user_metas($user_id);
}

add_action('init', 'rcl_get_register_user_activate');
function rcl_get_register_user_activate ( ) {
  if ( isset( $_POST['submit-register'] ) ) {
	if( !wp_verify_nonce( $_POST['_wpnonce'], 'register-key-rcl' ) ) return false;
    add_action( 'wp', 'rcl_get_register_user' );
  }
}

