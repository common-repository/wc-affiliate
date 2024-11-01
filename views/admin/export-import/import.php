<div class="wf-import-export-tab" id="wf-tab-content-import">
	<form action="" id="wf-import-report-form">
		<?php wp_nonce_field( 'wc-affiliate' ); ?>
		<input type="hidden" name="action" value="wf-import-report">
		<p>
			<label for="csv"><?php _e( 'Choose File', 'wc-affiliate' ); ?></label>
			<input type="file" name="csv" id="csv">
		</p>
		<p>
			<input type="submit" class="button button-primary" id="wf-import-btn" value="Import">
		</p>
	</form>
</div>