<p class="wf-notice success">
	<?php 
	$dashboard = Codexpert\WC_Affiliate\Helper::get_option( 'wc_affiliate_basic', 'dashboard' );
	printf( __( 'You\'re already our Affiliate.', 'wc-affiliate' ) . __( ' Go to ', 'wc-affiliate' ) . '<a href="%s">'. __( 'Dashboard', 'wc-affiliate' ) .'</a>', get_permalink( $dashboard ) ); ?>
</p>