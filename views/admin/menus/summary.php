<?php
use Codexpert\WC_Affiliate\Helper;

// $_is_active = $plugin['license']->_is_active();

$from	= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
$to		= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y', current_time( 'timestamp' ) );


$affiliates_count 	= Helper::item_count( 'affiliates' );
$visits_count 		= Helper::item_count( 'visits' );
$referrals_count 	= Helper::item_count( 'referrals' );
$referral_amounts 	= Helper::get_transactions_amount();
$currency 			= function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '[currency]';
$amounts 			= [ 'paid' => 0, 'unpaid' => 0 ];

foreach ( $referral_amounts as $key => $referral ) {
	
	if( !isset( $amounts[ $referral->status ] ) ) {
		$amounts[ $referral->status ] = 0;
	}

	$amounts[ $referral->status ] += $referral->amount;
}
global $wpdb;
$paid_commission	= $amounts['paid'];
$unpaid_commission	= Helper::get_referrals_amount( 'approved' );
$total_commission	= $paid_commission + $unpaid_commission;

$reports = apply_filters( 'wc-affiliate-reports', [
	'visits'					=> __( 'Visits', 'wc-affiliate' ),
	'referrals'					=> __( 'Referrals', 'wc-affiliate' ),
	'earnings'					=> __( 'Earnings', 'wc-affiliate' ),
	'visits-referrals-earnings'	=> __( 'Visits vs. Referrals vs. Earnings', 'wc-affiliate' ),
	'conversions'				=> __( 'Conversions', 'wc-affiliate' ),
	'products'					=> __( 'Top Seller Products', 'wc-affiliate' ),
	'affiliates'				=> __( 'Top Affiliates', 'wc-affiliate' ),
	'landingpages'				=> __( 'Top Landing Pages', 'wc-affiliate' ),
	'referralurls'				=> __( 'Top Referrer URLs', 'wc-affiliate' ),
] );

