<?php global $typeform; ?>
<div class="form-tab-rcl" id="remember-form-rcl">
    <h4 class="form-title"><?php _e('Generation password','wp-recall'); ?></h4>

    <?php rcl_notice_form('remember'); ?>

    <?php if(!isset($_GET['success'])){ ?>
        <form action="<?php echo wp_lostpassword_url(); ?>" method="post">
            <div class="form-block-rcl">
                <label><?php _e('Username or e-mail','wp-recall'); ?></label>
                <div class="default-field">
                    <span class="field-icon"><i class="fa fa-key"></i></span>
                    <input required type="text" value="" name="user_login">
                </div>
            </div>
            <?php do_action( 'lostpassword_form' ); ?>
            <div class="input-container">
                <input type="submit" class="recall-button link-tab-form" name="remember-login" value="<?php _e('Send','wp-recall'); ?>">
                <a href="#" class="link-login-rcl link-tab-rcl "><i class="fa fa-reply-all"></i><?php _e('Authorization','wp-recall'); ?></a>
                <?php if($typeform!='sign'){ ?>
                    <a href="#" class="link-register-rcl link-tab-rcl "><i class="fa fa-reply-all"></i><?php _e('Registration','wp-recall'); ?></a>
                <?php } ?>
                <?php echo wp_nonce_field('remember-key-rcl','_wpnonce',true,false); ?>
                <input type="hidden" name="redirect_to" value="<?php rcl_referer_url('remember'); ?>">
            </div>

        </form>
    <?php } ?>
</div>

