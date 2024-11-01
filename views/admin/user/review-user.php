<?php 
	$user_id 			= (int)sanitize_text_field( $_GET['affiliate'] );
	$user 	 			= get_userdata( $user_id );

	if( ! $user ) return;

	$first_name 		= $user->first_name;
	$last_name 			= $user->last_name;
	$email 				= $user->user_email;
	$status 			= get_user_meta( $user_id, '_wc_affiliate_status', true );
	$website 			= get_user_meta( $user_id, '_wc_affiliate_website_url', true );
	$promotion_method 	= get_user_meta( $user_id, '_wc_affiliate_promotion_method', true );
	$time_applied 		= get_user_meta( $user_id, '_wc_affiliate_time_applied', true );
	$format 			= get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	$applied_time 		= date( $format, $time_applied );
?>

<form action="">	
	<input type="hidden" id="wf-affiliate-email" name="email" value="<?php echo esc_html( $email ); ?>">
	<table class="wc-affiliate-user-review-table">
		<tr>
			<td><?php _e( 'First Name:', 'wc-affiliate' ) ?></td>
			<td><?php echo esc_html( $first_name ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'Last Name:', 'wc-affiliate' ) ?></td>
			<td><?php echo esc_html( $last_name ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'E-mail:', 'wc-affiliate' ) ?></td>
			<td><?php echo esc_html( $email ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'Website:', 'wc-affiliate' ) ?></td>
			<td><?php echo esc_url( $website ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'Status:', 'wc-affiliate' ) ?></td>
			<td><?php echo esc_html( $status ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'Applied at:', 'wc-affiliate' ) ?></td>
			<td><?php echo esc_html( $applied_time ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'Promotion method:', 'wc-affiliate' ) ?></td>
			<td><?php echo esc_html( $promotion_method ); ?></td>
		</tr>	
		<tr>
			<td><?php _e( 'Message:', 'wc-affiliate' ) ?></td>
			<td>
				<textarea id="wf-affiliate-message" name="affiliate-message" cols="4"></textarea>
			</td>
		</tr>
		<tr class="wc-affiliate-user-review-actions">
			<td></td>
			<td>
				<div class="wf-user-review-btns">
					<button class="button button-primary wf-review-action" data-action="approve" data-id='<?php echo esc_html( $user_id ) ?>'><?php _e( 'Approve', 'wc-affiliate' ) ?></button>
					<button class="button button-danger wf-review-action" data-action="reject" data-id='<?php echo esc_html( $user_id ) ?>'><?php _e( 'Reject', 'wc-affiliate' ) ?></button>
				</div>	
			</td>
		</tr>
	</table>
</form>
