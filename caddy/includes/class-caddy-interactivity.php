<?php
/**
 * Caddy WordPress Interactivity API Integration
 *
 * Implements reactive cart state management using WordPress Interactivity API
 * combined with WooCommerce Store API for optimal performance.
 *
 * @package    Caddy
 * @subpackage Caddy/includes
 * @since      2.3.0
 */

class Caddy_Interactivity {

	/**
	 * Initialize Interactivity API integration
	 */
	public static function init() {
		// Initialize custom reactive cart system (no WordPress Interactivity API required)

		// Enqueue the reactive cart script and initialize store
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_interactivity_assets'));

		// Register REST API endpoints for Save for Later functionality
		add_action('rest_api_init', array(__CLASS__, 'register_cart_endpoints'));

		// Add interactivity directives to cart templates
		add_filter('caddy_cart_template_context', array(__CLASS__, 'add_interactivity_context'));

		// Clear recommendations cache when products are updated
		add_action('woocommerce_update_product', array(__CLASS__, 'clear_recommendations_cache'));
		add_action('woocommerce_delete_product', array(__CLASS__, 'clear_recommendations_cache'));
	}

	/**
	 * Enqueue Interactivity API assets and initialize cart store
	 */
	public static function enqueue_interactivity_assets() {
		// Load on all frontend pages since cart can be accessed from anywhere
		if (is_admin()) {
			return;
		}

		// Skip legacy loading if block system is active
		if (class_exists('Caddy_Block') && (has_block('caddy/cart') || Caddy_Block::should_auto_insert())) {
			return;
		}

		// Debug when this is called

		// Enqueue WordPress Interactivity API

		// WordPress Interactivity API is not available in plugin context
		// Switching to custom reactive implementation

		// Initialize cart state from server
		$initial_state = self::get_initial_cart_state();


		// Pass cart state to JavaScript using wp_interactivity_state for script modules
		// Note: Nonces are now handled via meta tags in class-caddy-block.php for better reliability
		wp_interactivity_state('caddy/cart', $initial_state);
	}

