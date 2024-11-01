<?php 
use Codexpert\WC_Affiliate\Helper;
$affiliate = $payment_method = $amount = $txn_id = $row_id  = $status = '';
$btn_text  = __( 'Save', 'wc-affiliate' );
$statuses  = Helper::get_transactions_statuses();
if ( $_GET['transaction'] != 'new' && sanitize_text_field( $_GET['transaction'] ) != '' ) {
	$trnx_row_id = (int)sanitize_text_field( $_GET['transaction'] );

	global $wpdb;
	$table 	= "{$wpdb->prefix}wca_transactions";
	$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE `id` = %d", $trnx_row_id ) );

	if( is_null( $result ) ) return;

	$id				= $result->id;
	$affiliate		= $result->affiliate;
	$amount			= $result->amount;
	$payment_method	= $result->payment_method;
	$txn_id			= $result->txn_id;
	$status			= $result->status;
	$request_at		= $result->request_at;
	$process_at		= $result->process_at;

	$btn_text  	= __( 'Update', 'wc-affiliate' );
	$row_id  	= "<input type='hidden' name='row_id' value='{$id}'>";
	$payment_method = get_user_meta( $affiliate, '_wc_affiliate_payout_method', true );
}

?>
<div class="payment-form-area">
	<form method="post" id="wf-transaction-form">
		<input type="hidden" name="action" value="wf-transaction-form-action">
		<?php 
			wp_nonce_field();
			echo $row_id;
		?>
		<div class="wf-tr-form-row">
			<label for="affiliate"><?php _e( 'Affiliate name', 'wc-affiliate' ) ?></label>
			<?php 
				$name = get_userdata( $affiliate )->display_name;
				echo "<strong>{$name}</strong>";		
			?>
		</div>
		<div class="wf-tr-form-row">
			<label for="payment_method"><?php _e( 'Payment method', 'wc-affiliate' ) ?></label>
			<select id="payment_method" name="payment_method">
				<?php foreach ( Helper::payout_options() as $key => $option ) {
					$select = selected( $key, $payment_method, false );
					echo "<option value='{$key}' {$select}>{$option}</option>";
				} ?>
			</select>
		</div>
		<div class="wf-tr-form-row">
			<label for="amount"><?php _e( 'Payment amount', 'wc-affiliate' ) ?></label>
			<input id="amount" type="text" name="amount" value="<?php echo esc_html( $amount ); ?>" readonly>
		</div>
		<div class="wf-tr-form-row">
			<label for="txn_id"><?php _e( 'Transaction ID', 'wc-affiliate' ) ?></label>
			<input id="txn_id" type="text" name="txn_id" value="<?php echo esc_attr( $txn_id ); ?>">
		</div>
		<button class="button" type="submit"><?php echo esc_html( $btn_text ); ?></button>
	</form>
</div>