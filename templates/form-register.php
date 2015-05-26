<?php global $typeform;
if($typeform=='register') $f_reg = 'style="display:block;"'; ?>
<div class="form-tab-rcl" id="register-form-rcl" <?php echo $f_reg; ?>>
    <h4 class="form-title"><?php _e('Registration','rcl'); ?></h4>

    <?php rcl_notice_form('register'); ?>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-block-rcl">
            <label><?php _e('Nickname','rcl'); ?> <span class="required">*</span></label>
            <input required type="text" value="" name="login-user">
        </div>
        <div class="form-block-rcl">
            <label><?php _e('E-mail','rcl'); ?> <span class="required">*</span></label>
            <input required type="email" value="" name="email-user">
        </div>

        <?php do_action( 'register_form' ); ?>

        <div class="input-container">
            <input type="submit" class="recall-button" name="submit-register" value="<?php _e('Send','rcl'); ?>">
            <?php if(!$typeform){ ?>
                <a href="#" class="link-login-rcl link-tab-rcl"><?php _e('Authorization ','rcl'); ?></a>
            <?php } ?>
            <?php echo wp_nonce_field('register-key-rcl','_wpnonce',true,false); ?>
            <input type="hidden" name="referer_rcl" value="<?php rcl_referer_url(); ?>">
        </div>
    </form>
</div>
