<?php
/**
 * All admin facing functions
 */
namespace Codexpert\WC_Affiliate;
use Codexpert\Plugin\Base;
use Codexpert\Plugin\Wizard;
use Codexpert\Plugin\Metabox;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Admin
 * @author codexpert <hello@codexpert.io>
 */
class Admin extends Base {

	public $plugin;
	public $slug;
	public $name;
	public $version;
	public $admin_url;

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

		wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swa' );

		wp_enqueue_style( 'chosen', plugins_url( "/assets/css/chosen.min.css", WCAFFILIATE ) );
		wp_enqueue_script( 'chosen', plugins_url( "/assets/js/chosen.jquery.min.js", WCAFFILIATE ) );

		if( function_exists( 'WC' ) && isset( $_GET['page'] ) && ( sanitize_text_field( $_GET['page'] ) == 'wc-affiliate' ) ) {
			wp_enqueue_script( 'gstatic', 'https://www.gstatic.com/charts/loader.js', [], '', true );
			wp_enqueue_script( "{$this->slug}-chart", plugins_url( "/assets/js/chart.admin{$min}.js", WCAFFILIATE ), [ 'jquery' ], $this->version, true );
		}

		wp_enqueue_style( $this->slug . '-cx-grid', plugins_url( "/assets/css/cx-grid{$min}.css", WCAFFILIATE ), '', $this->version, 'all' );
		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/admin{$min}.css", WCAFFILIATE ), '', $this->version, 'all' );

		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/admin{$min}.js", WCAFFILIATE ), [ 'jquery' ], $this->version, true );
		
