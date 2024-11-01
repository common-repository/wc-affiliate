<?php
use Codexpert\WC_Affiliate\Helper;
// authenticate
$_status = Helper::get_affiliate_status(); 
$status  = $_status != '' ? $_status : 'apply';

$is_mail_verified = Helper::is_mail_verified();

if( !is_user_logged_in() ) {
	$template = 'login';
}
elseif( $status == 'active' ){
	$template = 'panel';
}
elseif( !$is_mail_verified ){
	$template = 'email-varification';
}
else {
	$template = $status;
}

do_action( 'wc-affiliate-dashboard', $template );