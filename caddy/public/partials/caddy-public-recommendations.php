<?php
/**
 * Product recommendations screen html - Interactivity API version
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$cc_product_recommendation = get_option( 'cc_product_recommendation' );

// Only show if recommendations are enabled
if ('enabled' !== $cc_product_recommendation) {
	return;
}

// Skip if Caddy Premium is active (it has its own recommendations)
if (class_exists('Caddy_Premium')) {
	return;
}
?>

<div class="cc-pl-info-wrapper" data-wp-interactive="caddy/cart" data-wp-class--cc-hidden="!state.showRecommendations">
	<div class="cc-pl-upsells">
		<label><?php esc_html_e( 'We think you might also like...', 'caddy' ); ?></label>
		<div class="cc-pl-upsells-wrapper"
			 data-wp-on--pointerdown="actions.onSliderPointerDown"
			 data-wp-on--pointermove="actions.onSliderPointerMove"
			 data-wp-on--pointerup="actions.onSliderPointerUp"
			 data-wp-on--pointerleave="actions.onSliderPointerUp"
			 style="touch-action: pan-y;">
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
					<div class="cc-slide" data-wp-key="context.rec.id" data-wp-bind--data-product-id="context.rec.id">
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
								   data-wp-text="context.rec.buttonText"><?php esc_html_e('Select options', 'caddy'); ?></a>
								<!-- Grouped product button -->
								<a data-wp-bind--href="context.rec.permalink"
								   data-wp-class--cc-hidden="!context.rec.isGrouped"
								   class="button product_type_grouped"
								   data-wp-text="context.rec.buttonText"><?php esc_html_e('View products', 'caddy'); ?></a>
								<!-- Simple product button -->
								<button data-wp-on--click="actions.addRecommendationToCart"
										data-wp-class--cc-hidden="!context.rec.isSimple"
										data-wp-class--loading="context.rec.isAdding"
										class="button product_type_simple add_to_cart_button"
										data-wp-text="context.rec.buttonText"><?php esc_html_e('Add to cart', 'caddy'); ?></button>
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
