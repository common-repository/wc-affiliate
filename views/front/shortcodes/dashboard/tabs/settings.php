<?php
use Codexpert\WC_Affiliate\Helper;

$user_id 		= get_current_user_id();
$user 			= get_userdata( $user_id );

$first_name 	= $user->first_name;
$last_name 		= $user->last_name;
$email 			= $user->user_email;
$city 			= get_user_meta( $user_id, '_wc_affiliate_city', true );
$state 			= get_user_meta( $user_id, '_wc_affiliate_state', true );
$_country 		= get_user_meta( $user_id, '_wc_affiliate_country', true );
$avatar 		= get_user_meta( $user_id, '_wc_affiliate_avatar', true );
$_payout_method = get_user_meta( $user_id, '_wc_affiliate_payout_method', true );
$payout_methods = Helper::get_option( 'wc_affiliate_payout', 'enable_payout_methods', ['manual' ]);
$mannual_payment= get_user_meta( $user_id, '_wc_affiliate_mannual_payment', true );

$countries	= class_exists( 'WC_Countries' ) ? ( new \WC_Countries )->get_countries() : [];
?>
<div class="wf-dashboard-panel-head wf-dashboard-settings-header">
	<div class="wf-dashboard-panel-title">
		<h3><?php _e( 'Settings', 'wc-affiliate' ) ?></h3>
	</div>
</div>

<form action="" id="wf-user-settings" >
	<div class="wf-setting-panel">
		<input type="hidden" name="action" value="wf-update-user">
		<input type="hidden" name="_nonce" value="<?php echo wp_create_nonce(); ?>">
		<div class="wf-setting-panel-content wca-avatar">
			<div class="wf-setting-avatar wf-text-center" id="wf-setting-avatar-area">
			    <input type="hidden" name="image_url" id="image_url" class="regular-text" value="<?php echo esc_url( $avatar ); ?>">
			    <div id="wf-avatar">
			    	<img src="<?php echo get_avatar_url( get_current_user_id() ); ?>" alt="">
			    	<button type="button" name="upload-btn" id="wf-upload-btn"><i class="fas fa-pen"></i></button>
			    </div>
			</div>
		</div>
		<div class="wf-setting-panel-content wca-fname">
			<label for=""><?php _e( 'First Name', 'wc-affiliate' ); ?></label>
			<input class="" type="text" name="first_name" value="<?php echo esc_html( $first_name ); ?>" placeholder="<?php _e( 'First Name', 'wc-affiliate' ); ?>">
		</div>
		<div class="wf-setting-panel-content wca-lname">
			<label for=""><?php _e( 'Last Name', 'wc-affiliate' ); ?></label>
			<input class="" type="text" name="last_name" value="<?php echo esc_html( $last_name ); ?>" placeholder="<?php _e( 'Last Name', 'wc-affiliate' ); ?>">
		</div>
		<div class="wf-setting-panel-content wca-city">
			<label for=""><?php _e( 'City', 'wc-affiliate' ); ?></label>
			<input class="" type="text" name="city" value="<?php echo esc_html( $city ); ?>" placeholder="<?php _e( 'City Name', 'wc-affiliate' ); ?>">
		</div>
		<div class="wf-setting-panel-content wca-state">
			<label for=""><?php _e( 'State', 'wc-affiliate' ); ?></label>
			<input class="" type="text" name="state" value="<?php echo esc_html( $state ); ?>" placeholder="<?php _e( 'State Name', 'wc-affiliate' ); ?>">
		</div>
		<div class="wf-setting-panel-content wca-country">
			<label for=""><?php _e( 'Country', 'wc-affiliate' ); ?></label>
			<select name="country" id="">
				<option value=""><?php _e( 'Select a country', 'wc-affiliate' ); ?></option>
				<?php 
				foreach ( $countries as $key => $country ) {
					echo "<option value='{$key}' " . selected( $key, $_country, false ) . " >{$country}</option>";
				}
				?>
			</select>
		</div>
		<div class="wf-setting-panel-content wca-email">
			<label for=""><?php _e( 'Contact Email', 'wc-affiliate' ); ?></label>
			<input class="" type="email" name="email" value="<?php echo esc_html( $email ); ?>" placeholder="<?php _e( 'Your email', 'wc-affiliate' ); ?>">
		</div>
		<div class="wf-setting-panel-content wca-payout">
			<label for=""><?php _e( 'Payout Method', 'wc-affiliate' ); ?></label>
			<select name="payout_method" id="wf-payout-method">
				<option value=""><?php _e( 'Select a Payout Method', 'wc-affiliate' ); ?></option>
				<?php 
				if ( $payout_methods ) {
					foreach ( $payout_methods as $payout_method ) {
						$method = ucwords( $payout_method );
						echo "<option value='{$payout_method}' " . selected( $payout_method, $_payout_method, false ) . ">{$method}</option>";
					}
				}
				?>
			</select>
		</div>
		<div class="wf-setting-panel-content wca-mannual-payment">
			<div class="wf-setting-panel-content-mannual wf-setting-payout-method" style="display: none;">
				<label for=""><?php _e( 'Give your account details', 'wc-affiliate' ); ?></label>
				<textarea class="account_info" name="account_info"><?php echo $mannual_payment ? $mannual_payment : ''; ?></textarea>
			</div>
			<?php do_action( 'wc-affiliate-profile_settings_payment_methods' ) ?>
		</div>
		<div class="wf-setting-panel-content wca-doaction">
			<?php do_action( 'wc-affiliate-profile_settings_before_password' ) ?>
		</div>
		<div class="wf-setting-panel-content wca-reset-password">
			<a class="wf-setting-password-toggl" id="wf-setting-password-toggl" href="#"><?php _e( 'Change Password', 'wc-affiliate' ); ?></a>
		</div>
		<div class="wf-setting-panel-content wf-setting-password-area" id="wf-setting-password-area" style="display:none;">
			<div class="wf-setting-panel-content">
				<label for=""><?php _e( 'New Password', 'wc-affiliate' ); ?></label>
				<input class="" type="password" name="password" placeholder="<?php _e( 'New Password', 'wc-affiliate' ); ?>">
			</div>
			<div class="wf-setting-panel-content">
				<label for=""><?php _e( 'Retype Password', 'wc-affiliate' ); ?></label>
				<input class="" type="password" name="password2" placeholder="<?php _e( 'Retype Password', 'wc-affiliate' ); ?>">
				<p id="wf-upass-error"></p>
			</div>
		</div>
		<div class="wf-setting-panel-content wf-setting-update-button-area">
			<input class="wf-button" type="submit" value="<?php _e( 'Update Profile', 'wc-affiliate' ); ?>">
		</div>
	</div>
</form>