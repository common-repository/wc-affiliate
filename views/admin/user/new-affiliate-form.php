<?php 
global $wpdb;
$meta_table = "{$wpdb->prefix}usermeta";

if( is_multisite() ) {
    $blog_id 		= get_current_blog_id();
    $meta_table 	= "{$wpdb->base_prefix}{$blog_id}_usermeta";
}

$users = get_users();

$password = wp_generate_password();
?>
<div class="wf-wrap">
	<form method="post" id="wf-register-affiliate-form">
		<?php wp_nonce_field( 'wc-affiliate' ) ?>
		<input type="hidden" name="action" value="wf-register-affiliate">
		<div id="wf-register-user-type">
			<button class="wf-register-user-type-btn button button-primary wf-button" data-type="existing"><?php _e( 'Existing User', 'wc-affiliate' ) ?></button>
			<button class="wf-register-user-type-btn button wf-button new-user" data-type="new"><?php _e( 'New User', 'wc-affiliate' ) ?></button>
		</div>
		<table class="form-table">
		    <tr id="_wc_affiliate_users-wrap">
		        <th><label for="wf_user" ><?php _e( 'Select User', 'wc-affiliate' ); ?></label></th>
		        <td>
		           <select name="affiliate" class="wc-affiliate-chosen">
		           	<?php 
		           	foreach ( $users as $key => $data ) {
		           		$user = $data->data;
		           		echo '<option value="'. $user->ID .'">'. $user->display_name .'</option>';
		           	}
		           	?>
		           </select>
		        </td>
		    </tr>
		    <tr id="_wc_affiliate_fname-wrap">
		        <th><label for="first_name" ><?php _e( 'First Name', 'wc-affiliate' ); ?></label></th>
		        <td>
		            <input id="first_name" type="text" name="first_name" disabled="" required="">
		        </td>
		    </tr>
		    <tr id="_wc_affiliate_lname-wrap">
		        <th><label for="last_name" ><?php _e( 'Last Name', 'wc-affiliate' ); ?></label></th>
		        <td>
		            <input id="last_name" type="text" name="last_name" disabled="" required="">
		        </td>
		    </tr>
		    <tr id="_wc_affiliate_email-wrap">
		        <th><label for="email" ><?php _e( 'Email', 'wc-affiliate' ); ?></label></th>
		        <td>
		            <input id="email" type="email" name="email" disabled="" required="">
		        </td>
		    </tr>
		    <tr id="_wc_affiliate_password-wrap">
		        <th><label for="password" ><?php _e( 'Password', 'wc-affiliate' ); ?></label></th>
		        <td>
		            <input id="password" type="password" name="password" value="<?php echo $password; ?>" disabled="" required="">
		            <button class="button" id="wf-view-pass"><span class="dashicons dashicons-visibility"></span></button>
		        </td>
		    </tr>
		    <tr id="_wc_affiliate_status-wrap">
		        <th><label for="_wc_affiliate_status"><?php _e( 'Affiliate Status', 'wc-affiliate' ); ?></label></th>
		        <td>
		            <select name="status" id="_wc_affiliate_status" class="regular-text">
		                <option value=""><?php _e( 'Affiliate Status', 'wc-affiliate' ); ?></option>
		                <option value="active" ><?php _e( 'Active', 'wc-affiliate' ); ?></option>
		                <option value="pending" ><?php _e( 'Pending', 'wc-affiliate' ); ?></option>
		                <option value="rejected" ><?php _e( 'Rejected', 'wc-affiliate' ); ?></option>
		            </select>
		        </td>
		    </tr>
		    <tr id="commission_type-wrap">
		        <th><label for="commission_type"><?php _e( 'Commission Type', 'wc-affiliate' ); ?></label></th>
		        <td>
		            <select name="commission_type" id="commission_type" class="regular-text">
		                <option value="default" ><?php _e( 'Site Default', 'wc-affiliate' ); ?></option>
		                <option value="fixed" ><?php _e( 'Fixed', 'wc-affiliate' ); ?></option>
		                <option value="percent" ><?php _e( 'Percent', 'wc-affiliate' ); ?></option>
		            </select>
		        </td>
		    </tr>
		    <tr id="commission_amount-wrap">
		        <th><label for="commission_amount"><?php _e( 'Commission Amount', 'wc-affiliate' ); ?></label></th>
		        <td>
		            <input type="number" id="commission_amount" name="commission_amount" class="regular-text" value="">
		        </td>
		    </tr>
		    <tr id="commission_submit-wrap">
		        <th></th>
		        <td>
		            <button type="submit" id="wf-register-user-btn" class="button wf-button button-primary"><?php _e( 'Register', 'wc-affiliate' ) ?></button>
		        </td>
		    </tr>
		</table>
	</form>
</div>