<?php
function confirm_user_registration(){
global $wpdb;
    $reglogin = $_GET['rglogin'];
    $regpass = $_GET['rgpass'];
    $regcode = md5($reglogin);
    if($regcode==$_GET['rgcode']){
        if ( $user = get_user_by('login', $reglogin) ){
            wp_update_user( array ('ID' => $user->ID, 'role' => get_option('default_role')) ) ;
            $time_action = date("Y-m-d H:i:s");
            $action = $wpdb->get_var("SELECT time_action FROM ".RCL_PREF."user_action WHERE user = '$user->ID'");
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
                    wp_redirect(get_authorize_url_rcl($user->ID) ); exit;
            }
        }			
    }else{
        wp_redirect( get_bloginfo('wpurl').'?getconfirm=needed' ); exit;
    }	
}
add_action('init', 'confirm_user_resistration_activate');
function confirm_user_resistration_activate(){
global $rcl_options;
  if (isset($_GET['rgcode'])&&isset($_GET['rglogin'])){
	if($rcl_options['confirm_register_recall']==1) add_action( 'wp', 'confirm_user_registration' ); 
  }
}

function get_register_user_rcl(){
	global $wpdb,$rcl_options;
	$pass = $_POST['pass-user'];	
	$email = $_POST['email-user'];	
	$login = $_POST['login-user'];
        
        //print_r($_POST);exit;
	
	$ref = apply_filters('url_after_register_rcl',$_POST['referer-rcl']);

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
                    }else{
                        if(!$_POST[$slug]) $requared = false;	
                    }
                }
            }
	}

	if(!$pass||!$email||!$login||!$requared){
		wp_redirect(get_redirect_url_rcl($ref).'action-rcl=register&error=empty');exit;
	}

	$res_email = email_exists( $email );
	$res_login = username_exists($login);
	$correctemail = is_email($email);
	$valid = validate_username($login);
	if($res_login||$res_email||!$correctemail||!$valid){
		if(!$valid){
			wp_redirect(get_redirect_url_rcl($ref).'action-rcl=register&error=login');exit;
		}
		if($res_login){
			wp_redirect(get_redirect_url_rcl($ref).'action-rcl=register&error=login-us');exit;
		}
		if($res_email){			
			wp_redirect(get_redirect_url_rcl($ref).'action-rcl=register&error=email-us');exit;
		}		
		if(!$correctemail){			
			wp_redirect(get_redirect_url_rcl($ref).'action-rcl=register&error=email');exit;
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
            
            $regcode = md5($login);	
            $subject = 'Подтвердите регистрацию!';														
            $textmail = '
            <p>Вы или кто то другой зарегистрировались на сайте "'.get_bloginfo('name').'" со следующими данными:</p>
            <p>Логин: '.$login.'</p>
            <p>Пароль: '.$pass.'</p>';
            
            $url = get_bloginfo('wpurl').'/?rglogin='.$login.'&rgpass='.$pass.'&rgcode='.$regcode;

            if($rcl_options['confirm_register_recall']==1){				
                    wp_update_user( array ('ID' => $user_id, 'role' => 'need-confirm') ) ;
                    $res['recall']='<p style="text-align:center;color:green;">Регистрация завершена!<br />Для подтверждения регистрации перейдите по ссылке в письме, высланном на указанную вами почту.</p>';
                    $textmail .= '<p>Если это были вы, то подтвердите свою регистрацию перейдя по ссылке ниже:</p>
                    <p><a href="'.$url.'">'.$url.'</a></p>
                    <p>Не получается активировать аккаунт?</p>
                    <p>Скопируйте текст ссылки ниже, вставьте его в адресную строку вашего браузера и нажмите Enter</p>';
            }else{
                    $res['recall']='<p style="text-align:center;color:green;">Регистрация завершена!<br />Авторизуйтесь на сайте, используя логин и пароль указанные при регистрации</p>';
                    $wpdb->insert( RCL_PREF.'user_action', array( 'user' => $user_id, 'time_action' => '' ));
            }

            $textmail .= '<p>Если это были не вы, то просто проигнорируйте это письмо</p>';				
            rcl_mail($email, $subject, $textmail);	

            wp_redirect(get_redirect_url_rcl($ref).'action-rcl=login&success=true');exit;
            
        }
}

add_action('user_register','add_register_user_data_rcl',10);
function add_register_user_data_rcl($user_id){

    update_user_meta($user_id, 'show_admin_bar_front', 'false');
			
    $cf = new Rcl_Custom_Fields();
    $cf->register_user_metas($user_id);
}

add_action('init', 'get_register_user_rcl_activate');
function get_register_user_rcl_activate ( ) {
  if ( isset( $_POST['submit-register'] ) ) {
	if( !wp_verify_nonce( $_POST['_wpnonce'], 'register-key-rcl' ) ) return false;	
    add_action( 'wp', 'get_register_user_rcl' );
  }
}

