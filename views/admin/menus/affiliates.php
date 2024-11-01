<?php
use Codexpert\WC_Affiliate\Helper;
$from		= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
$to			= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y', current_time( 'timestamp' ) );
$per_page	= isset( $_GET['per_page'] ) ? (int)sanitize_text_field( $_GET['per_page'] ) : '';

$statuses	= Helper::get_affiliate_statuses();
$status		= isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : null;
$args 		= [ 'status' => $status, 'from' => $from, 'to' => $to ];
$edit_url	= admin_url( 'user-edit.php' );
$admin_url	= admin_url( 'admin.php' );
$data		= [];

/**
 * Prepare the data
 */
global $wpdb;
$meta_table = "{$wpdb->base_prefix}usermeta";

// V2.2.3 – 2023-01-03
// Fix list of affiliate users for multisite 
// Database don't get the data as expected for multisite
// if( is_multisite() ) {
//     $blog_id 		= get_current_blog_id();
//     $meta_table 	= "{$wpdb->base_prefix}{$blog_id}_usermeta";
// }


$user_id = "SELECT `user_id` FROM `{$meta_table}` WHERE meta_key = '_wc_affiliate_status'";

if( $status ) {
    $user_id .= " AND `meta_key` = '_wc_affiliate_status' AND meta_value = '{$status}'";
}

$sql = "SELECT * FROM `{$meta_table}` WHERE user_id IN( $user_id )";

if( $from && $to ) {
	$form_date 	= strtotime( $from );
	$to_date 	= strtotime( $to ) + DAY_IN_SECONDS - 1; // we need to consider that entire day
    $sql .= " AND `meta_key` = '_wc_affiliate_time_applied' AND `meta_value` >= {$form_date} AND `meta_value` <= {$to_date}";
}

$affiliates = $wpdb->get_results( $sql );

foreach ( $affiliates as $affiliate ) {
	$user 			= get_userdata( $affiliate->user_id );
	$format 		= get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	$status 		= get_user_meta( $affiliate->user_id, '_wc_affiliate_status', true );
	$applied_time 	= date( $format, $affiliate->meta_value );

	$data[] = [
		'id'			=> $affiliate->user_id,
		'affiliate_id'	=> "#{$affiliate->user_id}",
		'name'			=> $user->display_name,
		'registered'	=> $user->user_registered,
		'applied_time'	=> $applied_time,
		'status'		=> $status,
		'action'		=> '<a href="' . add_query_arg( [ 'page' => 'affiliates', 'affiliate' => $affiliate->user_id ], $admin_url ) . '" class="button">' . __( 'Review', 'wc-affiliate' ) . '</a>
			<a href="' . add_query_arg( [ 'user_id' => $affiliate->user_id ], $edit_url ) . '#wf-title" class="button button-primary">' . __( 'Edit', 'wc-affiliate' ) . '</a>
			<a href="' . add_query_arg( [ 'page' => 'wc-affiliate', 'affiliate' => $affiliate->user_id ], $admin_url ) . '" class="button">' . __( 'Report', 'wc-affiliate' ) . '</a>',
	];
}

$_actions = [];
foreach ( Helper::get_affiliate_statuses() as $key => $status ) {
	// Translators: %s is the status (e.g., "Pending", "Completed").
	$_actions[ $key ] = sprintf( __( 'Mark %s', 'wc-affiliate' ),  $status ); 
}

/**
 * Config
 */
$config = [
	'id'			=> 'affiliate',
	'per_page'		=> $per_page != '' ? $per_page : 10,
	'columns'		=> [
		'affiliate_id'	=> __( 'Affiliate ID', 'wc-affiliate' ),
		'name'			=> __( 'Name', 'wc-affiliate' ),
		'registered'	=> __( 'Registered', 'wc-affiliate' ),
		'applied_time'	=> __( 'Applied Time', 'wc-affiliate' ),
		'status'		=> __( 'Status', 'wc-affiliate' ),
		'action'		=> __( 'Action', 'wc-affiliate' ),
	],
	'sortable'		=> [ 'affiliate_id', 'name', 'registered', 'applied_time', 'status' ],
	'orderby'		=> 'affiliate_id',
	'order'			=> 'desc',
	'data'			=> $data,
	'bulk_actions'	=> $_actions,
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
	<?php
	if( isset( $_GET['affiliate'] ) && ( $_user = get_userdata( (int)sanitize_text_field( $_GET['affiliate'] ) ) ) !== false ) :

		echo '<h2>';
		// Translators: %1$d is the affiliate's user ID, %2$s is the affiliate's display name.
		echo sprintf( __( 'Affiliate #%1$d: %2$s', 'wc-affiliate' ), $_user->ID, $_user->display_name );
		echo ' <a href="' . esc_url( add_query_arg( 'page', 'affiliates', $admin_url ) ) . '" class="button wf-al-reports">' . esc_html__( 'All Affiliates', 'wc-affiliate' ) . '</a>';
		echo '</h2>';

		do_action( 'wc-affiliate-review-user' );
	elseif ( isset( $_GET['action'] ) && sanitize_text_field( $_GET['action'] ) == 'add-new-affiliate' ) :
		echo "<h2>". __( 'New Affiliate', 'wc-affiliate' ) ."</h2>";
		do_action( 'wc-affiliate-new-affiliate' );
	else:
		echo '<h2>';
		esc_html_e( 'Affiliates', 'wc-affiliate' );
		$new_affiliate_url = add_query_arg( [ 'page' => 'affiliates', 'action' => 'add-new-affiliate' ], $admin_url );
	?>
	<a class="button" href="<?php echo esc_url( $new_affiliate_url ); ?>"><?php esc_html_e( 'Add New', 'wc-affiliate' ) ?></a>
	<button class="button button-primary" id="wc-affiliate-export-report-btn" data-params='<?php echo serialize( $_REQUEST ) ?>' data-headings='<?php echo serialize( $_config ) ?>' data-name='affiliates' <?php echo esc_attr( $disabled ); ?>><?php esc_html_e( 'Export Report', 'wc-affiliate' ); ?></button>
	</h2>
	<div class="wf-wrap">
		<form method="GET">
			<input type="hidden" name="page" value="affiliates">
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