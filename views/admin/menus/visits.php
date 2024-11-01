<?php
use Codexpert\WC_Affiliate\Helper;
$from		= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
$to			= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y', current_time( 'timestamp' ) );
$per_page	= isset( $_GET['per_page'] ) ? (int)sanitize_text_field( $_GET['per_page'] ) : '';
$referral	= isset( $_GET['referral'] ) ? (int)sanitize_text_field( $_GET['referral'] ) : '';
$admin_url 	= add_query_arg( 'page', 'visits', admin_url( 'admin.php' ) );
$admin_url 	= wp_nonce_url( $admin_url );;

$referral_url = add_query_arg( 'page', 'referrals', admin_url( 'admin.php' ) );
$referral_url = wp_nonce_url( $referral_url );

/**
 * Prepare the data
 */
global $wpdb;
$data 			= [];
$visits_table 	= "{$wpdb->prefix}wca_visits";

if( is_multisite() ) {
    $blog_id 		= get_current_blog_id();
    $visits_table 	= "{$wpdb->base_prefix}{$blog_id}_wca_visits";
}

$sql = "SELECT * FROM $visits_table WHERE 1 = 1";

if( isset( $_REQUEST['s'] ) ) {
    $s 	  = sanitize_text_field( $_REQUEST['s'] );
    $sql .= " AND page_url LIKE '%{$s}%' OR referrer_url LIKE '%{$s}%' OR campaign LIKE '%{$s}%' OR ip LIKE '%{$s}%'";
}

if( $from && $to ) {
	$form_date 	= strtotime( $from );
	$to_date 	= strtotime( $to ) + DAY_IN_SECONDS - 1; // we need to consider that entire day
    $sql 	   .= " AND `time` >= '{$form_date}' AND `time` <= '{$to_date}'";
}

if( $referral ) {
    $sql .= " AND `referral` = '{$referral}'";
}

$sql 	.= " ORDER BY `time` DESC";
$visits  = $wpdb->get_results( $sql );
$format  = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

foreach ( $visits as $visit ) {

	$action_url 	= add_query_arg( [
		'item_id' 	=> $visit->id,
		'action'	=> 'delete'
	], $admin_url );

	$referral_url 		= add_query_arg( [ 'visit' => $visit->referral ], $referral_url );

    $data[] = [
    	'id'			=> $visit->id,
        'referral'		=> $visit->referral > 0 ? '<a href="'. $referral_url .'">'. $visit->referral .'</a>' : $visit->referral,
        'page_url'		=> $visit->page_url,
        'referrer_url'	=> $visit->referrer_url,
        'campaign'		=> $visit->campaign,
        'ip'			=> $visit->ip,
        'time'			=> date( $format, $visit->time ),
        'action'		=> "<a href='" . esc_url( $action_url ) . "' class='wf-referral-action-button delete wl-action-delete'>" . __( 'Delete', 'wc-affiliate' ). "</a>",
    ];
}

/**
 * Config
 */
$config = [
	'id'			=> 'visit',
	'per_page'		=> $per_page != '' ? $per_page : 10,
	'columns'		=> [
		'referral'		=> __( 'Referral', 'wc-affiliate' ),
		'page_url'		=> __( 'Page URL', 'wc-affiliate' ),
		'referrer_url'	=> __( 'Referrer URL', 'wc-affiliate' ),
		'campaign'		=> __( 'Campaign', 'wc-affiliate' ),
		'ip'			=> __( 'IP', 'wc-affiliate' ),
		'time'			=> __( 'Time', 'wc-affiliate' ),
		'action'		=> __( 'Action', 'wc-affiliate' ),
	],
	'sortable'		=> [ 'referral', 'page_url', 'referrer_url', 'campaign', 'ip', 'action', 'time' ],
	'orderby'		=> 'time',
	'order'			=> 'desc',
	'data'			=> $data,
	'bulk_actions'	=> [
		'delete' 	=> __( 'Delete', 'wc-affiliate' ),
	],
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
	<h2><?php _e( 'Visits', 'wc-affiliate' ) ?>
		<button class="button button-primary" id="wc-affiliate-export-report-btn" data-params='<?php echo serialize( $_REQUEST ) ?>' data-headings='<?php echo serialize( $_config ) ?>' data-name='visits' <?php echo $disabled; ?>><?php _e( 'Export Report', 'wc-affiliate' ) ?></button>
	</h2>
	<div class="wf-wrap visits">
		<form method="GET">
			<input type="hidden" name="page" value="visits">
			<?php 
			$table = new Codexpert\Plugin\Table( $config ); 
			$table->prepare_items();
			$table->display();
			?>
		</form>
	</div>
</div>