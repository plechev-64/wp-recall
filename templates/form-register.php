<?php global $typeform;
$f_reg = ($typeform=='register')? 'style="display:block;"': ''; ?>
<div class="form-tab-rcl" id="register-form-rcl" <?php echo $f_reg; ?>>
    <h4 class="form-title"><?php _e('Registration','wp-recall'); ?></h4>

    <?php rcl_notice_form('register'); ?>

    <form action="<?php rcl_form_action('register'); ?>" method="post" enctype="multipart/form-data">
        <div class="form-block-rcl">
            <label><?php _e('Login','wp-recall'); ?> <span class="required">*</span></label>
            <div class="default-field">
                <span class="field-icon"><i class="fa fa-user"></i></span>
                <input required type="text" value="" name="user_login" id="login-user">
            </div>
        </div>
        <div class="form-block-rcl">
            <label><?php _e('E-mail','wp-recall'); ?> <span class="required">*</span></label>
            <div class="default-field">
                <span class="field-icon"><i class="fa fa-at"></i></span>
                <input required type="email" value="" name="user_email" id="email-user">
            </div>
        </div>

        <?php do_action( 'register_form' ); ?>

        <div class="input-container">
            <input type="submit" class="recall-button" name="submit-register" value="<?php _e('Send','wp-recall'); ?>">
            <?php if(!$typeform){ ?>
                <a href="#" class="link-login-rcl link-tab-rcl"><i class="fa fa-reply-all"></i><?php _e('Authorization ','wp-recall'); ?></a>
            <?php } ?>
            <?php echo wp_nonce_field('register-key-rcl','_wpnonce',true,false); ?>
            <input type="hidden" name="redirect_to" value="<?php rcl_referer_url('register'); ?>">
        </div>
    </form>
</div>
