<?php

use Codexpert\WC_Affiliate\Helper;

$from		= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
$to			= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y', current_time( 'timestamp' ) );
$affiliate 	= isset( $_GET['affiliate'] ) ? (int)sanitize_text_field( $_GET['affiliate'] ) : '';
$per_page	= isset( $_GET['per_page'] ) ? (int)sanitize_text_field( $_GET['per_page'] ) : '';
$product	= isset( $_GET['product'] ) ? sanitize_text_field( $_GET['product'] ) : '';
$visit		= isset( $_GET['visit'] ) ? sanitize_text_field( $_GET['visit'] ) : '';
$currency	= function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '[currency]';

$statuses	= Helper::get_referral_statuses();
$admin_url 	= add_query_arg( 'page', 'referrals', admin_url( 'admin.php' ) );
$admin_url 	= wp_nonce_url( $admin_url );

$visits_url = add_query_arg( 'page', 'visits', admin_url( 'admin.php' ) );
$visits_url = wp_nonce_url( $visits_url );

$data		= [];
$status_counts = [];

/**
 * Prepare the data
 */
global $wpdb;
$referrals_table = "{$wpdb->prefix}wca_referrals";

if( is_multisite() ) {
    $blog_id = get_current_blog_id();
    $referrals_table = "{$wpdb->base_prefix}{$blog_id}_wca_referrals";
}

$sql = "SELECT * FROM $referrals_table WHERE 1 = 1";

if( isset( $_GET['referral'] ) && $_GET['referral'] != '' ) {
    $sql .= " AND `id` = '" . sanitize_text_field( $_GET['referral'] ) . "'";
}

if( isset( $_GET['status'] ) && array_key_exists( $_GET['status'], $statuses ) ) {
    $sql .= " AND `payment_status` = '" . sanitize_text_field( $_GET['status'] ) . "'";
}

if( $from && $to ) {
	$form_date 	= strtotime( $from );
	$to_date 	= strtotime( $to ) + DAY_IN_SECONDS - 1; // we need to consider that entire day
    $sql 	   .= " AND `time` >= '{$form_date}' AND `time` <= '{$to_date}'";
}

if( $affiliate ) {
    $sql .= " AND `affiliate` = '{$affiliate}'";
}

if( $product ) {
    $sql .= " AND `products` LIKE '%{$product}%'";
}

if( $visit ) {
    $sql .= " AND `visit` = '{$visit}'";
}

$sql .= " ORDER BY `time` DESC";

$referrals = $wpdb->get_results( $sql );

$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

//wc_affiliate_get_referral_statuses
$_actions 		= [];
foreach ( $statuses as $key => $status ) {
	// Translators: %s is the status name that will be marked.
	$_actions[ $key ] = sprintf( __( 'Mark %s', 'wc-affiliate' ),  $status );
}
$_actions[ 'delete' ] 	= __( 'Delete', 'wc-affiliate' );

foreach ( $referrals as $referral ) {
    $products   = '';
    $_products  = unserialize( $referral->products );

    foreach ( $_products as $key => $product_name ) {
        $products .= $product_name . ", ";
    }

    $actions        = $_actions;
    unset( $actions[ $referral->payment_status ] );
    $action_url     = add_query_arg( 'item_id', $referral->id, $admin_url );
    $action_btns    = [];

    foreach ( $actions as $key => $action ) {
        $action_url     = add_query_arg( 'action', $key, $action_url );
        $action_btns[]  = "<a href='" . esc_url( $action_url ) . "' class='wf-referral-action-button {$key} wl-action-{$key}'>" . $action . "</a>";
    }

    $products       = rtrim( $products, ", " );

    $affiliate_user = get_userdata( $referral->affiliate );

    if ( $affiliate_user ) {
        $affiliate_display_name = $affiliate_user->display_name;
    } else {
        $affiliate_display_name = 'User not found';
    }

    $visit          = add_query_arg( [ 'referral' => $referral->visit ], $visits_url );

    $data[] = [
        'id'                => $referral->id,
        'affiliate'         => $affiliate_display_name,
        'commission'        => $currency . $referral->commission,
        'type'              => $referral->type,
        'visit'             => '<a href="' . $visit . '">' . $referral->visit . '</a>',
        'order_id'          => '<a href="' . get_edit_post_link( $referral->order_id ) . '">' . $referral->order_id . '</a>',
        'products'          => $products,
        'order_total'       => $currency . $referral->order_total,
        'payment_status'    => $referral->payment_status,
        'actions'           => implode( " | ", $action_btns ),
        'time'              => date( $format, $referral->time ),
    ];
}

/**
 * Config
 */
$config = [
	'id'			=> 'referral',
	'per_page'		=> $per_page != '' ? $per_page : 10,
	'columns'		=> [
		'affiliate'			=> __( 'Affiliate', 'wc-affiliate' ),
		'commission'		=> __( 'Commission', 'wc-affiliate' ),
		'type'				=> __( 'type', 'wc-affiliate' ),
		'visit'				=> __( 'Visit #', 'wc-affiliate' ),
		'order_id'			=> __( 'Order #', 'wc-affiliate' ),
		'products'			=> __( 'Products', 'wc-affiliate' ),
		'order_total'		=> __( 'Order Total', 'wc-affiliate' ),		
		'payment_status'	=> __( 'Status', 'wc-affiliate' ),
		'time'				=> __( 'Time', 'wc-affiliate' ),
		'actions'			=> __( 'Actions', 'wc-affiliate' ),
	],
	'sortable'		=> [ 'affiliate', 'visit', 'order_id', 'products', 'commission', 'payment_status', 'actions', 'time' ],
	'orderby'		=> 'time',
	'order'			=> 'desc',
	'data'			=> $data,
	'bulk_actions'	=> $_actions,
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
	<h2>
		<?php esc_html_e( 'Referrals', 'wc-affiliate' ); ?>
		<button class="button button-primary" id="wc-affiliate-export-report-btn" data-params='<?php echo esc_attr( serialize( $_REQUEST ) ); ?>' data-headings='<?php echo esc_attr( serialize( $_config ) ); ?>' data-name='referrals' <?php echo esc_attr( $disabled ); ?>>
			<?php esc_html_e( 'Export Report', 'wc-affiliate' ); ?>
		</button>

	</h2>
	<div class="wf-wrap">
		<form method="GET">
			<input type="hidden" name="page" value="referrals">
			<?php 
			$table = new Codexpert\Plugin\Table( $config );
			$table->views();
			$table->prepare_items();
			$table->display();
			?>
		</form>
	</div>
</div>
