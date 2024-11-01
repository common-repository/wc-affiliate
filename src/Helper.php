<?php
/**
 * All helpers functions
 */
namespace Codexpert\WC_Affiliate;
use Codexpert\Plugin\Base;
use mukto90\Ncrypt;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Helper
 * @author codexpert <hi@codexpert.io>
 */
class Helper extends Base {

	public $plugin;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->server	= $this->plugin['server'];
		$this->version	= $this->plugin['Version'];
	}

	public static function pri( $data ) {
		echo '<pre>';
		if( is_object( $data ) || is_array( $data ) ) {
			print_r( $data );
		}
		else {
			var_dump( $data );
		}
		echo '</pre>';
	}

	/**
	 * @param bool $show_cached either to use a cached list of posts or not. If enabled, make sure to wp_cache_delete() with the `save_post` hook
	 */
	public static function get_posts( $args = [], $show_heading = true, $show_cached = true ) {

		$defaults = [
			'post_type'         => 'post',
			'posts_per_page'    => -1,
			'post_status'		=> 'publish'
		];

		$_args = wp_parse_args( $args, $defaults );

		// use cache
		if( true === $show_cached && ( $cached_posts = wp_cache_get( "wc_affiliate_{$_args['post_type']}", 'wc-affiliate' ) ) ) {
			$posts = $cached_posts;
		}

		// don't use cache
		else {
			$queried = new \WP_Query( $_args );

			$posts = [];
			foreach( $queried->posts as $post ) :
				$posts[ $post->ID ] = $post->post_title;
			endforeach;
			
			wp_cache_add( "wc_affiliate_{$_args['post_type']}", $posts, 'wc-affiliate', 3600 );
		}

		// Translators: %s is the post type (e.g., "post", "page").
		$posts = $show_heading ? [ '' => sprintf( __( '- Choose a %s -', 'wc-affiliate' ), $_args['post_type'] ) ] + $posts : $posts;

		return apply_filters( 'wc_affiliate_get_posts', $posts, $_args );
	}

	public static function get_option( $key, $section, $default = '' ) {

		$options = get_option( $key );

		if ( isset( $options[ $section ] ) ) {
			return $options[ $section ];
		}

		return $default;
	}

	/**
	 * Includes a template file resides in /views diretory
	 *
	 * It'll look into /wc-affiliate directory of your active theme
	 * first. if not found, default template will be used.
	 * can be overwriten with wc_affiliate_template_overwrite_dir hook
	 *
	 * @param string $slug slug of template. Ex: template-slug.php
	 * @param string $sub_dir sub-directory under base directory
	 * @param array $fields fields of the form
	 */
	public static function get_template( $slug, $base = 'views', $args = null ) {

		// templates can be placed in this directory
		$overwrite_template_dir = apply_filters( 'wc_affiliate_template_overwrite_dir', get_stylesheet_directory() . '/wc-affiliate/', $slug, $base, $args );
		
		// default template directory
		$plugin_template_dir = dirname( WCAFFILIATE ) . "/{$base}/";

		// full path of a template file in plugin directory
		$plugin_template_path =  $plugin_template_dir . $slug . '.php';
		
		// full path of a template file in overwrite directory
		$overwrite_template_path =  $overwrite_template_dir . $slug . '.php';

		// if template is found in overwrite directory
		if( file_exists( $overwrite_template_path ) ) {
			ob_start();
			include $overwrite_template_path;
			return ob_get_clean();
		}
		// otherwise use default one
		elseif ( file_exists( $plugin_template_path ) ) {
			ob_start();
			include $plugin_template_path;
			return ob_get_clean();
		}
		else {
			return __( 'Template not found!', 'wc-affiliate' );
		}
	}

	/**
	 * Generates some action links of a plugin
	 *
	 * @since 1.0
	 */
	public static function action_link( $plugin, $action = '' ) {

		$exploded	= explode( '/', $plugin );
		$slug		= $exploded[0];

		$links = [
			'install'		=> wp_nonce_url( admin_url( "update.php?action=install-plugin&plugin={$slug}" ), "install-plugin_{$slug}" ),
			'update'		=> wp_nonce_url( admin_url( "update.php?action=upgrade-plugin&plugin={$plugin}" ), "upgrade-plugin_{$plugin}" ),
			'activate'		=> wp_nonce_url( admin_url( "plugins.php?action=activate&plugin={$plugin}&plugin_status=all&paged=1&s" ), "activate-plugin_{$plugin}" ),
			'deactivate'	=> wp_nonce_url( admin_url( "plugins.php?action=deactivate&plugin={$plugin}&plugin_status=all&paged=1&s" ), "deactivate-plugin_{$plugin}" ),
		];

		if( $action != '' && array_key_exists( $action, $links ) ) return $links[ $action ];

		return $links;
	}

	/**
	 * Gets plugin data
	 *
	 * @since 1.0
	 */
	public static function get_plugin_data( $plugin_file ) {
		return get_plugin_data( $plugin_file );
	}

	/**
	 * Return Boolean 
	 * The pro version is activated or not
	 * @author Jakaria Istauk <jakariamd35@gmail.com>
	 * @since 1.0
	 */
	public static function has_pro() {
		if ( in_array( 'wc-affiliate-pro/wc-affiliate-pro.php', get_option('active_plugins') ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Return pro notice 
	 *
	 * @author Jakaria Istauk <jakariamd35@gmail.com>
	 * @since 1.0
	 */
	public static function pro_notice( $preview = '', $text = '', $show_note = true ) {
		// Translators: %s is the URL to the WC Affiliate Pro upgrade page.
		$text = $text != '' ? esc_html( $text ) : sprintf( __( 'This is a premium feature. Upgrade to <a href="%s" target="_blank">WC Affiliate Pro</a> to unlock this feature.', 'wc-affiliate' ), 'https://codexpert.io/wc-affiliate/?utm_campaign=upgrade-link' );
		
		$title = __( 'Admin Notice', 'wc-affiliate' );
		
		$note = $show_note ? __( 'No worries. Your affiliates won\'t see this notice!', 'wc-affiliate' ) : '';
		
		$preview = $preview != '' ? "<img src='" . esc_url( $preview ) . "'>" : ''; 
		
		$notice_html = "<div class='wc-affiliate-pro-notice'>
			<h3 class='wc-affiliate-pro-notice-title'>{$title}</h3>
			<p class='wc-affiliate-pro-notice-desc'>{$text}</p>
			<p class='wc-affiliate-pro-notice-note'>{$note}</p>
			{$preview}
		</div>";

		return $notice_html;
	}

	/**
	 * Return pro Preview HTML 
	 *
	 * @author Jakaria Istauk <jakariamd35@gmail.com>
	 * @since 1.0
	 */
	public static function pro_preview_html( $title = '', $description = '', $redirect_url = '', $placeholder_img = '' ) {
		$preview_html = "
		<div class='woffiliate-section-preview'>
			<div class='wsp-left'>
				<img class='wsp-preview-img' src='{$placeholder_img}'>
			</div>
			<div class='wsp-right'>
				<h2 class='wsp-title'>{$title}</h2>
				<div class='wsp-feature-section'>
					<div class='wsp-features'>{$description}</div>
				</div>
				<div class='wsp-footer'>
					<a target='_blank' class='wsp-button button button-primary' href='" . esc_url( $redirect_url ) . "'>" . __( 'Learn More', 'wc-affiliate' ) . "</a>
				</div>
			</div>
		</div>
		";

		return $preview_html;
	}

	/**
	 * Gets plugin data
	 *
	 * @since 1.0
	 */
	public static function get_token( $user_id = '' ) {
		$token_type = wc_affiliate_token_type();
		$user_id = $user_id != '' ? $user_id : get_current_user_id(); 
		//id | ID | slug | email | login
		$user 	= get_userdata( $user_id );

		if( is_bool( $user ) ) return;

		if ( $token_type == 'email' ) {
			return $user->user_email;
		}
		else if(  $token_type == 'login' ){
			return $user->user_login;
		}
		else{
			return $user_id;
		}
	}

	/**
	 * Gets Order Place hook
	 *
	 * @since 1.0
	 */
	public static function get_statuses() {
		return [
			'woocommerce_thankyou'	=> __( 'WooCommerce Thankyou', 'wc-affiliate' ),
			'order_completed'		=> __( 'WooCommerce Order Completed', 'wc-affiliate' ),
		];
	}

	/**
	 * Gets current page URI with query strings
	 *
	 * @since 1.0
	 */
	public static function get_current_uri() {
		global $wp;
		return home_url( $wp->request );
	}

	/**
	 * Gets current page URI with query strings
	 *
	 * @since 1.0
	 */
	public static function get_landing_uri() {
		return home_url( add_query_arg( NULL, NULL ) ); // @TODO: needs fixed
	}

	/**
	 * Gets visitor IP
	 *
	 * @since 1.0
	 */
	public static function get_user_ip() {

		$ipaddress = '';
		    if ( isset($_SERVER['HTTP_CLIENT_IP']) )
		        $ipaddress = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
		    else if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) )
		        $ipaddress = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		    else if( isset($_SERVER['HTTP_X_FORWARDED']) )
		        $ipaddress = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED'] );
		    else if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) )
		         $ipaddress = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ;
		    else if( isset($_SERVER['HTTP_FORWARDED']) )
		       $ipaddress = sanitize_text_field( $_SERVER['HTTP_FORWARDED'] );
		    else if( isset($_SERVER['REMOTE_ADDR']) )
		        $ipaddress = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		    else
		        $ipaddress = 'UNKNOWN';
		return $ipaddress;

	}

	/**
	 * Calculate commission based on the order total
	 *
	 * @var int|obj $order \WC_Order object or the order_id
	 *
	 * @return int|float
	 */
	public static function calculate_commissions( $order ) {
		if( !is_object( $order ) ) {
			$order = new \WC_Order( $order );
		}

		$order_subtotal 	= $order->get_subtotal();

		$users = self::get_affiliate_for_credit( $order );

		if( !$users ) return;

		$enable_mlc 		= self::get_option( 'wc_affiliate_mlc', 'enable_mlc' );
		$commission_level 	= self::get_option( 'wc_affiliate_mlc', 'commission_level' );

		$commission_type = $commission_amount = '';

		/**
		 * Get the global values first
		 */
		$global_commission_type 	= self::get_option( 'wc_affiliate_basic', 'commission_type', 'percent' );
		$global_commission_amount 	= self::get_option( 'wc_affiliate_basic', 'commission_amount', 20 );

		$commission_type = $global_commission_type;
		$commission_amount = $global_commission_amount;

		/**
		 * Get user's values and see if the user has custom commission assigned
		 */
		foreach( $users as $user ){
			$user_id = $user->ID;
			$user_commission_type 	= get_user_meta( $user_id, 'commission_type', true );
			$user_commission_amount = get_user_meta( $user_id, 'commission_amount', true );

			if ( $user_commission_type != 'default' && $user_commission_amount != '' ) {
				$commission_type   = $user_commission_type;
				$commission_amount = $user_commission_amount;
			}

			$total_commission = 0;

			$base_type = Helper::get_option( 'wc_affiliate_basic', 'commission_base' );

			if ( $base_type == 'payable_amount' ) {
				$order_total= $order->get_total();
				$discount 	= $order->get_total_discount();
				$shipping 	= $order->get_shipping_total();

				$commissionable = $order_total - $shipping;
				$commission 	= $commission_type == 'fixed' ? $commission_amount : ( (float)$commissionable * (float)$commission_amount * 0.01 );
				
				$total_commission += (float)$commission;
			}
			else if( $base_type == 'product_price' ){
				foreach ( $order->get_items() as $key => $item ) {
					$product_id 		= $item->get_product_id();
					$item_price			= $item->get_subtotal();
					$variation_id 		= isset( $item['variation_id'] ) ? $item['variation_id'] : false;

					if ( $variation_id ) {
						$product_id = $variation_id;
					}

					$commission = $commission_type == 'fixed' ? $commission_amount : ( (float)$item_price * (float)$commission_amount * 0.01 );
					$product = wc_get_product( $product_id );
					$product_affiliate_commission = $product->get_meta( 'affiliate_commission', true );

					// if commission is disabled at product level, don't calculate
					if( $product_affiliate_commission == 'disabled' ) {
						$commission = 0;
					}

					// if commission of the product is set to site default
					elseif( $product_affiliate_commission == 'default' ) {
						$commission = $commission_type == 'fixed' ? $commission_amount : ( (float)$item_price * (float)$commission_amount * 0.01 );
					}

					// if the product has a custom commission set
					elseif( $product_affiliate_commission == 'custom' ) {
						$commission_type = $product->get_meta( 'commission_type', true );
						$_commission_amount = $product->get_meta( 'commission_amount', true );
						$commission_amount = $_commission_amount != '' ? $_commission_amount : $commission_amount;
						$commission = $commission_type == 'fixed' ? $commission_amount : ( $item_price * $commission_amount * 0.01 );
					}

					$total_commission += (float)$commission;
				}
			}

			$referrers[ $user_id ] 	= $total_commission;


			if ( !empty( $enable_mlc ) && $enable_mlc == 'on' ) {
				$multilevel_commissions = self::get_multilevel_commissions( $user_id, $commission_level, $order_subtotal );
				return $referrers += $multilevel_commissions;
			}
		}

		return $referrers;
	}

	/**
	 * Return affiliate user
	 *
	 * @var obj $order \WC_Order object or the order_id
	 *
	 * @since 2.0.2
	 * @return array
	 */
	public static function get_affiliate_for_credit( $order ){


		if ( count( $order->get_coupon_codes() ) > 0 ) {
			$users = apply_filters( 'wc-affiliates-coupon_affiliates', [], $order );
			if ( count( $users ) > 0 ) return $users;
		}

		$_cookie_name 	= wc_affiliate_get_cookie_name();
		$_cookie_visit 	= wc_affiliate_get_visit_cookie_name();

		// has the key?
		if( !isset( $_COOKIE[ $_cookie_name ] ) || ( $affiliate = sanitize_text_field( $_COOKIE[ $_cookie_name ] ) ) == '' ) return false;
		if( !isset( $_COOKIE[ $_cookie_visit ] ) || ( $visit = sanitize_text_field( $_COOKIE[ $_cookie_visit ] ) ) == '' ) return false;

		// invalid ref value?
		if( ( $user = get_user_by( wc_affiliate_token_type(), $affiliate ) ) == false ) return false;
		$users[] = $user;
		return $users;
	}

	/**
	 * Check if the user is an affiliate memeber
	 *
	 * @since 1.0
	 */
	public static function get_affiliate_status( $user_id = null ) {
		if( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		
		return get_user_meta( $user_id, '_wc_affiliate_status', true );
	}

	/**
	 * Check if the user is an active affiliate memeber
	 *
	 * @since 1.0
	 */
	public static function is_active_affiliate( $user_id = null ) {
		if( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		
		return self::get_affiliate_status( $user_id ) == 'active';
	}

	/**
	 * List all affiliates
	 *
	 * @param string|array $status affiliate status - active|pending|rejected
	 *
	 * @since 1.0
	 * @return array of object
	 */
	public static function get_affiliates_raw( $args ) {
		global $wpdb;
		
		$sql = "SELECT * FROM `$wpdb->usermeta` WHERE `meta_key` = '_wc_affiliate_status'";

		if( isset( $args['status'] ) ) {

			if( !is_array( $args['status'] ) ) {
				$status = [ sanitize_text_field( $args['status'] ) ];
			}

			$sql .= " AND `meta_value` IN ('" . join( "','", $status ) . "')";
		}

		if( isset( $args['from'] ) && isset( $args['to'] ) && sanitize_text_field( $args['from'] ) && sanitize_text_field( $args['to'] ) ) {
			$form_date 	= strtotime( sanitize_text_field( $args['from'] ) );
			$to_date 	= strtotime( sanitize_text_field( $args['to'] ) ) + DAY_IN_SECONDS - 1; // we need to consider that entire day;

		    $sql = "SELECT * FROM `$wpdb->usermeta` WHERE `user_id` IN (SELECT `user_id` FROM `$wpdb->usermeta` WHERE `meta_key` = '_wc_affiliate_status') AND `meta_key` = '_wc_affiliate_time_applied' AND `meta_value` >= {$form_date} AND `meta_value` <= {$to_date}";
		}

		$affiliates =  $wpdb->get_results( $sql );

		return $affiliates;
	}

	/**
	 * Dashboard tabs
	 *
	 * @since 1.0
	 * @return array
	 */
	public static function get_tabs() {

		$dashboard_id 	= self::get_option( 'wc_affiliate_basic', 'dashboard' );
		$dashboard_url 	= get_the_permalink( $dashboard_id );

		return apply_filters( 'wc-affiliate-dashboard_navigation', [
			'summary'		=> [
				'label'		=> __( 'Summary', 'wc-affiliate' ),
				'icon'		=> '<i class="fas fa-home"></i>',
			],
			'visits'		=> [
				'label'		=> __( 'Visits', 'wc-affiliate' ),
				'icon'		=> '<i class="fas fa-shoe-prints"></i>',
			],
			'referrals'		=> [
				'label'		=> __( 'Referrals', 'wc-affiliate' ),
				'icon'		=> '<i class="fas fa-handshake"></i>',
			],
			'transactions'	=> [
				'label'		=> __( 'Transactions', 'wc-affiliate' ),
				'icon'		=> '<i class="fas fa-credit-card"></i>',
			],
			'url-generator'	=> [
				'label'		=> __( 'URL Generator', 'wc-affiliate' ),
				'icon'		=> '<i class="fas fa-paperclip"></i>',
			],
			'banners'		=> [
				'label'		=> __( 'Banners', 'wc-affiliate' ),
				'icon'		=> '<i class="far fa-images"></i>',
			],
			'settings'		=> [
				'label'		=> __( 'Settings', 'wc-affiliate' ),
				'icon'		=> '<i class="fas fa-cog"></i>',
			],
			'logout'		=> [
				'label'		=> __( 'Logout', 'wc-affiliate' ),
				'icon'		=> '<i class="fas fa-sign-out-alt"></i>',
				'url'		=> wp_logout_url( $dashboard_url ),
			],
		] );
	}

	/**
	 * Affiliate statuses
	 *
	 * @since 1.0
	 * @return array
	 */
	public static function get_affiliate_statuses() {
		return apply_filters( 'wc-affiliate-affiliate_statuses', [
			'pending'	=> __( 'Pending', 'wc-affiliate' ),
			'active'	=> __( 'Active', 'wc-affiliate' ),
			'rejected'	=> __( 'Rejected', 'wc-affiliate' ),
			'blocked'	=> __( 'Blocked', 'wc-affiliate' ),
		] );
	}

	/**
	 * Affiliate statuses
	 *
	 * @since 1.0
	 * @return array
	 */
	public static function get_referral_statuses() {
		return apply_filters( 'wc-affiliate-referrals_statuses', [
			'pending'	=> __( 'Pending', 'wc-affiliate' ),
			'approved'	=> __( 'Approved', 'wc-affiliate' ),
			// 'paid'		=> __( 'Paid', 'wc-affiliate' ),
			// 'unpaid'	=> __( 'Unpaid', 'wc-affiliate' ),
			'rejected'	=> __( 'Rejected', 'wc-affiliate' ),
			'cancelled'	=> __( 'Cancelled', 'wc-affiliate' ),
		] );
	}

	/**
	 * Affiliate statuses
	 *
	 * @since 1.0
	 * @return array
	 */
	public static function get_transactions_statuses() {
		return apply_filters( 'wc-affiliate-referrals_statuses', [
			'paid'		=> __( 'Paid', 'wc-affiliate' ),
			'unpaid'	=> __( 'Unpaid', 'wc-affiliate' ),
		] );
	}

	public static function get_transactions_amount() {
		global $wpdb;
		$table_name	= $wpdb->prefix . 'wca_transactions';
		
		$sql = "SELECT `amount`, `status` FROM {$table_name}";

		$results =  $wpdb->get_results( $sql );

		return $results;
	}

	public static function get_referrals_amount( $payment_status = '' ) {
		global $wpdb;
		$table_name	= $wpdb->prefix . 'wca_referrals';
		
		$sql = "SELECT SUM(`commission`) AS `commission` FROM `{$table_name}` WHERE 1";

		if( $payment_status != '' ) {
			$sql .= " AND `payment_status` = '{$payment_status}'";
		}

		$results =  $wpdb->get_results( $sql );

		return $results[0]->commission;
	}

	/**
	 * Affiliate statuses
	 *
	 * @since 1.0
	 * @return array
	 */
	public static function get_referrals( $from_timestamp = '', $to_timestamp = '' ) {
		global $wpdb;

		$referrals_table = "{$wpdb->prefix}wca_referrals";

		if( is_multisite() ) {
		    $blog_id = get_current_blog_id();
		    $referrals_table = "{$wpdb->base_prefix}{$blog_id}_wca_referrals";
		}

		$user_id = get_current_user_id();

		if( $from_timestamp == '' || $to_timestamp == '' ){
			$referrals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$referrals_table` WHERE `affiliate` = %d ORDER BY `time` DESC", $user_id ) );
		}else{
			$referrals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$referrals_table` WHERE `affiliate` = %d AND `time` >= %d AND `time` < %d ORDER BY `time` DESC", $user_id, $from_timestamp, $to_timestamp ) );
		}

		return $referrals;
	}

	/**
	 * Affiliate Users
	 *
	 * @since 1.0
	 * @return array
	 */
	public static function get_affiliate_users() {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->usermeta} WHERE `meta_key` = '_wc_affiliate_status'";

		$users = [];

		$results = $wpdb->get_results( $sql );

		foreach ( $results as $affiliate ) {
			$users[] = $affiliate->user_id;
		}

		return $users;
	}

	/**
	 * wc_affiliate_generate_charts_data
	 *
	 * @since 1.0
	 * @return array
	 */
	public static function generate_charts_data( $args ) {
		global $wpdb, $post;
		
		$visits_table 		= "{$wpdb->prefix}wca_visits";
		$referrals_table 	= "{$wpdb->prefix}wca_referrals";

		if( isset( $args['user_id'] ) && $args['user_id'] != '' ) {
			$compare = '=';
			$user_id = $args['user_id'];
		}
		else {
			$compare = '!=';
			$user_id = 0;
		}
		$from_timestamp		= strtotime( sanitize_text_field( $args['from'] ) );
		$to_timestamp		= strtotime( sanitize_text_field( $args['to'] ) ) + DAY_IN_SECONDS - 1; // we need to consider that entire day
		
		if( $to_timestamp - $from_timestamp <= DAY_IN_SECONDS ) {
			$_format = 'hA';
			$_increament = HOUR_IN_SECONDS;
		}
		elseif( $to_timestamp - $from_timestamp <= MONTH_IN_SECONDS ) {
			$_format = 'm/d';
			$_increament = DAY_IN_SECONDS;
		}
		else{
			$_format = 'F';
			$_increament = MONTH_IN_SECONDS;
		}

		$visits_ranges = [];
		for ( $_time = $from_timestamp; $_time < $to_timestamp; $_time += $_increament ) { 
			$visits_ranges[ date( $_format, $_time ) ] = 0;
		}

		$referrals_ranges = $earnings_ranges = $visits_ranges;

		/**
		 * Visits bar graph
		 */
		$queried_visits =  $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$visits_table` WHERE `affiliate` {$compare} %d AND `time` >= %d AND `time` < %d", $user_id, $from_timestamp, $to_timestamp ) );
		foreach ( $queried_visits as $visit ) {
			$_visit_date = date( $_format, $visit->time );
			if( array_key_exists( $_visit_date, $visits_ranges ) ) {
				$visits_ranges[ $_visit_date ] += 1;
			}
			else {
				$visits_ranges[ $_visit_date ] = 0;
			}
		}

		$charts['visits'][] = [ 'Date', 'Visits' ];
		foreach ( $visits_ranges as $date => $count ) {
			$charts['visits'][] = [ $date, $count ];
		}

		/**
		 * Referrals bar graph
		 */
		$queried_referrals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$referrals_table` WHERE `affiliate` {$compare} %d AND `time` >= %d AND `time` < %d", $user_id, $from_timestamp, $to_timestamp ) );

		$earnings = 0;
		foreach ( $queried_referrals as $referral ) {
			$_referral_date = date( $_format, $referral->time );
			if( array_key_exists( $_referral_date, $referrals_ranges ) ) {
				$referrals_ranges[ $_referral_date ] += 1;
				$earnings_ranges[ $_referral_date ] += $referral->commission;
			}
			else {
				$referrals_ranges[ $_referral_date ] = 0;
				$earnings_ranges[ $_referral_date ] = 0;
			}
			$earnings += $referral->commission;
		}

		$charts['referrals'][] = [ 'Date', 'Referrals' ];
		foreach ( $referrals_ranges as $date => $count ) {
			$charts['referrals'][] = [ $date, $count ];
		}

		$charts['earnings'][] = [ 'Date', 'Amount' ];
		foreach ( $earnings_ranges as $date => $amount ) {
			$charts['earnings'][] = [ $date, $amount ];
		}

		/**
		 * Graph for visits vs referral vs earnings
		 */
		$charts['visits_referrals_earnings'] = [];
		foreach ( $visits_ranges as $date => $count ) {
			$charts['visits_referrals_earnings'][] = [ $date, $count, $referrals_ranges[ $date ], $earnings_ranges[ $date ] ];
		}

		/**
		 * Conversion pie chart
		 */
		$charts['conversions'] = [
			__( 'Visit Type', 'wc-affiliate' )		=> __( 'Count', 'wc-affiliate' ),
			__( 'Converted', 'wc-affiliate' )		=> $wpdb->query( "SELECT * FROM `$visits_table` WHERE `affiliate` {$compare} $user_id AND `time` >= $from_timestamp AND `time` < $to_timestamp AND `referral` != 0" ),
			__( 'Non-converted', 'wc-affiliate' )	=> $wpdb->query( "SELECT * FROM `$visits_table` WHERE `affiliate` {$compare} $user_id AND `time` >= $from_timestamp AND `time` < $to_timestamp AND `referral` = 0" ),
		];

		/**
		 * Products pie chart
		 */
		$charts['products'][] = [ 'Product', 'Sold' ];
		foreach ( $queried_referrals as $referral ) {
			foreach ( unserialize( $referral->products ) as $item_id => $item_name ) {
				if( !is_bool( $item_id ) ) {
					if( !isset( $charts['products'][ $item_name ] ) ) {
						$charts['products'][ $item_name ] = 0;
					}
					$charts['products'][ $item_name ] += 1; 
				}
			}
		}


		/**
		 * Affiliates table
		 */
		$curreny = get_woocommerce_currency();
		$_visits = $_referrals = [];
		foreach ( $queried_visits as $visit ) {
			if( !isset( $_visits[ $visit->affiliate ] ) ) {
				$_visits[ $visit->affiliate ] = 0;
			}
			$_visits[ $visit->affiliate ]++;
		}

		foreach ( $queried_referrals as $referral ) {
			if( !isset( $_referrals[ $referral->affiliate ]['referral'] ) ) {
				$_referrals[ $referral->affiliate ]['referral'] = 0;
			}
			$_referrals[ $referral->affiliate ]['referral']++;

			if( !isset( $_referrals[ $referral->affiliate ]['earning'] ) ) {
				$_referrals[ $referral->affiliate ]['earning'] = 0;
			}
			$_referrals[ $referral->affiliate ]['earning'] += $referral->commission;
		}
		
		foreach ( $_referrals as $affiliate => $data ) {
		    $user = get_userdata( $affiliate );
		    if ( is_a( $user, 'WP_User' ) ) {
		        $charts['afiliates'][] = [ 
		            $user->display_name,
		            isset( $_visits[ $affiliate ] ) ? $_visits[ $affiliate ] : '',
		            isset( $data['referral'] ) ? $data['referral'] : '',
		            [ 
		                'v' => isset( $data['earning'] ) ? $data['earning'] : '',
		                'f' => isset( $data['earning'] ) ? "{$curreny} {$data['earning']}" : ''
		            ]
		        ];
		    }
		}


		/**
		 * Top landingpages table
		 */
		$_landingpages = [];
		foreach ( $queried_visits as $visit ) {
			if( !isset( $_landingpages[ $visit->page_url ]['visits'] ) ) {
				$_landingpages[ $visit->page_url ]['visits'] = 0;
			}
			$_landingpages[ $visit->page_url ]['visits']++;

			if( $visit->referral != 0 ) {
				if( !isset( $_landingpages[ $visit->page_url ]['referrals'] ) ) {
					$_landingpages[ $visit->page_url ]['referrals'] = 0;
				}
				$_landingpages[ $visit->page_url ]['referrals']++;
			}
		}

		foreach ( $_landingpages as $page => $data ) {
			$visits = isset( $data[ 'visits' ] ) ? $data[ 'visits' ] : 0;
			$referrals = isset( $data[ 'referrals' ] ) ? $data[ 'referrals' ] : 0;
			$charts['landingpages'][] = [ $page, $visits, $referrals ];
		}

		/**
		 * Top referral table
		 */
		$_referralurls = [];
		foreach ( $queried_visits as $visit ) {
			if( $visit->referrer_url != '' ) {
				if( !isset( $_referralurls[ $visit->referrer_url ]['visits'] ) ) {
					$_referralurls[ $visit->referrer_url ]['visits'] = 0;
				}
				$_referralurls[ $visit->referrer_url ]['visits']++;

				if( $visit->referral != 0 ) {
					if( !isset( $_referralurls[ $visit->referrer_url ]['referrals'] ) ) {
						$_referralurls[ $visit->referrer_url ]['referrals'] = 0;
					}
					$_referralurls[ $visit->referrer_url ]['referrals']++;
				}
			}
		}
		$charts['referralurls'] = [];
		foreach ( $_referralurls as $page => $data ) {
			$visits 	= isset( $data[ 'visits' ] ) ? $data[ 'visits' ] : 0;
			$referrals 	= isset( $data[ 'referrals' ] ) ? $data[ 'referrals' ] : 0;
			$charts['referralurls'][] = [ $page, $visits, $referrals ];
		}

		/**
		 * Other stats
		 */
		$charts['stats'] = [
			'visits'	=> count( $queried_visits ),
			'referrals'	=> count( $queried_referrals ),
			'earnings'	=> get_woocommerce_currency_symbol() . number_format( $earnings, 2 ),
		];

		return apply_filters( 'wc-affiliate-chart_items', $charts, $args );
	}

	/**
	 * wc_affiliate_update_referral_status
	 *
	 * @since 1.0
	 * @return array
	 */

	public static function update_referral_status( $rows, $status = 'unpaid' ){
		global $wpdb;
		$_table = "{$wpdb->prefix}wca_referrals";

		if ( is_array( $rows ) && !empty( $rows ) ) {
			foreach ( $rows as $key => $row ) {
				$wpdb->update( $_table, [ 'payment_status' => $status ], [ 'id' => $row ], [ '%s' ] );
			}
		}
		else{
			$wpdb->update( $_table, [ 'payment_status' => $status ], [ 'id' => $rows ], [ '%s' ] );
		}
	}


	/**
	 * wc_affiliate_delete_referral
	 *
	 * @since 1.0
	 * @return array
	 */

	public static function delete_referral( $rows ){
		global $wpdb;
		$_table = "{$wpdb->prefix}wca_referrals";

		if ( is_array( $rows ) && !empty( $rows ) ) {
			foreach ( $rows as $key => $row ) {
				$wpdb->delete( $_table, array( 'id' => $row ) );
			}
		}
		else{
			$wpdb->delete( $_table, array( 'id' => $rows ) );
		}
	}


	/**
	 * wc_affiliate_update_payment_status
	 *
	 * @since 1.0
	 * @return array
	 */

	public static function update_payment_status( $rows, $status = 'unpaid' ){
		global $wpdb;
		$transactions_table = "{$wpdb->prefix}wca_transactions";
		$referrals_table 	= "{$wpdb->prefix}wca_referrals";
		
		if ( is_array( $rows ) && !empty( $rows ) ) {
			foreach ( $rows as $key => $row ) {
				$wpdb->update( $transactions_table, 
					[ 'status' => $status, 'process_at' => current_time( 'timestamp' ) ], 
					[ 'id' => $row ], 
					[ '%s', '%d' ], 
					[ '%d' ] 
				);

				$wpdb->update( $referrals_table, 
					[ 'payment_status' => $status ], 
					[ 'transaction_id' => $row ], 
					[ '%s' ], 
					[ '%d' ] 
				);
			}
		}
		else{
			$wpdb->update( $transactions_table, [ 'status' => $status ], [ 'id' => $rows ], [ '%s' ], [ '%d' ] );
			$wpdb->update( $referrals_table, [ 'payment_status' => $status ], [ 'transaction_id' => $row ], [ '%s' ], [ '%d' ] );
		}
	}

	/**
	 * wc_affiliate_delete_transaction
	 *
	 * @since 1.0
	 * @return array
	 */

	public static function delete_transaction( $rows ){
		global $wpdb;
		$_table = "{$wpdb->prefix}wca_transactions";

		if ( is_array( $rows ) && !empty( $rows ) ) {
			foreach ( $rows as $key => $row ) {
				$wpdb->delete( $_table, array( 'id' => $row ) );
			}
		}
		else{
			$wpdb->delete( $_table, array( 'id' => $rows ) );
		}
	}

	/**
	 * wc_affiliate_delete_transaction
	 *
	 * @since 1.0
	 * @return array
	 */

	public static function delete_visit( $rows ){
		global $wpdb;
		$_table = "{$wpdb->prefix}wca_visits";

		if ( is_array( $rows ) && !empty( $rows ) ) {
			foreach ( $rows as $key => $row ) {
				$wpdb->delete( $_table, array( 'id' => $row ) );
			}
		}
		else{
			$wpdb->delete( $_table, array( 'id' => $rows ) );
		}
	}

	/**
	 * User unpaid amount
	 *
	 * @since 1.0
	 * @return amount
	 */
	public static function get_user_unpaid_amount( $user_id ) {
		global $wpdb;

		$referrals_table = "{$wpdb->prefix}wca_referrals";

		if( is_multisite() ) {
		    $blog_id = get_current_blog_id();
		    $referrals_table = "{$wpdb->base_prefix}{$blog_id}_wca_referrals";
		}

		$amount 	= $wpdb->get_var( "SELECT SUM( `commission` ) FROM `$referrals_table` WHERE `affiliate` = {$user_id} AND `payment_status` = 'approved' AND `transaction_id` = 0" );

		return (float)$amount;
	}


	/**
	 * generate a shorten link from database
	 *
	 * @since 1.0
	 * @return random unique shortlink
	 */
	public static function get_shortlink( $url_id, $user_id = '' ) {	
		global $wpdb;

		$user_id    = $user_id == '' ? get_current_user_id() : $user_id;

		$shortlink 	= $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}wca_shortlinks` WHERE `affiliate` = %d AND `id` = %d",  $user_id, $url_id ) );

		$base_url 	= home_url( wc_affiliate_redirection_base() );

		if ( count( $shortlink ) < 1 ) {
			return false;
		}

		return $base_url.$shortlink[0]->identifier;
	}

	/**
	 * Defaunt different between the current date and `from` date
	 *
	 * @since 1.0
	 * @return int
	 */
	public static function date_range_diff() {
		return apply_filters( 'wc-affiliate-date_range_diff', MONTH_IN_SECONDS );
	}

	public static function closest_set( $array, $target ) {
		$set = [];
		$total = 0;
		foreach ( $array as $n ) {
			if( $n <= $target && $total + $n <= $target ) {
				$set[] = $n;
				$total += $n;
			}
		}
		return $set;
	}

	/**
	 * User eligible amount & product ids to pay with credits
	 *
	 * @since 1.0
	 * @return amount
	 */
	public static function get_eligible_info( $user_id, $cart_total ) {
		global $wpdb;
		$referrals_table = "{$wpdb->prefix}wca_referrals";

		if( is_multisite() ) {
		    $blog_id = get_current_blog_id();
		    $referrals_table = "{$wpdb->base_prefix}{$blog_id}_wca_referrals";
		}

		$commissions 	= $wpdb->get_results( "SELECT `commission`, `id` FROM `$referrals_table` WHERE `affiliate` = {$user_id} AND `payment_status` = 'approved' AND `transaction_id` = 0" );

		rsort( $commissions );

		$product_set = [];
		$total 		 = 0;
		foreach ( $commissions as $commission ) {
			if( $commission->commission <= $cart_total && $total + $commission->commission <= $cart_total ) {
				$product_set[] = $commission->id;
				$total += $commission->commission;
			}
		}

		return [ 'amount' => (float)$total, 'products' => $product_set ];
	}


	/**
	 * User eligible amount & product ids to pay with credits
	 *
	 * @since 1.0
	 * @return amount
	 */
	public static function get_payable_affiliates( $affiliate = '', $from = '', $to = '' ) {

		$payout_amount 	= 0;//wc_affiliate_get_option( 'wc_affiliate_basic', 'payout_amount' );

		/**
		 * Prepare the data
		 */
		global $wpdb;
		$referral_table = "{$wpdb->prefix}wca_referrals";

		if( is_multisite() ) {
		    $blog_id 		= get_current_blog_id();
		    $referral_table 	= "{$wpdb->base_prefix}{$blog_id}_wca_referrals";
		}

		$sql = "SELECT * FROM `{$referral_table}` WHERE `payment_status` = 'approved'";

		if( $affiliate != '' ) {
		    $sql .= " AND `affiliate` = '{$affiliate}'";
		}

		if( $from != '' && $to != '' ) {
			$form_date 	= strtotime( $from );
			$to_date 	= strtotime( $to ) + DAY_IN_SECONDS - 1; // we need to consider that entire day
		    $sql 	   .= " AND `time` >= '{$form_date}' AND `time` <= '{$to_date}'";
		}

		$referrals = $wpdb->get_results( $sql );


		$_affiliates = [];
		foreach ( $referrals as $referral ) {
			
			if( !isset( $_affiliates[ $referral->affiliate ] ) ) {
				$_affiliates[ $referral->affiliate ] = 0;
			}

			$_affiliates[ $referral->affiliate ] += $referral->commission;
		}

		$affiliates = [];
		foreach ( $_affiliates as $affiliate_id => $commission ) {
			if ( $commission >= (float) $payout_amount ) {
				$affiliates[ $affiliate_id ] = $commission;
			}
		}

		return $affiliates;
	}


	/**
	 * Count items from different DB tables
	 *
	 * @since 1.0
	 * @return amount
	 */
	public static function item_count( $type = null ) {
		if( is_null( $type ) ) return 0;

		global $wpdb;

		switch ( $type ) {

			case 'affiliates':
				$table = $wpdb->usermeta;
				return $wpdb->query( "SELECT * FROM `{$table}` WHERE `meta_key` = '_wc_affiliate_status' AND `meta_value` = 'active'" );
				break;
			
			case 'visits':
				$table = $wpdb->prefix . 'wca_visits';
				return $wpdb->query( "SELECT * FROM `{$table}`" );
				break;
			
			case 'referrals':
				$table = $wpdb->prefix . 'wca_referrals';
				return $wpdb->query( "SELECT * FROM `{$table}`" );
				break;
			
			default:
				# code...
				break;
		}

		return 0;
	}

	/**
	 * wc_affiliate_get_commission_type
	 *
	 * @since 1.0
	 * @return amount
	 */
	public static function get_commission_type() {
		$options = [
			'' 			=> __( 'Commission Type', 'wc-affiliate' ),
			'fixed' 	=> __( 'Fixed', 'wc-affiliate' ),
			'percent' 	=> __( 'Percent', 'wc-affiliate' ),
		];
		return $options;
	}

	/**
	 * wc_affiliate_get_commission_type
	 *
	 * @since 1.0
	 * @return amount
	 */
	public static function get_time_units() {
		$options = [
			MINUTE_IN_SECONDS 	=> __( 'Minutes', 'wc-affiliate' ),
			HOUR_IN_SECONDS 	=> __( 'Hours', 'wc-affiliate' ),
			DAY_IN_SECONDS 		=> __( 'Days', 'wc-affiliate' ),
			MONTH_IN_SECONDS 	=> __( 'Months', 'wc-affiliate' ),
			YEAR_IN_SECONDS 	=> __( 'Years', 'wc-affiliate' ),
		];
		return $options;
	}

	/**
	 * wc_affiliate_payout_options
	 *
	 * @since 1.0
	 * @return payout options
	 */
	public static function payout_options() {
		$options = [
			'mannual' 	=> __( 'Mannual', 'wc-affiliate' ),
		];
		return apply_filters( 'wc_affiliate_payout_options', $options );
	}

	public static function get_multilevel_commissions( $user_id, $levels, $amount, $instance = 1 ) {

		$referrers	= [];

		$_referrer = get_user_meta( $user_id, '_wc_affiliate_referrer', true );

		if( $_referrer && $instance < $levels ) {
			$instance++;

			$commission_type 	= self::get_option( 'wc_affiliate_mlc', "commission_{$instance}_type", 'percent' );
			$commission_amount 	= self::get_option( 'wc_affiliate_mlc', "commission_{$instance}_amount", 20 );
			
			if ( $commission_type == 'percent' ) {
				$commission = $amount * ( $commission_amount * 0.01 );
			}
			else{
				$commission = $commission_amount;
			}

			$referrers[ $_referrer ] = $commission;
			$referrers += self::get_multilevel_commissions( $_referrer, $levels, $amount, $instance );
		}

		return $referrers;
	}

	public static function ncrypt() {
	    $ncrypt = new \mukto90\Ncrypt;
	    return $ncrypt;
	}


	/**
	 * Check if the user's email is verified
	 *
	 * @author Jakaria Istauk <jakariamd35@gmail.com>
	 * 
	 * @since 2.0
	 */
	public static function is_mail_verified( $user_id = null ) {
		if( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$applied = get_user_meta( $user_id, '_wc_affiliate_time_applied', true );
		$enabled = Helper::get_option( 'wc_affiliate_basic', 'enable_email_validation' );

		if ( !$enabled || $enabled != 'on' || !$applied ) return true;
		$status 			= get_user_meta( $user_id, '_wc_affiliate_status', true );
		$promotion_method 	= get_user_meta( $user_id, '_wc_affiliate_promotion_method', true );

		if( ( !$status && $promotion_method ) || ( !$status && !$promotion_method ) ) return false;
		
		return true;
	}
}