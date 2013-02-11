<?php

if ( !defined( 'ABSPATH' ) )
	die( '-1' );

?>
<form method="post" class="register">

	<?php if ( get_option( 'wc_registration_email_for_username' ) == 'no' ) : ?>

		<p class="form-row form-row-first">
			<label for="reg_username"><?php _e('Username', 'wc_wishlist'); ?> <span class="required">*</span></label>
			<input type="text" class="input-text" name="username" id="reg_username" value="<?php if (isset($_POST['username'])) echo esc_attr($_POST['username']); ?>" />
		</p>

		<p class="form-row form-row-last">

	<?php else : ?>

		<p class="form-row form-row-wide">

	<?php endif; ?>

		<label for="reg_email"><?php _e('Email', 'wc_wishlist'); ?> <span class="required">*</span></label>
		<input type="email" class="input-text" name="email" id="reg_email" value="<?php if (isset($_POST['email'])) echo esc_attr($_POST['email']); ?>" />
	</p>

	<div class="clear"></div>

	<p class="form-row form-row-first">
		<label for="reg_password"><?php _e('Password', 'wc_wishlist'); ?> <span class="required">*</span></label>
		<input type="password" class="input-text" name="password" id="reg_password" value="<?php if (isset($_POST['password'])) echo esc_attr($_POST['password']); ?>" />
	</p>
	<p class="form-row form-row-last">
		<label for="reg_password2"><?php _e('Re-enter password', 'wc_wishlist'); ?> <span class="required">*</span></label>
		<input type="password" class="input-text" name="password2" id="reg_password2" value="<?php if (isset($_POST['password2'])) echo esc_attr($_POST['password2']); ?>" />
	</p>
	<div class="clear"></div>

	<!-- Spam Trap -->
	<div style="left:-999em; position:absolute;"><label for="trap">Anti-spam</label><input type="text" name="email_2" id="trap" /></div>

	<?php do_action( 'register_form' ); ?>

	<p class="form-row">
		<?php $WC->nonce_field('register', 'register') ?>
		<input type="submit" class="button" name="register" value="<?php _e('Register', 'wc_wishlist'); ?>" />
	</p>

</form>