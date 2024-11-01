<?php 
use Codexpert\WC_Affiliate\Helper;

$features = [
	'Generate shortened links to hide the long affiliate links',
	'Create unlimited number of links',
	'Use customized strings',
	'Set convenient string length'
];

$title 			 = __( 'Short Link', 'wc-affiliate' );
$redirect_url 	 = 'https://codexpert.io/wc-affiliate/';
$placeholder_img = plugins_url( "/assets/img/Shortlinks.png", WCAFFILIATE );
$features_html 	 = '<ul>';
foreach ( $features as $key => $feature ) {
	$features_html .= "<li>{$feature}</li>";
} 
$features_html .= '</ul>';
$description = "<p class='wsp-features-description'>" . __( 'Allow your affiliates to share ortened URLs with their audiences. The pro version allows your affiliates to:', 'wc-affiliate' ) . "</p>
					{$features_html}";

echo Helper::pro_preview_html( $title , $description , $redirect_url , $placeholder_img );