<?php 
use Codexpert\WC_Affiliate\Helper;

$shortlinks = [
	[
		'mainurl' => 'https://codexpert.io/wc-affiliate/category/subcategory/product',
		'shorturl' => 'https://codexpert.io/wc-affiliate/go/qwerty',
		'time' 	=> date( 'M d, Y' )
	],
	[
		'mainurl' => 'https://codexpert.io/wc-affiliate/category/subcategory/product2',
		'shorturl' => 'https://codexpert.io/wc-affiliate/go/uiopas',
		'time' 	=> date( 'M d, Y' )
	],
];

$table_rows = '';
foreach( $shortlinks as $shortlink ){

	$table_rows .= "
		<tr>
			<td>
				" . esc_url( $shortlink['mainurl'] ) . "
			</td>
			<td>
				" . esc_url( $shortlink['shorturl'] ) . " <a href='?tab=banners'>" . __( 'Generate Banner', 'wc-affiliate' ) . "</a>
			</td>
			<td>" . esc_html( $shortlink['time'] ) . "</td>
			<td class='wf-delete-shortlink'><span data-id='#' title='" . __( 'Delete', 'wc-affiliate' ) . "'>&times;</span></td>
		</tr>";
}
?>
<style type="text/css">
	.wc-affiliate-pro-notice-title{
		text-align: center;
		font-family: 'DM Sans', sans-serif;
		color: #f48d02;
	}
		/*background: #f48d02;*/
	.wc-affiliate-pro-notice{
		text-align: center;
	}
	
	.wf-shortlinks-list-demo,
	.wf-generate-shortlink-demo {
		background: #ffd59c;
		padding: 17px;
		border-radius: 8px;
	}
</style>
<div class="wf-shortlinks-list-demo">
	<?php echo Helper::pro_notice(); ?>
	<div id="wf-shortlinks-list" class="wf-shortlinks-list">
		<table class='wf-shortlinks-table'>
			<thead>
				<tr>
					<th><?php _e( 'Main url', 'wc-affiliate' ) ?></th>
					<th><?php _e( 'Shortlink', 'wc-affiliate' ) ?></th>
					<th><?php _e( 'Created at', 'wc-affiliate' ) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody><?php echo $table_rows; ?></tbody>
		</table>
	</div>
</div>