<?php
use Codexpert\WC_Affiliate\Helper;
if ( ! function_exists( 'add_screen_option' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
}
if ( ! function_exists( 'render_list_table_columns_preferences' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php' );
}
global $wpdb;
$from			= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
$from_timestamp	= strtotime( $from );
$to				= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y' );
$to_timestamp	= strtotime( $to ) + DAY_IN_SECONDS - 1; // we need to consider that entire day
$per_page		= isset( $_GET['per_page'] ) ? (int)sanitize_text_field( $_GET['per_page'] ) : '';
$permalink 		= get_permalink();
$data 			= [];
$visits_table 	= "{$wpdb->prefix}wca_visits";
$user_id 		= get_current_user_id();

if( is_multisite() ) {
    $blog_id = get_current_blog_id();
    $visits_table = "{$wpdb->base_prefix}{$blog_id}_wca_visits";
}

$visits = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$visits_table` WHERE `affiliate` = %d AND `time` >= %d AND `time` < %d ORDER BY `time` DESC", $user_id, $from_timestamp, $to_timestamp ) );

$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

foreach ( $visits as $visit ) {
    $data[] = array(
        'referral'		=> $visit->referral,
        'page_url'		=> $visit->page_url,
        'referrer_url'	=> $visit->referrer_url,
        'campaign'		=> $visit->campaign,
        'ip'			=> $visit->ip,
        'time'			=> date( $format, $visit->time ),
    );
}

/**
 * Config
 */
$config = [
	'per_page'		=> $per_page != '' ? $per_page : 10,
	'columns'		=> [
		'referral'		=> __( 'Referral', 'wc-affiliate' ),
		'page_url'		=> __( 'Page URL', 'wc-affiliate' ),
		'referrer_url'	=> __( 'Referrer URL', 'wc-affiliate' ),
		'campaign'		=> __( 'Campaign', 'wc-affiliate' ),
		'ip'			=> __( 'IP', 'wc-affiliate' ),
		'time'			=> __( 'Time', 'wc-affiliate' ),
	],
	'sortable'		=> [ 'referral', 'page_url', 'referrer_url', 'campaign', 'ip', 'time' ],
	'orderby'		=> 'time',
	'order'			=> 'desc',
	'data'			=> $data,
	// 'screen'			=> 'null'
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
$_REQUEST['page'] = sanitize_text_field( $_REQUEST['tab'] );
$_REQUEST['affiliate'] = get_current_user_id();
?>
<div class="wf-dashboard-panel-head wf-dashboard-visit-header">
	<div class="wf-dashboard-panel-head-title">
		<h3><?php _e( 'Visits', 'wc-affiliate' ) ?></h3>
		<button class="button button-primary" id="wc-affiliate-export-report-btn" data-params='<?php echo serialize( $_REQUEST ) ?>' data-headings='<?php echo serialize( $_config ) ?>' data-name='referrals' <?php echo $disabled; ?>><?php _e( 'Export Report', 'wc-affiliate' ) ?></button>
	</div>
	<div class="wf-dashboard-panel-filter">
		<form id="wf-dashboard-visit-filter" method="GET">
			<input type="hidden" name="tab" value="visits" />
			<input class="datepicker" type="text" name="from" value="<?php echo esc_attr( $from ) ?>">
			<input class="datepicker" type="text" name="to" value="<?php echo esc_attr( $to ) ?>">
			<input class="wfd-perpage" type="number" name="per_page" value="<?php echo esc_attr( $per_page ) ?>" placeholder="<?php _e( 'Per Page', 'wc-affiliate' ) ?>">
			<input type="submit" value="<?php _e( 'Filter', 'wc-affiliate' ); ?>" class="button button-submit wf-button" >
		</form>
	</div>
</div>

<div class="wfd-list-table wf-visits-panel">
	<form method="post">
		<?php
		$table = new Codexpert\Plugin\Table( $config );
		$table->prepare_items();
		$table->display();
		?>
	</form>
</div>
