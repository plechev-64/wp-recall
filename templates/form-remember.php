<?php global $form; ?>
<div class="form-tab-rcl" id="remember-form-rcl">
    <h4 class="form-title"><?php _e('Generation password','rcl'); ?></h4>

    <?php notice_form_rcl('remember'); ?>

    <?php if(!isset($_GET['success'])){ ?>
        <form action="<?php echo esc_url( site_url( 'wp-login.php?action=lostpassword', 'login_post' )); ?>" method="post">							
            <div class="form-block-rcl">
                <label><?php _e('Username or e-mail','rcl'); ?></label>
                <input required type="text" value="" name="user_login">								
            </div>
            <input type="submit" class="recall-button link-tab-form" name="remember-login" value="<?php _e('Send','rcl'); ?>">
            <a href="#" class="link-login-rcl link-tab-rcl "><?php _e('Login','rcl'); ?></a>
            <?php if($form!='sign'){ ?>
                <a href="#" class="link-register-rcl link-tab-rcl "><?php _e('Registration','rcl'); ?></a>
            <?php } ?>
            <?php echo wp_nonce_field('remember-key-rcl','_wpnonce',true,false); ?>
            <input type="hidden" name="redirect_to" value="<?php referer_url('remember'); ?>">
        </form>
    <?php } ?>
</div>

