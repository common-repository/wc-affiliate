<?php 
use Codexpert\WC_Affiliate\Helper;
if ( is_user_logged_in() ){
	// authenticate
	$_status = Helper::get_affiliate_status(); 
	if ( $_status != '' ) {
		$status  	= $_status != '' ? $_status : 'apply';
		$template 	= $status;
	    $template_path = 'views/front/authenticate';
	    echo Helper::get_template( $template, $template_path );
	    return;
	}
}

$first_name = $last_name = $user_name = $email = $website_url = $disabled = '';
$btn_text = __( 'Register', 'wc-affiliate' );
if ( is_user_logged_in() ) {
	$user_id 	= get_current_user_id();
	$user 		= get_userdata( $user_id );
	$first_name = $user->first_name;
	$last_name 	= $user->last_name;
	$user_name 	= $user->user_login;
	$email 		= $user->user_email;
	$website_url= $user->user_url;
	$btn_text	= __( 'Apply', 'wc-affiliate' );
	$disabled	= 'disabled';
}

$terms_url 			= Helper::get_option( 'wc_affiliate_basic', 'terms_url' );
$site_key 			= Helper::get_option( 'wc_affiliate_advanced', 'wc_affiliate_sitekey_recaptcha' );
$enable_recaptcha 	= Helper::get_option( 'wc_affiliate_advanced', 'wc_affiliate_enable_recaptcha' );

 ?>

<div class="wf-application-form-panel">
	<form id="wf-application-form" class="wf-form" action="">
		<input type="hidden" name="action" value="wf-register-user">
		<input type="hidden" name="_nonce" value="<?php echo wp_create_nonce(); ?>">

		<div class="wf-application-form-panel-content" style="grid-area: fname;">
			<label for="first-name" class="wf-label"><?php _e( 'First Name', 'wc-affiliate' ) ?><span class="wf-af-required">*</span></label>
			<input id="first-name" class="wf-input" type="text" name="first_name" value="<?php echo esc_attr( $first_name ); ?>" required="required">
		</div>
		<div class="wf-application-form-panel-content" style="grid-area: lname;">
			<label for="last-name" class="wf-label"><?php _e( 'Last Name', 'wc-affiliate' ) ?><span class="wf-af-required">*</span></label>
			<input id="last-name" class="wf-input" type="text" name="last_name" value="<?php echo esc_attr( $last_name ); ?>" required="required">
		</div>		
		<div class="wf-application-form-panel-content" style="grid-area: username;">
			<label for="user-name" class="wf-label"><?php _e( 'User Name', 'wc-affiliate' ) ?><span class="wf-af-required">*</span></label>
			<input id="user-name" class="wf-input" type="text" name="user_name" value="<?php echo esc_attr( $user_name ); ?>" required="required" <?php echo $disabled ?>>
			<div id="wf-uname-error" class="wf-red wf-error"></div>
		</div>		
		<div class="wf-application-form-panel-content" style="grid-area: email;">
			<label for="email" class="wf-label"><?php _e( 'Email', 'wc-affiliate' ) ?><span class="wf-af-required">*</span></label>
			<input id="email" class="wf-input" type="email" name="email" value="<?php echo esc_attr( $email ); ?>" required="required" <?php echo $disabled ?>>
			<div id="wf-email-error" class="wf-red wf-error"></div>
		</div>
		<div class="wf-application-form-panel-content" style="grid-area: website;">
			<label for="website-url" class="wf-label"><?php _e( 'Website URL', 'wc-affiliate' ) ?></label>
			<input id="website-url" class="wf-input" type="text" name="website_url" value="<?php echo esc_url( $website_url ); ?>">
		</div>
		<div class="wf-application-form-panel-content" style="grid-area: promotion;">
			<label for="promotion-method" class="wf-label"><?php _e( 'How will you promote us?', 'wc-affiliate' ) ?><span class="wf-af-required">*</span></label>
			<textarea id="promotion-method" name="promotion_method" rows="5" cols="30" required="required"></textarea>
		</div>

		<?php if( ! is_user_logged_in() ): ?>

		<div class="wf-application-form-panel-content" style="grid-area: password;">
			<label for="password" class="wf-label"><?php _e( 'Password', 'wc-affiliate' ) ?><span class="wf-af-required">*</span></label>
			<input id="password" class="wf-input" type="password" name="password" value="" required="required">
		</div>		
		<div class="wf-application-form-panel-content" style="grid-area: password2;">
			<label for="password2" class="wf-label"><?php _e( 'Confirm Password', 'wc-affiliate' ) ?><span class="wf-af-required">*</span></label>
			<input id="password2" class="wf-input" type="password" name="password2" value="" required="required">
			<div id="wf-pass-error" class="wf-red wf-error"></div>
		</div>

		<?php endif; ?>
		
		<div class="wf-application-form-panel-content" style="grid-area: terms;">
			<label for="terms-agree" class="wf-label">
			<input id="terms-agree" required="required" type="checkbox" name="terms_agree">
			<?php _e( 'Agree to our', 'wc-affiliate' ); ?> <a href="<?php echo esc_url( $terms_url ); ?>" target="_blank"><?php _e( 'Terms of Use', 'wc-affiliate' ); ?></a></label>
		</div>

		<?php if ( $enable_recaptcha ): ?>
			<div class="wf-application-recaptcha-panel" style="grid-area: recaptcha;">
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			</div>
		<?php endif; ?>

		<div class="wf-application-form-panel-button" style="grid-area: submit;">
			<input class="button wf-button" type="submit" value="<?php echo esc_attr( $btn_text ); ?>">
		</div>
	</form>
</div>