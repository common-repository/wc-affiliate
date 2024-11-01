<?php 
use Codexpert\WC_Affiliate\Helper;

$features = [
	'Set as many levels as you want',
	'Different amount/percentage per level',
	'Different commission types (e.g. fixed or percentage)'
]; 

$title 			 = __( 'Multilevel Commission', 'wc-affiliate' );
$redirect_url 	 = 'https://codexpert.io/wc-affiliate/';
$placeholder_img = plugins_url( "/assets/img/Multilevel-commission.png", WCAFFILIATE );
$features_html   = '<ul>';
foreach ( $features as $key => $feature ) {
	$features_html .= "<li>{$feature}</li>";
} 
$features_html .= '</ul>';
$description = "<p class='wsp-features-description'>" . __( 'Gain exponential business growth from the army of affiliates you have!', 'wc-affiliate' ) . "</p>
					{$features_html}";

echo Helper::pro_preview_html( $title , $description , $redirect_url , $placeholder_img );
?>