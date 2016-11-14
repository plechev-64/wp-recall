<?php
if(!function_exists('wp_send_new_user_notifications')){
    function wp_send_new_user_notifications( $user_id, $notify = 'both' ) {
        wp_new_user_notification( $user_id, null, $notify );
    }
}

function rcl_insert_user($data){
    global $wpdb,$rcl_options;

    if ( get_user_by('email', $data['user_email']) )
        return false;

    if ( get_user_by('login', $data['user_login']) )
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

    wp_send_new_user_notifications( $user_id, 'admin' );

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

            if(!rcl_is_user_role($user->ID, 'need-confirm')) return false;
            
            wp_update_user( array ('ID' => $user->ID, 'role' => get_option('default_role')) );
            $time_action = current_time('mysql');
            $action = rcl_get_time_user_action($user->ID);
            
            if(!$action)
                $wpdb->insert( RCL_PREF.'user_action', array( 'user' => $user->ID, 'time_action' => $time_action ) );

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

    $ref = ($_POST['redirect_to'])? apply_filters('url_after_register_rcl',esc_url($_POST['redirect_to'])): wp_registration_url();

    $get_fields = get_option( 'rcl_profile_fields' );
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
        $wp_errors->add( 'rcl_register_empty', __('Fill in the required fields!','wp-recall') );
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
                wp_redirect(rcl_format_url($ref).'action-rcl=login&success=confirm-email');
            else
                wp_redirect(rcl_format_url($ref).'action-rcl=login&success=true');
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
    }
}

//письмо высылаемое при регистрации
function rcl_register_mail($userdata){
    global $rcl_options;

    $subject = __('Confirm your registration!','wp-recall');
    $textmail = '
    <p>'.__('You or someone else signed up on our website','wp-recall').' "'.get_bloginfo('name').'" '.__('with the following data:','wp-recall').'</p>
    <p>'.__('Login','wp-recall').': '.$userdata['user_login'].'</p>
    <p>'.__('Password','wp-recall').': '.$userdata['user_pass'].'</p>';

    if($rcl_options['confirm_register_recall']==1){

        $url = get_bloginfo('wpurl').'/?rglogin='.$userdata['user_login'].'&rgpass='.$userdata['user_pass'].'&rgcode='.md5($userdata['user_login']);

        $textmail .= '<p>'.__('If it was you, then confirm your registration by clicking on the link below','wp-recall').':</p>
        <p><a href="'.$url.'">'.$url.'</a></p>
        <p>'.__('Unable to activate the account?','wp-recall').'</p>
        <p>'.__('Copy the link below, paste it into the address bar of your browser and hit Enter','wp-recall').'</p>';
    }

    $textmail .= '<p>'.__('If it wasn’t you, then just ignore this email','wp-recall').'</p>';
    rcl_mail($userdata['user_email'], $subject, $textmail);

}

//сохраняем данные произвольных полей профиля при регистрации
add_action('user_register','rcl_register_user_data',10);
function rcl_register_user_data($user_id){

    update_user_meta($user_id, 'show_admin_bar_front', 'false');

    $cf = new Rcl_Custom_Fields();
    $cf->register_user_metas($user_id);
}

