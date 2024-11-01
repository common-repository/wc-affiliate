<?php
/**
 * Plugin Name: WC Affiliate
 * Description: The Most Feature-rich Affiliate Plugin for WooCommerce. Everything You Need To Run A Full-Featured Affiliate Program
 * Plugin URI: https://codexpert.io/wc-affiliate/?utm_campaign=plugin-uri
 * Author: Codexpert
 * Author URI: https://codexpert.io/?utm_campaign=author-uri
 * Version: 2.3.5
 * Text Domain: wc-affiliate
 * Domain Path: /languages
 *
 * WC Affiliate is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * WC Affiliate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

namespace Codexpert\WC_Affiliate;
use Codexpert\Plugin\Widget;
use Codexpert\Plugin\Notice;
use Pluggable\Marketing\Survey;
use Pluggable\Marketing\Deactivator;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class for the plugin
 * @package Plugin
 * @author codexpert <hello@codexpert.io>
 */
final class Plugin {
	
	public static $_instance;

	public $plugin;
	
	public function __construct() {
		$this->include();
		$this->define();
		$this->hook();
	}

	/**
	 * Includes files
	 */
	public function include() {
		require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );
		require_once( dirname( __FILE__ ) . '/inc/functions.php' );
	}

	/**
	 * Define variables and constants
	 */
	public function define() {
        // constants
        define( 'WCAFFILIATE', __FILE__ );
        define( 'WCAFFILIATE_DIR', dirname( WCAFFILIATE ) );
        define( 'WOOLEMENTOR_ASSETS', plugins_url( 'assets', WCAFFILIATE ) );
        define( 'WCAFFILIATE_DEBUG', apply_filters( 'wc-affiliate_debug', true ) );

        // plugin data
        $this->plugin                = get_plugin_data( WCAFFILIATE );
        $this->plugin['basename']    = plugin_basename( WCAFFILIATE );
        $this->plugin['file']        = WCAFFILIATE;
        $this->plugin['server']      = apply_filters( 'wc-affiliate_server', 'https://codexpert.io/dashboard' );
        $this->plugin['min_php']     = '5.6';
        $this->plugin['min_wp']      = '4.0';
        $this->plugin['doc_id']      = 11009;
        $this->plugin['depends']     = [ 'woocommerce/woocommerce.php' => 'WooCommerce' ];
    }

	/**
	 * Hooks
	 */
	public function hook() {

		if( is_admin() ) :

			/**
			 * Setup wizard facing hooks
			 *
			 * To add an action, use $admin->action()
			 * To apply a filter, use $admin->filter()
			 */
			$wizard = new Wizard( $this->plugin );
			$wizard->filter( "plugin_action_links_{$this->plugin['basename']}", 'action_links' );
			$wizard->action( 'plugins_loaded', 'render' );
			$wizard->action( 'admin_print_styles', 'enqueue_style' );

			/**
			 * Admin facing hooks
			 *
			 * To add an action, use $admin->action()
			 * To apply a filter, use $admin->filter()
			 */
			$admin = new Admin( $this->plugin );
			$admin->activate( 'install' );
			$admin->action( 'wc_affiliate_daily', 'daily' );
			$admin->action( 'wp_dashboard_setup', 'dashboard_widget', 99 );
			$admin->action( 'admin_enqueue_scripts', 'enqueue_scripts' );
			$admin->action( 'plugins_loaded', 'i18n' );
			$admin->filter( "plugin_action_links_{$this->plugin['basename']}", 'action_links' );
			$admin->filter( 'plugin_row_meta', 'plugin_row_meta', 10, 2 );
			$admin->action( 'admin_menu', 'add_menu' );
			$admin->filter( 'wc-affiliate-admin_charts', 'generate_charts_data' );
			$admin->action( 'edit_user_profile', 'show_commission_fields' );
			$admin->action( 'edit_user_profile_update', 'save_commission_fields' );
			$admin->action( 'show_user_profile', 'personal_show_commission_fields' );
			$admin->action( 'personal_options_update', 'personal_save_commission_fields' );
			$admin->action( 'woocommerce_product_options_general_product_data', 'show_product_comission_fields' );
			$admin->action( 'woocommerce_process_product_meta', 'save_product_comission_fields' );
			$admin->action( 'woocommerce_variation_options_pricing', 'show_variation_comission_fields', 10, 3 );
			$admin->action( 'woocommerce_save_product_variation', 'save_variation_comission_fields', 10, 2 );
			$admin->action( 'cx-plugin_tablenav', 'custom_table_data_filter', 10, 2 );
			$admin->action( 'cx-settings-after-title', 'custom_button' );
			$admin->action( 'admin_init', 'bulk_actions' );
		    $admin->action( 'woocommerce_order_status_changed', 'update_referral_status', 10, 4);
		    $admin->action( 'wc-affiliate-after_chart', 'add_overlay', 10, 2 );
 			$admin->action( 'admin_notices', 'admin_notices' );
		    $admin->action( 'woocommerce_subscription_renewal_payment_complete', 'reorder_subscription', 10, 2 );

			/**
			 * Settings related hooks
			 *
			 * To add an action, use $settings->action()
			 * To apply a filter, use $settings->filter()
			 */
			$settings = new Settings( $this->plugin );
			$settings->action( 'plugins_loaded', 'init_menu' );
			$settings->action( 'admin_bar_menu', 'add_admin_bar', 70 );

			// Product related classes
			$widget			= new Widget( $this->plugin );
			$survey			= new Survey( WCAFFILIATE );
			$notice			= new Notice( $this->plugin );
			$deactivator	= new Deactivator( WCAFFILIATE );

		else : // !is_admin() ?

			/**
			 * Front facing hooks
			 *
			 * To add an action, use $front->action()
			 * To apply a filter, use $front->filter()
			 */
			$front = new Front( $this->plugin );
			$front->action( 'wp_head', 'head' );
			$front->action( 'wp_enqueue_scripts', 'enqueue_scripts' );
			$front->action( 'init', 'set_token' );
			$front->action( 'init', 'verify_email' );
			$front->action( 'woocommerce_thankyou', 'add_credit' );
			$front->action( 'pre_get_posts', 'alter_query_vars' );
			$front->filter( 'wc-affiliate-front_charts', 'generate_charts_data' );
			$front->filter( 'get_avatar_url', 'avatar_url', 10, 3 );
			$front->action( 'woocommerce_cart_totals_before_order_total', 'use_credit_to_pay' );
			$front->action( 'woocommerce_cart_calculate_fees', 'apply_credit_pay' );
			// $front->action( 'woocommerce_thankyou', 'pay_with_credit_transaction' );
			$front->filter( 'wc-affiliate-dashboard_navigation', 'remove_banner_tab' );
			$front->action( 'wp_footer', 'loader_html' );

			/**
			 * Shortcode hooks
			 *
			 * To enable a shortcode, use $shortcode->register()
			 */
			$shortcode = new Shortcode( $this->plugin );
			$shortcode->register( 'wc-affiliate-dashboard', 'dashboard' );
			$shortcode->register( 'wc-affiliate-application-form', 'application_form' );

		endif;

		/**
		 * Template hooks
		 *
		 * To add an action, use $template->action()
		 * To apply a filter, use $template->filter()
		 */
		$template = new Template( $this->plugin );
		$template->action( 'wc-affiliate-dashboard', 'dashboard' );
		$template->action( 'wc-affiliate-dashboard-profile', 'profile' );
		$template->action( 'wc-affiliate-dashboard-navigation', 'navigation' );
		$template->action( 'wc-affiliate-dashboard-content', 'tab_content' );
		$template->action( 'wc-affiliate-dashboard-report', 'report' );
		$template->action( 'wc-affiliate-review-user', 'review_user' );
		$template->action( 'wc-affiliate-transaction-form', 'transaction_form' );
		$template->action( 'wc-affiliate-new-affiliate', 'new_affiliate_form' );

		/**
		 * AJAX facing hooks
		 *
		 * To add a hook for logged in users, use $ajax->priv()
		 * To add a hook for non-logged in users, use $ajax->nopriv()
		 */
		$ajax = new AJAX( $this->plugin );
		$ajax->nopriv( 'wf-register-user', 'apply' );
		$ajax->priv( 'wf-register-user', 'apply' );
		$ajax->priv( 'wf-remove-affiliate', 'remove_affiliate' );
		$ajax->priv( 'wf-update-user', 'update_user' );
		$ajax->priv( 'wf-request-payout', 'request_payout' );
		$ajax->priv( 'wf-transaction-form-action', 'transaction_form_action' );
		$ajax->priv( 'wf-review-action', 'review_action' );
		$ajax->priv( 'wf-url-generator', 'generate_url' );
		$ajax->priv( 'wf-export-report', 'export_csv_report' );
		$ajax->priv( 'wf-payout', 'payout' );
		$ajax->priv( 'wf-export-table-report', 'export_table_report' );
		// $ajax->priv( 'wf-pay-with-credit', 'pay_with_credit' );
		$ajax->all( 'wf-register-affiliate', 'register_new_affiliate' );
		$ajax->priv( 'wca-resend-varify-url', 'resend_varify_url' );
		$ajax->priv( 'wf-export-all', 'export_all_data' );
		$ajax->priv( 'wca-import-data', 'import_data' );


		/**
		 * Email hooks
		 *
		 * To add an action, use $email->action()
		 * To apply a filter, use $email->filter()
		 */
		$email = new Email( $this->plugin );
		$email->action( 'wc-affiliate-affiliate_applied', 'affiliate_applied_affiliate' );
		$email->action( 'wc-affiliate-affiliate_applied', 'affiliate_applied_admin' );
		$email->action( 'wc-affiliate-account_reviewed', 'account_review_affiliate', 10, 3 );
		$email->action( 'wc-affiliate-request_payout', 'request_payout_affiliate', 10, 2 );
		$email->action( 'wc-affiliate-request_payout', 'request_payout_admin', 10, 2 );
		$email->action( 'wc-affiliate-add_credit', 'add_credit_affiliate', 10, 2 );
		$email->action( 'wc-affiliate-add_credit', 'add_credit_admin', 10, 2 );
		$email->action( 'wc-affiliate-marked_paid', 'payout_processed' );
		$email->filter( 'wc-affiliate-resend_varify_email', 'resend_varify_url', 10, 2 );

		/**
		 * Common hooks
		 *
		 * Executes on both the admin area and front area
		 */
		$common = new Common( $this->plugin );
		$common->action('ajax_query_attachments_args', 'show_current_user_attachments');
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() { }

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() { }

	/**
	 * Instantiate the plugin
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

Plugin::instance();