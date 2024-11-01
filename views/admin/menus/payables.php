<?php
use Codexpert\WC_Affiliate\Helper;
$from			= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
$to				= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y', current_time( 'timestamp' ) );
$txn_id			= isset( $_GET['txn'] ) ? sanitize_text_field( $_GET['txn'] ) : '';
$affiliate		= isset( $_GET['affiliate'] ) ? (int)sanitize_text_field( $_GET['affiliate'] ) : '';
$per_page		= isset( $_GET['per_page'] ) ? (int)sanitize_text_field( $_GET['per_page'] ) : '';
$currency		= function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '[currency]';

$data			= [];

$affiliates 	= Helper::get_payable_affiliates( $affiliate, $from, $to );
$format 		= get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
$payout_amount 	= Helper::get_option( 'wc_affiliate_payout', 'payout_amount', 50 );

if ( count( $affiliates ) > 0 ){
	foreach ( $affiliates as $affiliate_id => $commission ) {
		$user 			= get_userdata( $affiliate_id );
		$requested 		= get_user_meta( $affiliate_id, '_wc-affiliate-applied_payout', true );
		$payout_option 	= get_user_meta( $affiliate_id, '_wc_affiliate_payout_method', true );
		$is_applied 	= $requested ? date( $format, $requested ) : __( 'No', 'wc-affiliate' );		
		$acc_info 		= get_user_meta( $affiliate_id, '_wc_affiliate_mannual_payment', true );
		$payout_option  = $payout_option == 'mannual' && $acc_info ? "<div>Mannual <button class='wca-view-acc-info-btn'>" . __( 'View Details', 'wc-affiliate' ) . "</button><div class='wca-acc-info'>{$acc_info}</div></div>" : ucwords( $payout_option );

		if ( $commission >= $payout_amount ) {
			$data[] = [
				'id'			=> $affiliate_id,
				'affiliate_id'	=> "#{$affiliate_id}",
				'name'			=> $user->display_name,
				'amount'		=> $currency . $commission,
				'applied'		=> $is_applied,
				'payout_option'	=> $payout_option,
				'action'		=> "<button class='wf-payout button button-primary' data-affiliate='" . esc_attr( $affiliate_id ) . "' >" . __( 'Mark Paid', 'wc-affiliate' ) . "</button>",
			];
		}
	}
}

/**
 * Config
 */
$config = [
	'id'			=> 'payables',
	'per_page'		=> $per_page != '' ? $per_page : 10,
	'columns'		=> [
		'affiliate_id'	=> __( 'Affiliate ID', 'wc-affiliate' ),
		'name'			=> __( 'Name', 'wc-affiliate' ),
		'amount'		=> __( 'Payable Amount', 'wc-affiliate' ),
		'applied'		=> __( 'Applied for Payout?', 'wc-affiliate' ),
		'payout_option'		=> __( 'Payout Option', 'wc-affiliate' ),
		'action'		=> __( 'Action', 'wc-affiliate' ),
	],
	'sortable'		=> [ 'affiliate_id', 'name', 'status', 'amount', 'applied', 'payout_option' ],
	'orderby'		=> 'affiliate_id',
	'order'			=> 'desc',
	'data'			=> $data,
	'bulk_actions'	=> [],
];
$disabled = '';
if ( !is_array( $data ) || empty( $data ) ) {
	$disabled = 'disabled';
}
$_config 	= $config['columns'];
unset( $_config['action'] );
unset( $_REQUEST['_wp_http_referer'] );
unset( $_REQUEST['action'] );
unset( $_REQUEST['per_page'] );
unset( $_REQUEST['action2'] );
?>
<div class="wrap wca-wrap">
	<h2>
		<?php esc_html_e( 'Payables', 'wc-affiliate' ); ?>
		<button class="button button-primary" id="wc-affiliate-export-report-btn" data-params='<?php echo esc_attr( serialize( $_REQUEST ) ); ?>' data-headings='<?php echo esc_attr( serialize( $_config ) ); ?>' data-name='payables' <?php echo esc_attr( $disabled ); ?>>
		    <?php esc_html_e( 'Export Report', 'wc-affiliate' ); ?>
		</button>
	</h2>
	<div class="wf-wrap">
		<form method="GET">
			<input type="hidden" name="page" value="payables">
			<?php 
			$table = new Codexpert\Plugin\Table( $config );
			$table->views();
			$table->prepare_items();
			$table->display();
			?>
		</form>
	</div>
</div>