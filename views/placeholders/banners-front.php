<?php
use Codexpert\WC_Affiliate\Helper;

$placeholder = plugins_url( "/assets/img/banner.png", WCAFFILIATE );	
$ref_key	= wc_affiliate_get_ref_key();
$token		= Helper::get_token();
$affiliate_url 	= add_query_arg( $ref_key, $token, home_url() );
$dummy_banners = [
	[
		'title' => __( 'Dummy 1', 'wc-affiliate' ),
		'desc' => "Banner Description goes here",
		'img_url' => plugins_url( "/assets/img/logo.png", WCAFFILIATE ),
		'size' => '623x564px',
		'code' => "<a href='https://codexpert.io/wc-affiliate'><img src='" . plugins_url( "/assets/img/logo.png", WCAFFILIATE ) . "'>",
	],
	[
		'title' => __( 'Dummy 2', 'wc-affiliate' ),
		'desc' => "Banner Description goes here",
		'img_url' => plugins_url( "/assets/img/logo.png", WCAFFILIATE ),
		'size' => '623x564px',
		'code' => "<a href='https://codexpert.io/wc-affiliate'><img src='" . plugins_url( "/assets/img/logo.png", WCAFFILIATE ) . "'>",
	],
];

echo Helper::pro_notice();
?>
<div class="wf-dashboard-panel-head wf-dashboard-urlg-header">
	<div class="wf-dashboard-panel-head-title">
		<h3><?php _e( 'Banners', 'wc-affiliate' ) ?></h3>
	</div>
</div>

<div class="wf-banner-panel">	
	<div class="wf-url-inputs">
		<input type="text" name="wf_url" id="wf-banner-url" value="<?php echo esc_url( $affiliate_url ); ?>">
		<button class="button wf-button" id="wf-banner-url-generator"> <?php _e( 'Change Url', 'wc-affiliate' ) ?> </button>
	</div>
	<div class="wf-banner-content">
		<table>
			<thead>
				<tr>
					<th><?php _e( 'Banner Name', 'wc-affiliate' ); ?></th>
					<th><?php _e( 'Banner Image', 'wc-affiliate' ); ?></th>
					<th><?php _e( 'Size', 'wc-affiliate' ); ?></th>
					<th><?php _e( 'Code', 'wc-affiliate' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php 
			$args	= array(
				'post_type'			=>	'banner',
				'posts_per_page'	=>	-1,
			);
			foreach ( $dummy_banners as $key => $banner ):
			?>
					
				<tr>
					<td class="wf-banner-name">
							<h4 class="wf-banner-title"><?php echo esc_html( $banner['title'] ); ?></h4>
							<p class="wf-banner-desc"><?php echo esc_html( $banner['desc'] ); ?></p>
					</td>
					<td>
						<div class="wf-banner-ref-img">
							<a href="<?php echo esc_url( $banner['img_url'] ); ?>" class="wf-fancybox"><img src="<?php echo esc_url( $banner['img_url'] ); ?>"></a>
						</div>
					</td>
					<td class="wf-banner-dimension">
						<p><?php echo esc_html( $banner['size'] ); ?></p>
					</td>
					<td>
						<div class="wf-banner-code-area">
							<textarea class="wf-copy-banner-content" readonly><?php echo esc_html( $banner['code'] ); ?></textarea>
							<button class="wf-copy-banner-btn"><i class="far fa-copy"></i></button>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>