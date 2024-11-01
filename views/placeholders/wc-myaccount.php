<?php
$features = [
	'Feature 1',
	'Feature 2',
	'Feature 3',
	'Feature 4',
	'Feature 5',
];
?>

<div class="woffiliate-section-preview">
	<div class="wsp-lock-icon">
		<span class="dashicons dashicons-lock"></span>
	</div>
	<div class="wsp-left">
		<img class="wsp-preview-img" src="<?php echo esc_url( plugins_url( "/assets/img/shortlink-list.png", WCAFFILIATE ) ); ?>">
	</div>
	<div class="wsp-right">
		<h2 class="wsp-title"><?php _e( 'My Account', 'wc-affiliate' ) ?></h2>
		<div class="wsp-feature-section">
			<h2 class="wsp-feature-title"><?php _e( 'Features', 'wc-affiliate' ) ?></h2>
			<div class="wsp-features">
				<ul>
					<?php foreach ( $features as $key => $feature ) {
						echo "<li>{$feature}</li>";
					} ?>
				</ul>
			</div>
		</div>
		<div class="wsp-footer">
			<a class="wsp-button button" href="https://codexpert.io/wc-affiliate"><?php _e( 'Learn More', 'wc-affiliate' ) ?></a>
		</div>
	</div>
</div>