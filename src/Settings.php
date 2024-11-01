<?php
/**
 * All settings related functions
 */
namespace Codexpert\WC_Affiliate;
use Codexpert\Plugin\Base;
use Codexpert\Plugin\Table;

/**
 * @package Plugin
 * @subpackage Settings
 * @author codexpert <hello@codexpert.io>
 */
class Settings extends Base {

	public $plugin;
	public $slug;
	public $name;
	public $version;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->version	= $this->plugin['Version'];
	}

	public function add_admin_bar( $admin_bar ) {
		if( is_admin() || !current_user_can( 'manage_options' ) ) return;

		$admin_bar->add_menu( [
			'id'    => $this->slug,
			'title' => $this->name,
			'href'  => add_query_arg( 'page', $this->slug, admin_url( 'admin.php' ) ),
			'meta'  => [
				'title' => $this->name,            
			],
		] );
	}
	
	public function init_menu() {

		$currency		= function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '[currency]';
		
		$settings = [
			'id'            => "{$this->slug}-settings",
			'label'         => __( 'Settings', 'wc-affiliate' ),
			'title'         => __( 'WC Affiliate', 'wc-affiliate' ),
			'header'        => __( 'Settings', 'wc-affiliate' ),
			'priority'      => 11,
			'parent'		=> $this->slug,
			'capability'    => 'manage_options',
			'icon'          => 'dashicons-chart-line',
			'position'      => 25,
			'sections'      => [
				'wc_affiliate_basic'	=> [
					'id'        => 'wc_affiliate_basic',
					'label'     => __( 'Basic Settings', 'wc-affiliate' ),
					'icon'      => 'dashicons-admin-tools',
					// 'color'		=> '#4c3f93',
					'sticky'	=> false,
					'fields'    => [
						'affiliate-commission-divider' => [
							'id'		=> 'affiliate-commission-divider',
							'label'     => __( 'Affiliate Commission', 'wc-affiliate' ),
							'type'      => 'divider',
						],
						'commission_base' => [
							'id'		=> 'commission_base',
							'label'     => __( 'Commission Base', 'wc-affiliate' ),
							'type'      => 'select',
							'options'	=> [
								'product_price' 	=> __( 'Product Price', 'wc-affiliate' ),
								'payable_amount' 	=> __( 'Payable Amount', 'wc-affiliate' ),
							],
							'default'   => 'payable_amount'
						],
						'commission'	=> [
							'id'		=> 'commission',
							'label'		=> __( 'Commission Amount', 'wc-affiliate' ),
							'type'		=> 'group',
							'desc'		=> __( 'Amount of commision you want to give to your affiliates. Calculated based on quantities sold.', 'wc-affiliate' ),
							'items'	=> [
								'commission_type' => [
									'id'		=> 'commission_type',
									'label'     => __( 'Commission Type', 'wc-affiliate' ),
									'type'      => 'select',
									'percent'		=> 20,
									'options'	=> [
										''			=> __( 'Commission Type', 'wc-affiliate' ),
										'fixed'		=> __( 'Fixed', 'wc-affiliate' ),
										'percent'	=> __( 'Percent', 'wc-affiliate' ),
									],
									'required'	=> true,
								],
								'commission_amount' => [
									'id'			=> 'commission_amount',
									'label'     	=> __( 'Commission Amount', 'wc-affiliate' ),
									'type'      	=>  'number',
									'required'		=> true,
									'placeholder'	=> __( 'Input commission amount', 'wc-affiliate' ),
									'default'		=> 20,
								],
							]
						],
						'affiliate-click-divider' => [
							'id'		=> 'affiliate-click-divider',
							'label'     => __( 'Click bonus', 'wc-affiliate' ),
							'type'      => 'divider',
						],			
						'click_bonus_enabled' => [
							'id'		=> 'click_bonus_enabled',
							'label'     => __( 'Enable click Bonus', 'wc-affiliate' ),
							'type'      => 'checkbox',
							'desc'      => __( 'Do you want to give click bonus to the affiliate when link is clicked ?', 'wc-affiliate' ),
						],		
						'click_bonus_amount' 	=> [
							'id'		=> 'click_bonus_amount',
							'label'     => __( 'Bonus Amount', 'wc-affiliate' ),
							'type'      => 'number',
							'default'   => '5',
							'condition'	=> [
								'key'		=> 'click_bonus_enabled',
								'compare'	=> 'checked'
							]
						],
						'recurring_order' => [
							'id'		=> 'recurring_order',
							'label'     => __( 'Recurring Order', 'wc-affiliate' ),
							'type'      => 'checkbox',
							'desc'      => __( 'Do you want to pay bonus for recurring order', 'wc-affiliate' ),
						],				
						'referral-statues' 	=> [
							'id'		=> 'referral-statues',
							'label'     => __( 'Referral Statuses', 'wc-affiliate' ),
							'type'      => 'divider',
						],
						'rf-status-for-pending' => [
							'id'		=> 'rf-status-for-pending',
							'label'     => __( 'Pending Payment', 'wc-affiliate' ),
							'type'      => 'select',
							'options'	=> Helper::get_referral_statuses(),
							'desc' 		=> __( 'What should be the referral status when order status is \'Pending payment\'?', 'wc-affiliate' )
						],
						'rf-status-for-processing' => [
							'id'		=> 'rf-status-for-processing',
							'label'     => __( 'Processing', 'wc-affiliate' ),
							'type'      => 'select',
							'options'	=> Helper::get_referral_statuses(),
							'desc' 		=> __( 'What should be the referral status when order status is \'Processing\'?', 'wc-affiliate' )
						],
						'rf-status-for-on-hold' => [
							'id'		=> 'rf-status-for-on-hold',
							'label'     => __( 'On hold', 'wc-affiliate' ),
							'type'      => 'select',
							'options'	=> Helper::get_referral_statuses(),
							'desc' 		=> __( 'What should be the referral status when order status is \'On hold\'?', 'wc-affiliate' )
						],
						'rf-status-for-completed' => [
							'id'		=> 'rf-status-for-completed',
							'label'     => __( 'Completed', 'wc-affiliate' ),
							'type'      => 'select',
							'options'	=> Helper::get_referral_statuses(),
							'desc' 		=> __( 'What should be the referral status when order status is \'Completed\'?', 'wc-affiliate' )
						],
						'rf-status-for-cancelled' => [
							'id'		=> 'rf-status-for-cancelled',
							'label'     => __( 'Cancelled', 'wc-affiliate' ),
							'type'      => 'select',
							'options'	=> Helper::get_referral_statuses(),
							'desc' 		=> __( 'What should be the referral status when order status is \'Cancelled\'?', 'wc-affiliate' )
						],
						'rf-status-for-refunded' => [
							'id'		=> 'rf-status-for-refunded',
							'label'     => __( 'Refunded', 'wc-affiliate' ),
							'type'      => 'select',
							'options'	=> Helper::get_referral_statuses(),
							'desc' 		=> __( 'What should be the referral status when order status is \'Refunded\'?', 'wc-affiliate' )
						],
						'rf-status-for-failed' => [
							'id'		=> 'rf-status-for-failed',
							'label'     => __( 'Failed', 'wc-affiliate' ),
							'type'      => 'select',
							'options'	=> Helper::get_referral_statuses(),
							'desc' 		=> __( 'What should be the referral status when order status is \'Failed\'?', 'wc-affiliate' )
						],
						'other-divider' => [
							'id'		=> 'other-divider',
							'label'     => __( 'Others', 'wc-affiliate' ),
							'type'      => 'divider',
						],
						
						'cookie_expiry'	=> [
							'id'		=> 'cookie_expiry',
							'label'		=> __( 'Cookie expiry', 'wc-affiliate' ),
							'type'		=> 'group',
							'desc'		=> __( 'How long should it remember a visitor coming from an affiliate link?', 'wc-affiliate' ),
							'items'		=> [								
								'time' 	=> [
									'id'			=> 'expiry_time',
									'label'     	=> __( 'Time', 'wc-affiliate' ),
									'type'      	=>  'number',
									'required'		=> true,
									'placeholder'	=> __( 'Input time', 'wc-affiliate' ),
									'default'		=> 1
								],
								'unit' 	=> [
									'id'		=> 'expiry_unit',
									'label'     => __( 'Time unit', 'wc-affiliate' ),
									'type'      => 'select',
									'options'	=> Helper::get_time_units(),								    
									'default'	=> MONTH_IN_SECONDS,
									'required'	=> true, 
								],
							]
						],
						'allow_self_referral' 	=> [
							'id'		=> 'allow_self_referral',
							'label'     => __( 'Self Referral', 'wc-affiliate' ),
							'type'      => 'checkbox',
							'desc' 		=> __( 'Allow affiliates to refer themselves', 'wc-affiliate' ),
						],						
						'enable_email_validation'	=> [
							'id'		=> 'enable_email_validation',
							'label'     => __( 'Email validation', 'wc-affiliate' ),
							'type'      => 'checkbox',
							'desc' 		=> __( 'Validate email while a new affiliate applies', 'wc-affiliate' ),
						],
						'email_validation_expiary'	=> [
							'id'		=> 'email_validation_expiary',
							'label'		=> __( 'Validation expiary', 'wc-affiliate' ),
							'type'		=> 'group',
							'desc'		=> __( 'After this time the validation url doesn\'t work.', 'wc-affiliate' ),
							'items'	=> [
								'evalidation_expiary_type' => [
									'id'			=> 'evalidation_expiary_type',
									'label'     	=> __( 'Time', 'wc-affiliate' ),
									'type'      	=>  'number',
									'required'		=> true,
									'placeholder'	=> __( 'Input time', 'wc-affiliate' ),
									'default'		=> 1
								],
								'evalidation_expiary_unit' => [
									'id'		=> 'evalidation_expiary_unit',
									'label'     => __( 'Time unit', 'wc-affiliate' ),
									'type'      => 'select',
									'options'	=> Helper::get_time_units(),								    
									'default'	=> HOUR_IN_SECONDS,
									'required'	=> true, 
								],
							],
							'condition'		=> [
								'key'		=> 'enable_email_validation',
								'compare'	=> 'checked'
							]
						],						
						'auto_approve_affiliate'	=> [
							'id'		=> 'auto_approve_affiliate',
							'label'     => __( 'Auto approve affiliate', 'wc-affiliate' ),
							'type'      => 'checkbox',
							'desc' 		=> __( 'Automatically approve a affiliate after email verification', 'wc-affiliate' ),							
							'condition'		=> [
								'key'		=> 'enable_email_validation',
								'compare'	=> 'checked'
							]
						],
						'dashboard' 	=> [
							'id'		=> 'dashboard',
							'label'     => __( 'Customer Dashboard', 'wc-affiliate' ),
							'type'      => 'select',
							'options'	=> Helper::get_posts( [ 'post_type' => 'page' ] ),
							'desc' 		=> __( 'Choose a page that will be used as the affiliate dashboard. The page must conatins the shortcode ', 'wc-affiliate' ) . '[wc-affiliate-dashboard]',
						],
						'terms_url' 	=> [
							'id'		=> 'terms_url',
							'label'     => __( 'Terms &amp; Conditions', 'wc-affiliate' ),
							'type'      => 'url',
							'desc' 		=> __( 'Input a URL to your T&C page to be shown in the affiliate registration form. The checkbox in the form won\'t show up if it\'s left empty', 'wc-affiliate' ),
							'default' 	=> 'https://codexpert.io/terms-of-service/',
						],
					]
				],
				'wc_affiliate_advanced'	=> [
					'id'        => 'wc_affiliate_advanced',
					'label'     => __( 'Advanced Settings', 'wc-affiliate' ),
					'icon'      => 'dashicons-admin-generic',
					// 'color'		=> '#c36',
					'sticky'	=> false,
					'fields'    => [
						'ref_key' 		=> [
							'id'		=> 'ref_key',
							'label'     => __( 'Referral Key', 'wc-affiliate' ),
							'type'      => 'text',
							'required'	=> true,								    
							'default'	=> 'ref',
							// Translators: %s is an example of the key to be used in the affiliate URL.
							'desc'		=> sprintf( __( 'Key to be used in the affiliate URL. Example: %s', 'wc-affiliate' ), add_query_arg( '<strong>ref</strong>', 1, trailingslashit( get_home_url() ) ) )
						],
						'token_type' 	=> [
							'id'		=> 'token_type',
							'label'     => __( 'Token type', 'wc-affiliate' ),
							'type'      => 'select',
							'required'	=> true,	
							'options' 	=> [
								'ID'	=>	__( 'ID', 'wc-affiliate' ),
								'slug'	=>	__( 'Slug', 'wc-affiliate' ),
								'email'	=>	__( 'Email', 'wc-affiliate' ),
								'login'	=>	__( 'Login', 'wc-affiliate' ),
							],							    
							'default'	=> 'ID',
							'desc'		=> __( 'What should an affiliate be identified by?', 'wc-affiliate' ),
						],						
						'cookie_name' 	=> [
							'id'		=> 'cookie_name',
							'label'     => __( 'Cookie name', 'wc-affiliate' ),
							'type'      => 'text',
							'required'	=> true,								    
							'default'	=> '_wc-affiliate',
							'desc'		=> __( 'Name of the cookie key to indentify an affiliate. Don\'t change unless you know what you\'re doing!', 'wc-affiliate' ),
						],					
						'visit_cookie_name' => [
							'id'		=> 'visit_cookie_name',
							'label'     => __( 'Visit Cookie name', 'wc-affiliate' ),
							'type'      => 'text',
							'required'	=> true,								    
							'default'	=> '_wc-affiliate_visit',
							'desc'		=> __( 'Name of the cookie key to store visitor count. Don\'t change unless you know what you\'re doing!', 'wc-affiliate' ),
						],						
						'allow_overwrite' => [
							'id'		=> 'allow_overwrite',
							'label'     => __( 'Allow overwrite', 'wc-affiliate' ),
							'type'      => 'checkbox',
							'desc'		=> __( 'Should we credit the latest affiliate if a visitor visits from different affiliates\' link at different time?', 'wc-affiliate' ),
						],						
						'credit_once' 	=> [
							'id'		=> 'credit_once',
							'label'     => __( 'Credit once', 'wc-affiliate' ),
							'type'      => 'checkbox',							    
							'desc'		=> __( 'Check this if you want to give affiliate commission <strong>only once</strong> even if a referred customer places multiple orders.', 'wc-affiliate' ),
						],				
						'wc_affiliate_recaptcha_divider' => [
							'id'		=> 'wc_affiliate_recaptcha_divider',
							'label'     => __( 'reCAPTCHA', 'wc-affiliate' ),
							'type'      => 'divider',
						],
						'wc_affiliate_enable_recaptcha' => [
							'id'		=> 'wc_affiliate_enable_recaptcha',
							'label'     => __( 'Enable reCAPTCHA', 'wc-affiliate' ),
							'type'      => 'checkbox',
							'desc'      => __( 'Should we show reCAPTCHA in ragistration form?', 'wc-affiliate' ),
						],	
						'wc_affiliate_sitekey_recaptcha' => [
							'id'		=> 'wc_affiliate_sitekey_recaptcha',
							'label'     => __( 'Site Key', 'wc-affiliate' ),
							'type'      => 'text',							    
							'desc'		=> sprintf( '%s <a target="_blank" href="%s">%s</a>', 
								__( 'You can generate your Site Key from here', 'wc-affiliate' ), 
								'https://developers.google.com/recaptcha', 
								'https://developers.google.com/recaptcha'
							),
							'condition'		=> [
								'key'		=> 'wc_affiliate_enable_recaptcha',
								'compare'	=> 'checked'
							]
						],
						'wc_affiliate_secret_recaptcha' => [
							'id'		=> 'wc_affiliate_secret_recaptcha',
							'label'     => __( 'Secret Key', 'wc-affiliate' ),
							'type'      => 'text',							    
							'desc'		=> sprintf( '%s <a target="_blank" href="%s">%s</a>', 
								__( 'You can generate your Secret Key from here', 'wc-affiliate' ), 
								'https://developers.google.com/recaptcha', 
								'https://developers.google.com/recaptcha'
							),
							'condition'		=> [
								'key'		=> 'wc_affiliate_enable_recaptcha',
								'compare'	=> 'checked'
							]
						],
					]
				],
				'wc_affiliate_payout'	=> [
					'id'        => 'wc_affiliate_payout',
					'label'     => __( 'Payout', 'wc-affiliate' ),
					'icon'      => 'dashicons-money-alt',
					// 'color'		=> '#617503',
					'sticky'	=> false,
					'fields'    => [
						'payout_amount' 	=> [
							'id'		=> 'payout_amount',
							'label'     => __( 'Minimum Payout Amount', 'wc-affiliate' ),
							'type'      => 'number',
							'default' 	=> '50',
							// Translators: %s is the currency.
							'desc'		=> sprintf( __( 'How much %s should an affiliate have to be able to request for payout?', 'wc-affiliate' ), $currency )
						],
						'enable_payout_methods' => [
							'id'      => 'enable_payout_methods',
							'label'     => __( 'Payout Methods', 'cx-plugin' ),
							'type'      => 'checkbox',
							'options'   => [
								'manual'  	=> __( 'Manual', 'wc-affiliate' ),
							],
							'default'   => [ 'manual' ],
							'multiple'  => true,
							// Translators: %s is an HTML link to the WC Affiliate Pro page.
							'desc'  	=> Helper::has_pro() ? '' : sprintf( __( 'More payout methods in %s', 'wc-affiliate' ), '<a href="https://codexpert.io/wc-affiliate/" target="_blank">WC Affiliate Pro</a>' ),
						],
					]
				],
				'wc_affiliate_mlc' => [
					'id'        => 'wc_affiliate_mlc',
					'label'     => __( 'Multilevel Commission', 'wc-affiliate' ),
					'icon'      => 'dashicons-external',
					// 'color'		=> '#3757ccd4',
					'hide_form'	=> true,
					'unlock_url'=> 'https://codexpert.io/wc-affiliate/#pricing',
					'content'	=> Helper::get_template( 'multilevel-commission', 'views/placeholders' ),
					'fields'    => [],
				],
				'wc_affiliate_messages'	=> [
					'id'        => 'wc_affiliate_messages',
					'label'     => __( 'Messages', 'wc-affiliate' ),
					'icon'      => 'dashicons-buddicons-pm',
					// 'color'		=> '#b78c0cd4',
					'sticky'	=> false,
					'fields'    => [
						'register_message' => [
							'id'		=> 'register_message',
							'label'     => __( 'Registration Success', 'wc-affiliate' ),
							'type'      => 'textarea',
							'rows'      => 3,
							'required'	=> true,								    
							'default'	=> __( 'Thanks! Your application was received. We will review your account information and get back to you shortly.', 'wc-affiliate' )
						],
						'update_user_message' => [
							'id'		=> 'update_user_message',
							'label'     => __( 'Profile Update', 'wc-affiliate' ),
							'type'      => 'textarea',
							'rows'      => 3,
							'required'	=> true,								    
							'default'	=> __( 'Your profile has been updated successfully', 'wc-affiliate' )
						],
						'register_new_affiliate_message' => [
							'id'		=> 'register_new_affiliate_message',
							'label'     => __( 'New Affiliate Registration', 'wc-affiliate' ),
							'type'      => 'textarea',
							'rows'      => 2,
							'required'	=> true,								    
							'default'	=> __( 'Congratulation! New affiliates has been created successfully.', 'wc-affiliate' )
						],
						'verify_humann_message' => [
							'id'		=> 'verify_humann_message',
							'label'     => __( 'Human Verification', 'wc-affiliate' ),
							'type'      => 'textarea',
							'rows'      => 2,
							'required'	=> true,								    
							'default'	=> __( 'Please verify that you are a human.', 'wc-affiliate' )
						],
						'request_payout_message' => [
							'id'		=> 'request_payout_message',
							'label'     => __( 'Payout Request', 'wc-affiliate' ),
							'type'      => 'textarea',
							'rows'      => 2,
							'required'	=> true,								    
							'default'	=> __( 'We\'ve received your payout request. We\'ll proceed it shortly.', 'wc-affiliate' )
						],
						'insufficient_balance_message' => [
							'id'		=> 'insufficient_balance_message',
							'label'     => __( 'Insufficient Balance', 'wc-affiliate' ),
							'type'      => 'textarea',
							'rows'      => 2,
							'required'	=> true,								    
							'default'	=> __( 'Sorry! Your balance is insufficient to proceed.', 'wc-affiliate' )
						],
					]
				],
				'wc_affiliate_email'	=> [
					'id'        => 'wc_affiliate_email',
					'label'     => __( 'Email', 'wc-affiliate' ),
					'icon'      => 'dashicons-email-alt',
					// 'color'		=> '#2aaa86',
					'sticky'	=> false,
					'fields'    => [
						'wc_affiliate_tabs' => [
							'id'		=> 'wc_affiliate_tabs',
							'type'      => 'tabs',
							'items'		=> [
								'affiliate_email_tab'	=> [
									'id'		=> 'affiliate_email_tab',
									'label'		=> __( 'Affiliate Email', 'wc-affiliate' ),
									'fields'	=> [
										'affiliate_applied_divider' => [
											'id'		=> 'affiliate_applied_divider',
											'label'     => __( 'Affiliate Application', 'wc-affiliate' ),
											'type'      => 'divider',
										],
										'affiliate_applied_enable' => [
											'id'		=> 'affiliate_applied_enable',
											'label'     => __( 'Enable', 'wc-affiliate' ),
											'desc'     	=> __( 'Enable this if you want to send an email after an affiliate applies', 'wc-affiliate' ),
											'type'      =>  'checkbox',
											'default'   =>  'on',
										],
										'affiliate_applied_subject' => [
											'id'		=> 'affiliate_applied_subject',
											'label'     => __( 'Subject', 'wc-affiliate' ),
											'type'      => 'text',
											'required'	=> true,
											'default'	=> __( 'Thanks for your interest to be an affiliate on %%site_link%%', 'wc-affiliate' ),
										],
										'affiliate_applied_message' => [
											'id'		=> 'affiliate_applied_message',
											'label'     => __( 'Message', 'wc-affiliate' ),
											'type'      => 'wysiwyg',
											'rows'      => 4,
											'required'	=> true,
											'default'	=> sprintf( 
												// Translators: %s is the first name of the user, the second %s is the site URL.
												__( 'Hi %s, Your %s affiliate application was received. We\'ll review it and get back to you soon!', 'wc-affiliate' ), '%%first_name%%', get_bloginfo('url') ),
										],
										'account_approved_divider' => [
											'id'		=> 'account_approved_divider',
											'label'     => __( 'Affiliate Account Approval', 'wc-affiliate' ),
											'type'      => 'divider',
										],
										'affiliate_approved_enable' => [
											'id'		=> 'affiliate_approved_enable',
											'label'     => __( 'Enable', 'wc-affiliate' ),
											'desc'     	=> __( 'Enable this if you want to send an email when an affiliate account is approved.', 'wc-affiliate' ),
											'type'      =>  'checkbox',
											'default'   =>  'on',
										],
										'account_approved_subject' => [
											'id'		=> 'account_approved_subject',
											'label'     => __( 'Subject', 'wc-affiliate' ),
											'type'      => 'text',
											'required'	=> true,
											'default'	=> __( 'Congratulations! Your affiliate application was approved.', 'wc-affiliate' )
										],
										'account_approved_message' => [
											'id'		=> 'account_approved_message',
											'label'     => __( 'Message', 'wc-affiliate' ),
											'type'      => 'wysiwyg',
											'rows'      => 4,
											'required'	=> true,							    
											'default'	=> sprintf( 
												// Translators: %s is the first name of the user. The second %s is a message about the approval.
												__( 'Hi %s, Your affiliate application was approved. %s', 'wc-affiliate' ), '%%first_name%%', '%%message%%' ),
										],			
										'account_reject_divider' => [
											'id'		=> 'account_reject_divider',
											'label'     => __( 'Affiliate Account Rejection', 'wc-affiliate' ),
											'type'      => 'divider',
										],
										'account_reject_enable' => [
											'id'		=> 'account_reject_enable',
											'label'     => __( 'Enable', 'wc-affiliate' ),
											'desc'     	=> __( 'Enable this if you want to send an email if an affiliate application is rejected.', 'wc-affiliate' ),
											'type'      =>  'checkbox',
											'default'   =>  'on',
										],			
										'account_reject_subject' => [
											'id'		=> 'account_reject_subject',
											'label'     => __( 'Subject', 'wc-affiliate' ),
											'type'      => 'text',
											'required'	=> true,
											'default'	=> __( 'Your affiliate application was rejected', 'wc-affiliate' )
										],
										'account_reject_message' => [
											'id'		=> 'account_reject_message',
											'label'     => __( 'Message', 'wc-affiliate' ),
											'type'      => 'wysiwyg',
											'rows'      => 4,
											'required'	=> true,
											// Translators: %1$s is the recipient's first name, %2$s is the custom message.
											'default'   => sprintf( __( 'Hi %1$s, We\'re sorry to let you know that, your affiliate application was rejected. %2$s', 'wc-affiliate' ), '%%first_name%%', '%%message%%' )							    
										],
										'request_payout_divider' => [
											'id'		=> 'request_payout_divider',
											'label'     => __( 'Payout Request', 'wc-affiliate' ),
											'type'      => 'divider',
										],
										'request_payout_enable' => [
											'id'		=> 'request_payout_enable',
											'label'     => __( 'Enable', 'wc-affiliate' ),
											'desc'     	=> __( 'Enable this if you want to send an email after an affiliate requests for payout.', 'wc-affiliate' ),
											'type'      =>  'checkbox',
											'default'   =>  'on',
										],				
										'request_payout_subject' => [
											'id'		=> 'request_payout_subject',
											'label'     => __( 'Subject', 'wc-affiliate' ),
											'type'      => 'text',
											'required'	=> true,
											'default'	=> __( 'Your payout request is submitted', 'wc-affiliate' )
										],
										'request_payout_mail_message' => [
											'id'		=> 'request_payout_mail_message',
											'label'     => __( 'Message', 'wc-affiliate' ),
											'type'      => 'wysiwyg',
											'rows'      => 4,
											'required'	=> true,							    
											'default'	=> __( 'Hi %%first_name%%, We\'ve received your payout request and will process it soon.', 'wc-affiliate' )

				 						],
				 						'add_credit_divider' => [
											'id'		=> 'add_credit_divider',
											'label'     => __( 'Commission Earned', 'wc-affiliate' ),
											'type'      => 'divider',
										],
										'add_credit_enable' => [
											'id'		=> 'add_credit_enable',
											'label'     => __( 'Enable', 'wc-affiliate' ),
											'desc'     	=> __( 'Enable this if you want to send an email when an affiliate gets a commission.', 'wc-affiliate' ),
											'type'      =>  'checkbox',
											'default'   =>  'on',
										],					
										'add_credit_subject' => [
											'id'		=> 'add_credit_subject',
											'label'     => __( 'Subject', 'wc-affiliate' ),
											'type'      => 'text',
											'required'	=> true,
											'default'	=> __( 'Affiliate commission earned', 'wc-affiliate' )
										],
										'add_credit_message' => [
											'id'		=> 'add_credit_message',
											'label'     => __( 'Message', 'wc-affiliate' ),
											'type'      => 'wysiwyg',
											'rows'      => 4,
											'required'	=> true,							    
											'default'	=> sprintf( __( 'Hi %s, Congratulation! You\'ve just got an affiliate commission of %s from an order. Keep up the good work!', 'wc-affiliate' ), '%%first_name%%', '%%amount%%' )
										],
				 						'payout_process_divider' => [
											'id'		=> 'payout_process_divider',
											'label'     => __( 'Payout Process', 'wc-affiliate' ),
											'type'      => 'divider',
										],
										'payout_process_enable' => [
											'id'		=> 'payout_process_enable',
											'label'     => __( 'Enable', 'wc-affiliate' ),
											'desc'     	=> __( 'Enable this if you want to send an email when an affiliate earnings are disbursed.', 'wc-affiliate' ),
											'type'      =>  'checkbox',
											'default'   =>  'on',
										],	
										'payout_process_subject' => [
											'id'		=> 'payout_process_subject',
											'label'     => __( 'Subject', 'wc-affiliate' ),
											'type'      => 'text',
											'required'	=> true,
											'default'	=> __( 'Your payment is on the way', 'wc-affiliate' )
										],
										'payout_process_message' => [
											'id'		=> 'payout_process_message',
											'label'     => __( 'Message', 'wc-affiliate' ),
											'type'      => 'wysiwyg',
											'rows'      => 4,
											'required'	=> true,
											// Translators: %s is a placeholder for the payout amount.
											'default'	=> sprintf( __( 'We received your payout request for %s and processed the payment. You will receive it shortly.', 'wc-affiliate' ), '%%amount%%' )
										],
									]
								],
								'affiliate_admin_email_tab'	=> [
									'id'		=> 'affiliate_admin_email_tab',
									'label'		=> __( 'Admin Email', 'wc-affiliate' ),
									'fields'	=> [
										'affiliate_applied_admin_divider' => [
											'id'		=> 'affiliate_applied_admin_divider',
											'label'     => __( 'Affiliate Application', 'wc-affiliate' ),
											'type'      => 'divider',
										],
										'affiliate_applied_admin_enable' => [
											'id'		=> 'affiliate_applied_admin_enable',
											'label'     => __( 'Enable', 'wc-affiliate' ),
											'desc'     	=> __( 'Enable this if you want to get notified after an affiliate applies.', 'wc-affiliate' ),
											'type'      =>  'checkbox',
											'default'   =>  'on',
										],	
										'affiliate_applied_admin_subject' => [
											'id'		=> 'affiliate_applied_admin_subject',
											'label'     => __( 'Subject', 'wc-affiliate' ),
											'type'      => 'text',
											'required'	=> true,
											'default'	=> __( 'Someone just applied to become an affiliate', 'wc-affiliate' )
										],
										'affiliate_applied_admin_message' => [
											'id'		=> 'affiliate_applied_admin_message',
											'label'     => __( 'Message', 'wc-affiliate' ),
											'type'      => 'wysiwyg',
											'rows'      => 4,
											'required'	=> true,
											'default'	=> sprintf( __( '%s wants to become an affiliate on %s. Click the link below to approve their application %s', 'wc-affiliate' ), '%%first_name%%', '%%site_url%%', '%%user_url%%' )
										],
										'request_payout_admin_divider' => [
											'id'		=> 'request_payout_admin_divider',
											'label'     => __( 'Payout Request', 'wc-affiliate' ),
											'type'      => 'divider',
										],
										'request_payout_admin_enable' => [
											'id'		=> 'request_payout_admin_enable',
											'label'     => __( 'Enable', 'wc-affiliate' ),
											'desc'     	=> __( 'Enable this if you want to get notified when an affiliate requests for payout.', 'wc-affiliate' ),
											'type'      =>  'checkbox',
											'default'   =>  'on',
										],					
										'request_payout_admin_subject' => [
											'id'		=> 'request_payout_admin_subject',
											'label'     => __( 'Subject', 'wc-affiliate' ),
											'type'      => 'text',
											'required'	=> true,
											'default'	=> __( 'New payout request', 'wc-affiliate' )
										],
										'request_payout_admin_message' => [
											'id'		=> 'request_payout_admin_message',
											'label'     => __( 'Message', 'wc-affiliate' ),
											'type'      => 'wysiwyg',
											'rows'      => 4,
											'required'	=> true,							    
											'default'	=> __( '%%first_name%% sent you a request for %%amount%% payout.', 'wc-affiliate' )

				 						],
				 						'add_credit_admin_divider' => [
											'id'		=> 'add_credit_admin_divider',
											'label'     => __( 'Commission Earned', 'wc-affiliate' ),
											'type'      => 'divider',
										],
										'add_credit_admin_enable' => [
											'id'		=> 'add_credit_admin_enable',
											'label'     => __( 'Enable', 'wc-affiliate' ),
											'desc'     	=> __( 'Enable this if you want to get notified when an affiliate gets some commission', 'wc-affiliate' ),
											'type'      =>  'checkbox',
											'default'   =>  'on',
										],				
										'add_credit_admin_subject' => [
											'id'		=> 'add_credit_admin_subject',
											'label'     => __( 'Subject', 'wc-affiliate' ),
											'type'      => 'text',
											'required'	=> true,
											'default'	=> __( 'Commission assigned to %%first_name%%', 'wc-affiliate' )
										],
										'add_credit_admin_message' => [
											'id'		=> 'add_credit_admin_message',
											'label'     => __( 'Message', 'wc-affiliate' ),
											'type'      => 'wysiwyg',
											'rows'      => 4,
											'required'	=> true,							    
											'default'	=> sprintf( __( '%s has been successfully credited with %s', 'wc-affiliate' ), '%%first_name%%', '%%amount%%' )
										],
									]
								],
							]
						],
					]
				],
				'wc_affiliate_xdomain' => [
					'id'        => 'wc_affiliate_xdomain',
					'label'     => __( 'xDomain Cookie', 'wc-affiliate' ),
					'icon'      => 'dashicons-admin-site-alt3',
					// 'color'		=> '#4caf50',
					'hide_form'	=> true,
					'unlock_url'=> 'https://codexpert.io/wc-affiliate/#pricing',
					'content'	=> Helper::get_template( 'xdomain', 'views/placeholders'  ),
					'fields'    => []
				],
				'wc_affiliate_shortlinks' => [
					'id'		=> 'wc_affiliate_shortlinks',
					'label'		=> __( 'Shortlinks', 'wc-affiliate' ),
					'icon'		=> 'dashicons-admin-links',
					// 'color'		=> '#f24d4d',
					'hide_form'	=> true,
					'unlock_url'=> 'https://codexpert.io/wc-affiliate/#pricing',
					'content'	=> Helper::get_template( 'shortlink-settings', 'views/placeholders'  ),
					'fields'    => []
				],
				'wc_affiliate_migration'	=> [
					'id'        => 'wc_affiliate_migration',
					'label'     => __( 'Migration', 'wc-affiliate' ),
					'icon'      => 'dashicons-share-alt2',
					// 'color'		=> '#4c3f93',
					'hide_form'	=> true,
					'content'	=> Helper::get_template( 'export-import', 'views/admin/export-import' ),
					'fields'    => [],
				],
			],
		];

		new \Codexpert\Plugin\Settings( apply_filters( 'wc-affiliate-settings-fields', $settings ) );
	}
}