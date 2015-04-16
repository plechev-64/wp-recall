<?php global $form; ?>
<div class="form-tab-rcl" id="remember-form-rcl">
    <h4 class="form-title">Генерация пароля</h4>

    <?php notice_form_rcl('remember'); ?>

    <?php if(!isset($_GET['success'])){ ?>
        <form action="<?php echo esc_url( site_url( 'wp-login.php?action=lostpassword', 'login_post' )); ?>" method="post">							
            <div class="form-block-rcl">
                <label>Имя пользователя или e-mail</label>
                <input required type="text" value="" name="user_login">								
            </div>
            <input type="submit" class="recall-button link-tab-form" name="remember-login" value="Отправить">
            <a href="#" class="link-login-rcl link-tab-rcl ">Войти</a>
            <?php if($form!='sign'){ ?>
                <a href="#" class="link-register-rcl link-tab-rcl ">Регистрация</a>
            <?php } ?>
            <?php echo wp_nonce_field('remember-key-rcl','_wpnonce',true,false); ?>
            <input type="hidden" name="redirect_to" value="<?php referer_url('remember'); ?>">
        </form>
    <?php } ?>
</div>

