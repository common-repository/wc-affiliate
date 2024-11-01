<?php
/**
 * All Wizard related functions
 */
namespace Codexpert\WC_Affiliate;
use Codexpert\Plugin\Base;
use Codexpert\Plugin\Setup;
use Codexpert\WC_Affiliate\Helper;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Wizard
 * @author codexpert <hello@codexpert.io>
 */
class Wizard extends Base {

	public $plugin;
	public $slug;
	public $name;
	public $version;
	public $admin_url;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->version	= $this->plugin['Version'];
	}

	public function enqueue_style() {
        wp_enqueue_style( $this->slug . '-wizard', plugins_url( "/assets/css/wizard.css", WCAFFILIATE ), '', time(), 'all' );
    }

	public function action_links( $links ) {
		$this->admin_url = admin_url( 'admin.php' );

		$new_links = [
			'wizard'	=> sprintf( '<a href="%1$s">%2$s</a>', add_query_arg( [ 'page' => "{$this->slug}_setup" ], $this->admin_url ), __( 'Setup Wizard', 'wc-affiliate' ) )
		];
		
		return array_merge( $new_links, $links );
	}

	public function render() {

		// force setup once
		if( get_option( "{$this->slug}_setup" ) != 1 ) {
			update_option( "{$this->slug}_setup", 1 );
			wp_safe_redirect( add_query_arg( [ 'page' => "{$this->slug}_setup" ], admin_url( 'admin.php' ) ) );
			exit;
		}

		$this->plugin['steps'] = [
			'page-selection'	=> [
				'label'		=> __( 'Page selection' ),
				'callback'	=> [ $this, 'page_selection' ],
				'action'	=> [ $this, 'save_page_selection' ],
			],
			'configuration'	=> [
				'label'		=> __( 'Configuration' ),
				'callback'	=> [ $this, 'configuration' ],
				'action'	=> [ $this, 'save_configuration' ],
			],
			'referral_statuses'		=> [
				'label'		=> __( 'Referral Statuses' ),
				'callback'	=> [ $this, 'referral_statuses' ],
				'action'	=> [ $this, 'save_referral_statuses' ],
				'redirect'	=> add_query_arg( [ 'page' => "{$this->slug}-settings" ], admin_url( 'admin.php' ) )
			],
		];

		new Setup( $this->plugin );
	}

	public function page_selection() {
		$pages 			= Helper::get_posts( [ 'post_type' => 'page' ] );
		$settings 		= get_option( 'wc_affiliate_basic', [] );
		$_dashboard 	= isset( $settings['dashboard'] ) ? sanitize_text_field( $settings['dashboard'] ) : '';
		?>
		<div class="wc-affiliate-sw-panel-area">
			<div class="wc-affiliate-sw-panel wc-affiliate-sw-dashboard">
				<label for=""><?php _e( 'Customer Dashboard', 'wc-affiliate' ); ?></label>
				<select name="dashboard" id="">
					<?php 
					foreach ( $pages as $page_id => $page_name ) {
						echo "<option value='" . esc_attr( $page_id ) . "' " . selected( $page_id, $_dashboard, false ) .">" . esc_html( $page_name ) . "</option>";
					}
					?>
				</select>
				<div class="wc-affiliate-create-page-area">
					<input id="create-page-enable" type="checkbox" name="create-page-enable">
					<label for="create-page-enable"><?php _e( 'Or create new', 'wc-affiliate' ); ?></label>
				</div>
			</div>
			<div class="wc-affiliate-sw-panel wc-affiliate-sw-dashboard-new-page">
				<label for="dashboard-create-new-page"><?php _e( 'Page name', 'wc-affiliate' ); ?></label>
				<input type="text" name="dashboard-create-new-page" placeholder="<?php _e( 'New page name', 'wc-affiliate' ) ?>" value="">
			</div>
		</div>

		<script type='text/javascript'>
	        jQuery(function($){
				var dashboard 	= $('.wc-affiliate-sw-dashboard select[name="dashboard"]');
				var required    = $('.wc-affiliate-sw-dashboard-new-page input[name="dashboard-create-new-page"]');
				var create_new 	= $('.wc-affiliate-sw-dashboard-new-page');

				create_new.hide();
				$('#create-page-enable').change(function(e) {
					if(this.checked) {
						dashboard.prop('disabled',true);
						required.prop('required',true);
						create_new.slideDown();
					}
					else{
						dashboard.prop('disabled',false);
						required.prop('required',false);
						create_new.slideUp();
					}
				}).change();
	        }); 
	    </script>
		<?php
	}

	public function configuration() {
		$settings 			= get_option( 'wc_affiliate_basic', [] );
		$commission_type 	= isset( $settings['commission_type'] ) ? sanitize_text_field( $settings['commission_type'] ) : 'percent';
		$commission_amount 	= isset( $settings['commission_amount'] ) ? sanitize_text_field( $settings['commission_amount'] ) : 20;
		$payout_amount 		= isset( $settings['payout_amount'] ) ? sanitize_text_field( $settings['payout_amount'] ) : 100;
		$expiry_time 		= isset( $settings['expiry_time'] ) ? sanitize_text_field( $settings['expiry_time'] ) : 1;
		$expiry_unit 		= isset( $settings['expiry_unit'] ) ? sanitize_text_field( $settings['expiry_unit'] ) : 2592000;
		$commissions 		= Helper::get_commission_type();
		$time_units 		= Helper::get_time_units();
		// $_unit 				= $expiry_unit;

		?>
		<div class="wc-affiliate-sw-panel-area">
			<div class="wc-affiliate-sw-panel wc-affiliate-sw-commission-type">
				<label for=""><?php _e( 'Commission Type', 'wc-affiliate' ); ?></label>
				<select name="commission-type" id="commission-type" required>
					<?php 
					foreach ( $commissions as $key => $commission ) {
						echo "<option value='{$key}' ". selected( $key, $commission_type, false ) .">{$commission}</option>";
					}
					?>
				</select>
			</div>
			<div class="wc-affiliate-sw-panel wc-affiliate-sw-commission-amount">
				<label for=""><?php _e( 'Commission Amount', 'wc-affiliate' ); ?></label>
				<input type="number" name="commission-amount" placeholder="<?php _e( 'Input commission amount', 'wc-affiliate' ) ?>" value="<?php echo $commission_amount; ?>" required>
			</div>

			<div class="wc-affiliate-sw-panel wc-affiliate-sw-payout-amount">
				<label for="create-page"><?php _e( 'Minimum Payout Amount', 'wc-affiliate' ); ?></label>
				<input type="number" name="payout-amount" placeholder="<?php _e( 'Payout Amount', 'wc-affiliate' ) ?>" value="<?php echo $payout_amount; ?>">
			</div>

			<div class="wc-affiliate-sw-panel-area">
				<div class="wc-affiliate-sw-panel wc-affiliate-sw-cookie-expiry-time">
					<label for=""><?php _e( 'Cookie expiry', 'wc-affiliate' ); ?></label>
					<input type="number" name="cookie-expiry-time" placeholder="<?php _e( 'input time', 'wc-affiliate' ) ?>" value="<?php echo $expiry_time; ?>" required>
					<select name="cookie-expiry-time-unit" id="" required>
						<?php 
						foreach ( $time_units as $key => $time_unit ) {
							echo "<option value='{$key}' ". selected( $key, $expiry_unit, false ) .">{$time_unit}</option>";
						}
						?>
					</select>
				</div>
			</div>
		</div>
		<?php
	}

	private function referral_statuses_fields(){
		$fields = [			
			'rf-status-for-pending' => [
				'label'     => __( 'Pending Payment', 'wc-affiliate' ),
				'desc' 		=> __( 'What should be the referral status when order status is \'Pending payment\'?', 'wc-affiliate' )
			],
			'rf-status-for-processing' => [
				'label'     => __( 'Processing', 'wc-affiliate' ),
				'desc' 		=> __( 'What should be the referral status when order status is \'Processing\'?', 'wc-affiliate' )
			],
			'rf-status-for-on-hold' => [
				'label'     => __( 'On hold', 'wc-affiliate' ),
				'desc' 		=> __( 'What should be the referral status when order status is \'On hold\'?', 'wc-affiliate' )
			],
			'rf-status-for-completed' => [
				'label'     => __( 'Completed', 'wc-affiliate' ),
				'desc' 		=> __( 'What should be the referral status when order status is \'Completed\'?', 'wc-affiliate' )
			],
			'rf-status-for-cancelled' => [
				'label'     => __( 'Cancelled', 'wc-affiliate' ),
				'desc' 		=> __( 'What should be the referral status when order status is \'Cancelled\'?', 'wc-affiliate' )
			],
			'rf-status-for-refunded' => [
				'label'     => __( 'Refunded', 'wc-affiliate' ),
				'desc' 		=> __( 'What should be the referral status when order status is \'Refunded\'?', 'wc-affiliate' )
			],
			'rf-status-for-failed' => [
				'label'     => __( 'Failed', 'wc-affiliate' ),
				'desc' 		=> __( 'What should be the referral status when order status is \'Failed\'?', 'wc-affiliate' )
			],
		];

		return $fields;
	}

	public function referral_statuses(){
		$fields 	= $this->referral_statuses_fields();
		$settings 	= get_option( 'wc_affiliate_basic', [] );
		$referral_statuses = Helper::get_referral_statuses();

		$input_divs = '';
		foreach( $fields as $key => $field ){
			$options = '';
			$current_value = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
			foreach ( $referral_statuses as $value => $label ) {
				$options .= "<option value='{$value}' ". selected( $value, $current_value, false ) .">" . ucfirst( $label ) . "</option>";
			}
			$input_divs .= "
				<div class='wc-affiliate-sw-panel wca-{$key}'>
					<label for='{$key}'>{$field['label']}</label>
					<select name='{$key}' id='{$key}' required>
						{$options}
					</select>
				<p class='wca-rf-field-desc'>{$field['desc']}</p>
				</div>
			";
		}
		echo "<div class='wc-affiliate-sw-panel-area'>{$input_divs}</div>";
	}

	public function save_page_selection() {
		// save one to DB
		$settings = get_option( 'wc_affiliate_basic', [] );

		if( isset( $_POST['create-page-enable'] ) && isset( $_POST['dashboard-create-new-page'] ) && $_POST['dashboard-create-new-page'] != '' ) {
			$args = [
				'post_type' 	=> 'page',
				'post_status' 	=> 'publish',
				'post_title' 	=> sanitize_text_field( $_POST['dashboard-create-new-page'] ),
				'post_content' 	=> '[wc-affiliate-dashboard]',
			];

			$page_id = wp_insert_post( $args );
			$settings['dashboard'] = $page_id;
		}
		else {
			if( isset( $_POST['dashboard'] ) ) {
				$settings['dashboard'] = sanitize_text_field( $_POST['dashboard'] );
			}
		}

		update_option( 'wc_affiliate_basic', $settings );
	}

	public function save_configuration() {
		// save two to DB
		$settings = get_option( 'wc_affiliate_basic', [] );
		$payout_settings = get_option( 'wc_affiliate_payout', [] );

		if( isset( $_POST['commission-type'] ) ) {
			$settings['commission_type'] = sanitize_text_field( $_POST['commission-type'] );
		}

		if( isset( $_POST['commission-amount'] ) ) {
			$settings['commission_amount'] = (int) sanitize_text_field( $_POST['commission-amount'] );
		}
		
		if( isset( $_POST['enable-discount'] ) ) {
			$settings['enable_discount'] = sanitize_text_field( $_POST['enable-discount'] );
		}

		if( isset( $_POST['payout-amount'] ) ) {
			$payout_settings['payout_amount'] = sanitize_text_field( $_POST['payout-amount'] );
		}

		if( isset( $_POST['cookie-expiry-time'] ) ) {
			$settings['expiry_time'] = sanitize_text_field( $_POST['cookie-expiry-time'] );
		}

		if( isset( $_POST['cookie-expiry-time-unit'] ) && isset( $_POST['cookie-expiry-time'] ) ) {
			$settings['expiry_unit'] =  sanitize_text_field( $_POST['cookie-expiry-time-unit'] );
		}

		update_option( 'wc_affiliate_basic', $settings );
		update_option( 'wc_affiliate_payout', $payout_settings );
	}

	public function save_referral_statuses(){
		$fields 	= $this->referral_statuses_fields();
		$settings 	= get_option( 'wc_affiliate_basic', [] );

		foreach ( $fields as $key => $field ) {
			if( isset( $_POST[ $key ] ) ) {
				$settings[ $key ] =  sanitize_text_field( $_POST[ $key ] );
			}
		}

		update_option( 'wc_affiliate_basic', $settings );
	}
}