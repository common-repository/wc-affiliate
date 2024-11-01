<?php
use Codexpert\WC_Affiliate\Helper;
echo "<div class='wf-generate-shortlink-demo'>";
echo Helper::pro_notice(); ?>
<div class="wf-generate-shortlink">
	<label for="wf-enable-shortlink" class=""><?php _e( 'Create Shortlink: ', 'wc-affiliate' ) ?></label>
	<input id="wf-enable-shortlink" type="checkbox" name="create_shortlink"> 
	<div class="wf-shortlink-inputs" > 					
		<div id="wf-shorten-fixed">
			<?php echo home_url( wc_affiliate_redirection_base() ); ?>
			<?php echo "<span class='wf-shorten-part'>kihjhh</span>"; ?>
		</div>
		<span id="wf-shortlink-error"></span>
	</div>
</div>
</div>