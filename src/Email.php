<?php
/**
 * All email facing functions
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
 * @subpackage Email
 * @author codexpert <hello@codexpert.io>
 */
class Email extends Base {

	public $plugin;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	public function send( $to, $subject, $message, $attachments = '', $headers = "Content-Type: text/html\r\n" ) {
		
		$wc_email = new \WC_Emails;
		$_message = stripslashes( $message );

		ob_start();
		$wc_email->email_header( $subject );
		echo sanitize_textarea_field( $_message );
		$wc_email->email_footer();
		$message = ob_get_clean();

		$wc_email->send( sanitize_email( $to ), sanitize_text_field( $subject ), wpautop( $message ), $headers, $attachments );
	}

	public function affiliate_applied_affiliate( $user_id ) {

		$enable 				= Helper::get_option( 'wc_affiliate_email', 'affiliate_applied_enable' );
		$enable_email_verify 	= Helper::get_option( 'wc_affiliate_basic', 'enable_email_validation' );
		$user 					= get_userdata( $user_id );

		if( $enable_email_verify ){
			$email 		= $user->user_email;
			$subject 	= __( 'Email Verification', 'wc-affiliate' );

			$dashboard_id 	= Helper::get_option( 'wc_affiliate_basic', 'dashboard' );
			$dashboard_url 	= get_the_permalink( $dashboard_id );
			$validation_data = [
				'id' 	=> $user->ID,
				'email' => $user->data->user_email,
				'time' 	=> time(),
			];
			$validation_data 	= json_encode( $validation_data );
			$validation_data 	= Helper::ncrypt()->encrypt( $validation_data );
			$verify_url 		= add_query_arg( 'validate', $validation_data, $dashboard_url );
			$message 			= __( 'Verify your email: ', 'wc-affiliate' ) . $verify_url;

			$this->send( $email, $subject, wpautop( $message ) );
		}

		if ( empty( $enable ) || $enable != 'on' ) return;
		
		$email 		= $user->user_email;
		$first_name = $user->first_name;
		$_site_url 	= get_bloginfo('url');
		$site_name 	= get_bloginfo('name');
		$site_url 	= '<a href="'. $_site_url .'">'. $site_name .'</a>';

    	$_subject 	= Helper::get_option( 'wc_affiliate_email', 'affiliate_applied_subject' );
    	$subject 	= str_replace( '%%site_link%%', $site_url, $_subject );

    	$_message 	= Helper::get_option( 'wc_affiliate_email', 'affiliate_applied_message' );
        $message 	= str_replace( '%%first_name%%', $first_name, $_message );

        $this->send( $email, $subject, wpautop( $message ) );
	}

	public function affiliate_applied_admin( $user_id ) {

		$enable 	= Helper::get_option( 'wc_affiliate_email', 'affiliate_applied_admin_enable' );

		if ( empty( $enable ) || $enable != 'on' ) return;

		$email 		= wc_affiliate_admin_email();

		$user 		= get_userdata( $user_id );
		$first_name = $user->first_name;

		$_site_url 	= get_bloginfo('url');
		$site_name 	= get_bloginfo('name');
		$site_url 	= '<a href="'. $_site_url .'">'. $site_name .'</a>';
		$admin_url  = admin_url( 'admin.php?page=affiliates' );
		$_user_url	= add_query_arg( 'affiliate', $user_id, $admin_url );
		$user_url 	= '<a href="'. esc_url( $_user_url ) .'">'. esc_url( $_user_url ) .'</a>';

    	$subject 	= Helper::get_option( 'wc_affiliate_email', 'affiliate_applied_admin_subject' );
    	$message 	= Helper::get_option( 'wc_affiliate_email', 'affiliate_applied_admin_message' );
    	$_message 	= str_replace( [ '%%first_name%%', '%%site_url%%', '%%user_url%%' ], [ $first_name, $site_url, $user_url ], $message );

        $this->send( $email, $subject, wpautop( $_message ) );
	}

	public function account_review_affiliate( $user_id, $review_action, $message ) {

		$user 		= get_userdata( $user_id );
		$email 		= $user->user_email;
		$first_name = $user->first_name;

		if ( $review_action == 'approve' ) {

			$enable 	= Helper::get_option( 'wc_affiliate_email', 'affiliate_approved_enable' );

			if ( !empty( $enable ) && $enable == 'on' ) {
	        	$subject 	= Helper::get_option( 'wc_affiliate_email', 'account_approved_subject' );
	        	$_message 	= Helper::get_option( 'wc_affiliate_email', 'account_approved_message' );

		        $rep_message = str_replace( [ '%%first_name%%', '%%message%%' ], [ $first_name, $message ], $_message );
		        $this->send( $email, $subject, wpautop( $rep_message ) );
			}
        }
        elseif ( $review_action == 'reject' ) {
        	$enable 	= Helper::get_option( 'wc_affiliate_email', 'account_reject_enable' );

			if ( !empty( $enable ) && $enable == 'on' ) {
	        	$subject 	= Helper::get_option( 'wc_affiliate_email', 'account_reject_subject' );
	        	$_message 	= Helper::get_option( 'wc_affiliate_email', 'account_reject_message' );

		        $rep_message = str_replace( [ '%%first_name%%', '%%message%%' ], [ $first_name, $message ], $_message );
		        $this->send( $email, $subject, wpautop( $rep_message ) );
			}
        }
	}

