<?php global $typeform;
    if(!$typeform||$typeform=='sign') $f_sign = 'style="display:block;"'; ?>
<div class="form-tab-rcl" id="login-form-rcl" <?php echo $f_sign; ?>>
    <h4 class="form-title"><?php _e('Authorization','rcl'); ?></h4>

    <?php rcl_notice_form('login'); ?>

    <form action="" method="post">
        <div class="form-block-rcl">
            <label><?php _e('Nickname','rcl'); ?> <span class="required">*</span></label>
            <input required type="text" value="" name="login-user">
        </div>
        <div class="form-block-rcl">
            <label><?php _e('Password','rcl'); ?> <span class="required">*</span></label>
            <input required type="password" value="" name="pass-user">
        </div>

        <?php do_action( 'login_form' ); ?>

        <div class="form-block-rcl">
            <label><input type="checkbox" value="1" name="member-user"> <?php _e('Remember','rcl'); ?></label>
        </div>

        <div class="input-container">
            <input type="submit" class="recall-button link-tab-form" name="submit-login" value="<?php _e('Login','rcl'); ?>">

            <?php if(!$typeform){ ?><a href="#" class="link-register-rcl link-tab-rcl "><?php _e('Registration','rcl'); ?></a><?php } ?>

            <a href="#" class="link-remember-rcl link-tab-rcl "><?php _e('Forgot your password','rcl'); ?></a>

            <?php echo wp_nonce_field('login-key-rcl','_wpnonce',true,false); ?>
            <input type="hidden" name="referer_rcl" value="<?php rcl_referer_url(); ?>">
        </div>
        
    </form>
</div>
