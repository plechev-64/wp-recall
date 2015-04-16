<?php global $form;
if($form=='register') $f_reg = 'style="display:block;"'; ?>
<div class="form-tab-rcl" id="register-form-rcl" <?php echo $f_reg; ?>>
    <h4 class="form-title">Регистрация</h4>

    <?php notice_form_rcl('register'); ?>

    <form action="" method="post">							
        <div class="form-block-rcl">
            <label>Логин <span class="required">*</span></label>
            <input required type="text" value="" name="login-user">
        </div>
        <div class="form-block-rcl">
            <label>E-mail <span class="required">*</span></label>
            <input required type="email" value="" name="email-user">
        </div>

        <?php do_action( 'register_form' ); ?>

        <input type="submit" class="recall-button" name="submit-register" value="Отправить">
        <?php if(!$form){ ?>
            <a href="#" class="link-login-rcl link-tab-rcl">Вход</a>
        <?php } ?>
        <?php echo wp_nonce_field('register-key-rcl','_wpnonce',true,false); ?>
        <input type="hidden" name="referer_rcl" value="<?php referer_url(); ?>">
    </form>
</div>
