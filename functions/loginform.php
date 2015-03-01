<?php

add_filter('regform_fields_rcl','get_custom_fields_regform_rcl');
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
	global $user_ID,$rcl_user_URL,$rcl_options;
	
	ob_start();	
        echo '<div class="panel_lk_recall '.$type.'">';
        
		if($type=='floatform') echo '<a href="#" class="close-popup"><i class="fa fa-times-circle"></i></a>';
		if($user_ID){
		
			echo '<div class="username"><b>'.__('Привет,','rcl').' '.get_the_author_meta('display_name', $user_ID).'!</b></div>
				<div class="author-avatar">';
				echo '<a href="'.$rcl_user_URL.'" title="В личный кабинет">'.get_avatar($user_ID, 60).'</a>';
				if(function_exists('get_rayting_block_rcl')):
					
                                    $karma = apply_filters('get_all_rayt_user_rcl',0,$user_ID);
                                    echo get_rayting_block_rcl($karma);
					
				endif;
				echo '</div>';				
				echo '<div class="buttons">';
					
					$buttons = '<p>'.get_button_rcl('Личный кабинет',$rcl_user_URL,array('icon'=>'fa-home')).'</p>
					<p>'.get_button_rcl('Выход',wp_logout_url( home_url() ),array('icon'=>'fa-external-link')).'</p>';
					echo apply_filters('buttons_widget_rcl',$buttons);
					
				echo '</div>';
				
		}else{
				
			$login_form = $rcl_options['login_form_recall'];
			
			if($login_form==1&&$type!='pageform'){
				
				$redirect_url = get_redirect_url_rcl(get_permalink($rcl_options['page_login_form_recall']));
				
				echo '<div class="buttons">';
				
					$buttons = '<p>'.get_button_rcl('Войти',$redirect_url.'action-rcl=login',array('icon'=>'fa-sign-in')).'</p>
					<p>'.get_button_rcl('Регистрация',$redirect_url.'action-rcl=register',array('icon'=>'fa-book')).'</p>';
					echo apply_filters('buttons_widget_rcl',$buttons);
					
				echo '</div>';
				
			}else if($login_form==2){											
				echo '<div class="buttons">';
					$buttons = '<p class="parent-recbutton">'.wp_register('', '', 0).'</p>
					<p class="parent-recbutton">'.wp_loginout('/', 0).'</p>';
					echo apply_filters('buttons_widget_rcl',$buttons);
				echo '</div>';
			}else if($login_form==3||$type){
				
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
				
				if(!$form||$form=='sign') $f_sign = 'style="display:block;"';
				if($form=='register') $f_reg = 'style="display:block;"';
				if($form!='register'){
					echo '<div class="form-tab-rcl" id="login-form-rcl" '.$f_sign.'>
						<h4 class="form-title">Авторизация</h4>';
                                        
                                                echo notice_form_rcl('login');

						echo '<form action="" method="post">							
							<div class="form-block-rcl">
								<label>'.__('Логин','rcl').' <span class="required">*</span></label>
								<input required type="text" value="" name="login-user">
							</div>
							<div class="form-block-rcl">
								<label>'.__('Пароль','rcl').' <span class="required">*</span></label>
								<input required type="password" value="" name="pass-user">';
								do_action( 'login_form' );
							echo '</div>';
                                                        
                                                        $signfields = '';
                                                        echo apply_filters('signform_fields_rcl',$signfields);  
                                                        
							echo '<div class="form-block-rcl">
								<label><input type="checkbox" value="1" name="member-user"> '.__('Запомнить','rcl').'</label>								
							</div>
							<input type="submit" class="recall-button link-tab-form" name="submit-login" value="'.__('Отправить','rcl').'">';
							
							if(!$form) echo '<a href="#" class="link-register-rcl link-tab-rcl ">'.__('Регистрация','rcl').'</a>';
							
                                                        echo '<a href="#" class="link-remember-rcl link-tab-rcl ">'.__('Забыли пароль?','rcl').'</a>';

							echo wp_nonce_field('login-key-rcl','_wpnonce',true,false).'
							<input type="hidden" name="referer_rcl" value="http://'.$host.'">
						</form>
					</div>';
				}
				if($form!='sign'){
					echo '<div class="form-tab-rcl" id="register-form-rcl" '.$f_reg.'>
						<h4 class="form-title">Регистрация</h4>';
                                                    
                                                    echo notice_form_rcl('register');
	
                                                    echo '<form action="" method="post">							
                                                            <div class="form-block-rcl">
                                                                    <label>'.__('Логин','rcl').' <span class="required">*</span></label>
                                                                    <input required type="text" value="" name="login-user">
                                                            </div>
                                                            <div class="form-block-rcl">
                                                                    <label>'.__('E-mail','rcl').' <span class="required">*</span></label>
                                                                    <input required type="email" value="" name="email-user">
                                                            </div>
                                                            <div class="form-block-rcl">
                                                                    <label>'.__('Пароль','rcl').' <span class="required">*</span></label>';
                                                    
                                                                    if($rcl_options['difficulty_parole']==1) echo '<input required id="primary-pass-user" type="password" onkeyup="passwordStrength(this.value)" value="" name="pass-user">';
                                                                    else echo '<input required type="password" value="" id="primary-pass-user" name="pass-user">';

                                                        if($rcl_options['difficulty_parole']==1){
                                                            echo '<div>
                                                                        <label>Индикатор надёжности пароля:</label>
                                                                        <div id="passwordStrength" class="strength0">
                                                                                <div id="passwordDescription">Пароль не введён</div>
                                                                        </div>
                                                                        
                                                                    </div>';
                                                        }
                                                            echo '</div>';
      
                                                    $regfields = '';	
                                                    echo apply_filters('regform_fields_rcl',$regfields);
                                                    
                                                    do_action( 'register_form' );
                                                    
                                                        echo '<input type="submit" class="recall-button" name="submit-register" value="'.__('Отправить','rcl').'">';
                                                        if(!$form) echo '<a href="#" class="link-login-rcl link-tab-rcl">'.__('Вход','rcl').'</a>';
                                                        echo wp_nonce_field('register-key-rcl','_wpnonce',true,false).'
                                                        <input type="hidden" name="referer_rcl" value="http://'.$host.'">
                                                    </form>';
					echo '</div>';
				}
				if(!$form||$form=='sign'){
					echo '<div class="form-tab-rcl" id="remember-form-rcl">
						<h4 class="form-title">'.__('Генерация пароля','rcl').'</h4>';
                                        
                                                echo notice_form_rcl('remember');
						
						if(!isset($_GET['success'])){
						echo '<form action="'.esc_url( site_url( 'wp-login.php?action=lostpassword', 'login_post' )).'" method="post">							
							<div class="form-block-rcl">
								<label>'.__('Имя пользователя или e-mail','rcl').'</label>
								<input required type="text" value="" name="user_login">								
							</div>
							<input type="submit" class="recall-button link-tab-form" name="remember-login" value="'.__('Войти','rcl').'">
							<a href="#" class="link-login-rcl link-tab-rcl ">'.__('Вход','rcl').'</a>';
							if($form!='sign') echo '<a href="#" class="link-register-rcl link-tab-rcl ">'.__('Регистрация','rcl').'</a>';
							echo wp_nonce_field('remember-key-rcl','_wpnonce',true,false).'
							<input type="hidden" name="redirect_to" value="http://'.get_redirect_url_rcl($host).'action-rcl=remember&success=true">
						</form>';
						}
					echo '</div>';
				}
			}else if(!$login_form){
				echo '<div class="buttons">';
					$buttons .= '<p>'.get_button_rcl('Войти','#',array('icon'=>'fa-sign-in','class'=>'sign-button')).'</p>
					<p>'.get_button_rcl('Регистрация','#',array('icon'=>'fa-book','class'=>'reglink')).'</p>';
					echo apply_filters('buttons_widget_rcl',$buttons);
				echo '</div>';
			}
				
		}
		
	echo '</div>';
	$html = ob_get_contents();
	ob_end_clean();
	
	return $html;
}

