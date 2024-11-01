<?php

$reports = apply_filters( 'wc-affiliate-reports', [
	'visits'					=> __( 'Visits', 'wc-affiliate' ),
	'referrals'					=> __( 'Referrals', 'wc-affiliate' ),
	'earnings'					=> __( 'Earnings', 'wc-affiliate' ),
	'visits-referrals-earnings'	=> __( 'Visits vs. Referrals vs. Earnings', 'wc-affiliate' ),
	'conversions'				=> __( 'Conversions', 'wc-affiliate' ),
	'products'					=> __( 'Top Seller Products', 'wc-affiliate' ),
	'landingpages'				=> __( 'Top Landing Pages', 'wc-affiliate' ),
	'referralurls'				=> __( 'Top Referrer URLs', 'wc-affiliate' ),
] );

echo '<div class="wf-reports meta-box-sortables ui-sortable">';
foreach ( $reports as $key => $label ) {
	echo "
	<div id='wf-{$key}-wrap' class='postbox wf-report'>
		<h2 class='handle ui-sortable-handle'><span>{$label}</span></h2>
		<div id='wf-{$key}' class='inside'></div>
	</div>";
}
echo '</div>';