	public function add_credit_affiliate( $user, $order_id ) {

		$enable 	= Helper::get_option( 'wc_affiliate_email', 'add_credit_enable' );

		if ( empty( $enable ) || $enable != 'on' ) return;
		
		$email 		= $user->user_email;
		$first_name = $user->first_name;
		$currency	= function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '[currency]';

    	$amount 	= get_post_meta( $order_id, '_wc_affiliate_credited', true );
    	$subject 	= Helper::get_option( 'wc_affiliate_email', 'add_credit_subject' );
    	$_message 	= Helper::get_option( 'wc_affiliate_email', 'add_credit_message' );

    	$rep_message = str_replace( [ '%%first_name%%', '%%amount%%' ], [ $first_name, $currency . $amount ], $_message );
	    $this->send( $email, $subject, wpautop( $rep_message ) );
	}

	public function add_credit_admin( $user, $order_id ) {

		$enable 	= Helper::get_option( 'wc_affiliate_email', 'add_credit_admin_enable' );

		if ( empty( $enable ) || $enable != 'on' ) return;
		
		$email 		= wc_affiliate_admin_email();

		$first_name = $user->first_name;
		$currency	= function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '[currency]';

    	$amount 	= get_post_meta( $order_id, '_wc_affiliate_credited', true );
    	$_subject 	= Helper::get_option( 'wc_affiliate_email', 'add_credit_admin_subject' );
    	$_message 	= Helper::get_option( 'wc_affiliate_email', 'add_credit_admin_message' );

    	$rep_subject = str_replace( [ '%%first_name%%' ], [ $first_name ], $_subject );
    	$rep_message = str_replace( [ '%%first_name%%', '%%amount%%' ], [ $first_name, $currency . $amount ], $_message );
	    $this->send( $email, $rep_subject, wpautop( $rep_message ) );
	}

	public function request_payout_affiliate( $user_id, $amount ) {

		$enable 	= Helper::get_option( 'wc_affiliate_email', 'request_payout_enable' );

		if ( empty( $enable ) || $enable != 'on' ) return;

		$user 		= get_userdata( $user_id );
		$email 		= $user->user_email;
		$first_name = $user->first_name;

    	$subject 	= Helper::get_option( 'wc_affiliate_email', 'request_payout_subject' );
    	$message 	= Helper::get_option( 'wc_affiliate_email', 'request_payout_mail_message' );

    	$rep_message = str_replace( [ '%%first_name%%' ], [ $first_name ], $message );

        $this->send( $email, $subject, $rep_message );
	}

	public function request_payout_admin( $user_id, $amount ) {

		$enable 	= Helper::get_option( 'wc_affiliate_email', 'request_payout_admin_enable' );

		if ( empty( $enable ) || $enable != 'on' ) return;

		$email 		= wc_affiliate_admin_email();

		$user 		= get_userdata( $user_id );

		$first_name = $user->first_name;
		$currency	= function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '[currency]';

    	$subject 	= Helper::get_option( 'wc_affiliate_email', 'request_payout_admin_subject' );
    	$message 	= Helper::get_option( 'wc_affiliate_email', 'request_payout_admin_message' );

    	$rep_message = str_replace( [ '%%first_name%%', '%%amount%%' ], [ $first_name, $currency . $amount ], $message );

        $this->send( $email, $subject, $rep_message );
	}

	public function payout_processed( $affiliate ) {

		$enable 	= Helper::get_option( 'wc_affiliate_email', 'payout_process_enable' );

		if ( empty( $enable ) || $enable != 'on' ) return;

		$user 		= get_userdata( $affiliate );
		$email 		= $user->user_email;
		$amount 	= Helper::get_user_unpaid_amount( $affiliate );
		$currency	= function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '[currency]';

    	$subject 	= Helper::get_option( 'wc_affiliate_email', 'payout_process_subject' );
    	$message 	= Helper::get_option( 'wc_affiliate_email', 'payout_process_message' );

    	$rep_message = str_replace( [ '%%amount%%' ], [ $currency . $amount ], $message );

        $this->send( $email, $subject, $rep_message );
	}

	/**
	 * Resend Varify email to 
	 * */
	public function resend_varify_url( $response, $user ){

		$user 		= get_user_by( 'ID', get_current_user_id() );
		$email 		= $user->data->user_email;
		$subject 	= __( 'Email Verification', 'wc-affiliate' );

		$dashboard_id 	= Helper::get_option( 'wc_affiliate_basic', 'dashboard' );
		$dashboard_url 	= get_the_permalink( $dashboard_id );
		$validation_data = [
			'id' 	=> $user->ID,
			'email' => $user->data->user_email,
			'time' 	=> time(),
		];
		
		$validation_data 	= json_encode( $validation_data );
		$validation_data 	= Helper::ncrypt()->encrypt( $validation_data );
		$verify_url 		= add_query_arg( 'validate', $validation_data, $dashboard_url );
		$message 			= __( 'Verify your email: ', 'wc-affiliate' ) . $verify_url;

		$this->send( $email, $subject, $message );

		$response['status'] 	= 1;
		$response['message'] 	= __( 'Verification mail was sent. Please Check your inbox', 'wc-affiliate' );

		return $response;
	}
}