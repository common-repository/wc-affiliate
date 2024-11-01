<?php
/**
 * All public facing functions
 */
namespace Codexpert\WC_Affiliate;
use Codexpert\Plugin\Base;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Front
 * @author codexpert <hello@codexpert.io>
 */
class Front extends Base {

	public $plugin;

	public $slug;

	public $name;
	
	public $version;
	
	public static $license;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->slug = $this->plugin['TextDomain'];
		$this->name = $this->plugin['Name'];
		$this->version = $this->plugin['Version'];
	}
	
	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$min = defined( 'WCAFFILIATE_DEBUG' ) && WCAFFILIATE_DEBUG ? '' : '.min';
		
		wp_enqueue_style( 'jquery-ui-datepicker', plugins_url( "/assets/css/jquery-ui{$min}.css", WCAFFILIATE ) );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		

		wp_enqueue_style( $this->slug . '-google-fonts', 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap' );
		wp_enqueue_style( 'fancybox', plugins_url( "/assets/css/jquery.fancybox.min.css", WCAFFILIATE ) );
		wp_enqueue_script( 'fancybox', plugins_url( "/assets/js/jquery.fancybox.min.js", WCAFFILIATE ), [ 'jquery' ] );

		wp_enqueue_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js', [ 'jquery' ] );
		wp_enqueue_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css', [], '5.15.3' );

		global $post;
		if( is_object( $post ) && has_shortcode( $post->post_content, 'wc-affiliate-dashboard' ) && Helper::is_active_affiliate() ) {
			
			wp_enqueue_script( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js', [], '5.15.3', true );

			if( ( !isset( $_GET['tab'] ) || ( isset( $_GET['tab'] ) && sanitize_text_field( $_GET['tab'] ) == 'summary' ) ) ){
				wp_enqueue_script( "{$this->slug}-gstatic", 'https://www.gstatic.com/charts/loader.js', [], '', true );
				wp_enqueue_script( "{$this->slug}-chart", plugins_url( "/assets/js/chart.front{$min}.js", WCAFFILIATE ), [], $this->version, true );
			}
		}
		
		wp_enqueue_media();
		
		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/front{$min}.css", WCAFFILIATE ), '', time(), 'all' );
		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/front{$min}.js", WCAFFILIATE ), [ 'jquery' ], $this->version, true );
		
		$localized = [
			'nonce'		=> wp_create_nonce( $this->slug ),
			'ajaxurl'	=> admin_url( 'admin-ajax.php' ),
			'charts'	=> apply_filters( "{$this->slug}-front_charts", [] ),
			'token'		=> Helper::get_token(),
			'ref_key'	=> wc_affiliate_get_ref_key(),
			'_nonce'	=> wp_create_nonce( $this->slug ),
			'has_pro'	=> Helper::has_pro(),
			'enable_recaptcha'	=> Helper::get_option( 'wc_affiliate_advanced', 'wc_affiliate_enable_recaptcha' ),
			'recaptcha_message'	=> __( 'Please verify you are humann!', 'wc-affiliate' ),
		];
		wp_localize_script( $this->slug, 'WCAFFILIATE', apply_filters( "{$this->slug}-localized", $localized ) );
	}

	/**
	 * Add some script to head
	 */
	public function head() {}

	/**
	 * Set affiliate token
	 */
	public static function set_token() {

		if( is_admin() ) return;
		
		$_key 			= wc_affiliate_get_ref_key();
		$_cookie_name 	= wc_affiliate_get_cookie_name();
		$_cookie_visit 	= wc_affiliate_get_visit_cookie_name();
		// has the key?
		if( !isset( $_GET[ $_key ] ) || ( $affiliate = sanitize_text_field( $_GET[ $_key ] ) ) == '' ) return;

		// invalid ref value?
		if( ( $user = get_user_by( wc_affiliate_token_type(), $affiliate ) ) == false ) return;

		// self referral?
		if( !wc_affiliate_allow_self_referral() && $user->ID == get_current_user_id() ) return;

		// not an active affiliate?
		if( !Helper::is_active_affiliate( $user->ID ) ) return;
		
		if( !isset( $_COOKIE[ $_cookie_name ] ) || wc_affiliate_allow_overwrite() ) {
		
			$expiry = current_time( 'timestamp' ) + wc_affiliate_cookie_expiry();
			setcookie( $_cookie_name, $affiliate, $expiry, COOKIEPATH, COOKIE_DOMAIN );

			if( wc_affiliate_log_enabled() ) {
				global $wpdb;
				$wpdb->insert(
					$wpdb->prefix . 'wca_visits',
					[
						'affiliate'		=> $user->ID,
						'referral'		=> 0,
						'page_url'		=> Helper::get_landing_uri(),
						'referrer_url'	=> wp_get_raw_referer(),
						'campaign'		=> isset( $_GET['campaign'] ) ? sanitize_text_field( $_GET['campaign'] ) : '',
						'ip'			=> Helper::get_user_ip(),
						'time'			=> current_time( 'timestamp' ),
					],
					[
						'%d',
						'%d',
						'%s',
						'%s',
						'%s',
						'%s',
						'%d',
					]
				);

				// store visitor #
				setcookie( $_cookie_visit, $wpdb->insert_id, $expiry, COOKIEPATH, COOKIE_DOMAIN );

				//click bonus
				if ( !Helper::get_option( 'wc_affiliate_basic', 'click_bonus_enabled' ) ) return;

				$commission 	= Helper::get_option( 'wc_affiliate_basic', 'click_bonus_amount' );
				$affiliate_id 	= sanitize_text_field( $_GET[ $_key ] );
				$visit 			= $wpdb->insert_id;

				wc_affiliate_insert_credit( $affiliate_id, $commission, 'visit', $visit,  0, [], 0, 'pending' );



			}
		}
	}

	/**
	 * Very user email
	 * 
	 * @author Jakaria Istauk <jakariamd35@gmail.com>
	 * 
	 * @since 2.0.2
	*/

	public function verify_email(){
		if ( !Helper::get_option( 'wc_affiliate_basic', 'enable_email_validation' ) ) return;

		if( !isset( $_GET['validate'] ) || $_GET['validate'] == '' ) return;

		$validation_data 	= Helper::ncrypt()->decrypt( $_GET['validate'] );
		$validation_data 	= json_decode( $validation_data );

		if( !$validation_data ) return;

		$expiry_type 	= Helper::get_option( 'wc_affiliate_basic', 'evalidation_expiary_type', 1 );
		$expiry_unit 	= Helper::get_option( 'wc_affiliate_basic', 'evalidation_expiary_unit', HOUR_IN_SECONDS );
		$expiry_time	= $expiry_unit * $expiry_unit;
		$time_dif 		= time() - $validation_data->time;

		if( $time_dif > $expiry_time ) return;
		$auto_approved 	= Helper::get_option( 'wc_affiliate_basic', 'auto_approve_affiliate' );
		$status 		= $auto_approved ? 'active' : 'pending';

		update_user_meta( $validation_data->id, '_wc_affiliate_status', $status );
	}

	/**
	 * Set referrals
	 */
	public function add_credit( $order_id ) {
		if( get_post_meta( $order_id, '_wc_affiliate_credited', true ) != '' ) return;

		global $wpdb;
		$order 	= new \WC_Order( $order_id );

		$users = Helper::get_affiliate_for_credit( $order );

		if( !$users ) return;

		$user_ids = [];
		foreach( $users as $_user ){
			$user_ids[] = $_user->ID;
		}

		$_cookie_name 	= wc_affiliate_get_cookie_name();
		$_cookie_visit 	= wc_affiliate_get_visit_cookie_name();
		$visit 			= isset( $_COOKIE[ $_cookie_visit ] ) ? sanitize_text_field( $_COOKIE[ $_cookie_visit ] ) : 0;

		$order->get_status();

		$order_total	= $order->get_total();
		$order_status	= $order->get_status();
		// $products 		= serialize( $order->get_items() );
		$payment_status	= Helper::get_option( 'wc_affiliate_basic', "rf-status-for-{$order_status}", 'pending' );

		$products 	= [];
		$items 		= $order->get_items();
		foreach ( $items as $item ) {
		    $products[ $item->get_product_id() ] = $item->get_name();
		}

		$commissions = Helper::calculate_commissions( $order );

		foreach ( $commissions as $user_id => $commission ) {

			wc_affiliate_insert_credit( $user_id, $commission, 'sale', $visit, $order_id, $products, $order_total, $payment_status );

			$admin_url 		= admin_url( 'admin.php?page=referrals' );
			$referral_url 	= add_query_arg( 'referral', $wpdb->insert_id, $admin_url );
			if ( in_array( $user_id, $user_ids ) ) {

				/**
				 * update referral data
				 */
				$wpdb->update( $wpdb->prefix . 'wca_visits',
					[
						'referral' 	=> $wpdb->insert_id
					], 
					[
						'id' 		=> $visit
					] 
				);
			}


			// update the order
			update_post_meta( $order_id, '_wc_affiliate_credited', $commission );

			$affiliate 	= get_user_by( 'ID', $user_id );
			$order->add_order_note( sprintf( '%s affiliate commission assigned to <a href="%s">%s</a>!', wc_price( $commission, [ 'decimals' => 2 ] ), $referral_url, $affiliate->display_name ) );

			do_action( 'wc-affiliate-add_credit', $affiliate, $order_id );
		}

		// should we remove cookies once the order is placed?
		if( wc_affiliate_credit_once() ) {
			setcookie( $_cookie_name, 0, time() - 1, COOKIEPATH, COOKIE_DOMAIN );
			setcookie( $_cookie_visit, 0, time() - 1, COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	/**
	 * paged query_vars set null
	 */
	public function alter_query_vars( $query ) {
		global $wp_query;
		
		if ( isset( $wp_query->query ) && is_array( $wp_query->query ) && array_key_exists( 'affiliate', $wp_query->query ) ) {
			$query->set( 'paged', null );
		}
	}

	public function generate_charts_data( $charts ) {
		global $post;

		if( !function_exists( 'WC' ) || ( is_object( $post ) && !has_shortcode( $post->post_content, 'wc-affiliate-dashboard' ) ) && !is_account_page() ) return $charts;

		if ( isset( $_GET['tab'] ) && $_GET['tab'] != 'summary' ) return $charts;

		$from	= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
		$to		= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y' );

		$args = [
			'from'		=> $from,
			'to'		=> $to,
			'user_id'	=> get_current_user_id(),
		];

		return Helper::generate_charts_data( $args );
		
	}

	public function avatar_url( $url, $id_or_email, $args ) {
		// $current_user_id = get_current_user_id();
		
		// if( $id_or_email != $current_user_id ) return $url;

		$avatar = get_user_meta( $id_or_email, '_wc_affiliate_avatar', true );
		
		if ( $avatar ) {
			return $avatar;
		}

	    return $url; 
	}

	public function tofloat($num) {
	    $dotPos = strrpos($num, '.');
	    $commaPos = strrpos($num, ',');
	    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
	        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
	  
	    if (!$sep) {
	        return floatval( preg_replace( "/[^0-9]/", "", $num ) );
	    }

	    return floatval(
	        preg_replace( "/[^0-9]/", "", substr( $num, 0, $sep ) ) . '.' .
	        preg_replace( "/[^0-9]/", "", substr( $num, $sep+1, strlen( $num ) ) )
	    );
	}

	public function use_credit_to_pay()	{
		if ( !can_pay_with_credit() ) return;
		$user_id 		= get_current_user_id();
		$is_affiliate 	= get_user_meta( $user_id, '_wc_affiliate_status', true ) == 'active';

		if ( !$is_affiliate ) return;
		$curreny 		= get_woocommerce_currency_symbol();
		$cart_total 	= str_replace( $curreny, "", WC()->cart->get_cart_total());
		$cart_total 	= $this->tofloat( $cart_total );	
		$unpaid_amount 	= Helper::get_user_unpaid_amount( $user_id );
		$eligible 		= Helper::get_eligible_info( $user_id, $cart_total );
		$checked 		= WC()->session->get('wf_credit') ? 'checked' : '';

		if ( $eligible['amount'] <= 0 ) return;

		?>
			<tr>
				<th><?php esc_html_e( 'Pay with credit', 'wc-affiliate' ); ?></th>
				<td>
					<input type="checkbox" name="pay_with_credit" id="pay_with_credit" <?php echo $checked ?>>
					<span><?php echo sprintf( __( 'You have total %s%s. You can pay %s%s from your credits', 'wc-affiliate' ), $curreny, '<strong>'.$unpaid_amount.'</strong>', $curreny, '<strong>'.$eligible['amount'].'</strong>' ) ?></span>
					<input type="hidden" name="wf_cart_total" id="wf_cart_total" value="<?php echo $cart_total; ?>">
				</td>
			</tr>
		<?php
	}

	public function apply_credit_pay( $cart ) {
		$credit = WC()->session->get('wf_credit');

	    if ( $credit != '' ) {
	    	$label = __( 'Credit Applied', 'wc-affiliate' );
	        WC()->cart->add_fee( $label, -$credit );
	    }
	}

	public function remove_banner_tab( $tabs ){
		if ( !Helper::has_pro() && !current_user_can('manage_options') ) {
			unset( $tabs['banners'] );
		}
		return $tabs;
	}

	public function loader_html(){
		echo Helper::get_template( 'loader' );
	}
}