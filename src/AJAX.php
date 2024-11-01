<?php
/**
 * All AJAX related functions
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
 * @subpackage AJAX
 * @author codexpert <hello@codexpert.io>
 */
class AJAX extends Base {

	public $plugin;

	public $slug;

	public $name;
	
	public $version;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->slug = $this->plugin['TextDomain'];
		$this->name = $this->plugin['Name'];
		$this->version = $this->plugin['Version'];
	}

	public function remove_affiliate() {
		$response = [ 'status' => 0, 'message' => __( 'Something is wrong!', 'wc-affiliate' ) ];

		if( !wp_verify_nonce( $_POST['nonce'], 'wc-affiliate' ) ) {
			wp_send_json( [ 'status' => 0, 'message' => __( 'Unauthorized!', 'wc-affiliate' ) ] );
		}

		if( isset( $_POST['id'] ) ) {
			delete_user_meta( (int) sanitize_text_field( $_POST['id'] ), '_wc_affiliate_status' );
			wp_send_json( [ 'status' => 1, 'message' => __( 'Affiliate was removed!', 'wc-affiliate' ) ] );
		}

		wp_send_json( $response );
	}

	public function apply() {
		$response = [ 'status' => 0, 'message' => __( 'Something is wrong!', 'wc-affiliate' ) ];
		
		$_nonce				= isset( $_POST['_nonce'] ) ? sanitize_text_field( $_POST['_nonce'] ) : '';
		$first_name			= isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
		$last_name			= isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
		$user_name			= isset( $_POST['user_name'] ) ? sanitize_text_field( $_POST['user_name'] ) : '';
		$email				= isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$website_url 		= isset( $_POST['website_url'] ) ? sanitize_text_field( $_POST['website_url'] ) : '';
		$promotion_method	= isset( $_POST['promotion_method'] ) ? sanitize_text_field( $_POST['promotion_method'] ) : '';
		$password			= isset( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '';
		$password2			= isset( $_POST['password2'] ) ? sanitize_text_field( $_POST['password2'] ) : '';

		if ( !wp_verify_nonce( $_nonce ) ) {
			$response['message'] = __( 'Unauthorized!', 'wc-affiliate' );
			wp_send_json( $response );
		}

		$enable_recaptcha 	= Helper::get_option( 'wc_affiliate_advanced', 'wc_affiliate_enable_recaptcha' );

		if ( $enable_recaptcha ) {
			$endpoint 			= 'https://www.google.com/recaptcha/api/siteverify';
			$secret_key 		= Helper::get_option( 'wc_affiliate_advanced', 'wc_affiliate_secret_recaptcha' );
			 
			$data = [
			    'secret' 	=> $secret_key,
			    'response' 	=> sanitize_text_field( $_POST['g-recaptcha-response'] ),
			];

			$resp = wp_remote_post( $endpoint, array(
			    'body'    	=> $data,
			    'headers' 	=> array(),
			) );

			$data = json_decode( $resp['body'], true );

			if ( $data['success'] != true ) {
				$response['verify'] 	= 1;
				$response['message'] 	= Helper::get_option( 'wc_affiliate_messages', 'verify_humann_message' );
				wp_send_json( $response );
			}
		}

		if( ! is_user_logged_in() ) {
			if( email_exists( $email ) ) {
				$response['email'] = __( 'Email already exists!', 'wc-affiliate' );
				wp_send_json( $response );
			}
			elseif ( username_exists( $user_name ) ) {
				$response['user_name'] = __( 'User Name already exists!', 'wc-affiliate' );
				wp_send_json( $response );
			}
			else{

				if ( $password != $password2 ) {
					$response['password'] = __( 'Password not matched', 'wc-affiliate' );
					wp_send_json( $response );
				}

				$user_data = array(
					'user_login' 	=> $user_name,
					'user_pass' 	=> $password,
					'user_email' 	=> $email,
					'first_name' 	=> $first_name,
					'last_name' 	=> $last_name,
					'display_name' 	=> $first_name . ' ' . $last_name 
				);

				$user_id = wp_insert_user( $user_data );

				$user = new \WP_User( $user_id );
        		$user->remove_role('subscriber');
        		$user->add_role('affiliate');

				$credentials = array(
					'user_login'    => $user_name,
			        'user_password' => $password,
			        'remember'      => true
				);
				wp_signon( $credentials );
			}

		}
		else {
			$user_id = get_current_user_id();
		}

		if ( ! Helper::get_option( 'wc_affiliate_basic', 'enable_email_validation' ) ) {
			update_user_meta( $user_id, '_wc_affiliate_status', 'pending' );
		}
		
		update_user_meta( $user_id, '_wc_affiliate_time_applied', time() );
		update_user_meta( $user_id, '_wc_affiliate_website_url', $website_url );
		update_user_meta( $user_id, '_wc_affiliate_promotion_method', $promotion_method );

		$_cookie_name 	= wc_affiliate_get_cookie_name();

		if ( isset( $_COOKIE[ $_cookie_name ] ) && $_COOKIE[ $_cookie_name ] != '' ) {
			update_user_meta( $user_id, '_wc_affiliate_referrer', sanitize_text_field( $_COOKIE[ $_cookie_name ] ) );
		}

        do_action( 'wc-affiliate-affiliate_applied', $user_id );

		$response['status'] 	= 1;
		$response['message'] 	= Helper::get_option( 'wc_affiliate_messages', 'register_message', __( 'Congratulation! You have applied successfully. We will review your account information and inform you.', 'wc-affiliate' ) );

		wp_send_json( $response );
	}

	public function request_payout() {
		$response = [ 'status' => 0, 'message' => __( 'Something is wrong!', 'wc-affiliate' ) ];

		$_nonce				= isset( $_POST['_nonce'] ) ? sanitize_text_field( $_POST['_nonce'] ) : '';

		if( !wp_verify_nonce( $_POST['_nonce'], 'wc-affiliate' ) ) {
		    $response['message'] 	= __( 'Unauthorized!', 'wc-affiliate' );
		    wp_send_json( $response );
		}

		$user_id 			= get_current_user_id();
		$amount 			= Helper::get_user_unpaid_amount( $user_id );
		$payout_amount 		= Helper::get_option( 'wc_affiliate_payout', 'payout_amount', 50 );

		if ( $amount < $payout_amount ) {
			$response['status']  	= 0;
			$response['message'] 	= Helper::get_option( 'wc_affiliate_messages', 'insufficient_balance_message', __( 'Sorry! Your balance is insufficient to proceed.', 'wc-affiliate' ) );
			wp_send_json( $response );
		}
		elseif ( get_user_meta( $user_id, '_wc-affiliate-applied_payout', true ) ) {
			$response['status']  	= 0;
			$response['message'] 	= __( 'Already Requested!', 'wc-affiliate' );
			wp_send_json( $response );
		}

		update_user_meta( $user_id, '_wc-affiliate-applied_payout', time() );

        do_action( 'wc-affiliate-request_payout', $user_id, $amount );

		$response['status']  	= 1;
		$response['message'] 	= Helper::get_option( 'wc_affiliate_messages', 'request_payout_message' );
		wp_send_json( $response );
	}

	public function update_user() {
		$response = [ 'status' => 0, 'message' => __( 'Something is wrong!', 'wc-affiliate' ) ];
		
		$_nonce			= isset( $_POST['_nonce'] ) ? sanitize_text_field( $_POST['_nonce'] ) : '';
		$city			= isset( $_POST['city'] ) ? sanitize_text_field( $_POST['city'] ) : '';
		$country		= isset( $_POST['country'] ) ? sanitize_text_field( $_POST['country'] ) : '';
		$email			= isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$first_name		= isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
		$image_url		= isset( $_POST['image_url'] ) ? sanitize_text_field( $_POST['image_url'] ) : '';
		$last_name		= isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
		$password		= isset( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '';
		$password2		= isset( $_POST['password2'] ) ? sanitize_text_field( $_POST['password2'] ) : '';
		$payout_method	= isset( $_POST['payout_method'] ) ? sanitize_text_field( $_POST['payout_method'] ) : '';
		$state			= isset( $_POST['state'] ) ? sanitize_text_field( $_POST['state'] ) : '';
		$mannual_payment= isset( $_POST['payout_method'] ) && $_POST['payout_method'] == 'mannual' ? sanitize_textarea_field( $_POST['account_info'] ) : '';

		if ( !wp_verify_nonce( $_nonce ) ) {
			$response['message'] = __( 'Unauthorized!', 'wc-affiliate' );
			wp_send_json( $response );
		}
		if ( $password != $password2 ) {
			$response['password'] = __( 'Password not matched', 'wc-affiliate' );
			wp_send_json( $response );
		}

		$user_id = get_current_user_id();

		$user_data = array(
			'ID'       		=> $user_id,
			'user_email' 	=> $email,
			'first_name' 	=> $first_name,
			'last_name' 	=> $last_name,
		);

		if ( isset( $password ) && isset( $password2 ) ) {
			if ( $password != $password2 ) {
				$response['password'] = __( 'Password not matched', 'wc-affiliate' );
				wp_send_json( $response );
			}

			$user_data['user_pass'] = $password;
		}

		wp_update_user( $user_data );

		update_user_meta( $user_id, '_wc_affiliate_payout_method', $payout_method );
		if ( isset( $_POST['paypal_email'] ) ) {
			update_user_meta( $user_id, '_wc_affiliate_paypal_email', sanitize_email( $_POST['paypal_email'] ) );
		}
		update_user_meta( $user_id, '_wc_affiliate_city', 	$city );
		update_user_meta( $user_id, '_wc_affiliate_state', 	$state );
		update_user_meta( $user_id, '_wc_affiliate_country', $country );
		update_user_meta( $user_id, '_wc_affiliate_avatar', $image_url );
		update_user_meta( $user_id, '_wc_affiliate_mannual_payment', $mannual_payment );

		$response['status'] 	= 1;
		$response['message'] 	= Helper::get_option( 'wc_affiliate_messages', 'update_user_message', __( 'Congratulation! You have successfully updated your user information.', 'wc-affiliate' ) );
		wp_send_json( $response );
	}

	public function transaction_form_action()	{
		$response = [ 'status' => 0, 'message' => __( 'Something is wrong!', 'wc-affiliate' ) ];
		
		$_wpnonce		= isset( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : '';
		$row_id			= isset( $_POST['row_id'] ) ? (int) sanitize_text_field( $_POST['row_id'] ) : '';
		$payment_method	= isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : '';
		$amount			= isset( $_POST['amount'] ) ? (float) sanitize_text_field( $_POST['amount'] ) : '';
		$txn_id			= isset( $_POST['txn_id'] ) ? sanitize_text_field( $_POST['txn_id'] ) : '';

		if ( !wp_verify_nonce( $_wpnonce ) ) {
			$response['message'] = __( 'Unauthorized!', 'wc-affiliate' );
			wp_send_json( $response );
		}

		global $wpdb;
		$transactions_table = $wpdb->prefix . 'wca_transactions';		
		$referrals_table 	= $wpdb->prefix . 'wca_referrals';		

		if ( isset( $row_id ) ) {
			$wpdb->update( $transactions_table, 
				[ 
					'amount'		=> $amount,
					'payment_method'=> $payment_method,
					'txn_id'		=> $txn_id,
					'process_at'	=> current_time( 'timestamp' ),
				],
				[ 'id' => $row_id ],
				[
					'%d',
					'%s',
					'%s',
					'%s',
				]
			);

			$wpdb->update( $referrals_table, 
				[ 'payment_status' => 'paid' ], 
				[ 'transaction_id' => $row_id ], 
				[ '%s' ], 
				[ '%d' ] 
			);

			$response['message'] = __( 'Transaction Updated.', 'wc-affiliate' );			
			$response['status']  = 1;
		}
		else{
			$wpdb->insert( $transactions_table,
				[
					'affiliate'		=> $affiliate,
					'amount'		=> $amount,
					'payment_method'=> $payment_method,
					'txn_id'		=> $txn_id,
					'status'		=> $status,
					'request_at'	=> current_time( 'timestamp' ),
					'process_at'	=> current_time( 'timestamp' ),
				],
				[
					'%d',
					'%f',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
				]
			);

			$response['message'] = __( 'Transaction Inserted.', 'wc-affiliate' );
			$response['status']  = 1;
		}

		wp_send_json( $response );
	}

	public function review_action() {
		$response = [ 'status' => 0, 'message' => __( 'Something is wrong!', 'wc-affiliate' ) ];

        $action			= isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
        $nonce			= isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
        $review_action	= isset( $_POST['review_action'] ) ? sanitize_text_field( $_POST['review_action'] ) : '';
        $user_id		= isset( $_POST['user_id'] ) ? (int) sanitize_text_field( $_POST['user_id'] ) : '';
        $message		= isset( $_POST['message'] ) ? sanitize_text_field( $_POST['message'] ) : '';
        $email			= isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

        if( !wp_verify_nonce( $nonce, 'wc-affiliate' ) ) {
            $response['message'] 	= __( 'Unauthorized!', 'wc-affiliate' );
            wp_send_json( $response );
        }

        if ( $review_action == 'approve' ) {
        	update_user_meta( $user_id, '_wc_affiliate_status', 'active' );

        	$response['message']    = __( 'User Approved', 'wc-affiliate' );
	    	$response['status'] 	= 1;
        }
        elseif ( $review_action == 'reject' ) {
        	update_user_meta( $user_id, '_wc_affiliate_status', 'rejected' );

        	$response['message']    = __( 'User rejected', 'wc-affiliate' );
	    	$response['status'] 	= 2;
        }

        do_action( 'wc-affiliate-account_reviewed', $user_id, $review_action, $message );

    	wp_send_json( $response );
	}

	public function generate_url() {
		$response = [ 'status' => 0, 'message' => __( 'Something is wrong!', 'wc-affiliate' ) ];

        if( !wp_verify_nonce( $_POST['_wpnonce'], 'wc-affiliate' ) ) {
            $response['message'] 	= __( 'Unauthorized!', 'wc-affiliate' );
            wp_send_json( $response );
        }


        $this_site 	= site_url();
        $given_url 	= esc_url( $_POST['url'] );
        
        // $matched 	= stripos( $given_url, $this_site );
        // if ( $matched === false ) {
        // 	$response['message'] 	= __( 'It looks like the URL you provided doesn\'t belong to this site! Please try another one.', 'wc-affiliate' );
        // 	wp_send_json( $response );
        // }

        $affiliate_link = false;

        // generate affiliate link
        $args = [ wc_affiliate_get_ref_key() => Helper::get_token() ];
        if( isset( $_POST['campaign'] ) && $_POST['campaign'] != '' ) {
        	$args['campaign'] = sanitize_text_field( $_POST['campaign'] );
        }
        $affiliate_link = add_query_arg( $args, $given_url );

        // generate shortlink
        do_action( 'wc-affiliate-after_generate_url', $_POST, $affiliate_link );

        $response['status']			= 1;
        $response['message']		= __( 'Affiliate link generated!' );
        $response['affiliate_link'] = $affiliate_link;

        $response = apply_filters( 'wc-affiliate-response_generate_url', $response, $_POST );
        wp_send_json( $response );
	}

	public function export_csv_report()	{

        $headings 	= sanitize_text_field( $_POST['headings'] );
        $headings 	= stripslashes( $headings );
        $headings 	= unserialize( $headings );
        $wf_data 	= sanitize_text_field( $_POST['data'] );
        $wf_data	= stripslashes( $wf_data );
        $wf_data 	= unserialize( $wf_data );
        $format 	= get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        $data 		= [];

        $_wpnonce	= isset( $wf_data['_wpnonce'] ) ? sanitize_text_field( $wf_data['_wpnonce'] ) : false;
        $page		= isset( $wf_data['page'] ) ? sanitize_text_field( $wf_data['page'] ) : false;
        $status		= isset( $wf_data['status'] ) ? sanitize_text_field( $wf_data['status'] ) : false;
        $from		= isset( $wf_data['from'] ) ? sanitize_text_field( $wf_data['from'] ) : false;
        $to			= isset( $wf_data['to'] ) ? sanitize_text_field( $wf_data['to'] ) : false;
        $paged		= isset( $wf_data['paged'] ) ? sanitize_text_field( $wf_data['paged'] ) : false;

        global $wpdb;

        if ( $page == 'affiliates' ) {
        	$meta_table = "{$wpdb->prefix}usermeta";

        	if( is_multisite() ) {
        	    $blog_id 		= get_current_blog_id();
        	    $meta_table 	= "{$wpdb->base_prefix}{$blog_id}_usermeta";
        	}

        	$user_id = "SELECT `user_id` FROM `{$meta_table}` WHERE meta_key = '_wc_affiliate_status'";

        	if( $status ) {
        	    $user_id .= " AND `meta_key` = '_wc_affiliate_status' AND meta_value = '{$status}'";
        	}

        	$sql = "SELECT * FROM `{$meta_table}` WHERE user_id IN( $user_id ) AND meta_key = '_wc_affiliate_time_applied'";

        	if( $from && $to ) {
        		$form_date 	= strtotime( $from );
        		$to_date 	= strtotime( $to ) + DAY_IN_SECONDS - 1; // we need to consider that entire day
        	    $sql .= " AND `meta_key` = '_wc_affiliate_time_applied' AND `meta_value` >= {$form_date} AND `meta_value` <= {$to_date}";
        	}

        	$results 	= $wpdb->get_results( $sql );
        }
        elseif ( $page == 'payables' ) {
        	$results = wc_affiliate_get_payable_affiliates();
        }
        else {
        	$_table = "{$wpdb->prefix}wca_{$page}";

        	if( is_multisite() ) {
        	    $blog_id 			= get_current_blog_id();
        	    $_table = "{$wpdb->base_prefix}{$blog_id}_wc_affiliate_{$page}";
        	}

        	$sql = "SELECT * FROM $_table WHERE 1 = 1";

        	if( isset( $status ) && $status != '' ) {
        		if( $page == 'referrals' ){
        	    	$sql .= " AND `payment_status` = '{$status}'";
        		}
        		else{
        	    	$sql .= " AND `status` = '{$status}'";
        		}
        	}

        	if( $from && $to ) {
        		$form_date 	= strtotime( $from );
        		$to_date 	= strtotime( $to ) + DAY_IN_SECONDS - 1; // we need to consider that entire day
        		if ( $page == 'transactions' ) {
        	    	$sql 	   .= " AND `request_at` >= '{$form_date}' AND `request_at` <= '{$to_date}'";
        		}
        		elseif( $page == 'visits' || $page == 'referrals' ){
        			$sql 	   .= " AND `time` >= '{$form_date}' AND `time` <= '{$to_date}'";
        		}
        	}

        	if( isset( $txn ) && $txn != '' ) {
        	    $sql .= " AND `txn_id` LIKE '%{$txn}%'";
        	}

        	if( isset( $affiliate )  && $affiliate != '' ) {
        	    $sql .= " AND `affiliate` = '{$affiliate}'";
        	}

        	$sql 	   .= " ORDER BY `id` DESC";
        	$results 	= $wpdb->get_results( $sql );
        }

        if ( $page == 'transactions' ) {
        	foreach ( $results as $result ) {
        	    $data[] = [
        	        'affiliate'			=> get_userdata( $result->affiliate )->display_name,
        	        'amount'			=> $result->amount,
        	        'payment_method'	=> $result->payment_method,
        	        'txn_id'			=> $result->txn_id,
        	        'request_at'		=> date( $format, $result->request_at ),
        	        'process_at'		=> date( $format, $result->process_at ),
        	        'status'			=> $result->status,
        	    ];
        	}
        }
        elseif( $page == 'visits' ){        	
        	foreach ( $results as $result ) {
        	    $data[] = [
        	        'referral'		=> $result->referral,
        	        'page_url'		=> $result->page_url,
        	        'referrer_url'	=> $result->referrer_url,
        	        'campaign'		=> $result->campaign,
        	        'ip'			=> $result->ip,
        	        'time'			=> date( $format, $result->time ),
        	    ];
        	}
        }
        elseif( $page == 'referrals' ){        	
        	foreach ( $results as $result ) {
        		$products 	= '';
        		$_products 	= unserialize( $result->products );

        		foreach ( $_products as $key => $product ) {
        		    $products .= $product . ", ";
        		}

        		$products = rtrim( $products, ", " );
        	    $data[] = [
        	        'affiliate'			=> get_userdata( $result->affiliate )->display_name,
        	        'visit'				=> $result->visit,
        	        'order_id'			=> $result->order_id,
        	        'products'			=> $products,
        	        'order_total'		=> $result->order_total,
        	        'commission'		=> $result->commission,
        	        'payment_status'	=> $result->payment_status,
        	        'time'				=> date( $format, $result->time ),
        	    ];
        	}
        }

        elseif( $page == 'affiliates' ){        	
        	foreach ( $results as $result ) {
        		$user 			= get_userdata( $result->user_id );
        		$status 		= get_user_meta( $result->user_id, '_wc_affiliate_status', true );
        		$registered 	= date( $format, strtotime( $user->user_registered ) );
        		$applied_time 	= date( $format, $result->meta_value );

        		$data[] = [
        			'affiliate_id'	=> "#{$result->user_id}",
        			'name'			=> $user->display_name,
        			'registered'	=> $registered,
        			'applied_time'	=> $applied_time,
        			'status'		=> $status,
        		];
        	}
        }
        elseif ( $page == 'payables' ) {
        	foreach ( $results as $affiliate_id => $result ) {
        		$affiliate = get_userdata( $affiliate_id );
        		$data[] = [
        			'affiliate_id'	=> "#{$affiliate_id}",
        			'name'			=> $affiliate->display_name,
        			'amount'		=> $result,
        			'applied'		=> 'Yes',
        		];
        	}
        }

        // create a file pointer connected to the output stream
        $output = fopen( 'php://output', 'w' );
        
        // output the column headings
        fputcsv( $output, $headings );

        //Loop through the array and add to the csv
        foreach ( $data as $row ) {
            fputcsv( $output, (array) $row );
        }
        
        wp_die();
	}

	public function export_table_report() {

		global $wpdb;

		$name 		= sanitize_text_field( $_POST['name'] );
        $table 		= "{$wpdb->prefix}wca_{$name}";
		$sql 		= "SELECT * FROM $table";
        $results 	= $wpdb->get_results( $sql );

        $data = [];
		if ( $name == 'transactions' ) {
			$headings = array(
			    'affiliate',
			    'amount',
			    'payment_method',
			    'txn_id',
			    'request_at',
			    'process_at',
			    'status',
			);

        	foreach ( $results as $result ) {
        	    $data[] = [
        	        'affiliate'			=> $result->affiliate,
        	        'amount'			=> $result->amount,
        	        'payment_method'	=> $result->payment_method,
        	        'txn_id'			=> $result->txn_id,
        	        'request_at'		=> $result->request_at,
        	        'process_at'		=> $result->process_at,
        	        'status'			=> $result->status,
        	    ];
        	}
        }
        elseif( $name == 'shortlinks' ){
        	$headings = array(
			    'affiliate',
			    'page_url',
			    'campaign',
			    'identifier',
			    'time',
			);

        	foreach ( $results as $result ) {
        	    $data[] = [
        	        'affiliate'		=> $result->affiliate,
        	        'page_url'		=> $result->page_url,
        	        'campaign'		=> $result->campaign,
        	        'identifier'	=> $result->identifier,
        	        'time'			=> $result->time,
        	    ];
        	}
        }
        elseif( $name == 'visits' ){
        	$headings = array(
			    'referral',
			    'page_url',
			    'referrer_url',
			    'campaign',
			    'ip',
			    'time',
			);

        	foreach ( $results as $result ) {
        	    $data[] = [
        	        'referral'		=> $result->referral,
        	        'page_url'		=> $result->page_url,
        	        'referrer_url'	=> $result->referrer_url,
        	        'campaign'		=> $result->campaign,
        	        'ip'			=> $result->ip,
        	        'time'			=> $result->time,
        	    ];
        	}
        }
        elseif( $name == 'referrals' ){
        	$headings = array(
			    'affiliate',
			    'visit',
			    'order_id',
			    'products',
			    'order_total',
			    'commission',
			    'payment_status',
			    'time',
			);

        	foreach ( $results as $result ) {
        	    $data[] = [
        	        'affiliate'			=> $result->affiliate,
        	        'visit'				=> $result->visit,
        	        'order_id'			=> $result->order_id,
        	        'products'			=> $result->products,
        	        'order_total'		=> $result->order_total,
        	        'commission'		=> $result->commission,
        	        'payment_status'	=> $result->payment_status,
        	        'time'				=> $result->time,
        	    ];
        	}
        }

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, $headings );

        foreach ( $data as $row ) {
            fputcsv( $output, (array) $row );
        }

        fclose($output);
        wp_die();
	}

	
	/**
	 * Process payout and insert transaction
	 */
	public function payout() {
		$response = [ 'status' => 0, 'message' => __( 'Something is wrong!', 'wc-affiliate' ) ];

		$_wpnonce	= isset( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : '';
		$affiliate	= isset( $_POST['affiliate'] ) ? (int) sanitize_text_field( $_POST['affiliate'] ) : '';

        if( !wp_verify_nonce( $_wpnonce, 'wc-affiliate' ) ) {
            $response['message'] 	= __( 'Unauthorized!', 'wc-affiliate' );
            wp_send_json( $response );
        }

        global $wpdb;
        $referrals_table 	= "{$wpdb->prefix}wca_referrals";
        $transactions_table = "{$wpdb->prefix}wca_transactions";

        if( is_multisite() ) {
            $blog_id 			= get_current_blog_id();
            $referrals_table 	= "{$wpdb->base_prefix}{$blog_id}_wca_referrals";
        	$transactions_table = "{$wpdb->base_prefix}{$blog_id}_wca_transactions";
        }

		$requested 		= get_user_meta( $affiliate, '_wc-affiliate-applied_payout', true );
		$payout_method	= get_user_meta( $affiliate, '_wc_affiliate_payout_method', true );
		$amount 		= Helper::get_user_unpaid_amount( $affiliate );

		/**
		 * Inster the transaction
		 *
		 * @since 1.0
		 */
        $wpdb->insert( $transactions_table,
			[
				'affiliate'		=> $affiliate,
				'amount'		=> $amount,
				'payment_method'=> '',
				'txn_id'		=> '',
				'status'		=> 'paid',
				'request_at'	=> $requested,
				'process_at'	=> current_time( 'timestamp' ),
			],
			[
				'%d',
				'%f',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
			]
		);

		$transaction_db_id = $wpdb->insert_id;

		do_action( 'wc-affiliate-transaction_inserted', $transaction_db_id );

		if ( $transaction_db_id ) {

			/**
			 * Update associated referrals in the database
			 *
			 * @since 1.0
			 */
			$wpdb->update( $referrals_table, 
				[ 
					'transaction_id'	=> $transaction_db_id,
					'payment_status'	=> 'paid',
				],
				[
					'affiliate' 		=> $affiliate,
					'payment_status' 	=> 'approved',
					'transaction_id' 	=> 0,
				],
				[
					'%d',
					'%s',
				],
				[
					'%d',
					'%s',
					'%d',
				]
			);

			$currency 	= get_woocommerce_currency();
			$txn_id 	= apply_filters( 'wc-affiliate-process_payout', '', $transaction_db_id, $affiliate, $amount, $payout_method  );

			/**
			 * Update transactins table with the txn_id from the payment processor
			 */
		    if( $txn_id ) {
	    	    $wpdb->update( $transactions_table, 
	    	        [ 
	    	            'txn_id'            => $txn_id,
	    	            'payment_method'    => $payout_method,
	    	        ],
	    	        [
	    	            'id'                => $transaction_db_id,
	    	        ],
	    	        [
	    	            '%s',
	    	            '%s',
	    	        ],
	    	        [
	    	            '%d',
	    	        ]
	    	    );
		    }
		}

        delete_user_meta( $affiliate, '_wc-affiliate-applied_payout' );

        do_action( 'wc-affiliate-marked_paid', $affiliate );

        // redirect to transaction edit screen
		$response['redirect'] = add_query_arg( [ 'page' => 'transactions', 'transaction' => $transaction_db_id ], admin_url( 'admin.php' ) );
		$response['message'] = __( 'Payout completed!' );
		$response['status'] = 1;
		wp_send_json( $response );
	}

	public function register_new_affiliate()	{
		$response = [ 'status' => 0, 'message' => __( 'Something is wrong!', 'wc-affiliate' ) ];
		
        $_nonce 			= isset( $_POST['_nonce'] ) ? sanitize_text_field( $_POST['_nonce'] ) : '';
        $first_name 		= isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
        $last_name 			= isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
        $user_name 			= isset( $_POST['user_name'] ) ? sanitize_text_field( $_POST['user_name'] ) : '';
        $email 				= isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $website_url 		= isset( $_POST['website_url'] ) ? sanitize_text_field( $_POST['website_url'] ) : '';
        $promotion_method 	= isset( $_POST['promotion_method'] ) ? sanitize_text_field( $_POST['promotion_method'] ) : '';
        $password 			= isset( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '';
        $password2 			= isset( $_POST['password2'] ) ? sanitize_text_field( $_POST['password2'] ) : '';
        $terms_agree 		= isset( $_POST['terms_agree'] ) ? sanitize_text_field( $_POST['terms_agree'] ) : '';
        $affiliate_status	= isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

        if( !wp_verify_nonce( $_POST['_wpnonce'], 'wc-affiliate' ) ) {
            $response['message'] 	= __( 'Unauthorized!', 'wc-affiliate' );
            wp_send_json( $response );
        }

		if( isset( $_POST['email'] ) ) {
			if( email_exists( $email ) ) {
				$response['message'] = __( 'Email already exists!', 'wc-affiliate' );
				wp_send_json( $response );
			}
			else{
				$user_data = array(
					'user_login' 	=> $email,
					'user_pass' 	=> $password,
					'user_email' 	=> $email,
					'first_name' 	=> $first_name,
					'last_name' 	=> $last_name,
					'display_name' 	=> $first_name . ' ' . $last_name
				);

				$user_id = wp_insert_user( $user_data );

				$user = new \WP_User( $user_id );
        		$user->remove_role('subscriber');
        		$user->add_role('affiliate');
			}

		}
		elseif( $_POST['affiliate'] ) {
			$user_id = (int) sanitize_text_field( $_POST['affiliate'] );
			if ( get_user_meta( $user_id, '_wc_affiliate_status' ) ) {
				$response['message'] = __( 'Already an affiliate!', 'wc-affiliate' );
				wp_send_json( $response );
			}
		}
		else{
        	wp_send_json( $response );
		}
			update_user_meta( $user_id, '_wc_affiliate_status', $affiliate_status );
			update_user_meta( $user_id, '_wc_affiliate_time_applied', time() );		

		if ( isset( $_POST['commission_amount'] ) && $_POST['commission_amount'] != '' ) {
			update_user_meta( $user_id, 'commission_type', sanitize_text_field( $commission_type ) );
			update_user_meta( $user_id, 'commission_amount', sanitize_text_field( $commission_amount ) );
		}

		$response['status'] 	= 1;
		$response['message'] 	= Helper::get_option( 'wc_affiliate_messages', 'register_new_affiliate_message', __( 'Congratulation! New affiliates has been created successfully.', 'wc-affiliate' ) );

        wp_send_json( $response );
	}

	/**
	 * Resend Varify email to 
	 * */
	public function resend_varify_url(){
		
		$user = get_user_by( 'ID', get_current_user_id() );

		$response['status'] 	= 0;
		$response['message'] 	= __( 'Something Went wrong', 'wc-affiliate' );

		$response = apply_filters( 'wc-affiliate-resend_varify_email', $response, $user );


		wp_send_json( $response );
	}

	/**
	 * export affiliate data
	 * @author Jakaria Istauk <jakariamd35@gmail.com>
	 */
	public function export_all_data(){
		global $wpdb;

		$meta_table = "{$wpdb->prefix}usermeta";

		if( is_multisite() ) {
		    $blog_id 		= get_current_blog_id();
		    $meta_table 	= "{$wpdb->base_prefix}{$blog_id}_usermeta";
		}

		$user_id = "SELECT `user_id` FROM `{$meta_table}` WHERE meta_key = '_wc_affiliate_status'";

		$sql = "SELECT * FROM `{$meta_table}` WHERE user_id IN( $user_id ) AND meta_key = '_wc_affiliate_time_applied'";

		$affiliates 	= $wpdb->get_results( $sql );

		$db_tables = [ 'visits', 'referrals', 'transactions' ];
		$db_data = [];

		if( Helper::has_pro() ){
			$db_tables[] = 'shortlinks';
		}

		foreach ( $db_tables as $table ) {
			$_table = "{$wpdb->prefix}wca_{$table}";

			if( is_multisite() ) {
			    $blog_id 	= get_current_blog_id();
			    $_table 	= "{$wpdb->base_prefix}{$blog_id}wca_{$table}";
			}

			$sql = "SELECT * FROM $_table ORDER BY `id` DESC";
			$db_data[ $table ] 	= $wpdb->get_results( $sql );
		}

		$data_set = [];
		foreach ( $db_data as $table => $_data_set ) {
			if( !empty( $_data_set ) ){
				if ( $table == 'visits' ) {
					foreach ( $_data_set as $_data ) {
						if ( !isset( $data_set[ $table ][ $_data->affiliate ] ) ) {
							$data_set[ $table ][ $_data->affiliate ] = [];
						}
						$data_set[ $table ][ $_data->affiliate ][] = [
							'referral' 	=> $_data->referral,
							'page_url' 	=> $_data->page_url,
							'referrer_url' => $_data->referrer_url,
							'campaign' 	=> $_data->campaign,
							'ip' 		=> $_data->ip,
							'time' 		=> $_data->time,
						];
					}
				}
				elseif( $table == 'transactions' ){
					foreach ( $_data_set as $_data ) {
						if ( !isset( $data_set[ $table ][ $_data->affiliate ] ) ) {
							$data_set[ $table ][ $_data->affiliate ] = [];
						}
						$data_set[ $table ][ $_data->affiliate ][] = [
							'amount' 		=> $_data->amount,
							'payment_method'=> $_data->payment_method,
							'txn_id' 		=> $_data->txn_id,
							'status' 		=> $_data->status,
							'request_at' 	=> $_data->request_at,
							'process_at' 	=> $_data->process_at,
						];
					}
				}
				elseif( $table == 'referrals' ){
					foreach ( $_data_set as $_data ) {
						if ( !isset( $data_set[ $table ][ $_data->affiliate ] ) ) {
							$data_set[ $table ][ $_data->affiliate ] = [];
						}
						$data_set[ $table ][ $_data->affiliate ][] = [
							'visit' 		=> $_data->visit,
							'order_id' 		=> $_data->order_id,
							'products' 		=> $_data->products,
							'order_total' 	=> $_data->order_total,
							'commission' 	=> $_data->commission,
							'payment_status'=> $_data->payment_status,
							'transaction_id'=> $_data->transaction_id,
							'time' 			=> $_data->time,
						];
					}
				}
				elseif( $table == 'shortlinks' ){
					foreach ( $_data_set as $_data ) {
						if ( !isset( $data_set[ $table ][ $_data->affiliate ] ) ) {
							$data_set[ $table ][ $_data->affiliate ] = [];
						}
						$data_set[ $table ][ $_data->affiliate ][] = [
							'page_url' 	=> $_data->page_url,
							'campaign' 	=> $_data->campaign,
							'identifier'=> $_data->identifier,
							'time' 		=> $_data->time,
						];
					}
				}
			}					
		}

		$export_data = [];
		foreach ( $affiliates as $affiliate ) {
			$user = get_user_by( 'ID', $affiliate->user_id );
			if ( $user ) {
				$_userdata = [
					'user_name' => $user->user_login,
					'applied'	=> $affiliate->meta_value,
					'status'	=> get_user_meta( $user->ID, '_wc_affiliate_status', true ),
					'city' 		=> get_user_meta( $user->ID, '_wc_affiliate_city', true ),
					'state' 	=> get_user_meta( $user->ID, '_wc_affiliate_state', true ),
					'country' 	=> get_user_meta( $user->ID, '_wc_affiliate_country', true ),
					'avatar' 	=> get_user_meta( $user->ID, '_wc_affiliate_avatar', true ),
					'paypal_email' 		=> get_user_meta( $user->ID, '_wc_affiliate_paypal_email', true ),
					'payout_method' 	=> get_user_meta( $user->ID, '_wc_affiliate_payout_method', true ),
					'mannual_payment' 	=> get_user_meta( $user->ID, '_wc_affiliate_mannual_payment', true ),
				];

				$commission_type 	= get_user_meta( $user->ID, 'commission_type', true );
				$commission_amount 	= get_user_meta( $user->ID, 'commission_amount', true );

				if( $commission_type && $commission_amount ){
					$_userdata['commission_type'] 	= $commission_type; 
					$_userdata['commission_amount'] = $commission_amount; 
				}
				
				foreach ( $db_data as $table => $data ) {
					if( !empty( $data ) && isset( $data_set[ $table ][ $affiliate->user_id ] ) ){
						$_userdata[ $table ] = $data_set[ $table ][ $affiliate->user_id ];
					}					
				}
				$export_data[ $user->user_email ] = $_userdata;
			}
		}

		$settings_section = [ 'basic', 'advanced', 'payout', 'mlc', 'messages', 'email', 'xdomain', 'shortlinks' ];
		foreach ( $settings_section as $section ) {
			$export_data['settings'][ $section ] = get_option( "wc_affiliate_{$section}" );
		}

		wp_send_json( $export_data );
	}

	/**
	 * Import affiliate data
	 * @author Jakaria Istauk <jakariamd35@gmail.com>
	 * 
	 */
	public function import_data(){
		
		$response = [ 'status' => 0, 'message' => __( 'Something Went wrong!', 'wc-affiliate' ) ];

		if( !wp_verify_nonce( $_POST['_wpnonce'], 'wc-affiliate' ) ) {
		    $response['message'] 	= __( 'Unauthorized!', 'wc-affiliate' );
		    wp_send_json( $response );
		}

		if ( $_POST['from'] == 'wc-affiliate' || $_POST['from'] == '' ) {
			$inserted = $this->import_data_from_wcaffiliate( $_FILES );
			$response['status']	= 1;
			// Translators: %d is the number of rows inserted.
			$response['message']= sprintf( __( '%d row inserted', 'wc-affiliate' ), $inserted );
		}

		wp_send_json( $response );
	}

	public function import_data_from_wcaffiliate( $data ){
		

		$json_data 	= file_get_contents( $data['file']['tmp_name'] );
		$affiliates = json_decode( $json_data );
		$row_inserted = 0;

		if ( isset( $affiliates->settings ) ) {
			$settings  = $affiliates->settings;
			unset( $affiliates->settings );
		}

		$has_pro = Helper::has_pro();
		global $wpdb;
		foreach ( $affiliates as $email => $affiliate ) {
			$user = [];
			$email = sanitize_email( $email );

			if ( email_exists( $email ) ) {
				$user = get_user_by( 'email', $email );
			}
			else{
				$user_data = array(
					'user_login' 	=> sanitize_text_field( $affiliate->user_name ),
					'user_email' 	=> $email,
					'display_name' 	=> sanitize_text_field( $affiliate->user_name )
				);
				$user_id 	= wp_insert_user( $user_data );
				$user 		= get_user_by( 'ID', $user_id );
			}

			if( $affiliate->payout_method ) update_user_meta( $user->ID, '_wc_affiliate_payout_method', sanitize_text_field( $affiliate->payout_method ) );

			if( $affiliate->paypal_email ) update_user_meta( $user->ID, '_wc_affiliate_paypal_email', sanitize_text_field( $affiliate->paypal_email ) );

			if( $affiliate->mannual_payment ) update_user_meta( $user->ID, '_wc_affiliate_mannual_payment', sanitize_text_field( $affiliate->mannual_payment ) );

			if( $affiliate->commission_type ) update_user_meta( $user->ID, 'commission_type', sanitize_text_field( $affiliate->commission_type ) );
			if( $affiliate->commission_amount ) update_user_meta( $user->ID, 'commission_amount', sanitize_text_field( $affiliate->commission_amount ) );

			update_user_meta( $user->ID, '_wc_affiliate_status', sanitize_text_field( $affiliate->status ) );
			update_user_meta( $user->ID, '_wc_affiliate_time_applied', sanitize_text_field( $affiliate->applied ) );
			update_user_meta( $user->ID, '_wc_affiliate_city', sanitize_text_field( $affiliate->city ) );
			update_user_meta( $user->ID, '_wc_affiliate_state', sanitize_text_field( $affiliate->state ) );
			update_user_meta( $user->ID, '_wc_affiliate_country', sanitize_text_field( $affiliate->country ) );
			update_user_meta( $user->ID, '_wc_affiliate_avatar', sanitize_text_field( $affiliate->avatar ) );

			$db_tables = [ 'visits', 'referrals', 'transactions', 'shortlinks' ];

			foreach ( $db_tables as $table ) {
				if ( isset( $affiliate->$table ) ) {
					$data_set = $affiliate->$table;
					if ( $table == 'visits' ) {
						$visits_table = "{$wpdb->prefix}wca_{$table}";
						foreach ( $data_set as $data) {
					        $wpdb->insert( $visits_table,
								[
									'affiliate'		=> $user->ID,
									'referral'		=> (int)sanitize_text_field( $data->referral ),
									'page_url'		=> esc_url( $data->page_url ),
									'referrer_url'	=> esc_url( $data->referrer_url ),
									'campaign'		=> sanitize_text_field( $data->campaign ),
									'ip'			=> sanitize_text_field( $data->ip ),
									'time'			=> (int)sanitize_text_field( $data->time ),
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
							$row_inserted++;
						}
					}
					else if ( $table == 'referrals' ) {
						$referrals_table = "{$wpdb->prefix}wca_{$table}";
						foreach ( $data_set as $data) {
					        $wpdb->insert( $referrals_table,
								[
									'affiliate'			=> $user->ID,
									'visit'				=> (int)sanitize_text_field( $data->visit ),
									'order_id'			=> (int)sanitize_text_field( $data->order_id ),
									'products'			=> sanitize_text_field( $data->products ),
									'order_total'		=> (float)sanitize_text_field( $data->order_total ),
									'commission'		=> (float)sanitize_text_field( $data->commission ),
									'payment_status'	=> sanitize_text_field( $data->payment_status ),
									'time'				=> (int)sanitize_text_field( $data->time ),
								],
								[
									'%d',
									'%d',
									'%d',
									'%s',
									'%f',
									'%f',
									'%s',
									'%d',
								]
							);
							$row_inserted++;
					    }
					}
					else if ( $table == 'shortlinks' && $has_pro ) {
						$visits_table = "{$wpdb->prefix}wca_{$table}";
						foreach ( $data_set as $data) {
					        $wpdb->insert( $visits_table,
								[
									'affiliate'		=> $user->ID,
									'page_url'		=> esc_url_raw( $data->page_url ),
									'campaign'		=> sanitize_text_field( $data->campaign ),
									'identifier'	=> sanitize_text_field( $data->identifier ),
									'time'			=> (int)sanitize_text_field( $data->time )
								],
								[
									'%d',
									'%s',
									'%s',
									'%s',
									'%d',
								]
							);
							$row_inserted++;
					    }
					}
					else if ( $table == 'transactions' ) {
						$visits_table = "{$wpdb->prefix}wca_{$table}";
						foreach ( $data_set as $data) {
					        $wpdb->insert( $visits_table,
								[
									'affiliate'		=> $user->ID,
									'amount'		=> (float)sanitize_text_field( $data->amount ),
									'payment_method'=> sanitize_text_field( $data->payment_method ),
									'txn_id'		=> sanitize_text_field( $data->txn_id ),
									'status'		=> sanitize_text_field( $data->status ),
									'request_at'	=> (int)sanitize_text_field( $data->request_at ),
									'process_at'	=> (int)sanitize_text_field( $data->process_at ),
								],
								[
									'%d',
									'%f',
									'%s',
									'%s',
									'%s',
									'%d',
									'%d',
								]
							);
							$row_inserted++;
					    }
					}
				}
			}
		}

		return $row_inserted;
	}
}