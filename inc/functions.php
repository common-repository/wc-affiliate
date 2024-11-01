<?php
use Codexpert\WC_Affiliate\Helper;

if( !function_exists( 'get_plugin_data' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

/********************************************
 * ********** FROM SETTINGS PAGES ********* *
 ********************************************/

if( !function_exists( 'wc_affiliate_get_ref_key' ) ) :
/**
 * Gets the key in the query string to detect an affiliate
 *
 * @since 1.0
 */
function wc_affiliate_get_ref_key() {
	return Helper::get_option( 'wc_affiliate_advanced', 'ref_key', 'ref' );
}
endif;

if( !function_exists( 'wc_affiliate_token_type' ) ) :
/**
 * What's an affiliate identified by? login or ID
 * more options can be used: id | ID | slug | email | login
 *
 * @since 1.0
 */
function wc_affiliate_token_type() {
	return Helper::get_option( 'wc_affiliate_advanced', 'token_type', 'ID' );
}
endif;

if( !function_exists( 'wc_affiliate_get_cookie_name' ) ) :
/**
 * Gets the cookie name
 *
 * @since 1.0
 */
function wc_affiliate_get_cookie_name() {
	return Helper::get_option( 'wc_affiliate_advanced', 'cookie_name', '_wc_affiliate' );
}
endif;

if( !function_exists( 'wc_affiliate_get_visit_cookie_name' ) ) :
/**
 * Gets the visit count cookie
 *
 * @since 1.0
 */
function wc_affiliate_get_visit_cookie_name() {
	return Helper::get_option( 'wc_affiliate_advanced', 'visit_cookie_name', '_wc_affiliate_visit' );
}
endif;

if( !function_exists( 'wc_affiliate_cookie_expiry' ) ) :
/**
 * Gets cookie validity
 *
 * @since 1.0
 */
function wc_affiliate_cookie_expiry() {
	$time = Helper::get_option( 'wc_affiliate_basic', 'expiry_time' );
	$unit = Helper::get_option( 'wc_affiliate_basic', 'expiry_unit' );
	return $time * $unit;
}
endif;

if( !function_exists( 'wc_affiliate_allow_overwrite' ) ) :
/**
 * Should a new affiliate replace old one?
 *
 * @since 1.0
 */
function wc_affiliate_allow_overwrite() {
	return Helper::get_option( 'wc_affiliate_advanced', 'allow_overwrite' ) == 'on';
}
endif;

if( !function_exists( 'wc_affiliate_log_enabled' ) ) :
/**
 * Log all hits/visits
 *
 * @since 1.0
 */
function wc_affiliate_log_enabled() {
	return apply_filters( 'wc_affiliate_log_enabled', true );
}
endif;


if( !function_exists( 'wc_affiliate_wc_tab_label' ) ) :
/**
 * WC Affiliate tab label in the WC My Account page
 *
 * @since 1.0
 */
function wc_affiliate_wc_tab_label() {
	return Helper::get_option( 'wc_affiliate_advanced', 'wc_tab_label', __( 'Affiliate', 'wc_affiliate' ) );
}
endif;

if( !function_exists( 'wc_affiliate_credit_once' ) ) :
/**
 * Should we remove the cookie as soon as an order is placed?
 *
 * @since 1.0
 */
function wc_affiliate_credit_once() {
	return Helper::get_option( 'wc_affiliate_advanced', 'credit_once' ) == 'on';
}
endif;

if( !function_exists( 'wc_affiliate_redirection_base' ) ) :
/**
 * redirection base for shortlinks
 *
 * @since 1.0
 */
function wc_affiliate_redirection_base() {
	return trailingslashit( Helper::get_option( 'wc_affiliate_shortlinks', 'shortlink_base', 'go' ) );
}
endif;

if( !function_exists( 'wc_affiliate_get_dashboard' ) ) :
/**
 * enable shortlink edit
 *
 * @since 1.0
 */
function wc_affiliate_get_dashboard( $type = 'link' ) {
	$id = Helper::get_option( 'wc_affiliate_basic', 'dashboard' );
	if ( $type == 'id' ) {
		return $id;
	}
	return get_the_permalink( $id );
}
endif;

if( !function_exists( 'can_pay_with_credit' ) ) :
/**
 * enable shortlink edit
 *
 * @since 1.0
 */
function can_pay_with_credit() {
	return Helper::get_option( 'wc_affiliate_basic', 'pay_with_credit' ) == 'on';
}
endif;

if( !function_exists( 'wc_affiliate_allow_self_referral' ) ) :
/**
 * Allow self referral?
 *
 * @since 1.0
 */
function wc_affiliate_allow_self_referral() {
	return Helper::get_option( 'wc_affiliate_basic', 'allow_self_referral' ) == 'on';
}
endif;


if( !function_exists( 'wc_affiliate_admin_email' ) ) :
/**
 * wc_affiliate_admin_email
 *
 * @since 1.0
 * @return admin_email
 */
function wc_affiliate_admin_email() {
	return get_option( 'admin_email' );
}
endif;

if( !function_exists( 'wc_affiliate_insert_credit' ) ) :
/**
 * insert credit
 *
 * @since 2.0.0
 */
function wc_affiliate_insert_credit( $affiliate_id, $commission,  $type = 'sale', $visit = 0, $order_id = 0, $products = '', $order_total = 0, $payment_status = 'pending' ) {
    /**
     * insert referrals
    */
    global $wpdb;
    $wpdb->insert( $wpdb->prefix . 'wca_referrals',
         [
            'affiliate'         => $affiliate_id,
            'type'              => $type,
            'visit'             => $visit,
            'order_id'          => $order_id,
            'products'          => serialize( $products ),
            'order_total'       => $order_total,
            'commission'        => $commission,
            'payment_status'    => $payment_status,
            'transaction_id'    => 0,
            'time'              => current_time( 'timestamp' ),
        ],
        [
            '%d',
            '%s',
            '%d',
            '%d',
            '%s',
            '%d',
            '%f',
            '%s',
            '%d',
            '%d',
        ]
    );
}
endif;
/**
 * Determines if the pro version is installed
 *
 * @since 1.0
 */
if( !function_exists( 'wca_is_pro' ) ) :
function wca_is_pro() {
    if ( is_plugin_active( 'wc-affiliate-pro/wc-affiliate-pro.php' ) ) {
        return true;
    }
    else{
        return false;
    } 
}
endif;