add_filter('regform_fields_rcl','get_secondary_password_field',1);
function get_secondary_password_field($fields){
    $fields .= '<div class="form-block-rcl">
                <label>'.__('Повторите пароль','rcl').' <span class="required">*</span></label>
                <input required id="secondary-pass-user" type="password" value="" name="secondary-email-user">
                <div id="notice-chek-password"></div>
            </div>
            <script>jQuery(function(){
            jQuery("#secondary-pass-user").live("keyup", function(){ 
                var pr = jQuery("#primary-pass-user").val();
                var sc = jQuery(this).val();
                var notice;
                if(pr!=sc) notice = "<span class=error>Пароли не совпадают!</span>";
                else notice = "<span class=success>Пароли совпадают</span>";
                jQuery("#notice-chek-password").html(notice);
            });});
        </script>';
    return $fields;
}

function notice_form_rcl($type='login'){
    

    if(!isset($_GET['action-rcl'])||$_GET['action-rcl']!=$type) return false; 
        
    $vls = array(
        'register'=> array(
            'error'=>array(
                'login'=>'В логине недопустимые символы!',
                'empty'=>'Заполните поля!',
                'captcha'=>'Не верно заполнено поле CAPTCHA!',
                'login-us'=>'Логин уже используется!',
                'email-us'=>'Е-mail уже используется!',
                'email'=>'Некорректный E-mail!',
            ),
            'success'=>array(
                'true'=>'Регистрация завершена! Проверьте свою почту.'
            )
        ),
        'login'=> array(
            'error'=>array(
                'confirm'=>'Ваш email не подтвержден!',
                'empty'=>'Заполните поля!',
                'failed'=>'Логин или пароль не верны!'
            ),
            'success'=>array(
                'true'=>'Регистрация завершена! Проверьте свою почту'
            )
        ),
        'remember'=> array(
            'error'=>array(),
            'success'=>array(
                'true'=>'Пароль был выслан!<br>Проверьте свою почту.'
            )
        )
    );
    
    $vls = apply_filters('notice_form_rcl',$vls);
        
    $act = $_GET['action-rcl'];
    $get = (isset($_GET['success']))? 'success': 'error';
    $notice = (isset($vls[$act][$get][$_GET[$get]]))? $vls[$act][$get][$_GET[$get]]:'Ошибка заполнения!';
    
    if($type=='login'){
        $errors = '';
        $errors = apply_filters('login_errors', $errors);
        if($errors) $notice .= '<br>'.$errors;
    }
    
    if(!$notice) return false;
    
    $text = '<span class="'.$get.'">'.$notice.'</span>';

    return $text;      
}
