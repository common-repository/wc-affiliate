<?php 
use Codexpert\WC_Affiliate\Helper;
?>
<div class="wca-migration-tabs">
	<button class="wca-migration-tab active" data-tab='export'><?php _e( 'Export', 'wc-affiliate' ) ?></button>
	<button class="wca-migration-tab" data-tab='import'><?php _e( 'Import', 'wc-affiliate' ) ?></button>
</div>
<div class="wc-affiliate-export-import-container">
	<div id="wca-export-section" class="wca-ei-section">
		<h3 class="wca-ei-title"><?php _e( 'Export', 'wc-affiliate' ) ?></h3>
		<div class="wca-export-button-area">
			<button id="wca-export-button" class="button"><span class="wca-export-button-text"><?php _e( 'Export', 'wc-affiliate' ) ?></span>
				<div class="wca-expo-ellipsis" style="display:none;"><div></div><div></div><div></div><div></div></div>
			</button>
		</div>
	</div>
	<div id="wca-import-section" class="wca-ei-section" style="display:none">
		<h3 class="wca-ei-title"><?php _e( 'Import', 'wc-affiliate' ) ?></h3>
		<form id="wca-import-form" method="post">
			<select id="wca-import-from" name="import_from" class="wca-ei-input">
				<option value=""><?php _e( 'Import Data From', 'wc-affiliate' ) ?></option>
				<option value="wc-affiliate"><?php _e( 'WC Affiliate', 'wc-affiliate' ) ?></option>
				<option value="wp-affiliate"><?php _e( 'AffiliateWP', 'wc-affiliate' ) ?></option>
				<option value="affiliate-candy"><?php _e( 'ReferralCandy', 'wc-affiliate' ) ?></option>
			</select>
			<input id="wca-import-file" class="wca-ei-input" type="file" name="import_file">
			<button id="wca-import-submit" class="button button-primary"><span class="wca-export-button-text"><?php _e( 'Import', 'wc-affiliate' ) ?></span>
				<div class="wca-expo-ellipsis" style="display:none;"><div></div><div></div><div></div><div></div></div>
			</button>
		</form>
	</div>
</div>