//Формируем массив сервисных сообщений формы регистрации и входа
function rcl_notice_form($form='login'){
    global $wp_errors;
    
    do_action('rcl_'.$form.'_form_head');
    
    $wp_error = new WP_Error();
    
    if ( !empty( $wp_errors ) ) {
        $wp_error->errors = $wp_errors->errors;
    }
    
    if ( $wp_error->get_error_code() ) {
        $errors = '';
        $messages = '';
        foreach ( $wp_error->get_error_codes() as $code ) {
                $severity = $wp_error->get_error_data( $code );
                foreach ( $wp_error->get_error_messages( $code ) as $error_message ) {
                    
                        if ( 'message' == $severity )
                                $messages .= ' ' . $error_message . "<br />\n";
                        else
                                $errors .= ' ' . $error_message . "<br />\n";
                }
        }
        
        if ( ! empty( $errors ) ) {
                echo '<div class="error">' . apply_filters( 'login_errors', $errors ) . "</div>\n";
        }
        if ( ! empty( $messages ) ) {
                echo '<span class="error">' . apply_filters( 'login_messages', $messages ) . "</span>\n";
        }
    }

    if(!isset($_GET['action-rcl'])||$_GET['action-rcl']!=$form) return;

    $vls = array(
        'register'=> array(
            'success'=>array(
                'true'=>__('Registration completed!','wp-recall'),
                'confirm-email'=>__('Registration is completed! Check your email.','wp-recall')
            )
        ),
        'login'=> array(
            'error'=>array(
                'confirm'=>__('Your email is not confirmed!','wp-recall')
            ),
            'success'=>array(
                'true'=>__('Registration completed! Check your email','wp-recall'),
                'confirm-email'=>__('Registration completed! Check your email','wp-recall')
            )
        ),
        'remember'=> array(
            'error'=>array(),
            'success'=>array(
                'true'=>__('Your password has been sent!<br>Check your email.','wp-recall')
            )
        )
    );

    $vls = apply_filters('rcl_notice_form',$vls);

    $gets = explode('&',$_SERVER['QUERY_STRING']);
    foreach($gets as $gt){
        $pars = explode('=',$gt);
        $get[$pars[0]] = $pars[1];
    }

    $act = $get['action-rcl'];

    if((isset($get['success']))){
        $type = 'success';
    }else if(isset($get['error'])){
        $type = 'error';
    }else{
        $type = false;
    }

    if(!$type) return false;

    $notice = (isset($vls[$act][$type][$get[$type]]))? $vls[$act][$type][$get[$type]]:__('Input error!','wp-recall');

    if($form=='login'){
        $errors = '';
        $errors = apply_filters('login_errors', $errors);
        if($errors) $notice .= '<br>'.$errors;
    }

    if(!$notice) return false;

    $text = '<span class="'.$type.'">'.$notice.'</span>';

    echo $text;
}

//Проверяем заполненность поля повтора пароля
add_filter('rcl_registration_errors','rcl_chek_repeat_pass');
function rcl_chek_repeat_pass($errors){
    global $rcl_options;
    if(!isset($rcl_options['repeat_pass'])||!$rcl_options['repeat_pass']) return false;
    if($_POST['user_secondary_pass']!=$_POST['user_pass']){
        $errors = new WP_Error();
        $errors->add( 'rcl_register_repeat_pass', __('Repeated password not correct!','wp-recall') );
    }
    return $errors;
}

function rcl_referer_url($typeform=false){
	echo rcl_get_current_url($typeform);
}

function rcl_get_current_url($typeform=false,$urlform = 0){
	$protocol  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://':  'https://';
    $url = $protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

    if ( false !== strpos($url, '?action-rcl') ){
            $matches = '';
            preg_match_all('/(?<=http\:\/\/)[A-zА-я0-9\/\.\-\s\ё]*(?=\?action\-rcl)/iu',$url, $matches);
            $host = $matches[0][0];
    }
    if ( false !== strpos($url, '&action-rcl') ){
            preg_match_all('/(?<=http\:\/\/)[A-zА-я0-9\/\.\_\-\s\ё]*(&=\&action\-rcl)/iu',$url, $matches);
            $host = $matches[0][0];
    }
    if(!isset($host)||!$host) $host = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    $host = $protocol.$host;

    if($urlform) $host = rcl_format_url($host).'action-rcl='.$typeform;

    if($typeform=='remember') $host = rcl_format_url($host).'action-rcl=remember&success=true';
    return $host;
}

function rcl_form_action($typeform){
    echo rcl_get_current_url($typeform,1);
}

//Добавляем фильтр для формы авторизации
add_action('login_form','rcl_filters_signform',1);
function rcl_filters_signform(){
    $signfields = '';
    echo apply_filters('signform_fields_rcl',$signfields);
}
//Добавляем фильтр для формы регистрации
add_action('register_form','rcl_filters_regform',1);
function rcl_filters_regform(){
    $regfields = '';
    echo apply_filters('regform_fields_rcl',$regfields);
}

