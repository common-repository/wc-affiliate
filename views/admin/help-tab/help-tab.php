<?php

$args = [
	'wc_affiliate_faq' 		=> __( 'FAQ', 'wc-affiliate' ),
	// 'wc_affiliate_video' 	=> __( 'Video Tutorial', 'wc-affiliate' ),
	'wc_affiliate_support' 	=> __( 'Ask Support', 'wc-affiliate' ),
];
$tab_links = apply_filters( 'wc_affiliate_help_tab_link', $args );

echo "<div class='wc_affiliate_tab_btns'>";
echo "<ul class='wc_affiliate_help_tablinks'>";

$count 	= 0;
foreach ( $tab_links as $id => $tab_link ) {
	$active = $count == 0 ? 'active' : '';
	echo "<li class='wc_affiliate_help_tablink {$active}' id='" . esc_attr( $id ) . "'>" . esc_html( $tab_link ) . "</li>";
	$count++;
}

echo "</ul>";
echo "</div>";
?>

<div id="wc_affiliate_faq_content" class="wc_affiliate_tabcontent active">
	 <div class='wrap'>
	 	<div id='wc-affiliate-helps'>
	    <?php

	    $helps = get_option( 'wc-affiliate-docs-json', [] );
	    
		$utm = [ 'utm_source' => 'dashboard', 'utm_medium' => 'settings', 'utm_campaign' => 'faq' ];
	    if( is_array( $helps ) ) {
	    foreach ( $helps as $help ){
	    	$help_link = esc_url( add_query_arg( $utm, $help['link'] ) );
	        ?>
	        <div id='wc-affiliate-help-<?php echo esc_html( $help['id'] ); ?>' class='wc-affiliate-help'>
	            <h2 class='wc-affiliate-help-heading' data-target='#wc-affiliate-help-text-<?php echo esc_html( $help['id'] ); ?>'>
	                <a href='<?php echo $help_link; ?>' target='_blank'>
	                <span class='dashicons dashicons-admin-links'></span></a>
	                <span class="heading-text"><?php echo esc_html( $help['title']['rendered'] ); ?></span>
	            </h2>
	            <div id='wc-affiliate-help-text-<?php echo esc_attr( $help['id'] ); ?>' class='wc-affiliate-help-text' style='display:none'>
	                <?php echo wpautop( wp_trim_words( $help['content']['rendered'], 55, " <a class='sc-more' href='{$help_link}' target='_blank'>[more..]</a>" ) ); ?>
	            </div>
	        </div>
	        <?php
	    	}
	    }
	    else {
	        _e( 'Something is wrong! No help found!', 'wc-affiliate' );
	    }
	    ?>
	    </div>
	</div>
</div>

<div id="wc_affiliate_video_content" class="wc_affiliate_tabcontent">
	<iframe width="900" height="525" src="https://www.youtube.com/embed/videoseries?list=PLljE6A-xP4wKNreIV76Tl6uQUw-40XQsZ" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
</div>

<div id="wc_affiliate_support_content" class="wc_affiliate_tabcontent">
	<p><?php _e( 'Having an issue or got something to say? Feel free to reach out to us! Our award winning support team is always ready to help you.', 'shop-catalog' ); ?></p>
	<div id="support_btn_div">
		<a href="https://help.codexpert.io/?utm_campaign=help-tab" class="button" id="support_btn" target="_blank"><?php _e( 'Submit a Ticket', 'shop-catalog' ); ?></a>
	</div>
</div>