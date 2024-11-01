<div class="wf-login-tabs">
	<div class="wf-tab-btns">
	  <button class="wf-tab-btn active" data-tab="login"><?php _e( 'Login', 'wc-affiliate' ) ?></button>
	  <button class="wf-tab-btn" data-tab="register"><?php _e( 'Registration', 'wc-affiliate' ) ?></button>
	</div>
	<div id="wf-login-form" class="wf-tab-content active">
		<?php woocommerce_login_form(); ?>
	</div>

	<div id="wf-register-form" class="wf-tab-content">
	  <?php echo do_shortcode( "[wc-affiliate-application-form]" ); ?>
	</div>
</div>

