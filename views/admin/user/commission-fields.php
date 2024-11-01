<?php
use Codexpert\WC_Affiliate\Helper;

$user = isset( $args['user'] ) ? $args['user'] : false;

$_wc_affiliate_status	= get_user_meta( $user->ID, '_wc_affiliate_status', true );
$commission_type		= get_user_meta( $user->ID, 'commission_type', true );
$commission_amount		= get_user_meta( $user->ID, 'commission_amount', true );
?>

<h3 id="wf-title"><?php esc_html_e( 'Affiliate', 'wc-affiliate' ); ?></h3>
<table class="form-table">
    <tr id="_wc_affiliate_status-wrap">
        <th><label for="_wc_affiliate_status"><?php esc_html_e( 'Affiliate Status', 'wc-affiliate' ); ?></label></th>
        <td>
            <select name="_wc_affiliate_status" id="_wc_affiliate_status" class="regular-text">
                <option value=""><?php esc_html_e( 'Affiliate Status', 'wc-affiliate' ); ?></option>
                <option value="active" <?php selected( 'active', $_wc_affiliate_status ); ?>><?php esc_html_e( 'Active', 'wc-affiliate' ); ?></option>
                <option value="pending" <?php selected( 'pending', $_wc_affiliate_status ); ?>><?php esc_html_e( 'Pending', 'wc-affiliate' ); ?></option>
                <option value="rejected" <?php selected( 'rejected', $_wc_affiliate_status ); ?>><?php esc_html_e( 'Rejected', 'wc-affiliate' ); ?></option>
            </select>
        </td>
    </tr>
    <tr id="commission_type-wrap">
        <th><label for="commission_type"><?php _e( 'Commission Type', 'wc-affiliate' ); ?></label></th>
        <td>
            <select name="commission_type" id="commission_type" class="regular-text">
                <option value="default" <?php selected( 'default', $commission_type ); ?>><?php _e( 'Site Default', 'wc-affiliate' ); ?></option>
                <option value="fixed" <?php selected( 'fixed', $commission_type ); ?>><?php _e( 'Fixed', 'wc-affiliate' ); ?></option>
                <option value="percent" <?php selected( 'percent', $commission_type ); ?>><?php _e( 'Percent', 'wc-affiliate' ); ?></option>
            </select>
        </td>
    </tr>
    <tr id="commission_amount-wrap">
        <th><label for="commission_amount"><?php _e( 'Amount', 'wc-affiliate' ); ?></label></th>
        <td>
            <input type="number" id="commission_amount" name="commission_amount" class="regular-text" value="<?php echo esc_attr( $commission_amount ); ?>">
        </td>
    </tr>
</table>