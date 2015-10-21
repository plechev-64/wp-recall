<?php

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

    $content .= '<div class="form-block-rcl">'
            . '<label>'.__('Password','rcl').' <span class="required">*</span></label>'
            . '<div class="default-field">
                <span class="field-icon"><i class="fa fa-lock"></i></span>';
    if($rcl_options['difficulty_parole']==1){
        $content .= '<input required id="primary-pass-user" type="password" onkeyup="passwordStrength(this.value)" value="" name="user_pass">';
    }else{
        $content .= '<input required type="password" value="" id="primary-pass-user" name="user_pass">';
    }
    $content .= '</div>'
            . '</div>';

    if($rcl_options['difficulty_parole']==1){
        $content .= '<div class="form-block-rcl">
                <label>'.__('The password strength indicator','rcl').':</label>
                <div id="passwordStrength" class="strength0">
                    <div id="passwordDescription">'.__('A password is not entered','rcl').'</div>
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

    $fields .= '<div class="form-block-rcl">
                <label>'.__('Repeat the password','rcl').' <span class="required">*</span></label>
                <div class="default-field">
                    <span class="field-icon"><i class="fa fa-lock"></i></span>
                    <input required id="secondary-pass-user" type="password" value="" name="user_secondary_pass">
                </div>
                <div id="notice-chek-password"></div>
            </div>
            <script>jQuery(function(){
            jQuery(".form-tab-rcl").on("keyup","#secondary-pass-user",function(){
                var pr = jQuery("#primary-pass-user").val();
                var sc = jQuery(this).val();
                var notice;
                if(pr!=sc) notice = "<span class=error>'.__('The passwords do not match!','rcl').'</span>";
                else notice = "<span class=success>'.__('The passwords match','rcl').'</span>";
                jQuery("#notice-chek-password").html(notice);
            });});
        </script>';

    return $fields;
}
//Вывод произвольных полей профиля в форме регистрации
add_filter('regform_fields_rcl','rcl_custom_fields_regform',20);
function rcl_custom_fields_regform($field){
	$get_fields = get_option( 'custom_profile_field' );

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
                if($custom_field['type']) $field .= ':';
                $field .= '</label>';

                $field .= $cf->get_input($custom_field,$_POST[$custom_field['slug']]);
                $field .= '</div>';

            }
	}
	return $field;
}

function rcl_login_form(){
	echo rcl_get_authorize_form('floatform');
}

add_shortcode('loginform','rcl_get_login_form');
function rcl_get_login_form($atts){
	extract(shortcode_atts(array( 'form' => false ),$atts));
	return rcl_get_authorize_form('pageform',$form);
}

