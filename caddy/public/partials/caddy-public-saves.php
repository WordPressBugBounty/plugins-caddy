<?php
/**
 * Reactive Saved Items Template for Interactivity API
 *
 * This template uses WordPress Interactivity API to reactively display saved items
 * while preserving all original formatting and styling.
 *
 * @package Caddy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$cc_disable_branding = get_option( 'cc_disable_branding' ); // Get disable branding
$cc_disable_branding_class = ( 'disabled' === $cc_disable_branding ) ? ' cc-no-branding' : '';

// Get custom browse products URL or default to shop page
$custom_browse_url = get_option('cc_browse_products_url', '');
$browse_url = !empty($custom_browse_url) ? $custom_browse_url : get_permalink( wc_get_page_id( 'shop' ) );
$account_url = trailingslashit( wc_get_account_endpoint_url( '' ) );
?>

<div class="cc-sfl-container" data-wp-interactive="caddy/cart">

	<div class="cc-sfl-notice"></div>
	<div class="cc-body-container">
		<div class="cc-body<?php echo esc_attr( $cc_disable_branding_class ); ?>">

			<?php do_action( 'caddy_display_registration_message' ); ?>

			<!-- Saved items list (shown when items exist) -->
			<div class="cc-row cc-cart-items cc-text-center"
			     id="cc-saved-items-list">
				<template data-wp-each--item="state.savedItems">
					<div class="cc-cart-product-list"
					     data-wp-class--cc-saving="context.item.isMoving">
						<div class="cc-cart-product">
							<a data-wp-bind--href="context.item.permalink"
							   class="cc-product-link cc-product-thumb"
							   data-wp-bind--data-title="context.item.name">
<?php
								$thumbnail_size = wc_get_image_size( 'woocommerce_thumbnail' );
								?>
								<img data-wp-bind--src="context.item.thumbnailImage"
								     data-wp-bind--alt="context.item.name"
								     width="<?php echo esc_attr( $thumbnail_size['width'] ); ?>"
								     height="<?php echo esc_attr( $thumbnail_size['height'] ); ?>"
								     class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"
								     decoding="async"
								     loading="lazy">
							</a>
							<div class="cc_item_content">
								<div class="cc_item_title">
									<a data-wp-bind--href="context.item.permalink"
									   class="cc-product-link"
									   data-wp-bind--data-title="context.item.name"
									   data-wp-text="context.item.name"></a>
								</div>
								<div class="cc_item_total_price">
									<div class="price">
										<span class="cc-sale-price-wrapper"
										      data-wp-class--cc-hidden="!context.item.isOnSale">
											<del><span class="woocommerce-Price-amount amount" data-wp-text="context.item.regularPrice"></span></del>
										</span>
										<span class="woocommerce-Price-amount amount" data-wp-text="context.item.price"></span>
									</div>
									<div class="cc_saved_amount"
									     data-wp-class--cc-hidden="!context.item.isOnSale">
										(<?php echo esc_html__('Save', 'caddy'); ?> <span data-wp-text="context.item.savingsPercent"></span>%)
									</div>
								</div>
								<div class="cc_move_to_cart_btn">
									<!-- For simple products that can be added to cart directly -->
									<a href="javascript:void(0);"
									   class="button cc-button-sm cc_cart_from_sfl"
									   aria-label="<?php esc_attr_e( 'Move to cart', 'caddy' ); ?>"
									   data-wp-bind--data-product_id="context.item.productId"
									   data-wp-on--click="actions.moveToCart"
									   data-wp-class--cc-hidden="!context.item.canAddToCart">
										<?php esc_html_e( 'Move to cart', 'caddy' ); ?>
									</a>
									<!-- For variable/bundle products that need options -->
									<a data-wp-bind--href="context.item.permalink"
									   class="button cc-button-sm"
									   aria-label="<?php esc_attr_e( 'See options', 'caddy' ); ?>"
									   data-wp-class--cc-hidden="context.item.canAddToCart">
										<?php esc_html_e( 'See options', 'caddy' ); ?>
									</a>
									<div class="cc-loader" style="display: none;"></div>
								</div>
							</div>
							<a href="javascript:void(0);"
							   class="remove remove_from_sfl_button"
							   aria-label="<?php esc_attr_e( 'Remove this item', 'caddy' ); ?>"
							   data-wp-bind--data-product_id="context.item.productId"
							   data-wp-on--click="actions.removeSavedItem">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5">
									<path stroke="currentColor" d="M1 6H23"></path>
									<path stroke="currentColor" d="M4 6H20V22H4V6Z"></path>
									<path stroke="currentColor" d="M9 10V18"></path>
									<path stroke="currentColor" d="M15 10V18"></path>
									<path stroke="currentColor" d="M8 6V6C8 3.79086 9.79086 2 12 2V2C14.2091 2 16 3.79086 16 6V6"></path>
								</svg>
							</a>
						</div>
					</div>
				</template>
			</div>

			<!-- Empty state (shown when no items) -->
			<div class="cc-empty-msg cc-text-center cc-hidden"
			     id="cc-empty-saved-items">
				<img class="cc-empty-saves-img" src="<?php echo esc_url( plugin_dir_url( __DIR__ ) ); ?>img/saves-empty.svg" alt="Empty Saves">
				<span class="cc-title"><?php esc_html_e( 'You haven\'t saved any items yet!', 'caddy' ); ?></span>
				<?php if ( is_user_logged_in() ) { ?>
					<p><?php esc_html_e( 'You can save your shopping cart items for later here.', 'caddy' ); ?></p>
					<a href="<?php echo esc_url( $browse_url ); ?>" class="cc-button"><?php esc_html_e( 'Browse Products', 'caddy' ); ?></a>
				<?php } else { ?>
					<p><?php esc_html_e( 'You must be logged into an account in order to save items.', 'caddy' ); ?></p>
					<a href="<?php echo esc_url( $account_url ); ?>"
					   class="cc-button"><?php esc_html_e( 'Login or Register', 'caddy' ); ?></a>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php
	if ( 'disabled' !== $cc_disable_branding ) {
		$cc_affiliate_id = get_option( 'cc_affiliate_id' );
		$powered_by_link = ! empty( $cc_affiliate_id ) ? 'https://www.usecaddy.com?ref=' . esc_attr( $cc_affiliate_id ) : 'https://www.usecaddy.com';
		?>
		<div class="cc-poweredby cc-text-center">
			<?php

			// SVG code
			$powered_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20.44,9.27A.48.48,0,0,0,20,9H12.62L14.49.61A.51.51,0,0,0,14.2,0a.5.5,0,0,0-.61.17l-10,14a.49.49,0,0,0,0,.52A.49.49,0,0,0,4,15h7.38L9.51,23.39A.51.51,0,0,0,9.8,24a.52.52,0,0,0,.61-.17l10-14A.49.49,0,0,0,20.44,9.27Z" fill="currentColor"></path></svg>';

			echo sprintf(
				'%1$s %2$s %3$s <a href="%4$s" rel="noopener noreferrer" target="_blank">%5$s</a>',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is hardcoded safe HTML
				$powered_svg,
				esc_html__( 'Powered', 'caddy' ),
				esc_html__( 'by', 'caddy' ),
				esc_url( $powered_by_link ),
				esc_html__( 'Caddy', 'caddy' )
			);
			?>
		</div>
	<?php } ?>
</div>