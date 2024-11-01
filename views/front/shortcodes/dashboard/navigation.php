<?php 
use Codexpert\WC_Affiliate\Helper;
$current_tab	= isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'summary';
$permalink 		= Helper::get_current_uri(); //get_permalink();
?>
<div class="wf-dashboard-navigation">
	<ul>
		<?php

		foreach ( Helper::get_tabs() as $key => $tab ) {
			$active = $key == $current_tab ? 'active' : '';
			$url 	= isset( $tab['url'] ) ? $tab['url'] : add_query_arg( 'tab', $key, $permalink );
			echo "<li class='{$active}'><a href='" . esc_url( $url ) . "'>{$tab['icon']} {$tab['label']}</a></li>";
		}
		?>
	</ul>
</div>