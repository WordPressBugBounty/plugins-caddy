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
 
?>
<form name="caddy-form" id="caddy-offers-form" method="post" action="">
	<?php wp_nonce_field('caddy-offers-settings-save', 'caddy_offers_settings_nonce'); ?>
	<input type="hidden" name="cc_submit_hidden" value="Y">
	<div class="cc-settings-container">
		<h2><span class="section-icons"><img src="<?php echo plugin_dir_url( CADDY_PLUGIN_FILE ) . 'admin/img/icon-offers.svg'; ?>" /></span>&nbsp;<?php echo esc_html( __( 'Offers', 'caddy' ) ); ?></h2>
		<p><?php echo esc_html( __( 'Configuration of coupons within the "offers" tab of Caddy that shoppers can apply to their cart.', 'caddy' ) ); ?></p>
		<?php if ( $caddy_license_status !== 'valid' ) { ?>
		<div class="cc-box cc-box-cta cc-upgrade">
			<img src="<?php echo plugin_dir_url( CADDY_PLUGIN_FILE ) . 'admin/img/icon-lock.svg'; ?>" />
			<h3><?php echo esc_html( __( 'Unlock offers with Caddy Pro', 'caddy' ) ); ?></h3>
			<p><?php echo esc_html( __( 'Display targeted in-cart offers to shoppers. Plus:', 'caddy' ) ); ?></p>
			<ul>
				<li><span class="dashicons dashicons-saved"></span><?php echo esc_html( __( 'Analytics dashboard.', 'caddy' ) ); ?></li>
				<li><span class="dashicons dashicons-saved"></span><?php echo esc_html( __( 'Cart & conversion tracking.', 'caddy' ) ); ?></li>
				<li><span class="dashicons dashicons-saved"></span><?php echo esc_html( __( 'Custom recommendation logic.', 'caddy' ) ); ?></li>
				<li><span class="dashicons dashicons-saved"></span><?php echo esc_html( __( 'Targeted workflows.', 'caddy' ) ); ?></li>
				<li><span class="dashicons dashicons-saved"></span><?php echo esc_html( __( 'Total design control.', 'caddy' ) ); ?></li>
				<li><span class="dashicons dashicons-saved"></span><?php echo esc_html( __( 'Bubble positioning options.', 'caddy' ) ); ?></li>
				<li><span class="dashicons dashicons-saved"></span><?php echo esc_html( __( 'Cart notices, add-ons & more.', 'caddy' ) ); ?></li>
			</ul>
			<p><strong><?php echo esc_html( __( 'Use promo code "PREMIUM20" to get 20% off for a limited time.', 'caddy' ) ); ?></strong></p>
			<?php
			echo sprintf(
				'<a href="%1$s" target="_blank" class="button-primary">%2$s</a>',
				esc_url( 'https://usecaddy.com/?utm_source=upgrade-notice&amp;utm_medium=plugin&amp;utm_campaign=plugin-links' ),
				esc_html( __( 'Get Caddy Pro', 'caddy' ) )
			); ?>
		</div>
		<?php } ?>
		<?php if ( $caddy_license_status == 'valid' ) { ?>
			<table class="form-table">
				<tbody>
					<?php do_action( 'caddy_offers_settings_start' ); ?>
					<?php do_action( 'caddy_offers_settings_end' ); ?>
				</tbody>
			</table>
		<?php } ?>
	</div>
	<?php if ( $caddy_license_status == 'valid' ) { ?>
		<p class="submit cc-primary-save">
			<input type="submit" name="Submit" class="button-primary cc-primary-save-btn" value="<?php echo esc_attr__( 'Save Changes' ); ?>" />
		</p>
	<?php } ?>
</form>
<?php 