if ( isset( $_GET['affiliate'] ) && sanitize_text_field( $_GET['affiliate'] ) != "" ) {
	unset( $reports['affiliates'] );
}
?>
<div class="wrap wca-wrap">
	<?php
		$admin_url	= admin_url( 'admin.php' );
		if( isset( $_GET['affiliate'] ) && ( $_user = get_userdata( (int)sanitize_text_field( $_GET['affiliate'] ) ) ) !== false ) :

			echo '<h2>';
			echo sprintf( __( 'Affiliate #%d: %s', 'wc-affiliate' ), $_user->ID, $_user->display_name );
			echo ' <a href="' . add_query_arg( 'page', 'affiliates', $admin_url ) . '" class="button wf-al-reports">' . __( 'All Affiliates', 'wc-affiliate' ) . '</a>';
			echo '</h2>';

		endif;
	?>

	<h2><?php _e( 'Summary', 'wc-affiliate' ) ?></h2>

	<div class="wf-transaction-summary-panel">
		<div class="cx-row">
			<div class="cx-col-sm-12">
				<div class="wf-dashboards" id="wf-referral-earning">
					<div class="cx-row">
						<div class="cx-col-sm-6 cx-col-md-2">
							<div class="wf-dashboard-content">
								<div class="wf-dashboard-content-col active-affiliates">
									<div class="wf-icon">
										<img src="<?php echo esc_url( plugins_url( 'assets/img/active-affiliates.png', WCAFFILIATE ) ); ?>">
									</div>
									<?php printf( '<div class="wf-value">%d</div>', $affiliates_count ); ?>
									<div class="wf-info"><?php _e( 'Active Affiliates', 'wc-affiliate' ); ?></div>
								</div>
							</div>
						</div>
						<div class="cx-col-sm-6 cx-col-md-2">
							<div class="wf-dashboard-content">
								<div class="wf-dashboard-content-col total-visits">
									<div class="wf-icon">
										<img src="<?php echo esc_url( plugins_url( 'assets/img/total-visits.png', WCAFFILIATE ) ); ?>">
									</div>
									<?php printf( '<div class="wf-value">%d</div>', $visits_count ); ?>
									<div class="wf-info"><?php _e( 'Total Visits', 'wc-affiliate' ); ?></div>
								</div>
							</div>
						</div>
						<div class="cx-col-sm-6 cx-col-md-2">
							<div class="wf-dashboard-content">
								<div class="wf-dashboard-content-col total-referrals">
									<div class="wf-icon">
										<img src="<?php echo esc_url( plugins_url( 'assets/img/total-referrals.png', WCAFFILIATE ) ); ?>">
									</div>
									<?php printf( '<div class="wf-value">%d</div>', $referrals_count ); ?>
									<div class="wf-info"><?php _e( 'Total Referrals', 'wc-affiliate' ); ?></div>
								</div>
							</div>
						</div>
						<div class="cx-col-sm-6 cx-col-md-2">
							<div class="wf-dashboard-content">
								<div class="wf-dashboard-content-col total-commissions">
									<div class="wf-icon">
										<img src="<?php echo esc_url( plugins_url( 'assets/img/total-commissions.png', WCAFFILIATE ) ); ?>">
									</div>
									<?php printf( '<div class="wf-value" >%s%.2f</div>', $currency, $total_commission ); ?>
									<div class="wf-info"><?php _e( 'Total Commissions', 'wc-affiliate' ); ?></div>
								</div>
							</div>
						</div>
						<div class="cx-col-sm-6 cx-col-md-2">
							<div class="wf-dashboard-content">
								<div class="wf-dashboard-content-col paid-commissions">
									<div class="wf-icon">
										<img src="<?php echo esc_url( plugins_url( 'assets/img/paid-commissions.png', WCAFFILIATE ) ); ?>">
									</div>
									<?php printf( '<div class="wf-value" >%s%.2f</div>', $currency, $paid_commission ); ?>
									<div class="wf-info"><?php _e( 'Paid Commissions', 'wc-affiliate' ); ?></div>
								</div>
							</div>
						</div>
						<div class="cx-col-sm-6 cx-col-md-2">
							<div class="wf-dashboard-content">
								<div class="wf-dashboard-content-col unpaid-commissions">
									<div class="wf-icon">
										<img src="<?php echo esc_url( plugins_url( 'assets/img/unpaid-commissions.png', WCAFFILIATE ) ); ?>">
									</div>
									<?php printf( '<div class="wf-value" >%s%.2f</div>', $currency, $unpaid_commission ); ?>
									<div class="wf-info"><?php _e( 'Unpaid Commissions', 'wc-affiliate' ); ?></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div id="wf-filter-wrap">
		<form method="get">
			<input type="hidden" name="page" value="wc-affiliate" />
			<select name='affiliate' class="input-hero">
				<option value=""><?php _e( 'All Affiliates', 'wc-affiliate' ) ?></option>
				<?php 
				foreach ( Helper::get_affiliates_raw( [ 'active', 'rejected' ] ) as $affiliate ) {
					$user = get_userdata( $affiliate->user_id );
					echo "<option value='{$user->ID}' " . selected( isset( $_GET['affiliate'] ) ? (int)sanitize_text_field( $_GET['affiliate'] ) : '', $user->ID ) . ">{$user->display_name}</option>";
				}
				?>
			</select>
			<input type="text" name="from" value="<?php echo esc_html( $from ); ?>" class="input-hero datepicker" />
			<input type="text" name="to" value="<?php echo esc_html( $to ); ?>" class="input-hero datepicker" />
			<input type="submit" value="<?php _e( 'Filter', 'wc-affiliate' ) ?>" class="button button-primary button-hero wf-button" />
		</form>
	</div>

	<div class="wf-reports meta-box-sortables ui-sortable">
		<?php
		// $pro_overlay = $_is_active != false ? '' : "<a href='" . admin_url( 'admin.php?page=wc-affiliate-settings#wc_affiliate_license' ) . "'><div class='wf-pro-overlay'></div></a>";
		foreach ( $reports as $key => $label ) {
			echo "
			<div id='wf-{$key}-wrap' class='postbox wf-report'>
				<h2 class='hndle ui-sortable-handle'><span>{$label}</span></h2>
				<div id='wf-{$key}' class='inside'></div>
				"; 
			do_action( 'wc-affiliate-after_chart', $key, $label );
			echo "</div>";
		}
		?>
	</div>
</div>