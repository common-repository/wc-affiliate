<div class="wf-dashboard-profile">
	<div class="wf-dashboard-profile-image">
		<img src="<?php echo esc_url( get_avatar_url( get_current_user_id() ) ); ?>" >
	</div>
	<div class="wf-dashboard-profile-name">
		<h4><?php echo esc_html( wp_get_current_user()->display_name ); ?></h4>
	</div>
	<div class="wf-dashboard-profile-desc">
		<p>
			<?php
			// Translators: %d is the affiliate's user ID.
			printf( __( 'Affiliate ID #%d', 'wc-affiliate' ), get_current_user_id() );
			?>
		</p>
	</div>
</div>