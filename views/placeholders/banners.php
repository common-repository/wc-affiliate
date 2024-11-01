<?php 
use Codexpert\WC_Affiliate\Helper;

$features = [
	'Highly customizable banners with links',
	'As many banners as you want',
	'Integrated with shortlinks',
	'Auto generated HTML code to share',
	'Can be pasted on any sites, even on non-WP sites'
];

$title 			 = __( 'Banners', 'wc-affiliate' );
$redirect_url 	 = 'https://codexpert.io/wc-affiliate/';
$placeholder_img = plugins_url( "/assets/img/Banners.png", WCAFFILIATE );
$features_html 	 = '<ul>';
foreach ( $features as $key => $feature ) {
	$features_html .= "<li>{$feature}</li>";
} 
$features_html .= '</ul>';
$description = "<p class='wsp-features-description'>" . __( 'Let your affiliates promote the banners you create on different occasions.', 'wc-affiliate' ) . "</p>
					{$features_html}";

$content = Helper::pro_preview_html( $title , $description , $redirect_url , $placeholder_img );
?>
<div class="wl-banner-builder-preview">
	<div class='wf-banner-preview-header woffiliate-section-preview'>
		<span class="dashicons dashicons-cover-image"></span> <span class="wf-heading-text"><?php _e( 'Banners', 'wc-affiliate' ) ?></span>
		<a href='https://codexpert.io/wc-affiliate/pricing/'  class='button button-primary wf-feature-unlock-btn'><span class='dashicons dashicons-lock'></span> <?php _e( 'Unlock Feature', 'wc-affiliate' ) ?></a>
	</div>
	<?php echo $content; ?>
</div>