add_filter('regform_fields_rcl','rcl_password_regform',5);
function rcl_password_regform($content){
    global $rcl_options;

    $difficulty = (isset($rcl_options['difficulty_parole']))? $rcl_options['difficulty_parole']: false;
    $user_pass = (isset($_REQUEST['user_pass']))? $_REQUEST['user_pass']: '';

    $content .= '<div class="form-block-rcl default-field">';
    if($difficulty==1){
        $content .= '<input placeholder="'.__('Password','wp-recall').'" required id="primary-pass-user" type="password" onkeyup="passwordStrength(this.value)" value="'.$user_pass.'" name="user_pass">';
    }else{
        $content .= '<input placeholder="'.__('Password','wp-recall').'" required type="password" value="'.$user_pass.'" id="primary-pass-user" name="user_pass">';
    }
	$content .= '<i class="fa fa-lock"></i>';
	$content .= '<span class="required">*</span>';
    $content .= '</div>';

    if($difficulty==1){
        $content .= '<div class="form-block-rcl">
                <label>'.__('Password strength indicator','wp-recall').':</label>
                <div id="passwordStrength" class="strength0">
                    <div id="passwordDescription">'.__('A password has not been entered','wp-recall').'</div>
                </div>
            </div>';
    }

    return $content;
}

//Добавляем поле повтора пароля в форму регистрации
add_filter('regform_fields_rcl','rcl_secondary_password',10);
function rcl_secondary_password($fields){
    global $rcl_options;
    if(!isset($rcl_options['repeat_pass'])||!$rcl_options['repeat_pass']) return $fields;

    $fields .= '<div class="form-block-rcl default-field">
                    <input placeholder="'.__('Repeat the password','wp-recall').'" required id="secondary-pass-user" type="password" value="'.$_REQUEST['user_secondary_pass'].'" name="user_secondary_pass">
					<i class="fa fa-lock"></i>
					<span class="required">*</span>
                <div id="notice-chek-password"></div>
            </div>
            <script>jQuery(function(){
            jQuery(".form-tab-rcl").on("keyup","#secondary-pass-user",function(){
                var pr = jQuery("#primary-pass-user").val();
                var sc = jQuery(this).val();
                var notice;
                if(pr!=sc) notice = "<span class=error>'.__('The passwords do not match!','wp-recall').'</span>";
                else notice = "<span class=success>'.__('The passwords match','wp-recall').'</span>";
                jQuery("#notice-chek-password").html(notice);
            });});
        </script>';

    return $fields;
}

//Вывод произвольных полей профиля в форме регистрации
add_filter('regform_fields_rcl','rcl_custom_fields_regform',20);
function rcl_custom_fields_regform($field){
    $get_fields = get_option( 'rcl_profile_fields' );

    if($get_fields){
        $get_fields = stripslashes_deep($get_fields);

        $cf = new Rcl_Custom_Fields();

        foreach((array)$get_fields as $custom_field){
            if($custom_field['register']!=1) continue;

            $custom_field = apply_filters('custom_field_regform',$custom_field);

            $class = (isset($custom_field['class']))? $custom_field['class']: '';
            $id = (isset($custom_field['id']))? 'id='.$custom_field['id']: '';
            $attr = (isset($custom_field['attr']))? ''.$custom_field['attr']: '';

            $field .= '<div class="form-block-rcl '.$class.'" '.$id.' '.$attr.'>';
            $star = ($custom_field['requared']==1)? ' <span class="required">*</span> ': '';
            $field .= '<label>'.$cf->get_title($custom_field).$star.'';
            if($custom_field['type']) $field .= '<span class="colon">:</span>';
            $field .= '</label>';

            $field .= $cf->get_input($custom_field,$_POST[$custom_field['slug']]);
            $field .= '</div>';

        }
    }
    return $field;
}

