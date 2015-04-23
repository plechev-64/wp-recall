<?php

function referer_url($typeform=false){
    $url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];				
				
    if ( false !== strpos($url, '?action-rcl') ){
            $matches = '';
            preg_match_all('/(?<=http\:\/\/)[A-zА-я0-9\/\.\-\s\ё]*(?=\?action\-rcl)/iu',$url, $matches); 
            $host = $matches[0][0];
    }
    if ( false !== strpos($url, '&action-rcl') ){
            preg_match_all('/(?<=http\:\/\/)[A-zА-я0-9\/\.\_\-\s\ё]*(&=\&action\-rcl)/iu',$url, $matches); 
            $host = $matches[0][0];
    }
    if(!$host) $host = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    $host = 'http://'.$host;
    if($typeform=='remember') $host = get_redirect_url_rcl($host).'action-rcl=remember&success=true';
    echo $host;
}

//Добавляем фильтр для формы авторизации
add_action('login_form','add_filters_signform_form',1);
function add_filters_signform_form(){
    $signfields = '';
    echo apply_filters('signform_fields_rcl',$signfields);
}
//Добавляем фильтр для формы регистрации
add_action('register_form','add_filters_register_form',1);
function add_filters_register_form(){
    $regfields = '';
    echo apply_filters('regform_fields_rcl',$regfields);
}

add_filter('regform_fields_rcl','add_parole_register_form',5);
function add_parole_register_form($content){
    global $rcl_options;
    
    $content .= '<div class="form-block-rcl">'
            . '<label>'.__('Пароль').' <span class="required">*</span></label>';
    if($rcl_options['difficulty_parole']==1){
        $content .= '<input required id="primary-pass-user" type="password" onkeyup="passwordStrength(this.value)" value="" name="pass-user">';
    }else{
        $content .= '<input required type="password" value="" id="primary-pass-user" name="pass-user">';
    }
    $content .= '</div>';

    if($rcl_options['difficulty_parole']==1){
        $content .= '<div class="form-block-rcl">
                <label>'.__('Индикатор надёжности пароля:').'</label>
                <div id="passwordStrength" class="strength0">
                    <div id="passwordDescription">'.__('Пароль не введён').'</div>
                </div>
            </div>';
    }
    
    return $content;
}

//Добавляем поле повтора пароля в форму регистрации
add_filter('regform_fields_rcl','get_secondary_password_field',10);
function get_secondary_password_field($fields){
    global $rcl_options;
    if(!isset($rcl_options['repeat_pass'])||!$rcl_options['repeat_pass']) return $fields;
    
    $fields .= '<div class="form-block-rcl">
                <label>'.__('Повторите пароль').' <span class="required">*</span></label>
                <input required id="secondary-pass-user" type="password" value="" name="secondary-email-user">
                <div id="notice-chek-password"></div>
            </div>
            <script>jQuery(function(){
            jQuery("#secondary-pass-user").live("keyup", function(){ 
                var pr = jQuery("#primary-pass-user").val();
                var sc = jQuery(this).val();
                var notice;
                if(pr!=sc) notice = "<span class=error>'.__('Пароли не совпадают!').'</span>";
                else notice = "<span class=success>'.__('Пароли совпадают').'</span>";
                jQuery("#notice-chek-password").html(notice);
            });});
        </script>';
    
    return $fields;
}
//Вывод произвольных полей профиля в форме регистрации
add_filter('regform_fields_rcl','get_custom_fields_regform_rcl',20);
function get_custom_fields_regform_rcl($field){
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
                $field .= '<label>'.$custom_field['title'].$star.'';
                if($custom_field['type']) $field .= ':';
                $field .= '</label>';

                $field .= $cf->get_input($custom_field);
                $field .= '</div>';

            }			
	}
	return $field;
}