function rcl_get_authorize_form($type=false,$form=false){
	global $user_ID,$rcl_user_URL,$rcl_options,$typeform;
        $typeform = $form;
	ob_start();
        echo '<div class="panel_lk_recall '.$type.'">';

		if($type=='floatform') echo '<a href="#" class="close-popup"><i class="fa fa-times-circle"></i></a>';
		if($user_ID){

                    echo '<div class="username"><b>'.__('Hi','rcl').', '.get_the_author_meta('display_name', $user_ID).'!</b></div>
                    <div class="author-avatar">';
                    echo '<a href="'.$rcl_user_URL.'" title="'.__('In personal account','rcl').'">'.get_avatar($user_ID, 60).'</a>';

                    if(function_exists('rcl_rating_block')):
                        echo rcl_rating_block(array('ID'=>$user_ID,'type'=>'user'));
                    endif;

                    echo '</div>';
                    echo '<div class="buttons">';

                            $buttons = '<p>'.rcl_get_button(__('In personal account','rcl'),$rcl_user_URL,array('icon'=>'fa-home')).'</p>
                            <p>'.rcl_get_button(__('Exit','rcl'),wp_logout_url( home_url() ),array('icon'=>'fa-external-link')).'</p>';
                            echo apply_filters('buttons_widget_rcl',$buttons);

                    echo '</div>';

		}else{

                    $login_form = $rcl_options['login_form_recall'];

                    if($login_form==1&&$type!='pageform'){

                        $redirect_url = rcl_format_url(get_permalink($rcl_options['page_login_form_recall']));

                        echo '<div class="buttons">';

                            $buttons = '<p>'.rcl_get_button(__('Login','rcl'),$redirect_url.'action-rcl=login',array('icon'=>'fa-sign-in')).'</p>
                            <p>'.rcl_get_button(__('Registration','rcl'),$redirect_url.'action-rcl=register',array('icon'=>'fa-book')).'</p>';
                            echo apply_filters('buttons_widget_rcl',$buttons);

                        echo '</div>';

                    }else if($login_form==2){
                        echo '<div class="buttons">';
                            $buttons = '<p>'.rcl_get_button(__('Login','rcl'),esc_url(wp_login_url('/')),array('icon'=>'fa-sign-in')).'</p>
                            <p>'.rcl_get_button(__('Registration','rcl'),esc_url(wp_registration_url()),array('icon'=>'fa-book')).'</p>';
                            echo apply_filters('buttons_widget_rcl',$buttons);
                        echo '</div>';
                    }else if($login_form==3||$type){
                        if($typeform!='register'){
                                rcl_include_template('form-sign.php');
                        }
                        if($typeform!='sign'){
                                rcl_include_template('form-register.php');
                        }
                        if(!$typeform||$typeform=='sign'){
                                rcl_include_template('form-remember.php');
                        }
                    }else if(!$login_form){
                        echo '<div class="buttons">';
                                $buttons = '<p>'.rcl_get_button(__('Login','rcl'),'#',array('icon'=>'fa-sign-in','class'=>'rcl-login')).'</p>
                                <p>'.rcl_get_button(__('Registration','rcl'),'#',array('icon'=>'fa-book','class'=>'rcl-register')).'</p>';
                                echo apply_filters('buttons_widget_rcl',$buttons);
                        echo '</div>';
                    }

		}

	echo '</div>';
	$html = ob_get_contents();
	ob_end_clean();

	return $html;
}

//Формируем массив сервисных сообщений формы регистрации и входа
function rcl_notice_form($form='login'){
    global $wp_errors;

    if ( $wp_errors->errors ) {
            $errors = '';
            $messages = '';
            foreach ( $wp_errors->errors as $code ) {
                    $severity = true;
                    foreach ( $code as $error_message ) {
                            if ( 'message' == $severity )
                                    $messages .= '	' . $error_message . "<br />\n";
                            else
                                    $errors .= '<span class="error">' . $error_message . "</span>\n";
                    }
            }
            if ( ! empty( $errors ) ) {
                    echo '<div id="login_error">' . apply_filters( 'login_errors', $errors ) . "</div>\n";
            }
            if ( ! empty( $messages ) ) {
                    echo '<span class="error">' . apply_filters( 'login_messages', $messages ) . "</span>\n";
            }
    }

    if(!isset($_GET['action-rcl'])||$_GET['action-rcl']!=$form) return;

    $vls = array(
        'register'=> array(
            'success'=>array(
                'true'=>__('Registration is completed!','rcl'),
                'confirm-email'=>__('Registration is completed! Check your email.','rcl')
            )
        ),
        'login'=> array(
            'error'=>array(
                'confirm'=>__('Your email is not confirmed!','rcl')
            ),
            'success'=>array(
                'true'=>__('Registration is completed! Check your email','rcl')
            )
        ),
        'remember'=> array(
            'error'=>array(),
            'success'=>array(
                'true'=>__('Your password has been sent!<br>Check your email.','rcl')
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

    $notice = (isset($vls[$act][$type][$get[$type]]))? $vls[$act][$type][$get[$type]]:__('Error filling!','rcl');

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
        $errors->add( 'rcl_register_repeat_pass', __('Repeat password is not correct!','rcl') );
    }
    return $errors;
}