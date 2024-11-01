<?php 
use Codexpert\WC_Affiliate\Helper;

$features = [
	'Bring in and connect multiple sites to the affiliate system.',
	'Manage affiliates from a single site',
	'Share affiliate links of other site'
];

$title 			 = __( 'Cross Domain', 'wc-affiliate' );
$redirect_url 	 = 'https://codexpert.io/wc-affiliate/';
$placeholder_img = plugins_url( "/assets/img/xDomain-Cookie.png", WCAFFILIATE );
$features_html 	 = '<ul>';
foreach ( $features as $key => $feature ) {
	$features_html .= "<li>{$feature}</li>";
} 
$features_html .= '</ul>';
$description = "<p class='wsp-features-description'>" . __( 'Share a site’s affiliate link and track referrals on another. It’s possible!', 'wc-affiliate' ) . "</p>
					{$features_html}";

echo Helper::pro_preview_html( $title , $description , $redirect_url , $placeholder_img );