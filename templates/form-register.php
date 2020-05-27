<?php
global $typeform;
$f_reg = ($typeform == 'register') ? 'style="display:block;"' : '';
?>

<div class="form-tab-rcl" id="register-form-rcl" <?php echo $f_reg; ?>>
	<div class="form_head">
		<div class="form_auth"><?php if ( ! $typeform ) { ?><a href="#" class="link-login-rcl link-tab-rcl"><?php _e( 'Authorization ', 'wp-recall' ); ?></a><?php } ?></div>
		<div class="form_reg form_active"><?php _e( 'Registration', 'wp-recall' ); ?></div>
	</div>

	<div class="form-block-rcl"><?php rcl_notice_form( 'register' ); ?></div>

	<?php $user_login	 = (isset( $_REQUEST['user_login'] )) ? wp_strip_all_tags( $_REQUEST['user_login'], 0 ) : ''; ?>
	<?php $user_email	 = (isset( $_REQUEST['user_email'] )) ? wp_strip_all_tags( $_REQUEST['user_email'], 0 ) : ''; ?>

	<form action="<?php rcl_form_action( 'register' ); ?>" method="post" enctype="multipart/form-data">
		<div class="form-block-rcl default-field">
			<input required type="text" placeholder="<?php _e( 'Login', 'wp-recall' ); ?>" value="<?php echo $user_login; ?>" name="user_login" id="login-user">
			<i class="rcli fa-user"></i>
			<span class="required">*</span>
		</div>
		<div class="form-block-rcl default-field">
			<input required type="email" placeholder="<?php _e( 'E-mail', 'wp-recall' ); ?>" value="<?php echo $user_email; ?>" name="user_email" id="email-user">
			<i class="rcli fa-at"></i>
			<span class="required">*</span>
		</div>
		<div class="form-block-rcl form_extend">
			<?php do_action( 'register_form' ); ?>
		</div>
		<div class="form-block-rcl">
			<?php
			echo rcl_get_button( array(
				'label'	 => __( 'Signup', 'wp-recall' ),
				'submit' => true
			) );
			?>
			<?php echo wp_nonce_field( 'register-key-rcl', 'register_wpnonce', true, false ); ?>
			<input type="hidden" name="redirect_to" value="<?php rcl_referer_url( 'register' ); ?>">
			<input type="hidden" class="recall-button" name="submit-register" value="1">
		</div>
	</form>
</div>