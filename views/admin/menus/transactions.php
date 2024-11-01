<?php
use Codexpert\WC_Affiliate\Helper;
$from			= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
$to				= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y', current_time( 'timestamp' ) );
$txn_id			= isset( $_GET['txn'] ) ? sanitize_text_field( $_GET['txn']  ): '';
$affiliate		= isset( $_GET['affiliate'] ) ? (int)sanitize_text_field( $_GET['affiliate'] ) : '';
$per_page		= isset( $_GET['per_page'] ) ? (int)sanitize_text_field( $_GET['per_page'] ) : '';
$currency		= function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '[currency]';

$statuses		= Helper::get_transactions_statuses();
$admin_url		= admin_url( 'admin.php' );

/**
 * Prepare the data
 */
global $wpdb;
$data 				= [];
$transactions_table = "{$wpdb->prefix}wca_transactions";

if( is_multisite() ) {
    $blog_id 			= get_current_blog_id();
    $transactions_table = "{$wpdb->base_prefix}{$blog_id}_wca_transactions";
}

$sql = "SELECT * FROM $transactions_table WHERE 1 = 1";

if( isset( $_GET['status'] ) && array_key_exists( sanitize_text_field( $_GET['status'] ), $statuses ) ) {
	$status = sanitize_text_field( $_GET['status'] );
    $sql .= " AND `status` = '{$status}'";
}

if( $from && $to ) {
	$form_date 	= strtotime( $from );
	$to_date 	= strtotime( $to ) + DAY_IN_SECONDS - 1; // we need to consider that entire day
    $sql 	   .= " AND `process_at` >= '{$form_date}' AND `process_at` <= '{$to_date}'";
}

if( $txn_id ) {
    $sql .= " AND `txn_id` LIKE '%{$txn_id}%'";
}

if( $affiliate ) {
    $sql .= " AND `affiliate` = '{$affiliate}'";
}

$sql 		 .= " ORDER BY `id` DESC";
$transactions = $wpdb->get_results( $sql );
$format 	  = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

foreach ( $transactions as $txn ) {
    $data[] = [
    	'id'				=> $txn->id,
        'affiliate'			=> get_userdata( $txn->affiliate )->display_name,
        'amount'			=> $currency . $txn->amount,
        'payment_method'	=> $txn->payment_method,
        'txn_id'			=> $txn->txn_id,
        // 'request_at'		=> date( $format, $txn->request_at ),
        'request_at'		=> date( $format, $txn->process_at ),
        'process_at'		=> date( $format, $txn->process_at ),
        // 'status'			=> $txn->status,
        'action'			=>  '<a href="' . esc_url( add_query_arg( [ 'page' => 'transactions', 'transaction' => $txn->id ], $admin_url ) ) . '" class="button wf-edit-transaction">' . __( 'Edit', 'wc-affiliate' ) . '</a>',
    ];
}

/**
 * Config
 */
$config = [
	'id' 			=> 'transaction',
	'per_page'		=> $per_page != '' ? $per_page : 10,
	'columns'		=> [
		'affiliate'			=> __( 'Affiliate', 'wc-affiliate' ),
		'amount'			=> __( 'Amount', 'wc-affiliate' ),
		'payment_method'	=> __( 'Payment Method', 'wc-affiliate' ),
		'txn_id'			=> __( 'Transaction ID', 'wc-affiliate' ),
		'request_at'		=> __( 'Requested', 'wc-affiliate' ),
		'process_at'		=> __( 'Processed', 'wc-affiliate' ),
		// 'status'			=> __( 'Status', 'wc-affiliate' ),
		'action'			=> __( 'Action', 'wc-affiliate' ),
	],
	'orderby'		=> 'process_at',
	'order'			=> 'desc',
	'data'			=> $data,
	'bulk_actions'	=> [],
];

$disabled = '';
if ( !is_array( $data ) || empty( $data ) ) {
	$disabled = 'disabled';
}
$_config 	= $config['columns'];
unset( $_REQUEST['_wp_http_referer'] );
unset( $_REQUEST['action'] );
unset( $_REQUEST['per_page'] );
unset( $_REQUEST['action2'] );
?>
<div class="wrap wca-wrap">
	<?php
	if( isset( $_GET['transaction'] ) && sanitize_text_field( $_GET['transaction'] ) != '' ) :

		echo '<h2>';
		if ( $_GET['transaction'] == 'new' ) {
			_e( 'New transaction', 'wc-affiliate' );
		}else{
			// Translators: %d is the transaction ID number.
			echo sprintf( __( 'Transaction #%d:', 'wc-affiliate' ), sanitize_text_field( $_GET['transaction'] ) );
		}
		echo '</h2>';
		do_action( 'wc-affiliate-transaction-form' );

	else:
		echo '<h2>';
		_e( 'Transactions ', 'wc-affiliate' );
	?>		
		<button class="button button-primary" id="wc-affiliate-export-report-btn" data-params='<?php echo serialize( $_REQUEST ) ?>' data-headings='<?php echo serialize( $_config ) ?>' data-name='transactions' <?php echo $disabled; ?>><?php _e( 'Export Report', 'wc-affiliate' ) ?></button>
	</h2>
	<div class="wf-wrap">

		<form method="GET">
			<input type="hidden" name="page" value="transactions">
			<?php		
			$table = new Codexpert\Plugin\Table( $config );
			$table->views();
			$table->prepare_items();
			$table->display();
			?>
		</form>
	</div>
<?php endif; ?>
</div>