	/**
	 * Get initial cart state for Interactivity API
	 * Server-renders current cart for instant display, fragments will update if stale
	 *
	 * @return array Cart state with current cart data
	 */
	public static function get_initial_cart_state() {
		// Get saved items for logged-in users
		$saved_items = array();
		if (is_user_logged_in()) {
			$user_id = get_current_user_id();
			$saved_item_ids = get_user_meta($user_id, 'cc_save_for_later_items', true);
			if (is_array($saved_item_ids)) {
				$saved_items = self::get_saved_items_data($saved_item_ids);
			}
		}

		// Get actual cart data from WooCommerce for instant display
		$cart = WC()->cart;
		$cart_items = array();
		$cart_count = 0;
		$cart_total = 0;
		$cart_subtotal = 0;
		$original_total = 0;
		$has_discount = false;
		$shipping_eligible_total = 0;

		if ($cart && !$cart->is_empty()) {
			foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
				$product = $cart_item['data'];
				$product_id = $cart_item['product_id'];
				$variation_id = $cart_item['variation_id'];
				$quantity = $cart_item['quantity'];

				// Get thumbnail
				$thumbnail_id = $product->get_image_id();
				$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail') : false;
				if (!$thumbnail_url) {
					$thumbnail_url = wc_placeholder_img_src('woocommerce_thumbnail');
				}
				// Normalize double slashes in URL path
				$thumbnail_url = preg_replace('#(?<!:)//+#', '/', $thumbnail_url);

				// Detect bundle status
				$is_bundle_container = function_exists('wc_pb_is_bundle_container_cart_item') &&
									   wc_pb_is_bundle_container_cart_item($cart_item);
				$is_bundled_item = function_exists('wc_pb_is_bundled_cart_item') &&
								   wc_pb_is_bundled_cart_item($cart_item);

				// Get parent bundle cart key if this is a bundled item
				$bundled_by = null;
				if ($is_bundled_item && isset($cart_item['bundled_by'])) {
					$bundled_by = $cart_item['bundled_by'];
				}

				// Build item class string
				$item_class = 'cc-cart-product-list cc-cart-item';
				if ($is_bundle_container) {
					$item_class .= ' bundle';
				}
				if ($is_bundled_item) {
					$item_class .= ' bundled_child';
				}

				// Calculate pricing - use WC tax-aware display prices
				$unit_price = floatval( wc_get_price_to_display( $product, array( 'qty' => 1 ) ) );
				$raw_regular = $product->get_regular_price();
				$regular_unit_price = ( $raw_regular !== '' )
					? floatval( wc_get_price_to_display( $product, array( 'qty' => 1, 'price' => $raw_regular ) ) )
					: $unit_price;
				$sale_unit_price = $product->get_sale_price() ? floatval( wc_get_price_to_display( $product, array( 'qty' => 1, 'price' => $product->get_sale_price() ) ) ) : 0;

				$line_total = $unit_price * $quantity;
				$regular_line_total = $regular_unit_price * $quantity;
				$sale_line_total = $sale_unit_price * $quantity;

				// For bundle containers with $0 price (aggregate pricing), sum children's prices
				if ($is_bundle_container && $line_total == 0 && function_exists('wc_pb_get_bundled_cart_items')) {
					$bundled_cart_items = wc_pb_get_bundled_cart_items($cart_item);
					if (!empty($bundled_cart_items)) {
						$agg_total = 0;
						foreach ($bundled_cart_items as $child) {
							$child_product = $child['data'];
							$agg_total += floatval(wc_get_price_to_display($child_product, array('qty' => $child['quantity'])));
						}
						$line_total = $agg_total;
						$regular_line_total = $agg_total;
					}
				}

				$is_on_sale = $product->is_on_sale();
				$savings_percentage = 0;
				if ($is_on_sale && $regular_unit_price > 0) {
					$savings_percentage = round((($regular_unit_price - $sale_unit_price) / $regular_unit_price) * 100);
				}

				// Get variation text
				$variation_text = '';
				if ($variation_id && function_exists('wc_get_formatted_cart_item_data')) {
					$variation_data = wc_get_formatted_cart_item_data($cart_item);
					$variation_text = strip_tags($variation_data);
				}

				// Smart price formatting - matches formatPriceSmart in JavaScript
				$wc_decimals = wc_get_price_decimals();
				$wc_dec_sep = wc_get_price_decimal_separator();
				$wc_thou_sep = wc_get_price_thousand_separator();
				$format_price_smart = function($amount) use ($wc_decimals, $wc_dec_sep, $wc_thou_sep) {
					return number_format($amount, $wc_decimals, $wc_dec_sep, $wc_thou_sep);
				};

				// Get quantity limits
				$sold_individually = $product->is_sold_individually();
				$max_quantity = $sold_individually ? 1 : ($product->get_max_purchase_quantity() > 0 ? $product->get_max_purchase_quantity() : INF);
				$min_quantity = max(1, $product->get_min_purchase_quantity());

				// For bundled items, get quantity limits from the bundle configuration
				if ($is_bundled_item && isset($cart_item['bundled_item_id'], $cart_item['bundled_by'])) {
					$parent_cart_item = WC()->cart->cart_contents[$cart_item['bundled_by']] ?? null;
					if ($parent_cart_item && $parent_cart_item['data']->is_type('bundle')) {
						$bundled_item = $parent_cart_item['data']->get_bundled_item($cart_item['bundled_item_id']);
						if ($bundled_item) {
							$pb_min = $bundled_item->get_quantity('min');
							$pb_max = $bundled_item->get_quantity('max');
							$min_quantity = max(1, $pb_min);
							$max_quantity = '' !== $pb_max ? (int) $pb_max : INF;
						}
					}
					// Fixed-qty bundled items get a CSS class to hide +/- buttons
					if ($min_quantity >= $max_quantity) {
						$item_class .= ' bundled_fixed_qty';
					}
				}

				// Compute display flags
				$shouldHideControls = $is_bundled_item;
				$hideQuantityButtons = $is_bundled_item && $min_quantity >= $max_quantity;
				$hidePrice = !$is_bundle_container && ($line_total == 0 && $regular_line_total == 0);

				// Format cart item for Caddy - match Store API converter structure
				$cart_items[] = array(
					'cartKey' => $cart_item_key,
					'productId' => $variation_id ? $variation_id : $product_id,
					'quantity' => $quantity,
					'name' => $product->get_name(),
					'variationText' => $variation_text,
					'price' => $format_price_smart($line_total),
					'priceHtml' => html_entity_decode( strip_tags( wc_price($line_total) ) ),
					'regularPrice' => $regular_unit_price,
					'regularLineTotal' => $regular_line_total,
					'regularPriceFormatted' => $format_price_smart($regular_line_total),
					'regularPriceHtml' => $is_on_sale ? html_entity_decode( strip_tags( wc_price($regular_line_total) ) ) : '',
					'salePrice' => $format_price_smart($sale_line_total),
					'unitPrice' => $unit_price,
					'isOnSale' => $is_on_sale,
					'savingsPercentage' => $savings_percentage,
					'lineTotal' => $line_total,
					'lineTotalFormatted' => html_entity_decode( strip_tags( wc_price($line_total) ) ),
					'image' => $thumbnail_url,
					'permalink' => $product->get_permalink(),
					'isBundleContainer' => $is_bundle_container,
					'isBundledItem' => $is_bundled_item,
					'bundledBy' => $bundled_by,
					'shouldHideControls' => $shouldHideControls,
					'hideQuantityButtons' => $hideQuantityButtons,
					'hidePrice' => $hidePrice,
					'itemClass' => $item_class,
					'showSalePrice' => $is_on_sale,
					'showSavings' => $is_on_sale && $savings_percentage > 0,
					'soldIndividually' => $sold_individually,
					'maxQuantity' => $max_quantity === INF ? null : $max_quantity,
					'minQuantity' => $min_quantity,
					'isAtMinQty' => $quantity <= $min_quantity,
					'isAtMaxQty' => $max_quantity !== INF && $quantity >= $max_quantity,
				);
			}

			$cart_count = $cart->get_cart_contents_count();
			$cart_total = $cart->get_total('');

			// Match subtotal logic used in the cart template (displayed subtotal minus coupon discounts).
			$cart_subtotal_before_coupons = $cart->get_displayed_subtotal();
			$coupon_discount_amount = 0;
			if ( wc_coupons_enabled() ) {
				$applied_coupons = $cart->get_applied_coupons();
				if ( ! empty( $applied_coupons ) ) {
					$tax_display = get_option( 'woocommerce_tax_display_cart' );
					$inc_tax     = ( 'incl' === $tax_display );
					foreach ( $applied_coupons as $code ) {
						$coupon = new WC_Coupon( $code );
						$coupon_discount_amount += $cart->get_coupon_discount_amount( $coupon->get_code(), ! $inc_tax );
					}
				}
			}
			$cart_subtotal = $cart_subtotal_before_coupons - $coupon_discount_amount;

			// Calculate original total from regular line totals for consistent discount display.
			foreach ( $cart_items as $item ) {
				$original_total += $item['regularLineTotal'];
			}
			$has_discount = $original_total > floatval( $cart_subtotal );
		}

