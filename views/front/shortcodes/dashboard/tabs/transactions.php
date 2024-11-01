<?php
use Codexpert\WC_Affiliate\Helper;
if ( ! function_exists( 'add_screen_option' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
}
if ( ! function_exists( 'render_list_table_columns_preferences' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php' );
}
$from				= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
$from_timestamp		= strtotime( $from );
$to					= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y' );
$to_timestamp		= strtotime( $to ) + DAY_IN_SECONDS - 1; // we need to consider that entire day
$per_page			= isset( $_GET['per_page'] ) ? (int)sanitize_text_field( $_GET['per_page'] ) : '';

$permalink 			= get_permalink();
$referrals 			= Helper::get_referrals();
$payables 			= [ 'paid' => 0, 'approved' => 0 ];

foreach ( $referrals as $key => $referral ) {
	if( !isset( $payables[ $referral->payment_status ] ) ){
		$payables[ $referral->payment_status ] = 0;
	}
	$payables[ $referral->payment_status ] += $referral->commission;
}

$user_id	= get_current_user_id();
$currency	= function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';

global $wpdb;

$data = [];

$transactions_table = "{$wpdb->prefix}wca_transactions";

if( is_multisite() ) {
    $blog_id = get_current_blog_id();
    $transactions_table = "{$wpdb->base_prefix}{$blog_id}_wca_transactions";
}

$user_id = get_current_user_id();

$transactions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$transactions_table` WHERE `affiliate` = %d AND `request_at` >= %d AND `request_at` < %d ORDER BY `request_at` DESC", $user_id, $from_timestamp, $to_timestamp ) );

$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

foreach ( $transactions as $transaction ) {
    $data[] = array(
        'payment_method'=> $transaction->payment_method,
        'amount'		=> $transaction->amount,
        'status'		=> $transaction->status,
        'txn_id'		=> $transaction->txn_id,
        'request_at'	=> date( $format, $transaction->request_at ),
        'process_at'	=> date( $format, $transaction->process_at ),
    );
}

/**
 * Config
 */
$config = [
	'per_page'		=> $per_page != '' ? $per_page : 10,
	'columns'		=> [
		'amount'		=> __( 'Amount', 'wc-affiliate' ),
		'payment_method'=> __( 'Payment Method', 'wc-affiliate' ),
		'txn_id'		=> __( 'Tax ID', 'wc-affiliate' ),
		'request_at'	=> __( 'Request Time', 'wc-affiliate' ),
		'process_at'	=> __( 'Process Time', 'wc-affiliate' ),
		'status'		=> __( 'Status', 'wc-affiliate' ),
	],
	'sortable'		=> [ 'payment_method', 'amount', 'status', 'txn_id', 'request_at', 'process_at' ],
	'orderby'		=> 'request_at',
	'order'			=> 'desc',
	'data'			=> $data,
];
$exp_disabled = '';
if ( !is_array( $data ) || empty( $data ) ) {
	$exp_disabled = 'disabled';
}
$_config 	= [ 'affiliate' => __( 'Affiliate', 'wc-affiliate' ) ] + $config['columns'];
unset( $_REQUEST['_wp_http_referer'] );
unset( $_REQUEST['action'] );
unset( $_REQUEST['per_page'] );
unset( $_REQUEST['action2'] );
$_REQUEST['page'] = sanitize_text_field( $_REQUEST['tab'] );
$_REQUEST['affiliate'] = get_current_user_id();
$total = (float)$payables['approved']+(float)$payables['paid'];
$transaction_cards = [
	'total' 	=> [
		'label' => __( 'Total ', 'wc-affiliate' ),
		'value'	=> "{$currency}{$total}",
		'color'	=> '#54a45d'
	],
	'paid' => [
		'label' => __( 'Paid ', 'wc-affiliate' ),
		'value' => "{$currency}{$payables['paid']}",
		'color'	=> '#f48d02'
	],
	'unpaid' 	=> [
		'label' => __( 'Unpaid ', 'wc-affiliate' ),
		'value'	=> "{$currency}{$payables['approved']}",
		'color'	=> '#2c00d5'
	],
];
?>

<div class="wf-dashboard-panel-head wf-dashboard-transactions-header">
	<div class="wf-dashboard-panel-head-title">
		<h3><?php _e( 'Transactions', 'wc-affiliate' ) ?></h3>
		<button class="button button-primary" id="wc-affiliate-export-report-btn" data-params='<?php echo serialize( $_REQUEST ) ?>' data-headings='<?php echo serialize( $_config ) ?>' data-name='transactions' <?php echo $exp_disabled; ?>><?php _e( 'Export Report', 'wc-affiliate' ) ?></button>
		<button class='wf-request-payout button button-primary'><?php  _e( 'Request Payout', 'wc-affiliate' ); ?></button>
	</div>
	<div class="wf-dashboard-panel-filter">
		<form id="wf-dashboard-transactions-filter" method="GET">
			<input type="hidden" name="tab" value="transactions" />
			<input class="datepicker" type="text" name="from" value="<?php echo esc_attr( $from ) ?>">
			<input class="datepicker" type="text" name="to" value="<?php echo esc_attr( $to ) ?>">
			<input class="wfd-perpage" type="number" name="per_page" value="<?php echo esc_attr( $per_page ) ?>" placeholder="<?php _e( 'Per Page', 'wc-affiliate' ) ?>">
			<input type="submit" value="<?php _e( 'Filter', 'wc-affiliate' ); ?>" class="button button-submit wf-button" >
		</form>
	</div>
</div>

<div class="wf-dashboard-transaction-cards">
	<?php 
	foreach ( $transaction_cards as $key => $card ) {
		echo "
			<div class='wf-dashboard-transaction-card' style='border-top:4px solid " . esc_attr( $card['color'] ) . "'>
				<div class='wf-dashboard-transaction-info' style='color:" . esc_attr( $card['color'] ) . "'>" . esc_html( $card['label'] ) . "</div>
				<div class='wf-dashboard-transaction-value' id='wf-" . esc_attr( $key ) . "-count'>" . esc_html( $card['value'] ) . "</div>
			</div>
		";
	} ?>	
</div>

<div class="wfd-list-table wf-transactions-panel">
	<form method="post">
		<?php

		$table = new Codexpert\Plugin\Table( $config );
		$table->prepare_items();
		$table->display();
		?>
	</form>
</div>
