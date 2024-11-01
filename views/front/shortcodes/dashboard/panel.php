<?php 
use Codexpert\WC_Affiliate\Helper;
?>
<div id="wf-dashboard-panel" class="">
	<div class="wf-dashboard-tab">
		<?php 
		do_action( 'wc-affiliate-dashboard-profile-before' );
		do_action( 'wc-affiliate-dashboard-profile' );
		do_action( 'wc-affiliate-dashboard-profile-after' );

		do_action( 'wc-affiliate-dashboard-navigation-before' );
		do_action( 'wc-affiliate-dashboard-navigation' );
		do_action( 'wc-affiliate-dashboard-navigation-after' ); 
		?>
	</div>
	<div class="wf-dashboard-tab-content">
		<?php 
		$tab = isset( $_GET['tab'] ) && array_key_exists( sanitize_text_field( $_GET['tab'] ), Helper::get_tabs() ) ? sanitize_text_field( $_GET['tab'] ) : 'summary';
		
		do_action( 'wc-affiliate-dashboard-before-content', $tab );
		do_action( 'wc-affiliate-dashboard-content', $tab );
		do_action( 'wc-affiliate-dashboard-after-content', $tab ); 
		?>
	</div>
</div>