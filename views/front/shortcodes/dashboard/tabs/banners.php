<?php 
use Codexpert\WC_Affiliate\Helper;
$has_pro = Helper::has_pro();
if ( current_user_can('manage_options') && !$has_pro ) {
	echo Helper::get_template( 'banners-front', 'views/placeholders' );
}
do_action( 'wc-affiliate-dashboard_banner_tab_content' );
?>