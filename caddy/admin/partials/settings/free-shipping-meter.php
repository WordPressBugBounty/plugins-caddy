<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin style screen of the plugin.
 *
 * @link       https://www.madebytribe.com
 * @since      1.0.0
 *
 * @package    Caddy
 * @subpackage Caddy/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial; these variables are scoped to the including method, not the global namespace.

$cc_free_shipping_amount = get_option( 'cc_free_shipping_amount' );
 
$cc_shipping_country = (string) get_option( 'cc_shipping_country', '' );

$cc_free_shipping_tax = get_option( 'cc_free_shipping_tax' );
$cc_free_shipping_tax = ( 'enabled' == $cc_free_shipping_tax ) ? 'checked' : '';

?>
<form name="caddy-form" id="caddy-shipping-meter-form" method="post" action="">
	<?php wp_nonce_field('caddy-shipping-meter-settings-save', 'caddy_shipping_meter_settings_nonce'); ?>
	<input type="hidden" name="cc_submit_hidden" value="Y">
	<div class="cc-settings-container">
		<h2><span class="section-icons"><img src="<?php echo esc_url( plugin_dir_url( CADDY_PLUGIN_FILE ) . 'admin/img/icon-free-shipping.svg' ); ?>" /></span>&nbsp;<?php echo esc_html( __( 'Free Shipping Meter', 'caddy' ) ); ?></h2>
		<p><?php echo esc_html( __( 'Displays a meter in Caddy that shows the total amount required for free shipping.', 'caddy' ) ); ?>
			<strong><?php echo esc_html( __( 'This requires a free shipping method configured within your WooCommerce', 'caddy' ) ); ?> <a href="<?php echo esc_url( get_site_url() ); ?>/wp-admin/admin.php?page=wc-settings&tab=shipping"><?php echo esc_html( __( 'shipping settings', 'caddy' ) ); ?></a></strong>.
		</p>
		<table class="form-table">
			<tbody>
			<?php do_action( 'caddy_free_shipping_settings_start' ); ?>
			<tr>
				<th scope="row">
					<label for="cc_free_shipping_amount"><?php echo esc_html( __( 'Trigger amount', 'caddy' ) ); ?></label>
				</th>
				<td>
					<input type="number" name="cc_free_shipping_amount" id="cc_free_shipping_amount" value="<?php echo esc_attr( $cc_free_shipping_amount ); ?>" />
					<p class="description"><?php echo esc_html( __( 'Set an amount to enable the free shipping meter.', 'caddy' ) ); ?>
						<strong><?php echo esc_html( __( 'This amount must match the "Minimum order amount" configured within your WooCommerce', 'caddy' ) ); ?>
							<a href="<?php echo esc_url( get_site_url() ); ?>/wp-admin/admin.php?page=wc-settings&tab=shipping"><?php echo esc_html( __( 'shipping settings.', 'caddy' ) ); ?></a>
							<?php echo esc_html( __( 'Leave blank to disable.', 'caddy' ) ); ?>
						</strong>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cc_free_shipping_tax"><?php echo esc_html( __( 'Include taxes in the shipping calculation', 'caddy' ) ); ?></label>
				</th>
				<td>
					<div class="cc-toggle cc-toggle--size-small">
						<input type="checkbox" name="cc_free_shipping_tax" id="cc_free_shipping_tax" value="enabled" <?php echo esc_attr( $cc_free_shipping_tax ); ?>>
						<label for="cc_free_shipping_tax">
							<div class="cc-toggle__switch" data-checked="On" data-unchecked="Off"></div>
						</label>
					</div>
				</td>
			</tr>		
			
			<tr>
				<th scope="row">
					<label for="cc_shipping_country"><?php echo esc_html( __( 'Free shipping country', 'caddy' ) ); ?></label>
				</th>
				<td>
					<input type="text" name="cc_shipping_country" id="cc_shipping_country" class="regular-text" value="<?php echo esc_attr( $cc_shipping_country ); ?>" placeholder="<?php echo esc_attr( WC()->countries->get_base_country() ); ?>" />
					<p class="description">
						<?php echo esc_html( __( 'Added to the free shipping message, as in "free US shipping". Enter anything you like, such as "US & Canada". Leave blank to leave it out of the message.', 'caddy' ) ); ?>
					</p>
				</td>
			</tr>
			<?php do_action( 'caddy_free_shipping_settings_end' ); ?>
			</tbody>
		</table>

		<?php
			if ( ! isset( $caddy_license_status ) || 'valid' !== $caddy_license_status ) {
			?>
			<div class="cc-unlock-msg">
				<div><span class="dashicons dashicons-unlock"></span><?php echo esc_html( __( 'Unlock free shipping meter exclusions & more with ', 'caddy' ) ); ?><a href="<?php echo esc_url( 'https://www.usecaddy.com' ); ?>" target="_blank"><?php echo esc_html( __( 'Caddy Pro', 'caddy' ) ); ?></a></div>
			</div>
			<?php
		}?>
	</div>
	<p class="submit cc-primary-save">
		<input type="submit" name="Submit" class="button-primary cc-primary-save-btn" value="<?php echo esc_attr__( 'Save Changes', 'caddy' ); ?>" />
	</p>
</form>