		$localized = [
			'nonce'		=> wp_create_nonce( $this->slug ),
			'charts'	=> apply_filters( "{$this->slug}-admin_charts", [] ),
			'admin_url'	=> admin_url( 'admin.php' ),
		];
		wp_localize_script( $this->slug, 'WCAFFILIATE', apply_filters( "{$this->slug}-admin_localized", $localized ) );
	}

	/**
	 * Add some script to head
	 */
	public function head() {}

	/**
	 * Internationalization
	 */
	public function i18n() {
		load_plugin_textdomain( 'wc-affiliate', false, dirname( plugin_basename( WCAFFILIATE ) ) . '/languages/' );
	}

	public function action_links( $links ) {
		$this->admin_url = admin_url( 'admin.php' );

		$new_links = [
			'settings'	=> sprintf( '<a href="%1$s">' . __( 'Settings', 'cx-plugin' ) . '</a>', add_query_arg( 'page', $this->slug, $this->admin_url ) )
		];
		
		return array_merge( $new_links, $links );
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		
		if ( $this->plugin['basename'] === $plugin_file ) {
			$plugin_meta['help'] = '<a href="https://help.codexpert.io/" target="_blank" class="cx-help">' . __( 'Help', 'cx-plugin' ) . '</a>';
		}

		return $plugin_meta;
	}

	public function install() {
		
		/**
		 * Create database tables
		 */
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		/**
		 * visits table
		 */
		$visits_sql = "CREATE TABLE `{$wpdb->prefix}wca_visits` (
		    id int(11) NOT NULL AUTO_INCREMENT,
		    affiliate int(11) NOT NULL,
		    referral int(11) NOT NULL DEFAULT 0,
		    page_url varchar(255) NOT NULL,
		    referrer_url varchar(255) NOT NULL DEFAULT '',
		    campaign varchar(255) NOT NULL DEFAULT '',
		    ip varchar(15) NOT NULL,
		    time int(10) NOT NULL,
		    UNIQUE KEY id (id)
		);";

		dbDelta( $visits_sql );

		/**
		 * referrals table
		 */
		$referrals_sql = "CREATE TABLE `{$wpdb->prefix}wca_referrals` (
		    id int(11) NOT NULL AUTO_INCREMENT,
		    affiliate int(11) NOT NULL,
		    type varchar(32) NOT NULL,
		    visit int(11) NOT NULL DEFAULT 0,
		    order_id int(16) NOT NULL,
		    products text NOT NULL,
		    order_total FLOAT(16,2) NOT NULL,
		    commission FLOAT(16,2) NOT NULL,
		    payment_status varchar(16) NOT NULL,
		    transaction_id int(16) NOT NULL DEFAULT 0,
		    time int(10) NOT NULL,
		    UNIQUE KEY id (id)
		);";

		dbDelta( $referrals_sql );

		/**
		 * transactions table
		 */
		$transactions_sql = "CREATE TABLE `{$wpdb->prefix}wca_transactions` (
		    id int(11) NOT NULL AUTO_INCREMENT,
		    affiliate int(11) NOT NULL,
		    amount FLOAT(16,2) NOT NULL,
		    payment_method varchar(255) NOT NULL,
		    txn_id varchar(255) NOT NULL,
		    status varchar(16) NOT NULL,
		    request_at int(10) NOT NULL,
		    process_at int(10) NOT NULL,
		    UNIQUE KEY id (id)
		);";

		dbDelta( $transactions_sql );

		if ( !wp_next_scheduled ( 'wc_affiliate_daily' )) {
		    wp_schedule_event( time(), 'daily', 'wc_affiliate_daily' );
		}
		
		if( get_option( 'wc-affiliate-docs-json' ) == '' ) {
			$this->daily();
		}

		if( ! get_option( 'wc-affiliate_survey' ) ){
			update_option( 'wc-affiliate_survey', time() );
		}

		/**
		 * Create new role 
		 * 
		 * Role Name : affiliate
		 * 
		 * Clone all capabilites of author & assign to affiliate.
		 */

		global $wp_roles;	   
		
		$author_role 	= $wp_roles->get_role('author');

		if ( $author_role ) {
			// Clone the 'Author' role capabilities
			$new_role_name = 'affiliate'; 

			// Check if the new role name doesn't already exist
			if ( ! isset($wp_roles->roles[$new_role_name]) ) {
				$capabilities = $author_role->capabilities;

				// Add a new role based on the 'Author' role
				add_role( $new_role_name, 'Affiliate', $capabilities );
			}
		}
	}

	/**
	 * Daily events
	 */
	public function daily() {
		/**
		 * Sync docs from https://help.codexpert.io daily
		 *
		 * @since 1.0
		 */
	    if( isset( $this->plugin['doc_id'] ) && !is_wp_error( $_docs_data = wp_remote_get( "https://help.codexpert.io/wp-json/wp/v2/docs/?parent={$this->plugin['doc_id']}&per_page=20" ) ) ) {
	        update_option( 'wc-affiliate-docs-json', json_decode( $_docs_data['body'], true ) );
	    }
	}

	/**
	 * Adds a widget in /wp-admin/index.php page
	 *
	 * @since 1.0
	 */
	public function dashboard_widget() {
		wp_add_dashboard_widget( 'cx-overview', __( 'Latest From Our Blog', 'wc-affiliate' ), [ $this, 'callback_dashboard_widget' ] );

		// Move our widget to top.
		global $wp_meta_boxes;

		$dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
		$ours = [
			'cx-overview' => $dashboard['cx-overview'],
		];

		$wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $ours, $dashboard );
	}

	/**
	 * Call back for dashboard widget in /wp-admin/
	 *
	 * @see dashboard_widget()
	 *
	 * @since 1.0
	 */
	public function callback_dashboard_widget() {
		$posts = get_option( 'codexpert-blog-json', [] );
		$utm = [ 'utm_source' => 'dashboard', 'utm_medium' => 'metabox', 'utm_campaign' => 'blog-post' ];
		
		if( count( $posts ) > 0 ) :
		
		$posts = array_slice( $posts, 0, 5 );

		echo '<ul id="cx-posts-wrapper">';
		
		foreach ( $posts as $post ) {

			$post_link = add_query_arg( $utm, $post['link'] );
			$title = sanitize_text_field( $post['title']['rendered'] );
			$content = wpautop( wp_trim_words( sanitize_text_field( $post['content']['rendered'] ), 10 ) );
			echo "
			<li>
				<a href='" . esc_url( $post_link ) . "' target='_blank'><span aria-hidden='true' class='cx-post-title-icon dashicons dashicons-external'></span> <span class='cx-post-title'>{$title}</span></a>
				{$content}
			</li>";
		}
		
		echo '</ul>';
		endif; // count( $posts ) > 0

		$_links = apply_filters( 'cx-overview_links', [
			'products'	=> [
				'url'		=> add_query_arg( $utm, 'https://codexpert.io/products/' ),
				'label'		=> __( 'Products', 'wc-affiliate' ),
				'target'	=> '_blank',
			],
			'hire'	=> [
				'url'		=> add_query_arg( $utm, 'https://codexpert.io/hire/' ),
				'label'		=> __( 'Hire Us', 'wc-affiliate' ),
				'target'	=> '_blank',
			],
		] );

		$footer_links = [];
		foreach ( $_links as $id => $link ) {
			$_has_icon = ( $link['target'] == '_blank' ) ? '<span class="screen-reader-text">' . __( '(opens in a new tab)', 'wc-affiliate' ) . '</span><span aria-hidden="true" class="dashicons dashicons-external"></span>' : '';

			$footer_links[] = "<a href='{$link['url']}' target='{$link['target']}'>{$link['label']}{$_has_icon}</a>";
		}

		echo '<p class="community-events-footer">' . esc_html( implode( ' | ', $footer_links ) ) . '</p>';
	}

	public function add_menu() {
		$count_icon = '';

		add_menu_page( __( 'WC Affiliate', 'wc-affiliate' ), __( 'WC Affiliate', 'wc-affiliate' ), 'manage_options', $this->slug, '', 'dashicons-chart-line', 25 );
		add_submenu_page( $this->slug, __( 'Summary', 'wc-affiliate' ), __( 'Summary', 'wc-affiliate' ), 'manage_options', $this->slug, [ $this, 'callback_summary' ] );
		add_submenu_page( $this->slug, __( 'Affiliates', 'wc-affiliate' ), __( 'Affiliates', 'wc-affiliate' ), 'manage_options', 'affiliates', [ $this, 'callback_affiliates' ] );
		add_submenu_page( $this->slug, __( 'Visits', 'wc-affiliate' ), __( 'Visits', 'wc-affiliate' ), 'manage_options', 'visits', [ $this, 'callback_visits' ] );
		add_submenu_page( $this->slug, __( 'Referrals', 'wc-affiliate' ), __( 'Referrals', 'wc-affiliate' ), 'manage_options', 'referrals', [ $this, 'callback_referrals' ] );
		add_submenu_page( $this->slug, __( 'Payables', 'wc-affiliate' ), __( 'Payables', 'wc-affiliate' ) .$count_icon , 'manage_options', 'payables', [ $this, 'callback_eligible_affiliates' ] );
		add_submenu_page( $this->slug, __( 'Transactions', 'wc-affiliate' ), __( 'Transactions', 'wc-affiliate' ), 'manage_options', 'transactions', [ $this, 'callback_transactions' ] );
		if ( !Helper::has_pro() ) {
			add_submenu_page( $this->slug, __( 'Banners', 'wc-affiliate' ), __( 'Banners', 'wc-affiliate' ), 'manage_options', 'banners', [ $this, 'callback_banners' ] );
		}

	}

	public function callback_summary() {
		echo Helper::get_template( 'summary', 'views/admin/menus', [ 'plugin' => $this->plugin ] );
	}

	public function callback_affiliates() {
		echo Helper::get_template( 'affiliates', 'views/admin/menus', [ 'plugin' => $this->plugin ] );
	}

	public function callback_visits() {
		echo Helper::get_template( 'visits', 'views/admin/menus', [ 'plugin' => $this->plugin ] );
	}

	public function callback_referrals() {
		echo Helper::get_template( 'referrals', 'views/admin/menus', [ 'plugin' => $this->plugin ] );
	}

	public function callback_transactions() {
		echo Helper::get_template( 'transactions', 'views/admin/menus', [ 'plugin' => $this->plugin ] );
	}

	public function callback_banners()	{
		echo Helper::get_template( 'banners', 'views/placeholders', [ 'plugin' => $this->plugin ] );
	}

	public function callback_eligible_affiliates() {
		echo Helper::get_template( 'payables', 'views/admin/menus', [ 'plugin' => $this->plugin ] );
	}

	public function callback_export_import() {
		echo Helper::get_template( 'export-import', 'views/admin/menus' );
	}

	public function generate_charts_data( $charts ) {

		if( !function_exists( 'WC' ) || !isset( $_GET['page'] ) || $_GET['page'] != 'wc-affiliate' ) return $charts;
		
		$from	= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
		$to		= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y' );

		$args = [
			'from'	=> $from,
			'to'	=> $to,
		];

		if( isset( $_GET['affiliate'] ) && $_GET['affiliate'] != '' ) {
			$args['user_id'] = (int)sanitize_text_field( $_GET['affiliate'] );
		}

		return Helper::generate_charts_data( $args );
	}

    public function show_commission_fields( $user ) {
		echo Helper::get_template( 'commission-fields', 'views/admin/user', [ 'user' => $user ] );
	}

    public function save_commission_fields( $user_id ) {

        if( isset( $_POST['_wc_affiliate_status'] ) ) {

        	if( $_POST['_wc_affiliate_status'] == 'active' ){
        		
        		update_user_meta( $user_id, '_wc_affiliate_status', sanitize_text_field( $_POST['_wc_affiliate_status'] ) );
        		update_user_meta( $user_id, '_wc_affiliate_time_applied', time() );
        	}
        	else {
        		update_user_meta( $user_id, '_wc_affiliate_status', sanitize_text_field( $_POST['_wc_affiliate_status'] ) );
        	}
        	
        }

        if( isset( $_POST['commission_type'] ) ) {
        	update_user_meta( $user_id, 'commission_type', sanitize_text_field( $_POST['commission_type'] ) );
        }

        if( isset( $_POST['commission_amount'] ) ) {
        	update_user_meta( $user_id, 'commission_amount', sanitize_text_field( $_POST['commission_amount'] ) );
        }
    }

    public function personal_show_commission_fields( $user ) {
    	if ( !current_user_can( 'administrator' ) ) return $user;

		echo Helper::get_template( 'commission-fields', 'views/admin/user', [ 'user' => $user ] );
	}

    public function personal_save_commission_fields( $user_id ) {
    	if ( !current_user_can('administrator') ) return $user_id;

        if( isset( $_POST['_wc_affiliate_status'] ) ) {
        	update_user_meta( $user_id, '_wc_affiliate_status', sanitize_text_field( $_POST['_wc_affiliate_status'] ) );
        }

        if( isset( $_POST['commission_type'] ) ) {
        	update_user_meta( $user_id, 'commission_type', sanitize_text_field( $_POST['commission_type'] ) );
        }

        if( isset( $_POST['commission_amount'] ) ) {
        	update_user_meta( $user_id, 'commission_amount', sanitize_text_field( $_POST['commission_amount'] ) );
        }
    }

	public function show_product_comission_fields() {
		
		$product = wc_get_product( get_the_ID() );

		if( Helper::get_option( 'wc_affiliate_basic', 'commission_base' ) != 'product_price' ) return;

		if ( !$product->is_type( 'simple' ) ) return;

		echo "<div class='wf-affiliate_commission'>";
		woocommerce_wp_select(
			[
				'id' 			=> 'affiliate_commission',
				'type'  		=> 'checkbox', 
				'class' 		=> 'affiliate_commission',
				'wrapper_class' => '',
				'label' 		=> __( 'Affiliate Commission', 'wc-affiliate' ),
				'value' 		=> get_post_meta( get_the_ID(), 'affiliate_commission', true ),
				'options' 		=> [
					'default'   => __( 'Default', 'wc-affiliate' ),
					'disabled'	=> __( 'Disabled', 'wc-affiliate' ),
					'custom' 	=> __( 'Custom', 'wc-affiliate' )
				]
			]
		);

		$_commission_type = get_post_meta( get_the_ID(), 'commission_type', true );
		$commission_type = $_commission_type != '' ? $_commission_type : Helper::get_option( 'wc_affiliate_basic', 'commission_type' );
		woocommerce_wp_select(
			[
				'id'          	=> 'commission_type', 
				'value'       	=> get_post_meta( get_the_ID(), 'commission_type', true ),
				'class' 		=> '',
				'wrapper_class' => '',
				'label'       	=> '', 
				'selected' 		=> true,
				'value' 		=> $commission_type,
				'options' 		=> [
					''   		=> __( 'Commission Type', 'wc-affiliate' ),
					'fixed'   	=> __( 'Fixed', 'wc-affiliate' ),
					'percent' 	=> __( 'Percent', 'wc-affiliate' )
				]
			]
		);

		$_commission_amount = get_post_meta( get_the_ID(), 'commission_amount', true );
		$commission_amount = $_commission_amount != '' ? $_commission_amount : Helper::get_option( 'wc_affiliate_basic', 'commission_amount' );
		woocommerce_wp_text_input(
			[
				'id' 			=> 'commission_amount',
				'type'  		=> 'number', 
				'class' 		=> '',
				'wrapper_class' => '',
				'label' 		=> '',
				'value' 		=> $commission_amount,
			]
		);

		echo "</div>";

		if( Helper::get_option( 'wc_affiliate_basic', 'enable_discount' ) == 'on' ):
			echo "<div class='wf-customer_discount'>";
			woocommerce_wp_select(
				[
					'id' 			=> 'customer_discount',
					'type'  		=> 'checkbox', 
					'class' 		=> 'customer_discount',
					'wrapper_class' => '',
					'label' 		=> __( 'Customer Discount', 'wc-affiliate' ),
					'value' 		=> get_post_meta( get_the_ID(), 'customer_discount', true ),
					'options' 		=> [
						'default'   => __( 'Default', 'wc-affiliate' ),
						'disabled'	=> __( 'Disabled', 'wc-affiliate' ),
						'custom' 	=> __( 'Custom', 'wc-affiliate' )
					]
				]
			);

			$_discount_type = get_post_meta( get_the_ID(), 'discount_type', true );
			$discount_type = $_discount_type != '' ? $_discount_type : Helper::get_option( 'wc_affiliate_basic', 'discount_type' );
			woocommerce_wp_select(
				[
					'id'          	=> 'discount_type', 
					'value'       	=> get_post_meta( get_the_ID(), 'discount_type', true ),
					'class' 		=> '',
					'wrapper_class' => '',
					'label'       	=> '', 
					'selected' 		=> true,
					'value' 		=> $discount_type,
					'options' 		=> [
						''   		=> __( 'Discount Type', 'wc-affiliate' ),
						'fixed'   	=> __( 'Fixed', 'wc-affiliate' ),
						'percent' 	=> __( 'Percent', 'wc-affiliate' )
					]
				]
			);

			$_discount_amount = get_post_meta( get_the_ID(), 'discount_amount', true );
			$discount_amount = $_discount_amount != '' ? $_discount_amount : Helper::get_option( 'wc_affiliate_basic', 'discount_amount' );
			woocommerce_wp_text_input(
				[
					'id' 			=> 'discount_amount',
					'type'  		=> 'number', 
					'class' 		=> '',
					'wrapper_class' => '',
					'label' 		=> '',
					'value' 		=> $discount_amount,
				]
			);

			echo "</div>";
		endif;
	}

	public function save_product_comission_fields( $post_id ) {

		$product = wc_get_product( $post_id );

		if ( !$product->is_type( 'simple' ) ) return $post_id;

		$affiliate_commission = isset( $_POST['affiliate_commission'] ) ? sanitize_text_field( $_POST['affiliate_commission'] ) : '';
		$product->update_meta_data( 'affiliate_commission', $affiliate_commission );

		$commission_type = isset( $_POST['commission_type'] ) ? sanitize_text_field( $_POST['commission_type'] ) : '';
		$product->update_meta_data( 'commission_type', $commission_type );

		$commission_amount = isset( $_POST['commission_amount'] ) ? sanitize_text_field( $_POST['commission_amount'] ) : '';
		$product->update_meta_data( 'commission_amount', $commission_amount );

		if( Helper::get_option( 'wc_affiliate_basic', 'enable_discount' ) == 'on' ):

			$customer_discount = isset( $_POST['customer_discount'] ) ? sanitize_text_field( $_POST['customer_discount'] ) : '';
			$product->update_meta_data( 'customer_discount', $customer_discount );

			$discount_type = isset( $_POST['discount_type'] ) ? sanitize_text_field( $_POST['discount_type'] ) : '';
			$product->update_meta_data( 'discount_type', $discount_type );

			$discount_amount = isset( $_POST['discount_amount'] ) ? sanitize_text_field( $_POST['discount_amount'] ) : '';
			$product->update_meta_data( 'discount_amount', $discount_amount );

		endif;

		$product->save();
	}

	public function show_variation_comission_fields( $loop, $variation_data, $variation ) {

		echo "<div class='wf-affiliate_commission'>";
		woocommerce_wp_select(
			[
				'id' 			=> 'affiliate_commission[' . $loop . ']',
				'type'  		=> 'checkbox', 
				'class' 		=> 'affiliate_commission wf-variation-commission-option',
				'wrapper_class' => 'wf-variation-commission-panel',
				'label' 		=> __( 'Affiliate Commission', 'wc-affiliate' ),
				'value' 		=> get_post_meta( $variation->ID, 'affiliate_commission', true ),
				'options' 		=> [
					'default'   => __( 'Default', 'wc-affiliate' ),
					'disabled'	=> __( 'Disabled', 'wc-affiliate' ),
					'custom' 	=> __( 'Custom', 'wc-affiliate' )
				]
			]
		);

		$_commission_type = get_post_meta( $variation->ID, 'commission_type', true );
		$commission_type = $_commission_type != '' ? $_commission_type : Helper::get_option( 'wc_affiliate_basic', 'commission_type' );
		woocommerce_wp_select(
			[
				'id'          	=> 'commission_type[' . $loop . ']', 
				'value'       	=> get_post_meta( $variation->ID, 'commission_type', true ),
				'class' 		=> 'wf-variation-commission-type',
				'wrapper_class' => 'wf-variation-commission-type-panel',
				'label'       	=> '', 
				'selected' 		=> true,
				'value' 		=> $commission_type,
				'options' 		=> [
					''   		=> __( 'Commission Type', 'wc-affiliate' ),
					'fixed'   	=> __( 'Fixed', 'wc-affiliate' ),
					'percent' 	=> __( 'Percent', 'wc-affiliate' )
				]
			]
		);

		$_commission_amount = get_post_meta( $variation->ID, 'commission_amount', true );
		$commission_amount = $_commission_amount != '' ? $_commission_amount : Helper::get_option( 'wc_affiliate_basic', 'commission_amount' );
		woocommerce_wp_text_input(
			[
				'id' 			=> 'commission_amount[' . $loop . ']',
				'type'  		=> 'number', 
				'class' 		=> 'wf-variation-commission-amount',
				'wrapper_class' => 'wf-variation-commission-amount-panel',
				'label' 		=> '',
				'value' 		=> $commission_amount,
			]
		);
		echo "</div>";

		if( Helper::get_option( 'wc_affiliate_basic', 'enable_discount' ) == 'on' ):
			echo "<div class='wf-customer_discount'>";
			woocommerce_wp_select(
				[
					'id' 			=> 'customer_discount[' . $loop . ']',
					'type'  		=> 'checkbox', 
					'class' 		=> 'customer_discount wf-variation-discount-option',
					'wrapper_class' => 'wf-variation-discount-panel',
					'label' 		=> __( 'Customer Discount', 'wc-affiliate' ),
					'value' 		=> get_post_meta( $variation->ID, 'customer_discount', true ),
					'options' 		=> [
						'default'   => __( 'Default', 'wc-affiliate' ),
						'disabled'	=> __( 'Disabled', 'wc-affiliate' ),
						'custom' 	=> __( 'Custom', 'wc-affiliate' )
					]
				]
			);

			$_discount_type = get_post_meta( $variation->ID, 'discount_type', true );
			$discount_type = $_discount_type != '' ? $_discount_type : Helper::get_option( 'wc_affiliate_basic', 'discount_type' );
			woocommerce_wp_select(
				[
					'id'          	=> 'discount_type[' . $loop . ']', 
					'value'       	=> get_post_meta( $variation->ID, 'discount_type', true ),
					'class' 		=> 'wf-variation-discount-type',
					'wrapper_class' => 'wf-variation-discount-type-panel',
					'label'       	=> '', 
					'selected' 		=> true,
					'value' 		=> $discount_type,
					'options' 		=> [
						''   		=> __( 'Discount Type', 'wc-affiliate' ),
						'fixed'   	=> __( 'Fixed', 'wc-affiliate' ),
						'percent' 	=> __( 'Percent', 'wc-affiliate' )
					]
				]
			);

			$_discount_amount = get_post_meta( $variation->ID, 'discount_amount', true );
			$discount_amount = $_discount_amount != '' ? $_discount_amount : Helper::get_option( 'wc_affiliate_basic', 'discount_amount' );
			woocommerce_wp_text_input(
				[
					'id' 			=> 'discount_amount[' . $loop . ']',
					'type'  		=> 'number', 
					'class' 		=> 'wf-variation-amount-type',
					'wrapper_class' => 'wf-variation-amount-type-panel',
					'label' 		=> '',
					'value' 		=> $discount_amount,
				]
			);

			echo "</div>";

			// not working
			echo "<script>
				$ = new jQuery.noConflict();
			    $('.wf-variation-commission-option').on( 'change', function(e){
			        if ( $(this).val() == 'custom' ) {
			        	$('.wf-variation-commission-type-panel, .wf-variation-commission-amount-panel', $(this).closest('.wf-affiliate_commission')).slideDown();
			        }
			        else {
			        	$('.wf-variation-commission-type-panel, .wf-variation-commission-amount-panel', $(this).closest('.wf-affiliate_commission')).slideUp();
			        }
				}).change();
				
			    $('.wf-variation-discount-option').on( 'change', function(e){
			        if ( $(this).val() == 'custom' ) {
			        	$('.wf-variation-discount-type-panel, .wf-variation-amount-type-panel', $(this).closest('.wf-customer_discount')).slideDown();
			        }
			        else {
			        	$('.wf-variation-discount-type-panel, .wf-variation-amount-type-panel', $(this).closest('.wf-customer_discount')).slideUp();
			        }
				}).change();
			</script>";
		endif;
	}
	 
	public function save_variation_comission_fields( $variation_id, $loop ) {
		$variation = wc_get_product( $variation_id );

		$affiliate_commission = isset( $_POST['affiliate_commission'][ $loop ] ) ? sanitize_text_field( $_POST['affiliate_commission'][ $loop ] ) : '';
		$variation->update_meta_data( 'affiliate_commission', $affiliate_commission );

		$commission_type = isset( $_POST['commission_type'][ $loop ] ) ? sanitize_text_field( $_POST['commission_type'][ $loop ] ) : '';
		$variation->update_meta_data( 'commission_type', $commission_type );

		$commission_amount = isset( $_POST['commission_amount'][ $loop ] ) ? sanitize_text_field( $_POST['commission_amount'][ $loop ] ) : '';
		$variation->update_meta_data( 'commission_amount', $commission_amount );

		if( Helper::get_option( 'wc_affiliate_basic', 'enable_discount' ) == 'on' ):

			$customer_discount = isset( $_POST['customer_discount'][ $loop ] ) ? sanitize_text_field( $_POST['customer_discount'][ $loop ] ) : '';
			$variation->update_meta_data( 'customer_discount', $customer_discount );

			$discount_type = isset( $_POST['discount_type'][ $loop ] ) ? sanitize_text_field( $_POST['discount_type'][ $loop ] ) : '';
			$variation->update_meta_data( 'discount_type', $discount_type );

			$discount_amount = isset( $_POST['discount_amount'][ $loop ] ) ? sanitize_text_field( $_POST['discount_amount'][ $loop ] ) : '';
			$variation->update_meta_data( 'discount_amount', $discount_amount );

		endif;

		$variation->save();
	}

	public function custom_table_data_filter( $config, $which )	{

		if ( !isset( $config['id'] ) || !in_array( $config['id'], [ 'transaction', 'referral', 'visit', 'affiliate', 'payables' ] ) ) return;

		if ( $which != 'top' ) return;

		$from			= isset( $_GET['from'] ) && $_GET['from'] != '' ? sanitize_text_field( $_GET['from'] ) : date( 'F d, Y', current_time( 'timestamp' ) - Helper::date_range_diff() );
		$to				= isset( $_GET['to'] ) && $_GET['to'] != '' ? sanitize_text_field( $_GET['to'] ) : date( 'F d, Y', current_time( 'timestamp' ) );
		$txn_id			= isset( $_GET['txn'] ) ? sanitize_text_field( $_GET['txn'] ) : '';
		$affiliate		= isset( $_GET['affiliate'] ) ? sanitize_text_field( $_GET['affiliate'] ) : '';
		$per_page		= isset( $_GET['per_page'] ) ? (int)sanitize_text_field( $_GET['per_page'] ) : '';
		$product		= isset( $_GET['product'] ) ? sanitize_text_field( $_GET['product'] ) : '';
		
		$statuses		= [];
		if( $config['id'] == 'affiliate'  ){
			$statuses	= Helper::get_affiliate_statuses();
		}
		elseif( $config['id'] == 'referral'  ){
			$statuses	= Helper::get_referral_statuses();
		}
		elseif( $config['id'] == 'transactions'  ){
			// $statuses	= wc_affiliate_get_transactions_statuses();
			$statuses	= [];
		}

		?>
		<div class="wf-admin-data-filter">
			<?php if( $config['id'] != 'visit' ): ?>
				<?php if( $config['id'] != 'affiliate' ): ?>
					<select name="affiliate" class="wc-affiliate-chosen">
						<option value=""><?php esc_html_e( 'All Affiliates', 'wc-affiliate' ); ?></option>
						<?php 
						$users = Helper::get_affiliate_users();
						foreach ( $users as $user_id ) {
							$name = get_userdata( $user_id )->display_name;
							echo '<option value="'. esc_attr( $user_id ) .'" '. selected( $user_id, $affiliate, false ).'>'. esc_attr( $name ) .'</option>';
						}
						?>
					</select>				
				<?php endif; // $config['id'] != 'affiliate' ):
				if( count( $statuses ) > 0 ) :
				?>
				<select name="status">
					<?php 
					echo '<option value="">'. esc_html__( 'All', 'wc-affiliate' ) .'</option>';
					foreach ( $statuses as $status_key => $status_name ) {
						$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
						echo '<option value="' . esc_attr( $status_key ) . '" ' . selected( $status_key, $status, false ) . '>' . esc_html( $status_name ) . '</option>';
					}
					?>
				</select>
			<?php endif; // count( $statuses ) > 0
			endif; ?>
			<input class="datepicker" type="text" name="from" value="<?php echo esc_attr( $from ) ?>">
			<input class="datepicker" type="text" name="to" value="<?php echo esc_attr( $to ) ?>">
			<?php if( $config['id'] == 'transaction' ): ?>		
				<input type="text" name="txn" value="<?php echo esc_attr( $txn_id ); ?>" placeholder="<?php esc_attr_e( 'Transaction ID', 'wc-affiliate' ); ?>">
			<?php endif; ?>
			<?php if( $config['id'] == 'referral' ): ?>	
				<input class="" type="text" name="product" value="<?php echo esc_attr( $product ); ?>" placeholder="<?php esc_attr_e( 'Product', 'wc-affiliate' ); ?>">
			<?php endif; ?>
			<input class="" type="number" name="per_page" value="<?php echo esc_attr( $per_page ); ?>" placeholder="<?php esc_attr_e( 'Per Page', 'wc-affiliate' ); ?>">
			<input type="submit" value="<?php esc_attr_e( 'Filter', 'wc-affiliate' ); ?>" class="button button-submit wf-button">
		</div>
		<?php
	}

	public function custom_button( $section ){
		if ( Helper::has_pro() ) return;
		if ( in_array( $section['id'], [ 'wc_affiliate_mlc', 'wc_affiliate_xdomain', 'wc_affiliate_shortlinks' ] ) ) {
			$text = __( 'Unlock Feature', 'wc-affiliate' );
			$url = 'https://codexpert.io/wc-affiliate/';
			echo "<a href='{$url}' class='button button-primary wf-feature-unlock-btn'><span class='dashicons dashicons-lock'></span> {$text}</a>";
		}
	}

	public function individual_actions( $page, $action, $row_ids )	{

		if ( $page == 'affiliates' ) {
			foreach ( $row_ids as $user_id ) {
				update_user_meta( $user_id, '_wc_affiliate_status', $action );
			}
		}
		else if ( $page == 'referrals' ) {
			if ( array_key_exists( $action, Helper::get_referral_statuses() ) ) {
				Helper::update_referral_status( $row_ids, $action );
			}
			else if( $action == 'delete' ){
				Helper::delete_referral( $row_ids );
			}
		}
		else if ( $page == 'visits' ) {
			if ( $action == 'delete' ) {
				Helper::delete_visit( $row_ids );
			}
		}
		else if ( $page == 'transactions' ) {
			if ( array_key_exists( $action, Helper::get_transactions_statuses() ) ) {
				Helper::update_payment_status( $row_ids, $action );
			}
			else if( $action == 'delete' ){
				Helper::delete_transaction( $row_ids );
			}
		}
	}

	public function bulk_actions(){
		if ( !isset( $_GET['page'] ) ) return;

		$page		= isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$action		= isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		$item_id	= isset( $_GET['item_id'] ) ? sanitize_text_field( $_GET['item_id'] ) : '';

		if ( isset( $_GET['_wpnonce'] ) && isset( $_GET['item_id'] ) ) {
			if ( wp_verify_nonce( $_GET['_wpnonce'] ) ) {
				$this->individual_actions( $page, $action, $item_id );
			}
		}

		if ( !isset( $_GET['action'] ) || $_GET['action'] == -1 ) return;
		if ( !isset( $_GET['ids'] ) || empty( $_GET['ids'] ) ) return;

		$this->individual_actions( $page, $action, $_GET['ids'] );

	}

	public function update_referral_status(  $order_id, $status_from, $status_to, $that ) {
		global $wpdb;
		$order 			 = new \WC_Order( $order_id );
		$order_status 	 = $order->get_status();
		$referral_status = Helper::get_option( 'wc_affiliate_basic', "rf-status-for-{$status_to}", 'pending' );

		$wpdb->update( $wpdb->prefix . 'wca_referrals',
			[
				'payment_status'	=> $referral_status,
			],
			[
				'order_id'			=> $order_id,
				'transaction_id'	=> 0,
			]
		);
	}

	public function add_overlay( $key, $label )	{}

	public function admin_notices() {

		if( ! current_user_can( 'manage_options' ) && wca_is_pro() ) return;

		$notice_key = "_{$this->slug}_notices-dismissed";
		/**
		 * Promotional banners
		 */
		$banners = [

			// Regular promotion. Shows on 1st to 7th of every month.
			
			'holiday-deals'	=> [
				'name'	=> __( 'Wc affiliate', 'wc-affiliate' ),
				'url'	=> 'http://codexpert.io/coupons',
				'type'	=> 'image',
				'image'	=>	WOOLEMENTOR_ASSETS.'/img/holiday-deals.png',
				'from'	=> strtotime( date( '2022-12-20 23:59:59' ) ),
				'to'	=> strtotime( date( '2023-01-07 23:59:59' ) ),
			],
			
		];

		if( isset( $_GET['is-dismiss'] ) && array_key_exists( $_GET['is-dismiss'], $banners ) ) {
			$dismissed = get_option( $notice_key ) ? : [];
			$dismissed[] = sanitize_text_field( $_GET['is-dismiss'] );
			update_option( $notice_key, array_unique( $dismissed ) );
		}

		$dismissed = get_option( $notice_key ) ? : [];
		$active_banners = array_values( array_diff( array_keys( $banners ), $dismissed ) );

		$rand_index = rand( 0, count( $active_banners ) - 1 );
		$rand_img = false;
		if( isset( $active_banners[ $rand_index ] ) ) {
			$rand_img = $active_banners[ $rand_index ];
		}
		if( ! wca_is_pro() && $rand_img ) {
			$query_args = [ 'is-dismiss' => $rand_img ];

			if( count( $_GET ) > 0 ) {
				$query_args = array_map( 'sanitize_text_field', $_GET ) + $query_args;
			}

			if( isset( $banners[ $rand_img ]['from'] ) && $banners[ $rand_img ]['from'] > time() ) return;
			if( isset( $banners[ $rand_img ]['to'] ) && $banners[ $rand_img ]['to'] < time() ) return;

			?>
			<div class="notice notice-success cx-notice cx-shadow is-dismissible cx-promo cx-promo-<?php echo $banners[ $rand_img ]['type']; ?>">

				<?php if( 'image' == $banners[ $rand_img ]['type'] ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'utm_campaign' => $rand_img ], $banners[ $rand_img ]['url'] ) ); ?>" target="_blank">
					<img id="<?php echo "promo-{$rand_img}"?>" src="<?php echo $banners[ $rand_img ]['image']; ?>">
				</a>
				<?php endif; ?>

				<?php if( 'text' == $banners[ $rand_img ]['type'] ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'utm_campaign' => $rand_img ], $banners[ $rand_img ]['url'] ) ); ?>" target="_blank">
					<?php echo $banners[ $rand_img ]['text']; ?>
				</a>
				<?php endif; ?>

				<a href="<?php echo esc_url( add_query_arg( $query_args, '' ) ); ?>" class="notice-dismiss">
					<span class="screen-reader-text"></span>
				</a>

			</div>
			<?php
		}
	}

	public function reorder_subscription( $subscription, $last_order ) {

		$bonus = Helper::get_option( 'wc_affiliate_basic', 'recurring_order' );
		if( $bonus == '' ) return;
		
		global $wpdb;

		$user_id 		= get_current_user_id();
		
		update_user_meta( $user_id, 'subscription', $subscription, $prev_value = '' );
		update_user_meta( $user_id, 'last_order', $last_order, $prev_value = '' );

		$subscription 	= get_user_meta( $user_id, 'subscription', true );
		$last_order 	= get_user_meta( $user_id, 'last_order', true );
		
		if( sizeof( $subscription ) == 0 &&  sizeof( $last_order ) == 0 )return;
						
		$order_id 		= $last_order->get_ID();
		$payment_status	= 'pending';
		$order_total 	= $last_order->get_total() ; 
		$time 			= time(); 
		$order 			= wc_get_order( $order_id );
		$items 			= $order->get_items();
		$total_commission = 0;
		$subscription 	= wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'any' ) );

		foreach ($subscription as $key => $value) {
			$order 		= wc_get_order( $key );
			$parent_id 	=$order->get_parent_id();
			$rows 		= $wpdb->get_results("SELECT * FROM wp_wca_referrals WHERE `order_id` = $parent_id");		

			foreach ($rows as $key => $value) {
				$affiliate_id =  $value->affiliate;
			}
		} 

		foreach ( $items as $item ) {
		    $products[ $item->get_product_id() ] = $item->get_name();
		}

		$commission_type 	= Helper::get_option( 'wc_affiliate_basic', 'commission_type', 'percent' );
		$commission_amount 	= Helper::get_option( 'wc_affiliate_basic', 'commission_amount', 20 );

		if( $commission_type =='fixed' ){
			$total_commission = $commission_amount;
		}

		if( $commission_type =='percent' ){
			$total_commission = (( $order_total / 100 ) * $commission_amount ); 
			$total_commission = number_format((float)$total_commission, 2, '.', '');
		}

		wc_affiliate_insert_credit( $affiliate_id, $total_commission,  $type = 'sale', $visit = 0 , $order_id , $products , $order_total, $payment_status  );
	}

}