<?php global $form;
    if(!$form||$form=='sign') $f_sign = 'style="display:block;"'; ?>
<div class="form-tab-rcl" id="login-form-rcl" <?php echo $f_sign; ?>>
    <h4 class="form-title"><?php _e('Авторизация'); ?></h4>

    <?php notice_form_rcl('login'); ?>

    <form action="" method="post">							
        <div class="form-block-rcl">
            <label><?php _e('Логин'); ?> <span class="required">*</span></label>
            <input required type="text" value="" name="login-user">
        </div>
        <div class="form-block-rcl">
            <label><?php _e('Пароль'); ?> <span class="required">*</span></label>
            <input required type="password" value="" name="pass-user">           
        </div>
        
        <?php do_action( 'login_form' ); ?>

        <div class="form-block-rcl">
            <label><input type="checkbox" value="1" name="member-user"> <?php _e('Запомнить'); ?></label>								
        </div>
        <input type="submit" class="recall-button link-tab-form" name="submit-login" value="<?php _e('Войти'); ?>">

        <?php if(!$form){ ?><a href="#" class="link-register-rcl link-tab-rcl "><?php _e('Регистрация'); ?></a><?php } ?>

        <a href="#" class="link-remember-rcl link-tab-rcl "><?php _e('Забыли пароль?'); ?></a>

        <?php echo wp_nonce_field('login-key-rcl','_wpnonce',true,false); ?>
        <input type="hidden" name="referer_rcl" value="<?php referer_url(); ?>">
    </form>
</div>
