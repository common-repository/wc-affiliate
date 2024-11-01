<?php
use Codexpert\WC_Affiliate\Helper;

$from	= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
$to		= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y', current_time( 'timestamp' ) );

$permalink = get_permalink();
$summary_cards = [
	'visits' 	=> [
		'label' => __( 'Visits ', 'wc-affiliate' ),
		'color'	=> '#54a45d'
	],
	'referrals' => [
		'label' => __( 'Referrals ', 'wc-affiliate' ),
		'color'	=> '#f48d02'
	],
	'earnings' 	=> [
		'label' => __( 'Earnings ', 'wc-affiliate' ),
		'color'	=> '#2c00d5'
	],
];
$summary_charts = [
	'visits-referrals-earnings' => __( 'Visits vs. Referrals vs. Earnings', 'wc-affiliate' ),
	'visits' 		=> __( 'Visits', 'wc-affiliate' ),
	'referrals' 	=> __( 'Referrals', 'wc-affiliate' ),
	'earnings' 		=> __( 'Earnings', 'wc-affiliate' ),
	'conversions' 	=> __( 'Conversions', 'wc-affiliate' ),
	'landingpages' 	=> __( 'Top Landing Pages', 'wc-affiliate' ),
	'referralurls' 	=> __( 'Top Referrer URLs', 'wc-affiliate' ),
];
?>
<div class="wf-dashboard-panel-head">
	<div class="wf-dashboard-panel-title">
		<h3><?php _e( 'Dashboard Summary', 'wc-affiliate' ) ?></h3>
	</div>
	<div class="wf-dashboard-panel-filter">
		<form id="wf-dashboard-summary-filter" method="GET">
			<input class="datepicker" type="text" name="from" value="<?php echo esc_attr( $from ) ?>">
			<input class="datepicker" type="text" name="to" value="<?php echo esc_attr( $to ) ?>">
			<input type="submit" value="<?php _e( 'Filter', 'wc-affiliate' ); ?>" class="button button-submit wf-button" >
		</form>
	</div>
</div>

<div class="wf-dashboard-summary-cards">
	<?php 
	$icon_url = plugins_url( "/assets/img/", WCAFFILIATE );
	foreach ( $summary_cards as $key => $card ) {
		echo "
			<div class='wf-dashboard-summary-card' style='border-top:4px solid " . esc_attr( $card['color'] ) . "'>
				<div class='wf-dashboard-summary-img'><img src='" . esc_url( $icon_url.$key ) . ".png' /></div>
				<div class='wf-dashboard-summary-info' style='color:" . esc_attr( $card['color'] ) . "'>" . esc_html( $card['label'] ) . "</div>
				<div class='wf-dashboard-summary-value' id='wf-" . esc_attr( $key ) . "-count'></div>
			</div>
		";
	} ?>	
</div>

<div class="wf-dashboard-charts">
	<?php 
	foreach ( $summary_charts as $key => $label ) {
		echo "
			<div class='wf-dashboard-chart' style='grid-area: " . esc_attr( $key ) . "'>
				<h4>" . esc_html( $label ) . "</h4>
				<div id='wf-" . esc_attr( $key ) . "'></div>
			</div>
		"; 	
	}
	?>
</div>