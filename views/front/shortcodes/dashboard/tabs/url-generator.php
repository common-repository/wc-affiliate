<?php 
use Codexpert\WC_Affiliate\Helper;
$has_pro = Helper::has_pro();
?>
<div class="wf-dashboard-panel-head wf-dashboard-urlg-header">
	<div class="wf-dashboard-panel-head-title">
		<h3><?php _e( 'URL Generator', 'wc-affiliate' ) ?></h3>
	</div>
</div>

<div class="wf-url-generator-container">
	<form id="wf-url-generator-form" class="">
		<input type="hidden" name="action" value="wf-url-generator">
		<?php wp_nonce_field( 'wc-affiliate' ) ?>
		<div class="wf-url-generator-input-fields">
			<div class="wfug-inputs wfug-main-url">
				<label for="wf-url" class="wf-label"><?php _e( 'URL', 'wc-affiliate' ) ?><span>*</span></label>
				<input id="wf-url" class="wf-input" type="url" name="url" value="" required="required">
			</div>
			<div class="wfug-inputs">
				<label for="wf-campaign" class="wf-label"><?php _e( 'Campaign', 'wc-affiliate' ) ?></label>
				<input id="wf-campaign" class="wf-input" type="text" name="campaign" value="" >
			</div>
			<?php 
			if ( current_user_can('manage_options') && !$has_pro ) {		
				echo Helper::get_template( 'shortlink-generator', 'views/placeholders' );
			}
			else if( !current_user_can('manage_options') && !$has_pro){
				echo "<div></div>";
			}
			do_action( 'wc-affiliate-after_url_generator_form_inputs' );
			?>
			<div class="wfug-inputs wfug-submit">
				<input class="button wf-button wf-generator-button" type="submit" value="<?php _e( 'Generate', 'wc-affiliate' ) ?>">
			</div>
		</div>
	</form>

	<div class="wf-long-affiliate-url wf-urls" style="display: none;">
		<div class="wf-urls-container">
			<label><?php _e( 'Affiliate Link: ', 'wc-affiliate' ) ?></label>
			<input type="text" id="wf_long_url" name="wf_long_url" readonly>
			<button id="wf-copy-long-url" class="wf-url-copy button"><i class="far fa-copy"></i></button>
		</div>
	</div>
	<?php 
	if ( current_user_can('manage_options') && !$has_pro ) {
		echo Helper::get_template( 'shortlink-list', 'views/placeholders' );
	}
	do_action( 'wc-affiliate-after_url_generator_section' ) ?>
</div>


