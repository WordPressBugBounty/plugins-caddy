<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! is_object( WC()->cart ) ) {
	return;
}

// Determine if the cart is empty and set a class accordingly
$cc_empty_class = WC()->cart->is_empty() ? ' cc-empty' : '';

// Get the free shipping amount setting first
$cc_free_shipping_amount = get_option('cc_free_shipping_amount');

// Calculate cart total for free shipping
$cart_total = 0;

// Get cart subtotal excluding virtual products
foreach (WC()->cart->get_cart() as $cart_item) {
    $product = $cart_item['data'];

    // Skip virtual products in the free shipping calculation
    if ($product->is_virtual()) {
        continue;
    }

    // Tax-aware price calculation using WC display settings
    $cart_total += floatval( wc_get_price_to_display( $product ) ) * $cart_item['quantity'];
}

// Calculate the remaining amount for free shipping
$free_shipping_remaining_amount = floatval($cc_free_shipping_amount) - floatval($cart_total);
$free_shipping_remaining_amount = !empty($free_shipping_remaining_amount) ? $free_shipping_remaining_amount : 0;

// Calculate the width of the free shipping bar as a percentage
$cc_bar_amount = 100;
if (!empty($cc_free_shipping_amount) && $cart_total <= $cc_free_shipping_amount) {
    $cc_bar_amount = ($cart_total * 100 / $cc_free_shipping_amount);
}

// Get the WooCommerce currency symbol
$wc_currency_symbol = get_woocommerce_currency_symbol();