		$price_decimal_separator = wc_get_price_decimal_separator();
		$price_trim_zeros = apply_filters( 'woocommerce_price_trim_zeros', false );
		$sample_whole_price = html_entity_decode( strip_tags( wc_price( 1 ) ) );
		if ( ! $price_trim_zeros && false === strpos( $sample_whole_price, $price_decimal_separator ) ) {
			$price_trim_zeros = true;
		}

		return array(
			'items' => $cart_items,
			'cartCount' => $cart_count,
			'cartTotal' => floatval($cart_total),
			'cartTotalFormatted' => html_entity_decode( strip_tags( wc_price($cart_total) ) ),
			'cartSubtotal' => floatval($cart_subtotal),
			'cartSubtotalFormatted' => html_entity_decode( strip_tags( wc_price($cart_subtotal) ) ),
			'cartSubtotalDisplay' => number_format(floatval($cart_subtotal), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator()),
			'originalTotal' => floatval($original_total),
			'originalTotalFormatted' => html_entity_decode( strip_tags( wc_price($original_total) ) ),
			'originalTotalDisplay' => number_format(floatval($original_total), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator()),
			'hasDiscount' => $has_discount,
			'cartHash' => $cart ? $cart->get_cart_hash() : '',
			'isOpen' => false,
			'isLoading' => false,
			'needsRefresh' => false, // Flag for background refresh
			'isItemSingular' => $cart_count === 1,
			'savedItems' => $saved_items,
			'recommendations' => array(), // Loaded via background prefetch to avoid blocking page render
			'recommendationIndex' => 0,
			'recommendationsLoading' => true, // Show skeleton until prefetch completes
			'showRecommendations' => true, // Controls container visibility (true while loading or has items)
			'currencySymbol' => html_entity_decode( get_woocommerce_currency_symbol() ),
			'currencyCode' => get_woocommerce_currency(),
			'currencyDecimals' => wc_get_price_decimals(),
			'currencyDecimalSep' => $price_decimal_separator,
			'currencyThousandSep' => wc_get_price_thousand_separator(),
			'currencyPosition' => get_option( 'woocommerce_currency_pos', 'left' ),
			'taxDisplayCart' => get_option( 'woocommerce_tax_display_cart', 'excl' ),
			'priceTrimZeros' => $price_trim_zeros,
			'i18n' => array(
				'addToCart' => __('Add to cart', 'caddy'),
				'seeOptions' => __('Select options', 'caddy'),
				'viewProducts' => __('View products', 'caddy'),
				'saveForLater' => __('Save for later', 'caddy'),
				'saved' => __('Saved', 'caddy'),
				'adding' => __('Adding...', 'caddy'),
				'added' => __('Added!', 'caddy'),
				'addedCheckmark' => __('Added ✓', 'caddy'),
				'error' => __('Error', 'caddy'),
				'errorTryAgain' => __('Error - Try Again', 'caddy'),
				'removeItem' => __('Remove this item', 'caddy'),
				'checkout' => __('Checkout Now', 'caddy'),
				'browseProducts' => __('Browse Products', 'caddy'),
				'viewSavedItems' => __('View Saved Items', 'caddy'),
				'apply' => __('Apply', 'caddy'),
				'promoCode' => __('Promo code', 'caddy'),
				'subtotal' => __('Subtotal', 'caddy'),
				'errorSaveItemFailed' => __('Failed to save item', 'caddy'),
				'errorRemoveSavedItemFailed' => __('Failed to remove saved item', 'caddy'),
				'errorAddToCartFailed' => __('Failed to add item to cart', 'caddy'),
				'errorUpdateFailed' => __('Failed to update quantity', 'caddy'),
				'errorRemoveFailed' => __('Failed to remove item', 'caddy'),
				'errorMoveToCartFailed' => __('Failed to move item to cart', 'caddy'),
				'errorApplyCouponFailed' => __('Failed to apply coupon', 'caddy'),
				'errorRemoveCouponFailed' => __('Failed to remove coupon', 'caddy'),
				'errorApplyCouponTryAgain' => __('Failed to apply coupon. Please try again.', 'caddy'),
				'errorRemoveCouponTryAgain' => __('Failed to remove coupon. Please try again.', 'caddy'),
				'alertSaveForLaterFailed' => __('Failed to save item for later. Please try again.', 'caddy'),
				'recommendationsEmpty' => __('No recommendations available', 'caddy'),
				'recommendationsLoadError' => __('Unable to load recommendations', 'caddy'),
			),
		);
	}

	/**
	 * Get initial recommendations based on cart items (for server-side rendering)
	 *
	 * @param array $cart_items Current cart items
	 * @return array Recommendations data
	 */
	private static function get_initial_recommendations($cart_items) {
		// Check if recommendations are enabled
		$cc_product_recommendation = get_option('cc_product_recommendation');
		if ('enabled' !== $cc_product_recommendation) {
			return array();
		}

		// Get the last product in cart for recommendations
		if (empty($cart_items)) {
			// No cart items - get best sellers
			$product_id = 0;
		} else {
			$last_item = end($cart_items);
			$product_id = $last_item['productId'];
		}

		// Get cart product IDs to exclude (resolve variation IDs to parent product IDs too)
		$cart_product_ids = array();
		foreach ($cart_items as $item) {
			$cart_product_ids[] = $item['productId'];
			$product_obj = wc_get_product($item['productId']);
			if ($product_obj && $product_obj->get_parent_id()) {
				$cart_product_ids[] = $product_obj->get_parent_id();
			}
		}
		$cart_product_ids = array_unique($cart_product_ids);

		// Get the recommendation type setting
		$cc_product_recommendation_type = get_option('cc_product_recommendation_type');
		$recommended_products = array();

		// Get recommendations based on type (only if we have a valid product)
		if ($product_id > 0) {
			$product = wc_get_product($product_id);
			if ($product && !empty($cc_product_recommendation_type)) {
				// For variations, look up recommendations on the parent product
				$lookup_id = $product_id;
				$lookup_product = $product;
				if ($product->is_type('variation')) {
					$lookup_id = $product->get_parent_id();
					$lookup_product = wc_get_product($lookup_id) ?: $product;
				} else {
					// For Variation Bundles: trace mapped bundle back to original variable product
					$variation_id = self::get_variation_bundle_source($product_id);
					if ($variation_id) {
						$parent_id = wp_get_post_parent_id($variation_id);
						if ($parent_id) {
							$parent_product = wc_get_product($parent_id);
							if ($parent_product) {
								$lookup_id = $parent_id;
								$lookup_product = $parent_product;
							}
						}
					}
				}
				switch ($cc_product_recommendation_type) {
					case 'caddy-recommendations':
						$recommended_products = get_post_meta($lookup_id, '_caddy_recommendations', true);
						break;
					case 'cross-sells':
						$recommended_products = $lookup_product->get_cross_sell_ids();
						break;
					case 'upsells':
						$recommended_products = $lookup_product->get_upsell_ids();
						break;
				}
			}
		}

		// Filter visible products and exclude products already in cart
		$limit = 3;
		$final_recommended_products = array();
		if (!empty($recommended_products) && is_array($recommended_products)) {
			foreach ($recommended_products as $recommended_id) {
				if (count($final_recommended_products) >= $limit) {
					break;
				}
				if (in_array($recommended_id, $cart_product_ids)) {
					continue;
				}
				$recommended_product = wc_get_product($recommended_id);
				if ($recommended_product && 'publish' === $recommended_product->get_status()) {
					$final_recommended_products[] = $recommended_id;
				}
			}
		}

		// Fallback to best sellers if no recommendations found
		if (empty($final_recommended_products)) {
			$best_sellers = wc_get_products(array(
				'limit' => $limit * 2,
				'orderby' => 'popularity',
				'order' => 'DESC',
				'return' => 'ids',
				'status' => 'publish'
			));

			if (!empty($best_sellers)) {
				foreach ($best_sellers as $rec_id) {
					if ($rec_id != $product_id && !in_array($rec_id, $cart_product_ids) && count($final_recommended_products) < $limit) {
						$final_recommended_products[] = $rec_id;
					}
				}
			}
		}

		// Format products for JS
		$formatted_products = array();
		foreach ($final_recommended_products as $product_id_to_load) {
			$product = wc_get_product($product_id_to_load);
			if (!$product) {
				continue;
			}

			$thumbnail_id = $product->get_image_id();
			$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail') : false;
			if (!$thumbnail_url) {
				$thumbnail_url = wc_placeholder_img_src('woocommerce_thumbnail');
			}
			$thumbnail_url = preg_replace('#(?<!:)//+#', '/', $thumbnail_url);

			$product_type = $product->get_type();
			$is_variable = $product_type === 'variable';
			$is_grouped = $product_type === 'grouped';

			// Get price information
			$regular_price = $product->get_regular_price();
			$sale_price = $product->get_sale_price();
			$price = $product->get_price();
			$is_on_sale = $product->is_on_sale() && $sale_price;

			// Format prices as plain text for Interactivity API using WooCommerce locale settings
			$price_formatted = html_entity_decode( strip_tags( wc_price($price) ) );
			$regular_price_formatted = $is_on_sale ? html_entity_decode( strip_tags( wc_price($regular_price) ) ) : '';

			$formatted_products[] = array(
				'id' => $product->get_id(),
				'name' => $product->get_name(),
				'permalink' => $product->get_permalink(),
				'price' => $price_formatted,
				'regularPrice' => $regular_price_formatted,
				'isOnSale' => $is_on_sale,
				'image' => $thumbnail_url,
				'type' => $product_type,
				'isVariable' => $is_variable,
				'isGrouped' => $is_grouped,
				'isSimple' => !$is_variable && !$is_grouped,
				'buttonText' => $is_variable ? __('Select options', 'caddy') : ($is_grouped ? __('View products', 'caddy') : __('Add to cart', 'caddy')),
				'isAdding' => false
			);
		}

		return $formatted_products;
	}

	/**
	 * Register Store API endpoints for cart operations
	 */
	public static function register_cart_endpoints() {
		// Save for Later endpoints only (cart endpoints are handled by Caddy_Block)
		register_rest_route('caddy/v1', '/saved-items/add', array(
			'methods' => 'POST',
			'callback' => array(__CLASS__, 'handle_save_for_later'),
			'permission_callback' => array(__CLASS__, 'check_save_for_later_permissions'),
			'args' => array(
				'product_id' => array(
					'required' => true,
					'type' => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => function($param) {
						return is_numeric($param) && $param > 0;
					}
				)
			)
		));

		register_rest_route('caddy/v1', '/saved-items/remove', array(
			'methods' => 'POST',
			'callback' => array(__CLASS__, 'handle_remove_saved_item'),
			'permission_callback' => array(__CLASS__, 'check_save_for_later_permissions'),
			'args' => array(
				'product_id' => array(
					'required' => true,
					'type' => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => function($param) {
						return is_numeric($param) && $param > 0;
					}
				)
			)
		));

		register_rest_route('caddy/v1', '/saved-items/move-to-cart', array(
			'methods' => 'POST',
			'callback' => array(__CLASS__, 'handle_move_to_cart'),
			'permission_callback' => array(__CLASS__, 'check_save_for_later_permissions'),
			'args' => array(
				'product_id' => array(
					'required' => true,
					'type' => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => function($param) {
						return is_numeric($param) && $param > 0;
					}
				)
			)
		));

		register_rest_route('caddy/v1', '/saved-items', array(
			'methods' => 'GET',
			'callback' => array(__CLASS__, 'handle_get_saved_items'),
			'permission_callback' => array(__CLASS__, 'check_get_saved_items_permissions'),
			'args' => array()
		));

		// Recommendations endpoint - public read-only
		register_rest_route('caddy/v1', '/recommendations/(?P<product_id>\d+)', array(
			'methods' => 'GET',
			'callback' => array(__CLASS__, 'handle_get_recommendations'),
			'permission_callback' => '__return_true',
			'args' => array(
				'product_id' => array(
					'required' => true,
					'type' => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => function($param) {
						return is_numeric($param) && $param > 0;
					}
				),
				'exclude' => array(
					'required' => false,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'description' => 'Comma-separated list of product IDs to exclude'
				),
				'limit' => array(
					'required' => false,
					'type' => 'integer',
					'default' => 3,
					'sanitize_callback' => 'absint',
					'validate_callback' => function($param) {
						$val = absint($param);
						return $val > 0 && $val <= 12;
					}
				)
			)
		));
	}

	/**
	 * Handle quantity update via Store API
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	/**
	 * Add interactivity context to cart templates
	 *
	 * @param array $context Template context
	 * @return array Enhanced context with interactivity directives
	 */
	public static function add_interactivity_context($context) {
		$context['interactivity'] = array(
			'namespace' => 'caddy/cart',
			'directives' => array(
				'cart_wrapper' => 'data-wp-interactive="caddy/cart"',
				'cart_count' => 'data-wp-text="state.cartCount"',
				'cart_total' => 'data-wp-text="state.cartTotal"',
				'cart_items' => 'data-wp-each--item="state.items"',
				'item_quantity' => 'data-wp-text="context.item.quantity"',
				'item_total' => 'data-wp-text="context.item.lineTotal"',
				'toggle_cart' => 'data-wp-on--click="actions.toggleCart"',
				'update_quantity' => 'data-wp-on--click="actions.updateQuantity"',
				'remove_item' => 'data-wp-on--click="actions.removeItem"'
			)
		);

		return $context;
	}

	/**
	 * Get cart response for legacy AJAX handlers
	 *
	 * This provides a compatibility layer while we transition to Interactivity API
	 *
	 * @return array Cart response in JSON format
	 */
	public static function get_json_response() {
		return array(
			'success' => true,
			'data' => self::get_initial_cart_state(),
			'optimization' => 'interactivity_api'
		);
	}

	/**
	 * Check permissions for Save for Later POST operations (add, remove, move)
	 * Requires user to be logged in and valid nonce
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error True if user has permission, WP_Error otherwise
	 */
	public static function check_save_for_later_permissions($request) {
		// Check if user is logged in
		if (!is_user_logged_in()) {
			return new WP_Error(
				'caddy_rest_not_logged_in',
				__('You must be logged in to save items.', 'caddy'),
				array('status' => 401)
			);
		}

		// Verify nonce for POST operations
		$nonce = $request->get_header('X-WP-Nonce');
		if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
			return new WP_Error(
				'caddy_rest_invalid_nonce',
				__('Invalid security token.', 'caddy'),
				array('status' => 403)
			);
		}

		// Rate limiting check
		$rate_limit_check = self::check_rate_limit($request);
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		return true;
	}

	/**
	 * Check permissions for Get Saved Items (read-only)
	 * Only requires user to be logged in, no nonce needed for GET
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error True if user has permission, WP_Error otherwise
	 */
	public static function check_get_saved_items_permissions($request) {
		// Only check if user is logged in for GET requests
		if (!is_user_logged_in()) {
			return new WP_Error(
				'caddy_rest_not_logged_in',
				__('You must be logged in to view saved items.', 'caddy'),
				array('status' => 401)
			);
		}

		return true;
	}

	/**
	 * Check rate limit for API requests
	 * Prevents abuse by limiting requests per user per minute
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error True if within rate limit, WP_Error otherwise
	 */
	public static function check_rate_limit($request) {
		// Get user identifier (user ID for logged in, IP for guests)
		$user_id = get_current_user_id();
		$identifier = $user_id ? 'user_' . $user_id : 'ip_' . self::get_client_ip();

		// Get endpoint from request
		$route = $request->get_route();
		$transient_key = 'caddy_rate_limit_' . md5($identifier . $route);

		// Get current request count
		$request_count = get_transient($transient_key);

		// Rate limit: 60 requests per minute per endpoint
		$rate_limit = apply_filters('caddy_rest_rate_limit', 60);
		$time_window = apply_filters('caddy_rest_rate_limit_window', 60); // seconds

		if ($request_count === false) {
			// First request in this time window
			set_transient($transient_key, 1, $time_window);
		} elseif ($request_count >= $rate_limit) {
			// Rate limit exceeded
			return new WP_Error(
				'caddy_rest_rate_limit_exceeded',
				__('Too many requests. Please try again later.', 'caddy'),
				array('status' => 429)
			);
		} else {
			// Increment counter
			set_transient($transient_key, $request_count + 1, $time_window);
		}

		return true;
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP address
	 */
	private static function get_client_ip() {
		// Only trust REMOTE_ADDR — proxy headers (X-Forwarded-For etc.) are trivially spoofable
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) && filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP ) ) {
			return $_SERVER['REMOTE_ADDR'];
		}

		return '0.0.0.0';
	}

	/**
	 * Handle save for later request
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public static function handle_save_for_later($request) {
		// Check if user is logged in
		if (!is_user_logged_in()) {
			return new WP_REST_Response(array(
				'success' => false,
				'message' => 'You must be logged in to save items'
			), 401);
		}

		// Initialize WooCommerce session and cart
		if (!WC()->session) {
			WC()->session = new WC_Session_Handler();
			WC()->session->init();
		}

		if (!WC()->cart) {
			wc_load_cart();
		}

		$product_id = (int) $request->get_param('product_id');
		$user_id = get_current_user_id();

		// Get saved items list
		$saved_items = get_user_meta($user_id, 'cc_save_for_later_items', true);
		if (!is_array($saved_items)) {
			$saved_items = array();
		}

		// Add item to saved list if not already there
		if (!in_array($product_id, $saved_items)) {
			$saved_items[] = $product_id;
			update_user_meta($user_id, 'cc_save_for_later_items', $saved_items);
		} else {
		}

		return new WP_REST_Response(array(
			'success' => true,
			'message' => 'Item saved for later',
			'saved_items' => self::get_saved_items_data($saved_items)
		), 200);
	}

	/**
	 * Handle remove saved item request
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public static function handle_remove_saved_item($request) {
		$product_id = (int) $request->get_param('product_id');
		$user_id = get_current_user_id();

		// Get saved items list
		$saved_items = get_user_meta($user_id, 'cc_save_for_later_items', true);
		if (!is_array($saved_items)) {
			$saved_items = array();
		}

		// Remove item from saved list
		$key = array_search($product_id, $saved_items);
		if ($key !== false) {
			unset($saved_items[$key]);
			$saved_items = array_values($saved_items); // Reindex array
			update_user_meta($user_id, 'cc_save_for_later_items', $saved_items);
		}

		return new WP_REST_Response(array(
			'success' => true,
			'message' => 'Item removed from saved list',
			'saved_items' => self::get_saved_items_data($saved_items)
		), 200);
	}

	/**
	 * Handle move to cart request
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public static function handle_move_to_cart($request) {
		// Initialize WooCommerce session and ensure it's properly loaded
		if (!WC()->session) {
			WC()->session = new WC_Session_Handler();
			WC()->session->init();
		}

		// Make sure we have a customer session
		if (!WC()->customer) {
			WC()->customer = new WC_Customer(get_current_user_id(), true);
		}

		if (!WC()->cart) {
			wc_load_cart();
		}

		$product_id = (int) $request->get_param('product_id');
		$user_id = get_current_user_id();

		// Validate product exists
		$product = wc_get_product($product_id);
		if (!$product) {
			return new WP_REST_Response(array(
				'success' => false,
				'message' => 'Product not found'
			), 404);
		}

		// Add to cart
		$cart_item_key = WC()->cart->add_to_cart($product_id, 1);


		if (!$cart_item_key) {
			return new WP_REST_Response(array(
				'success' => false,
				'message' => 'Could not add item to cart'
			), 400);
		}

		// Remove from saved items
		$saved_items = get_user_meta($user_id, 'cc_save_for_later_items', true);
		if (!is_array($saved_items)) {
			$saved_items = array();
		}

		$key = array_search($product_id, $saved_items);
		if ($key !== false) {
			unset($saved_items[$key]);
			$saved_items = array_values($saved_items);
			update_user_meta($user_id, 'cc_save_for_later_items', $saved_items);
		}

		WC()->cart->calculate_totals();

		// Ensure cart session is saved
		if (WC()->session) {
			WC()->session->save_data();
		}


		return new WP_REST_Response(array(
			'success' => true,
			'message' => 'Item moved to cart',
			'saved_items' => self::get_saved_items_data($saved_items)
		), 200);
	}

	/**
	 * Handle get saved items request
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public static function handle_get_saved_items($request) {
		$user_id = get_current_user_id();

		$saved_items = get_user_meta($user_id, 'cc_save_for_later_items', true);

		if (!is_array($saved_items)) {
			$saved_items = array();
		}

		$saved_items_data = self::get_saved_items_data($saved_items);

		return new WP_REST_Response(array(
			'success' => true,
			'saved_items' => $saved_items_data
		), 200);
	}

	/**
	 * Handle get recommendations request
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public static function handle_get_recommendations($request) {
		// Rate limit: 30 requests per minute per IP
		$rate_limit_check = self::check_rate_limit( $request );
		if ( is_wp_error( $rate_limit_check ) ) {
			return $rate_limit_check;
		}

		$product_id = $request->get_param('product_id');
		$exclude_param = $request->get_param('exclude');
		$limit = $request->get_param('limit');

		// Default to 3 recommendations if not specified
		if (empty($limit) || !is_numeric($limit)) {
			$limit = 3;
		} else {
			$limit = min(max(1, intval($limit)), 10); // Between 1-10
		}

		// Get product IDs to exclude from query parameter
		// Also resolve variation IDs to their parent product IDs so variable products in cart
		// are properly excluded from recommendations
		$cart_product_ids = array();
		if (!empty($exclude_param)) {
			$raw_ids = array_map('intval', explode(',', $exclude_param));
			foreach ($raw_ids as $id) {
				$cart_product_ids[] = $id;
				$product_obj = wc_get_product($id);
				if ($product_obj && $product_obj->get_parent_id()) {
					$cart_product_ids[] = $product_obj->get_parent_id();
				}
			}
			$cart_product_ids = array_unique($cart_product_ids);
		}

		// Check cache first (cache key includes version, product_id, resolved excluded items, and limit)
		$recs_version = (int) get_option( 'caddy_recs_cache_version', 0 );
		$resolved_exclude = implode(',', $cart_product_ids);
		$cache_key = 'caddy_recs_v' . $recs_version . '_' . $product_id . '_' . md5($resolved_exclude . '_' . $limit);
		$cached = get_transient($cache_key);
		if (false !== $cached) {
			return new WP_REST_Response($cached, 200);
		}

		// Check if recommendations are enabled
		$cc_product_recommendation = get_option('cc_product_recommendation');
		if ('enabled' !== $cc_product_recommendation) {
			return new WP_REST_Response(array(
				'success' => false,
				'message' => 'Recommendations are disabled',
				'products' => array()
			), 200);
		}

		// Get the recommendation type setting
		$cc_product_recommendation_type = get_option('cc_product_recommendation_type');
		$recommended_products = array();

		// Get product object
		$product = wc_get_product($product_id);
		if (!$product) {
			return new WP_REST_Response(array(
				'success' => false,
				'message' => 'Product not found',
				'products' => array()
			), 404);
		}

		// For variations, look up recommendations on the parent product
		$lookup_id = $product_id;
		if ($product->is_type('variation')) {
			$lookup_id = $product->get_parent_id();
			$product = wc_get_product($lookup_id) ?: $product;
		} else {
			// For Variation Bundles: trace mapped bundle back to original variable product
			$variation_id = self::get_variation_bundle_source($product_id);
			if ($variation_id) {
				$parent_id = wp_get_post_parent_id($variation_id);
				if ($parent_id) {
					$parent_product = wc_get_product($parent_id);
					if ($parent_product) {
						$lookup_id = $parent_id;
						$product = $parent_product;
					}
				}
			}
		}

		// Get recommendations based on type
		if (!empty($cc_product_recommendation_type)) {
			switch ($cc_product_recommendation_type) {
				case 'caddy-recommendations':
					$recommended_products = get_post_meta($lookup_id, '_caddy_recommendations', true);
					break;

				case 'cross-sells':
					$recommended_products = $product->get_cross_sell_ids();
					break;

				case 'upsells':
					$recommended_products = $product->get_upsell_ids();
					break;
			}
		}
		// If no type is set, leave $recommended_products empty to fall back to best sellers


		// Filter visible products and exclude products already in cart
		$final_recommended_products = array();
		if (!empty($recommended_products) && is_array($recommended_products)) {
			foreach ($recommended_products as $recommended_id) {
				// Stop if we've reached the limit
				if (count($final_recommended_products) >= $limit) {
					break;
				}

				// Skip if product is already in cart
				if (in_array($recommended_id, $cart_product_ids)) {
					continue;
				}

				$recommended_product = wc_get_product($recommended_id);
				if ($recommended_product && 'publish' === $recommended_product->get_status()) {
					$final_recommended_products[] = $recommended_id;
				}
			}
		}

		// Fallback to best sellers if no recommendations found
		if (empty($final_recommended_products)) {
			// Use wc_get_products for best sellers - faster than internal Store API calls
			$best_sellers = wc_get_products(array(
				'limit' => $limit * 2,
				'orderby' => 'popularity',
				'order' => 'DESC',
				'return' => 'ids',
				'status' => 'publish'
			));

			if (!empty($best_sellers)) {
				// Filter out current product and products already in cart
				foreach ($best_sellers as $rec_id) {
					// Skip if it's the current product or already in cart
					if ($rec_id != $product_id && !in_array($rec_id, $cart_product_ids) && count($final_recommended_products) < $limit) {
						$final_recommended_products[] = $rec_id;
					}
				}
			}
		}

		// Load product data
		$formatted_products = array();
		if (!empty($final_recommended_products)) {

			// Load products directly with wc_get_product - faster than Store API
			foreach ($final_recommended_products as $product_id_to_load) {
				$product = wc_get_product($product_id_to_load);

				if (!$product) {
					continue;
				}

				// Get thumbnail image (fall back to placeholder if attachment file is missing)
				$thumbnail_id = $product->get_image_id();
				$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail') : false;
				if (!$thumbnail_url) {
					$thumbnail_url = wc_placeholder_img_src('woocommerce_thumbnail');
				}
				$thumbnail_url = preg_replace('#(?<!:)//+#', '/', $thumbnail_url);

				// Format product data with only essential fields
				// Prices must be in cents (multiply by 100) to match Store API format
				$regular_price = $product->get_regular_price();
				$sale_price = $product->get_sale_price();

				$formatted_products[] = array(
					'id' => $product->get_id(),
					'name' => $product->get_name(),
					'permalink' => $product->get_permalink(),
					'prices' => array(
						'regular_price' => $regular_price ? strval((float)$regular_price * 100) : '',
						'sale_price' => $sale_price ? strval((float)$sale_price * 100) : ''
					),
					'images' => array(
						array('src' => $thumbnail_url)
					),
					'type' => $product->get_type()
				);
			}
		}

		$response_data = array(
			'success' => true,
			'products' => $formatted_products,
			'recommendation_type' => $cc_product_recommendation_type
		);

		// Cache the result for 1 hour (3600 seconds)
		set_transient($cache_key, $response_data, HOUR_IN_SECONDS);

		return new WP_REST_Response($response_data, 200);
	}

	/**
	 * Clear recommendations cache when products are updated
	 *
	 * @param int $product_id Product ID that was updated/deleted
	 */
	public static function clear_recommendations_cache($product_id) {
		// Bump the cache version — all old transients become stale and expire naturally
		$version = (int) get_option( 'caddy_recs_cache_version', 0 );
		update_option( 'caddy_recs_cache_version', $version + 1, true );
	}

	/**
	 * For WC Product Bundles Variation Bundles: find the variation that maps to a given bundle.
	 * Returns the variation ID if found, or 0 if this product isn't a mapped bundle.
	 *
	 * @param int $bundle_product_id The bundle product ID in the cart
	 * @return int Variation ID or 0
	 */
	private static function get_variation_bundle_source($bundle_product_id) {
		global $wpdb;
		return (int) $wpdb->get_var($wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wc_pb_variable_bundle' AND meta_value = %s LIMIT 1",
			(string) $bundle_product_id
		));
	}

	/**
	 * Get saved items data with product information
	 *
	 * @param array $saved_item_ids Array of product IDs
	 * @return array Array of saved items with product data
	 */
	public static function get_saved_items_data($saved_item_ids) {
		$saved_items = array();

		if ( empty( $saved_item_ids ) ) {
			return $saved_items;
		}

		// Batch-load all products in a single query to avoid N+1
		_prime_post_caches( $saved_item_ids, true, true );

		foreach ($saved_item_ids as $product_id) {
			$product = wc_get_product($product_id);
			if (!$product) {
				continue;
			}

			// Calculate savings if product is on sale
			$regular_price = $product->get_regular_price();
			$sale_price = $product->get_sale_price();
			$is_on_sale = $product->is_on_sale();
			$savings_percent = 0;

			if ($is_on_sale && $regular_price > 0 && $sale_price) {
				$savings_percent = round(( ($regular_price - $sale_price) / $regular_price ) * 100);
			}

			$product_type = $product->get_type();

			// Determine if product can be added directly to cart
			// Variable, bundle, and grouped products need to go to product page to select options
			$can_add_to_cart = !in_array($product_type, array('variable', 'bundle', 'grouped'));

			$price = (float) $product->get_price();
			$saved_items[] = array(
				'productId' => $product_id,
				'name' => wp_specialchars_decode( $product->get_name(), ENT_QUOTES ),
				'price' => html_entity_decode( strip_tags( wc_price( $price ) ) ),
				'regularPrice' => $is_on_sale ? html_entity_decode( strip_tags( wc_price( (float) $regular_price ) ) ) : '',
				'salePrice' => wc_format_decimal($sale_price, 2),
				'priceFormatted' => $product->get_price_html(),
				'image' => ($img_src = wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_single')) ? $img_src : wc_placeholder_img_src('woocommerce_single'),
				'thumbnailImage' => ($thumb_src = wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail')) ? $thumb_src : wc_placeholder_img_src('woocommerce_thumbnail'),
				'permalink' => get_permalink($product_id),
				'isInStock' => $product->is_in_stock(),
				'isOnSale' => $is_on_sale,
				'savingsPercent' => $savings_percent,
				'productType' => $product_type,
				'canAddToCart' => $can_add_to_cart
			);
		}

		return $saved_items;
	}
}