function login_form_rcl(){
	echo get_authorize_form_rcl('floatform');
}
function get_login_form_rcl($atts){
	extract(shortcode_atts(array( 'form' => false ),$atts));
	return get_authorize_form_rcl('pageform',$form);
}
add_shortcode('loginform','get_login_form_rcl');
function get_authorize_form_rcl($type=false,$form=false){
	global $user_ID,$rcl_user_URL,$rcl_options,$form;
	
	ob_start();	
        echo '<div class="panel_lk_recall '.$type.'">';
        
		if($type=='floatform') echo '<a href="#" class="close-popup"><i class="fa fa-times-circle"></i></a>';
		if($user_ID){
		
                    echo '<div class="username"><b>'.__('Привет,').' '.get_the_author_meta('display_name', $user_ID).'!</b></div>
                    <div class="author-avatar">';
                    echo '<a href="'.$rcl_user_URL.'" title="'.__('В личный кабинет').'">'.get_avatar($user_ID, 60).'</a>';
                    if(function_exists('get_rayting_block_rcl')):

                        $karma = apply_filters('get_all_rayt_user_rcl',0,$user_ID);
                        echo get_rayting_block_rcl($karma);

                    endif;
                    echo '</div>';				
                    echo '<div class="buttons">';

                            $buttons = '<p>'.get_button_rcl(__('Личный кабинет'),$rcl_user_URL,array('icon'=>'fa-home')).'</p>
                            <p>'.get_button_rcl(__('Выход'),wp_logout_url( home_url() ),array('icon'=>'fa-external-link')).'</p>';
                            echo apply_filters('buttons_widget_rcl',$buttons);

                    echo '</div>';
				
		}else{
				
                    $login_form = $rcl_options['login_form_recall'];

                    if($login_form==1&&$type!='pageform'){

                        $redirect_url = get_redirect_url_rcl(get_permalink($rcl_options['page_login_form_recall']));

                        echo '<div class="buttons">';

                            $buttons = '<p>'.get_button_rcl(__('Войти'),$redirect_url.'action-rcl=login',array('icon'=>'fa-sign-in')).'</p>
                            <p>'.get_button_rcl(__('Регистрация'),$redirect_url.'action-rcl=register',array('icon'=>'fa-book')).'</p>';
                            echo apply_filters('buttons_widget_rcl',$buttons);

                        echo '</div>';

                    }else if($login_form==2){											
                        echo '<div class="buttons">';
                            $buttons = '<p class="parent-recbutton">'.wp_register('', '', 0).'</p>
                            <p class="parent-recbutton">'.wp_loginout('/', 0).'</p>';
                            echo apply_filters('buttons_widget_rcl',$buttons);
                        echo '</div>';
                    }else if($login_form==3||$type){
                        if($form!='register'){
                                include_template_rcl('form-sign.php');
                        }
                        if($form!='sign'){
                                include_template_rcl('form-register.php');
                        }
                        if(!$form||$form=='sign'){
                                include_template_rcl('form-remember.php');
                        }
                    }else if(!$login_form){
                        echo '<div class="buttons">';
                                $buttons .= '<p>'.get_button_rcl(__('Войти'),'#',array('icon'=>'fa-sign-in','class'=>'sign-button')).'</p>
                                <p>'.get_button_rcl(__('Регистрация'),'#',array('icon'=>'fa-book','class'=>'reglink')).'</p>';
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
function notice_form_rcl($form='login'){

    if(!isset($_GET['action-rcl'])||$_GET['action-rcl']!=$form) return false; 
        
    $vls = array(
        'register'=> array(
            'error'=>array(
                'login'=>__('В логине недопустимые символы!'),
                'empty'=>__('Заполните поля!'),
                'captcha'=>__('Не верно заполнено поле CAPTCHA!'),
                'login-us'=>__('Логин уже используется!'),
                'email-us'=>__('Е-mail уже используется!'),
                'email'=>__('Некорректный E-mail!')
            ),
            'success'=>array(
                'true'=>__('Регистрация завершена! Проверьте свою почту.')
            )
        ),
        'login'=> array(
            'error'=>array(
                'confirm'=>__('Ваш email не подтвержден!'),
                'empty'=>__('Заполните поля!'),
                'failed'=>__('Логин или пароль не верны!')
            ),
            'success'=>array(
                'true'=>__('Регистрация завершена! Проверьте свою почту')
            )
        ),
        'remember'=> array(
            'error'=>array(),
            'success'=>array(
                'true'=>__('Пароль был выслан!<br>Проверьте свою почту.')
            )
        )
    );
    
    $vls = apply_filters('notice_form_rcl',$vls);

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

    $notice = (isset($vls[$act][$type][$get[$type]]))? $vls[$act][$type][$get[$type]]:__('Ошибка заполнения!');
    
    if($form=='login'){
        $errors = '';
        $errors = apply_filters('login_errors', $errors);
        if($errors) $notice .= '<br>'.$errors;
    }
    
    if(!$notice) return false;
    
    $text = '<span class="'.$type.'">'.$notice.'</span>';

    echo $text;      
}

//Добавляем сообщение о неверном заполнении поле повтора пароля
add_filter('notice_form_rcl','add_notice_chek_register_pass');
function add_notice_chek_register_pass($notices){
    global $rcl_options;
    if(!isset($rcl_options['repeat_pass'])||!$rcl_options['repeat_pass']) return $notices;
    $notices['register']['error']['repeat-pass'] = __('Повтор пароля не верен!');
    return $notices;
}
//Проверяем заполненность поля повтора пароля
add_action('pre_register_user_rcl','chek_repeat_register_pass');
function chek_repeat_register_pass($ref){
    global $rcl_options;
    if(!isset($rcl_options['repeat_pass'])||!$rcl_options['repeat_pass']) return false;
    if($_POST['secondary-email-user']!=$_POST['pass-user']){
        wp_redirect(get_redirect_url_rcl($ref).'action-rcl=register&error=repeat-pass');exit;
    }
}