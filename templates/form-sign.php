<?php global $typeform;
    if(!$typeform||$typeform=='sign') $f_sign = 'style="display:block;"'; ?>
<div class="form-tab-rcl" id="login-form-rcl" <?php echo $f_sign; ?>>
    <h4 class="form-title"><?php _e('Authorization','wp-recall'); ?></h4>

    <?php rcl_notice_form('login'); ?>

    <form action="<?php rcl_form_action('login'); ?>" method="post">
        <div class="form-block-rcl">
            <label><?php _e('Nickname','wp-recall'); ?> <span class="required">*</span></label>
            <div class="default-field">
                <span class="field-icon"><i class="fa fa-user"></i></span>
                <input required type="text" value="" name="user_login">
            </div>
        </div>
        <div class="form-block-rcl">
            <label><?php _e('Password','wp-recall'); ?> <span class="required">*</span></label>
            <div class="default-field">
                <span class="field-icon"><i class="fa fa-lock"></i></span>
                <input required type="password" value="" name="user_pass">
            </div>
        </div>

        <?php do_action( 'login_form' ); ?>

        <div class="form-block-rcl">
            <label><input type="checkbox" value="1" name="rememberme"> <?php _e('Remember','wp-recall'); ?></label>
        </div>

        <div class="input-container">
            <input type="submit" class="recall-button link-tab-form" name="submit-login" value="<?php _e('Login','wp-recall'); ?>">

            <?php if(!$typeform){ ?><a href="#" class="link-register-rcl link-tab-rcl "><i class="fa fa-reply-all"></i><?php _e('Registration','wp-recall'); ?></a><?php } ?>

            <a href="#" class="link-remember-rcl link-tab-rcl "><i class="fa fa-reply-all"></i><?php _e('Forgot your password','wp-recall'); ?></a>

            <?php echo wp_nonce_field('login-key-rcl','_wpnonce',true,false); ?>
            <input type="hidden" name="redirect_to" value="<?php rcl_referer_url('login'); ?>">
        </div>

    </form>
</div>