// Get the total count of items in the cart
$total_cart_item_count = is_object(WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;

// Flag to determine if free shipping bar is enabled
$cc_free_shipping_bar = true;

// Retrieve the current user's ID and their saved for later items
$current_user_id = get_current_user_id();
$cc_sfl_items_array = get_user_meta($current_user_id, 'cc_save_for_later_items', true);
if (!is_array($cc_sfl_items_array)) {
	$cc_sfl_items_array = array();
}
$cc_sfl_items = array_reverse(array_unique($cc_sfl_items_array));

// Get the shipping country and branding options
$cc_shipping_country = get_option('cc_shipping_country');
$cc_disable_branding = get_option('cc_disable_branding'); // Get disable branding option
$cc_disable_branding_class = ('disabled' === $cc_disable_branding) ? ' cc-no-branding' : '';

// Retrieve the currency symbol and cart items
$currency_symbol = get_woocommerce_currency_symbol();
$cart_items = WC()->cart->get_cart();
$cart_items_data = array_reverse($cart_items);

// Find the first product ID in the cart
$first_product_id = 0;
$first_cart_item = array_slice($cart_items_data, 0, 1, true);
if (!empty($first_cart_item)) {
	foreach ($first_cart_item as $first_product) {
		$first_product_id = $first_product['product_id'];
	}
}

// Determine if free shipping bar should be active
$cc_bar_active = ($cart_total >= $cc_free_shipping_amount) ? ' cc-bar-active' : '';
$cc_fs_active_class = (!empty($cc_free_shipping_amount) && $cc_free_shipping_bar) ? ' cc-fs-active' : '';

?>

<div class="cc-cart-container" data-wp-interactive="caddy/cart" data-wp-init="callbacks.init">

	<?php do_action( 'caddy_before_cart_screen_data' ); ?>

	<div class="cc-notice"><i class="ccicon-close"></i></div>
	
	<?php if ( ! empty( $cc_free_shipping_amount ) && $cc_free_shipping_bar ) { ?>
		<div class="cc-fs cc-text-left" data-free-shipping-amount="<?php echo esc_attr( $cc_free_shipping_amount ); ?>" data-shipping-country="<?php echo esc_attr( $cc_shipping_country ); ?>" data-wp-interactive="caddy/cart" data-wp-watch="callbacks.updateFreeShippingMeter">
			<?php if (class_exists('Caddy_Block') && (has_block('caddy/cart') || Caddy_Block::should_auto_insert())) { ?>
				<!-- Dynamic free shipping bar for Interactivity API -->
				<span class="cc-fs-title">
					<span class="cc-fs-icon">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path d="M22.87,7.1A.24.24,0,0,0,23,6.86a.23.23,0,0,0-.15-.21L16,3.92a1.13,1.13,0,0,0-.9,0L13,4.94a.24.24,0,0,0-.14.23.24.24,0,0,0,.15.22l6.94,3.07a.52.52,0,0,0,.44,0Z" fill="currentColor"></path><path d="M16.61,19.85a.27.27,0,0,0,.12.22.26.26,0,0,0,.24,0l6.36-3.18a1.12,1.12,0,0,0,.62-1V8.06a.26.26,0,0,0-.13-.22.25.25,0,0,0-.24,0L16.74,11.5a.26.26,0,0,0-.13.22Z" fill="currentColor"></path><path d="M7.52,8.31a.24.24,0,0,0-.23,0,.23.23,0,0,0-.11.2c0,.56,0,2.22,0,7.41a1.11,1.11,0,0,0,.68,1l7.42,3.16a.21.21,0,0,0,.23,0,.24.24,0,0,0,.12-.21V11.78a.26.26,0,0,0-.16-.23Z" fill="currentColor"></path><path d="M15.87,10.65a.54.54,0,0,0,.43,0l2.3-1.23a.26.26,0,0,0,.13-.23.24.24,0,0,0-.15-.22L11.5,5.82a.48.48,0,0,0-.42,0L8.31,7.12a.24.24,0,0,0-.14.23.23.23,0,0,0,.15.22Z" fill="currentColor"></path><path d="M5,13.76,1.07,11.94a.72.72,0,0,0-1,.37.78.78,0,0,0,.39,1l3.9,1.8a.87.87,0,0,0,.31.07.73.73,0,0,0,.67-.43A.75.75,0,0,0,5,13.76Z" fill="currentColor"></path><path d="M5,10.31,2.68,9.23a.74.74,0,0,0-1,.36.75.75,0,0,0,.36,1L4.4,11.65a.7.7,0,0,0,.31.07A.74.74,0,0,0,5,10.31Z" fill="currentColor"></path><path d="M5,6.86,3.91,6.35a.73.73,0,0,0-1,.36.74.74,0,0,0,.36,1L4.4,8.2a.7.7,0,0,0,.31.07A.74.74,0,0,0,5,6.86Z" fill="currentColor"></path></g></svg>
					</span>
					<span data-wp-class--cc-hidden="state.freeShippingAchieved"<?php echo ($cart_total >= $cc_free_shipping_amount) ? ' class="cc-hidden"' : ''; ?>>
						<?php
						printf(
							/* translators: 1: Amount remaining, 2: Country name */
							esc_html__('Spend %1$s more to get free %2$s shipping', 'caddy'),
							'<strong><span class="cc-fs-amount">' . wc_price($free_shipping_remaining_amount) . '</span></strong>',
							'<strong><span class="cc-fs-country">' . esc_attr($cc_shipping_country) . '</span></strong>'
						);
						?>
					</span>
					<span data-wp-class--cc-hidden="!state.freeShippingAchieved"<?php echo ($cart_total < $cc_free_shipping_amount) ? ' class="cc-hidden"' : ''; ?>>
						<?php
						printf(
							/* translators: %s: Country name */
							esc_html__("Congrats, you've activated free %s shipping!", 'caddy'),
							'<strong><span class="cc-fs-country">' . esc_attr($cc_shipping_country) . '</span></strong>'
						);
						?>
					</span>
				</span>
				<div class="cc-fs-meter">
					<span class="cc-fs-meter-used<?php echo esc_attr($cc_bar_active); ?>"
						  data-wp-class--cc-bar-active="state.freeShippingAchieved"
						  data-wp-style--width="state.freeShippingPercentage"
						  style="width: <?php echo esc_attr($cc_bar_amount); ?>%;"></span>
				</div>
			<?php } else { ?>
				<?php do_action( 'caddy_free_shipping_title_text' ); // Free shipping title html ?>
			<?php } ?>
		</div>
	<?php } ?>
	
	<div class="cc-body-container">
		<div class="cc-body<?php echo esc_attr( $cc_empty_class . $cc_fs_active_class . $cc_disable_branding_class ); ?>">
	
			<?php do_action( 'caddy_display_registration_message' ); ?>
	
			<!-- Interactivity API cart items (shown when cart has items) -->
			<div data-wp-class--cc-hidden="!state.cartCount">
				<?php do_action( 'caddy_before_cart_items' ); ?>

				<div class="cc-row cc-cart-items cc-text-center" data-wp-interactive="caddy/cart">
					<!-- Interactivity API template renders items from PHP state -->
					<template data-wp-each--item="state.items">
						<div data-wp-bind--class="context.item.itemClass"
						     data-wp-key="context.item.cartKey"
						     data-wp-class--cc-updating="context.item.isUpdating"
						     data-wp-class--cc-saving="context.item.isSaving">

							<div class="cc-cart-product">
								<a data-wp-bind--href="context.item.permalink" class="cc-product-link cc-product-thumb" data-wp-bind--data-title="context.item.name">
									<img data-wp-bind--src="context.item.image" data-wp-bind--alt="context.item.name" loading="lazy" />
								</a>

								<div class="cc_item_content">
									<div class="cc-item-content-top">
										<div class="cc_item_title">
											<a data-wp-bind--href="context.item.permalink" class="cc-product-link" data-wp-text="context.item.name"></a>
											<div class="cc_item_variation"
											     data-wp-class--cc-hidden="!context.item.variationText"
											     data-wp-text="context.item.variationText"></div>

											<!-- Quantity controls (hidden for bundled items) -->
											<div class="cc_item_quantity_wrap"
											     data-wp-class--cc-hidden="context.item.isBundledItem"
											     data-wp-class--cc-sold-individually="context.item.soldIndividually">
												<div class="cc_item_quantity_update cc_item_quantity_minus"
												     data-wp-class--cc-hidden="context.item.soldIndividually"
												     data-wp-on--click="actions.decreaseQuantity">−</div>

												<input type="text"
													readonly
													class="cc_item_quantity"
													data-wp-bind--data-product_id="context.item.productId"
													data-wp-bind--data-key="context.item.cartKey"
													data-wp-bind--value="context.item.quantity"
													step="1"
													min="1">

												<div class="cc_item_quantity_update cc_item_quantity_plus"
												     data-wp-class--cc-hidden="context.item.soldIndividually"
												     data-wp-on--click="actions.increaseQuantity">+</div>
											</div>
										</div>

										<div class="cc_item_total_price"
										     data-wp-class--cc-hidden="context.item.isBundledItem">
											<div class="price">
												<span class="cc-sale-price-wrapper"
												      data-wp-class--cc-hidden="!context.item.showSalePrice">
													<del><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol" data-wp-text="state.currencySymbol"><?php echo esc_html( html_entity_decode( get_woocommerce_currency_symbol() ) ); ?></span><span data-wp-text="context.item.regularPriceFormatted"></span></bdi></span></del>
												</span>
												<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol" data-wp-text="state.currencySymbol"><?php echo esc_html( html_entity_decode( get_woocommerce_currency_symbol() ) ); ?></span><span data-wp-text="context.item.price"></span></bdi></span>
											</div>
											<div class="cc_saved_amount"
											     data-wp-class--cc-hidden="!context.item.showSavings">
												(<?php echo esc_html__('Save', 'caddy'); ?> <span data-wp-text="context.item.savingsPercentage"></span>%)
											</div>
										</div>
									</div>

									<div class="cc-item-content-bottom">
										<div class="cc-item-content-bottom-left">
											<?php
											// Only show Save for Later button if user is logged in and feature is enabled
											$cc_enable_sfl_options = get_option('cc_enable_sfl_options', 'enabled');
											if (is_user_logged_in() && 'enabled' === $cc_enable_sfl_options) :
											?>
											<!-- Save for later (hidden for bundled items) -->
											<div class="cc_sfl_btn"
											     data-wp-class--cc-hidden="context.item.isBundledItem">
												<a href="javascript:void(0);"
												   class="button cc-button-sm save_for_later_btn"
												   aria-label="<?php esc_attr_e('Save for later', 'caddy'); ?>"
												   data-wp-bind--data-product_id="context.item.productId"
												   data-wp-bind--data-cart_item_key="context.item.cartKey">
													<?php esc_html_e('Save for later', 'caddy'); ?>
												</a>
												<div class="cc-loader" style="display: none;"></div>
											</div>
											<?php endif; ?>
										</div>

										<!-- Remove button (hidden for bundled items) -->
										<a href="javascript:void(0);"
										   class="cc-remove-item"
										   data-wp-class--cc-hidden="context.item.isBundledItem"
										   aria-label="<?php esc_attr_e('Remove this item', 'caddy'); ?>"
										   data-wp-bind--data-product_id="context.item.productId"
										   data-wp-bind--data-cart_item_key="context.item.cartKey"
										   data-wp-bind--data-cart-key="context.item.cartKey"
										   data-wp-bind--data-product_name="context.item.name"
										   data-wp-on--click="actions.removeItem">
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
								</div>
							</div>
					</template>
				</div>

				<!--Product recommendation screen (only shown when cart has items)-->
				<?php
				$cc_product_recommendation = get_option('cc_product_recommendation');
				if ('enabled' === $cc_product_recommendation) :
				?>
				<div class="cc-product-upsells-wrapper" data-wp-class--cc-hidden="state.cartCount === 0">
					<?php
					// For Interactivity API, use the reactive recommendations template
					if (class_exists('Caddy_Block') && (has_block('caddy/cart') || Caddy_Block::should_auto_insert())) {
						// Interactivity API implementation - recommendations render from state
						?>
						<div class="cc-pl-info-wrapper" data-wp-class--cc-hidden="!state.showRecommendations">
							<div class="cc-pl-upsells">
								<label><?php esc_html_e( 'We think you might also like...', 'caddy' ); ?></label>
								<div class="cc-pl-upsells-wrapper">
									<!-- Loading skeleton - shown while loading -->
									<div class="cc-pl-recommendations cc-recommendations-loading"
										 data-wp-class--cc-hidden="!state.recommendationsLoading">
										<div class="cc-slide">
											<div class="up-sells-product">
												<div class="cc-up-sells-image">
													<div class="cc-skeleton" style="width: 95px; height: 95px;"></div>
												</div>
												<div class="cc-up-sells-details">
													<div class="cc-skeleton" style="height: 16px; width: 80%; margin-bottom: 8px;"></div>
													<div class="cc-skeleton" style="height: 14px; width: 60%; margin-bottom: 12px;"></div>
													<div class="cc-skeleton" style="height: 36px; width: 120px;"></div>
												</div>
											</div>
										</div>
									</div>

									<!-- Actual recommendations - hidden while loading -->
									<div class="cc-pl-recommendations"
										 data-wp-class--cc-hidden="state.recommendationsLoading"
										 data-wp-style--transform="state.recommendationTransform"
										 data-wp-style--width="state.recommendationSliderWidth">
										<template data-wp-each--rec="state.recommendations" data-wp-each-key="context.rec.id">
											<div class="cc-slide" data-wp-key="context.rec.id">
												<div class="up-sells-product">
													<div class="cc-up-sells-image">
														<a data-wp-bind--href="context.rec.permalink">
															<img data-wp-bind--src="context.rec.image"
																 data-wp-bind--alt="context.rec.name"
																 loading="lazy"
																 class="attachment-woocommerce_thumbnail" />
														</a>
													</div>
													<div class="cc-up-sells-details">
														<a data-wp-bind--href="context.rec.permalink" class="title" data-wp-text="context.rec.name"></a>
														<div class="cc_item_total_price">
															<span class="price">
																<del data-wp-class--cc-hidden="!context.rec.isOnSale"><span data-wp-text="context.rec.regularPrice"></span></del>
																<span data-wp-text="context.rec.price"></span>
															</span>
														</div>
														<!-- Variable product button -->
														<a data-wp-bind--href="context.rec.permalink"
														   data-wp-class--cc-hidden="!context.rec.isVariable"
														   class="button product_type_variable"
														   data-wp-text="context.rec.buttonText"></a>
														<!-- Grouped product button -->
														<a data-wp-bind--href="context.rec.permalink"
														   data-wp-class--cc-hidden="!context.rec.isGrouped"
														   class="button product_type_grouped"
														   data-wp-text="context.rec.buttonText"></a>
														<!-- Simple product button -->
														<button data-wp-on--click="actions.addRecommendationToCart"
																data-wp-class--cc-hidden="!context.rec.isSimple"
																data-wp-class--loading="context.rec.isAdding"
																class="button product_type_simple add_to_cart_button"
																data-wp-text="context.rec.buttonText"><?php esc_html_e('Add to cart', 'woocommerce'); ?></button>
													</div>
												</div>
											</div>
										</template>
									</div>
								</div>
								<!-- Navigation arrows (outside wrapper for positioning) -->
								<div class="caddy-prev"
									 data-wp-on--click="actions.prevRecommendation"
									 data-wp-class--cc-disabled="state.isFirstRecommendation">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
										<path d="M13.75 0.68682C13.706 0.70136 13.6385 0.72918 13.6 0.74862C13.49172 0.80328 5.23742 9.06048 5.13158 9.22C4.8194 9.69054 4.8194 10.30936 5.13156 10.78C5.19276 10.87228 6.42668 12.1177 9.3839 15.072C14.07654 19.76 13.61372 19.32936 13.96 19.32978C14.12646 19.32998 14.16488 19.32306 14.27 19.274C14.41248 19.20752 14.56516 19.06538 14.6301 18.93874C14.69084 18.82024 14.72638 18.62038 14.70954 18.49188C14.7022 18.43584 14.67306 18.336 14.6448 18.27C14.5967 18.15768 14.33296 17.88932 10.52196 14.075L6.45052 10 10.52196 5.925C14.33296 2.11068 14.5967 1.84232 14.6448 1.73C14.71158 1.5741 14.72838 1.43654 14.69988 1.27938C14.66976 1.11322 14.60544 0.99154 14.48664 0.876C14.34748 0.74062 14.18934 0.67386 13.99 0.66636C13.89446 0.66278 13.79776 0.671 13.75 0.68682" fill="currentColor"/>
									</svg>
								</div>
								<div class="caddy-next"
									 data-wp-on--click="actions.nextRecommendation"
									 data-wp-class--cc-disabled="state.isLastRecommendation">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
										<path d="M5.83 0.68688C5.61066 0.75962 5.43864 0.91026 5.3468 1.11C5.29948 1.21294 5.29164 1.2556 5.2918 1.41C5.29196 1.5643 5.30016 1.60856 5.34928 1.72C5.40416 1.8445 5.57904 2.0226 9.47804 5.925L13.54948 10 9.47804 14.075C5.57904 17.9774 5.40416 18.1555 5.34928 18.28C5.30008 18.39162 5.29198 18.43546 5.29198 18.59C5.29198 18.744 5.29998 18.78764 5.34742 18.8921C5.41748 19.04638 5.5714 19.20006 5.73 19.27404C5.8351 19.32306 5.87358 19.32998 6.04 19.32978C6.38628 19.32936 5.92346 19.76 10.6161 15.072C13.57332 12.1177 14.80724 10.87228 14.86844 10.78C15.02564 10.54298 15.1 10.29254 15.1 10C15.1 9.70746 15.02564 9.45702 14.86844 9.22C14.80724 9.12772 13.57332 7.8823 10.6161 4.928C6.84122 1.15686 6.43968 0.76166 6.34 0.71968C6.20594 0.66322 5.95082 0.6468 5.83 0.68688" fill="currentColor"/>
									</svg>
								</div>
							</div>
						</div>
						<?php
					} else {
						// Fallback to action hook for legacy system
						do_action( 'caddy_product_upsells_slider', 0 );
					}
					?>
				</div>
				<?php endif; ?>

				<?php do_action( 'caddy_after_cart_items' ); ?>
			</div>


			<!-- Reactive empty cart state (shown when cart is empty) -->
			<div data-wp-class--cc-hidden="state.cartCount">
				<div class="cc-empty-msg">
					<img class="cc-empty-cart-img" src="<?php echo esc_url( plugin_dir_url( __DIR__ ) ); ?>img/cart-empty.svg" alt="Empty Cart">
					<span class="cc-title"><?php esc_html_e( 'Your Cart is Empty!', 'caddy' ); ?></span>

					<?php if ( ! empty( $cc_sfl_items ) ) { ?>
						<p><?php esc_html_e( 'You haven\'t added any items to your cart yet, but you do have products in your saved list.', 'caddy' ); ?></p>
						<a href="javascript:void(0);" class="cc-button cc-view-saved-items"><?php esc_html_e( 'View Saved Items', 'caddy' ); ?></a>
					<?php } else {
						// Get custom browse products URL or default to shop page
						$custom_browse_url = get_option('cc_browse_products_url', '');
						$browse_url = !empty($custom_browse_url) ? $custom_browse_url : get_permalink( wc_get_page_id( 'shop' ) );
					?>
						<p><?php esc_html_e( 'It looks like you haven\'t added any items to your cart yet.', 'caddy' ); ?></p>
						<a href="<?php echo esc_url( $browse_url ); ?>" class="cc-button"><?php esc_html_e( 'Browse Products', 'caddy' ); ?></a>
					<?php } ?>
				</div>
			</div>

	
		</div>
	</div>
	<?php do_action( 'caddy_after_cart_screen_data' ); ?>

	<!-- Reactive cart actions (shown when cart has items) -->
	<div data-wp-class--cc-hidden="!state.cartCount" class="cc-cart-actions<?php echo esc_attr( $cc_disable_branding_class ); ?>">

			<?php do_action( 'caddy_before_cart_screen_totals' ); ?>
			<?php
			$applied_coupons = wc_coupons_enabled() ? WC()->cart->get_applied_coupons() : array();
			if ( wc_coupons_enabled() ) {
				?>
				<div class="cc-coupon">
					<div class="woocommerce-notices-wrapper">
						<?php
						$notices = wc_get_notices();
						// Only print error notices
						if (isset($notices['error'])) {
							WC()->session->set('wc_notices', ['error' => $notices['error']]);
							wc_print_notices();
						}
						?>
					</div>
					<a class="cc-coupon-title" href="javascript:void(0);">
						<?php esc_html_e( 'Apply a promo code', 'caddy' ); ?>
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" version="1.1" id="Tailless-Line-Arrow-Up-1--Streamline-Core">
							<path d="M9.6881 4.9297C9.524140000000001 4.965380000000001 9.37644 5.02928 9.22 5.13224C9.1265 5.19378 7.92168 6.38728 4.928 9.3839C0.24 14.07654 0.6706399999999999 13.61372 0.67022 13.96C0.67002 14.126520000000001 0.6769799999999999 14.165080000000001 0.72642 14.2721C0.79824 14.4275 0.953 14.581199999999999 1.10956 14.65258C1.2111 14.698879999999999 1.25632 14.707180000000001 1.40956 14.7076C1.56528 14.70804 1.6078200000000002 14.700239999999999 1.72 14.65076C1.84446 14.59584 2.02348 14.42008 5.925 10.52196L10 6.45052 14.075000000000001 10.52196C17.88932 14.33296 18.15768 14.5967 18.27 14.6448C18.336 14.673060000000001 18.435840000000002 14.702200000000001 18.491880000000002 14.70954C18.62038 14.726379999999999 18.82024 14.690840000000001 18.93874 14.6301C19.06538 14.56516 19.20752 14.41248 19.274 14.27C19.32306 14.16488 19.329980000000003 14.12646 19.32978 13.96C19.32936 13.61372 19.76 14.07654 15.072000000000001 9.3839C12.1177 6.42668 10.872280000000002 5.19276 10.78 5.1315599999999995C10.4682 4.92474 10.05878 4.849060000000001 9.6881 4.9297" stroke="none" fill="currentColor" fill-rule="evenodd"></path>
						</svg>
					</a>
					<div class="cc-coupon-form" style="display: none;">
						<div class="coupon">
							<form name="apply_coupon_form" id="apply_coupon_form" method="post">
								<input type="text" name="cc_coupon_code" id="cc_coupon_code" placeholder="<?php echo esc_attr__( 'Promo code', 'caddy' ); ?>" />
								<input type="submit" class="cc-button-sm cc-coupon-btn" name="cc_apply_coupon" value="<?php echo esc_attr__( 'Apply', 'caddy' ); ?>">
							</form>
						</div>
					</div>
				</div>
			<?php } ?>
			<!-- Discounts container - managed by JavaScript/Store API -->
			<div class="cc-discounts" style="<?php echo empty($applied_coupons) ? 'display:none;' : ''; ?>">
				<div class="cc-discount">
					<?php
					if (!empty($applied_coupons)) {
						foreach ( $applied_coupons as $code ) {
							$coupon_detail = new WC_Coupon( $code );
							?>
							<div class="cc-applied-coupon">
								<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) ); ?>img/tag-icon.svg" alt="Discount Code">
								<span class="cc_applied_code"><?php echo esc_html( $code ); ?></span>
								<a href="javascript:void(0);" class="cc-remove-coupon"><i class="ccicon-close"></i></a>
							</div>
							<?php
						}
					}
					?>
				</div>
				<?php
				// Get coupon discounts only
				$coupon_discount_amount = 0;
				if ( wc_coupons_enabled() && !empty($applied_coupons) ) {
					$applied_coupons = WC()->cart->get_applied_coupons();
					if ( ! empty( $applied_coupons ) ) {
						foreach ( $applied_coupons as $code ) {
							$coupon = new WC_Coupon( $code );
							// Get discount amount respecting tax display setting
							$tax_display = get_option( 'woocommerce_tax_display_cart' );
							$inc_tax = ( 'incl' === $tax_display );
							$coupon_discount_amount += WC()->cart->get_coupon_discount_amount( $coupon->get_code(), !$inc_tax );
						}
					}
				}
				?>
				<div class="cc-savings">
					<?php
					// Display coupon discount amount if greater than 0
					if ($coupon_discount_amount > 0) {
						echo esc_html__('-', 'caddy') .
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_price returns escaped HTML
							wc_price($coupon_discount_amount);
					}
					?>
				</div>
			</div>
			<div class="cc-totals">
				<div class="cc-total-box">
					<div class="cc-total-text">
						<?php echo esc_html__( 'Subtotal', 'caddy' ); ?>
						<br><span class="cc-subtotal-subtext"><?php esc_html_e( 'Shipping &amp; taxes calculated at checkout.', 'caddy' ); ?></span>
					</div>

					<?php
					// Let WooCommerce handle the subtotal calculation with discounts
					$cart_subtotal = WC()->cart->get_displayed_subtotal();
					
					// Get the coupon discount amount
					$coupon_discount_amount = 0;
					if (wc_coupons_enabled()) {
						$applied_coupons = WC()->cart->get_applied_coupons();
						if (!empty($applied_coupons)) {
							$tax_display = get_option('woocommerce_tax_display_cart');
							$inc_tax = ('incl' === $tax_display);
							
							foreach ($applied_coupons as $code) {
								$coupon = new WC_Coupon($code);
								$coupon_discount_amount += WC()->cart->get_coupon_discount_amount($coupon->get_code(), !$inc_tax);
							}
						}
					}
					
					// Calculate the total (subtotal minus coupon discount)
					$cart_total = $cart_subtotal - $coupon_discount_amount;
					
					// Calculate original total (before any discounts) for comparison
					$original_total = 0;
					foreach (WC()->cart->get_cart() as $cart_item) {
						$product = $cart_item['data'];
						$original_price = $product->get_regular_price();
						$original_total += floatval($original_price) * $cart_item['quantity'];
					}
					?>
					<div class="cc-total-amount">
						<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span><span data-wp-text="state.cartSubtotalDisplay"><?php echo number_format($cart_total, 2, '.', ''); ?></span></bdi></span>
					</div>
				</div>
			</div>

			<?php do_action( 'caddy_after_cart_screen_totals' ); ?>
			<?php 
				$checkout_lock_svg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="cc-icon-lock"><path fill="currentColor" fill-rule="evenodd" d="M8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7V10H8V7ZM6 10V7C6 3.68629 8.68629 1 12 1C15.3137 1 18 3.68629 18 7V10H21V23H3V10H6ZM11 18.5V14.5H13V18.5H11Z" clip-rule="evenodd"></path></svg>';
				$checkout_arrow_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5px" class="cc-icon-arrow-right"><line x1="0.875" y1="12" x2="23.125" y2="12" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></line><polyline points="16.375 5.5 23.125 12 16.375 18.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></polyline></svg>';
			?>
			<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="cc-button cc-button-primary"><?php 
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is hardcoded safe HTML
			echo $checkout_lock_svg; ?> <?php esc_html_e( 'Checkout Now', 'caddy' ); ?><?php 
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is hardcoded safe HTML
			echo $checkout_arrow_svg; ?></a>

			<?php do_action( 'caddy_after_cart_screen_checkout_button' ); ?>
	</div>

	<input type="hidden" name="cc-compass-count-after-remove" class="cc-cart-count-after-product-remove" value="<?php echo esc_attr( $total_cart_item_count ); ?>">

	<?php
	$cc_compass_desk_notice = get_option( 'cp_desktop_notices', '' );
	$cc_compass_mob_notice  = get_option( 'cp_mobile_notices', 'mob_disable_notices' );
	$cc_compass_mob_notice  = ( wp_is_mobile() ) ? $cc_compass_mob_notice : '';
	$cc_is_mobile = ( wp_is_mobile() ) ? 'yes' : 'no';
	?>
	<input type="hidden" name="cc-compass-desk-notice" class="cc-compass-desk-notice" value="<?php echo esc_attr( $cc_compass_desk_notice ); ?>">
	<input type="hidden" name="cc-compass-mobile-notice" class="cc-compass-mobile-notice" value="<?php echo esc_attr( $cc_compass_mob_notice ); ?>">
	<input type="hidden" class="cc-is-mobile" value="<?php echo esc_attr( $cc_is_mobile ); ?>">

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