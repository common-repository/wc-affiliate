<?php
use Codexpert\WC_Affiliate\Helper;
if ( ! function_exists( 'add_screen_option' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
}
if ( ! function_exists( 'render_list_table_columns_preferences' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php' );
}
$from			= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
$from_timestamp	= strtotime( $from );
$to				= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y' );
$to_timestamp	= strtotime( $to ) + DAY_IN_SECONDS - 1; // we need to consider that entire day
$per_page		= isset( $_GET['per_page'] ) ? (int)sanitize_text_field( $_GET['per_page'] ) : '';
$permalink 		= get_permalink();
$data 			= [];
$referrals 		= Helper::get_referrals( $from_timestamp , $to_timestamp );
$format 		= get_option( 'date_format' ) ;

foreach ( $referrals as $referral ) {
	$products 	= '';
	$_products 	= unserialize( $referral->products );

	if ( $_products != '' ) {
		foreach ( $_products as $key => $product_name ) {
			$products .= $product_name .", ";
		}
	}
	

	$products = rtrim( $products, ", " );
    $data[] = array(
        'visit'				=> $referral->visit,
        'type'				=> $referral->type,
        'products'			=> $products,
        'order_total'		=> $referral->order_total,
        'commission'		=> $referral->commission,
        'payment_status'	=> $referral->payment_status,
        'time'				=> wp_date( $format, $referral->time ),
    );
}

/**
 * Config
 */
$config = [
	'per_page'		=> $per_page != '' ? $per_page : 10,
	'columns'		=> [
		'visit'				=> __( 'Visit #', 'wc-affiliate' ),
		'type'				=> __( 'type', 'wc-affiliate' ),
		'products'			=> __( 'Products', 'wc-affiliate' ),
		'order_total'		=> __( 'Order Total', 'wc-affiliate' ),
		'commission'		=> __( 'Commission', 'wc-affiliate' ),
		'payment_status'	=> __( 'Payment Status', 'wc-affiliate' ),
		'time'				=> __( 'Time', 'wc-affiliate' ),
	],
	'sortable'		=> [ 'visit', 'products', 'commission', 'payment_status', 'time' ],
	'orderby'		=> 'time',
	'order'			=> 'desc',
	'data'			=> $data,
];
$_config 	= $config['columns'];
unset( $_REQUEST['_wp_http_referer'] );
unset( $_REQUEST['action'] );
unset( $_REQUEST['per_page'] );
unset( $_REQUEST['action2'] );
$disabled = '';
if ( !is_array( $data ) || empty( $data ) ) {
	$disabled = 'disabled';
}
$_REQUEST['page'] = sanitize_text_field( $_REQUEST['tab'] );
$_REQUEST['affiliate'] = get_current_user_id();
?>

<div class="wf-dashboard-panel-head wf-dashboard-referrals-header">
	<div class="wf-dashboard-panel-head-title">
		<h3><?php _e( 'Referrals', 'wc-affiliate' ) ?></h3>
		<button class="button button-primary" id="wc-affiliate-export-report-btn" data-params='<?php echo serialize( $_REQUEST ) ?>' data-headings='<?php echo serialize( $_config ) ?>' data-name='referrals' <?php echo $disabled; ?>><?php _e( 'Export Report', 'wc-affiliate' ) ?></button>
	</div>
	<div class="wf-dashboard-panel-filter">
		<form id="wf-dashboard-referrals-filter" method="GET">
			<input type="hidden" name="tab" value="referrals" />
			<input class="datepicker" type="text" name="from" value="<?php echo esc_attr( $from ) ?>">
			<input class="datepicker" type="text" name="to" value="<?php echo esc_attr( $to ) ?>">
			<input class="wfd-perpage" type="number" name="per_page" value="<?php echo esc_attr( $per_page ) ?>" placeholder="<?php _e( 'Per Page', 'wc-affiliate' ) ?>">
			<input type="submit" value="<?php _e( 'Filter', 'wc-affiliate' ); ?>" class="button button-submit wf-button" >
		</form>
	</div>
</div>

<div class="wfd-list-table wf-referrals-panel">
	<form method="post">
		<?php
		$table = new Codexpert\Plugin\Table( $config );
		$table->prepare_items();
		$table->display();
		?>
	</form>
</div>
